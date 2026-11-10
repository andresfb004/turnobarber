<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Profesional;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function __construct(private TurnoService $turnos) {}

    /**
     * @return array<string, mixed>
     */
    public function metricas(): array
    {
        $hoy = Carbon::today();
        $profesionales = Profesional::where('activo', true)->orderBy('id')->get();

        return [
            'citasHoy' => $profesionales->map(fn (Profesional $p) => [
                'profesional' => $p,
                'total' => $p->citas()->activas()->whereDate('inicio', $hoy)->count(),
            ]),
            'inasistenciasMes' => Cita::where('estado', Cita::NO_ASISTIO)
                ->whereBetween('inicio', [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth()])
                ->count(),
            'clientesBloqueados' => Cliente::where('bloqueado', true)->count(),
            'ocupacionSemana' => $this->ocupacionSemana($profesionales),
            'grafico' => $this->atendidasPorDia($profesionales),
            'proximas' => Cita::with(['cliente', 'profesional'])
                ->where('estado', Cita::PENDIENTE)
                ->where('inicio', '>=', Carbon::now())
                ->orderBy('inicio')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Porcentaje de turnos ocupados de lunes a sábado de esta semana.
     */
    private function ocupacionSemana($profesionales): int
    {
        $lunes = Carbon::today()->startOfWeek();
        $turnos = 0;
        $ocupados = 0;

        foreach ($profesionales as $profesional) {
            for ($i = 0; $i < 6; $i++) {
                $dia = $lunes->copy()->addDays($i);
                $turnos += $this->turnos->turnosDelDia($profesional, $dia)->count();
            }

            $ocupados += $profesional->citas()->activas()
                ->whereBetween('inicio', [$lunes, $lunes->copy()->addDays(5)->endOfDay()])
                ->count();
        }

        return $turnos === 0 ? 0 : (int) round($ocupados * 100 / $turnos);
    }

    /**
     * Citas atendidas por día en los últimos 30 días, una serie por profesional.
     *
     * @return array{etiquetas: array<int, string>, series: array<int, array{nombre: string, especialidad: string, datos: array<int, int>}>}
     */
    private function atendidasPorDia($profesionales): array
    {
        $desde = Carbon::today()->subDays(29);
        $dias = collect(range(0, 29))->map(fn ($i) => $desde->copy()->addDays($i));

        $conteos = Cita::where('estado', Cita::ATENDIDA)
            ->where('inicio', '>=', $desde)
            ->get(['profesional_id', 'inicio'])
            ->groupBy(fn (Cita $c) => $c->profesional_id.'|'.$c->inicio->toDateString())
            ->map->count();

        return [
            'etiquetas' => $dias->map(fn (Carbon $d) => $d->locale('es')->isoFormat('D MMM'))->all(),
            'series' => $profesionales->map(fn (Profesional $p) => [
                'nombre' => $p->nombre,
                'especialidad' => $p->especialidad,
                'datos' => $dias->map(fn (Carbon $d) => $conteos->get($p->id.'|'.$d->toDateString(), 0))->all(),
            ])->all(),
        ];
    }
}
