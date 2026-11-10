<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\StoreCitaRequest;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Profesional;
use App\Services\CitaService;
use App\Services\TurnoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function __construct(private CitaService $citas) {}

    /**
     * Agenda de un día: una columna por profesional y una fila por turno.
     * El administrador ve ambas (o filtra); un profesional solo ve la suya.
     */
    public function index(Request $request, TurnoService $turnos): View
    {
        $fecha = $this->fecha($request->query('fecha'));
        $user = $request->user();
        $filtro = $user->esAdministrador() ? $request->query('profesional', 'todos') : null;

        $todos = Profesional::where('activo', true)->orderBy('id')->get();
        $visibles = match (true) {
            ! $user->esAdministrador() => Profesional::where('user_id', $user->id)->get(),
            $filtro === 'todos' => $todos,
            default => $todos->where('id', (int) $filtro)->values(),
        };

        $columnas = $visibles->map(function (Profesional $profesional) use ($fecha, $turnos) {
            $citas = $profesional->citas()->activas()->with('cliente')
                ->whereDate('inicio', $fecha)
                ->get()
                ->keyBy(fn (Cita $c) => $c->inicio->format('H:i'));

            return [
                'profesional' => $profesional,
                'turnos' => $turnos->turnosDelDia($profesional, $fecha)->map->format('H:i')->all(),
                'citas' => $citas,
                'cierres' => $profesional->cierres()->whereDate('fecha', $fecha)->get(),
            ];
        });

        $horas = $columnas
            ->flatMap(fn ($c) => [...$c['turnos'], ...$c['citas']->keys()->all()])
            ->unique()
            ->sort()
            ->values();

        return view('agenda.index', [
            'fecha' => $fecha,
            'filtro' => $filtro,
            'profesionales' => $todos,
            'columnas' => $columnas,
            'horas' => $horas,
            'clientes' => $user->esAdministrador()
                ? Cliente::where('bloqueado', false)->orderBy('nombre')->get(['id', 'nombre', 'telefono'])
                : collect(),
        ]);
    }

    /**
     * Cita creada desde el panel (por ejemplo, un cliente que llamó). Aplica las mismas reglas que el bot.
     */
    public function store(StoreCitaRequest $request): RedirectResponse
    {
        $inicio = Carbon::parse($request->validated('fecha').' '.$request->validated('hora'));

        $this->citas->agendar(
            Cliente::findOrFail($request->validated('cliente_id')),
            Profesional::findOrFail($request->validated('profesional_id')),
            $inicio,
        );

        return redirect()
            ->route('agenda', ['fecha' => $inicio->toDateString()])
            ->with('exito', 'Cita agendada.');
    }

    public function cambiarEstado(Request $request, Cita $cita): RedirectResponse
    {
        $user = $request->user();

        if (! $user->esAdministrador() && $cita->profesional->user_id !== $user->id) {
            abort(403, 'Solo puedes cambiar las citas de tu agenda.');
        }

        $datos = $request->validate([
            'estado' => ['required', Rule::in([Cita::ATENDIDA, Cita::NO_ASISTIO, Cita::CANCELADA])],
        ]);

        $this->citas->cambiarEstado($cita->load('cliente'), $datos['estado']);

        $mensajes = [
            Cita::ATENDIDA => 'Cita marcada como atendida.',
            Cita::NO_ASISTIO => 'Inasistencia registrada.',
            Cita::CANCELADA => 'Cita cancelada.',
        ];

        if ($datos['estado'] === Cita::NO_ASISTIO && $cita->cliente->fresh()->bloqueado) {
            return back()->with('exito', 'Inasistencia registrada. El cliente quedó bloqueado por inasistencias.');
        }

        return back()->with('exito', $mensajes[$datos['estado']]);
    }

    private function fecha(?string $valor): Carbon
    {
        try {
            return $valor ? Carbon::createFromFormat('Y-m-d', $valor)->startOfDay() : Carbon::today();
        } catch (\Throwable) {
            return Carbon::today();
        }
    }
}
