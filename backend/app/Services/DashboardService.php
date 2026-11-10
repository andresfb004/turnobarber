<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Profesional;
use Illuminate\Support\Carbon;

class DashboardService
{
    private const DIAS_GRAFICO = 30;

    public function __construct(private TurnoService $turnos) {}

    /**
     * @return array<string, mixed>
     */
    public function metricas(): array
    {
        $ahora = Carbon::now();
        $profesionales = Profesional::where('activo', true)->orderBy('id')->get();

        return [
            'hoy' => $profesionales->map(fn (Profesional $p) => $this->resumenDeHoy($p, $ahora)),
            'porMarcar' => $this->porMarcar($ahora),
            'inasistencias' => $this->inasistenciasDelMes(),
            'clientesBloqueados' => Cliente::where('bloqueado', true)->count(),
            'ocupacion' => $this->ocupacionSemana($profesionales),
            'grafico' => $this->atendidasPorDia($profesionales),
            'proximas' => Cita::with(['cliente', 'profesional'])
                ->where('estado', Cita::PENDIENTE)
                ->where('inicio', '>=', $ahora)
                ->orderBy('inicio')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Citas de hoy de un profesional, separadas por lo que ya pasó y lo que falta.
     *
     * @return array{profesional: Profesional, total: int, atendidas: int, no_asistio: int, por_venir: int, por_marcar: int}
     */
    private function resumenDeHoy(Profesional $profesional, Carbon $ahora): array
    {
        $citas = $profesional->citas()->activas()->whereDate('inicio', $ahora->toDateString())->get(['estado', 'inicio']);
        $pendientes = $citas->where('estado', Cita::PENDIENTE);

        return [
            'profesional' => $profesional,
            'total' => $citas->count(),
            'atendidas' => $citas->where('estado', Cita::ATENDIDA)->count(),
            'no_asistio' => $citas->where('estado', Cita::NO_ASISTIO)->count(),
            'por_venir' => $pendientes->filter(fn (Cita $c) => $c->inicio >= $ahora)->count(),
            'por_marcar' => $pendientes->filter(fn (Cita $c) => $c->inicio < $ahora)->count(),
        ];
    }

    /**
     * Citas que ya empezaron y siguen pendientes: sin marcarlas, las inasistencias (RN4) no se cuentan.
     *
     * @return array{total: int, desde: ?Carbon}
     */
    private function porMarcar(Carbon $ahora): array
    {
        $consulta = Cita::where('estado', Cita::PENDIENTE)->where('inicio', '<', $ahora);

        return [
            'total' => $consulta->count(),
            'desde' => $consulta->min('inicio') ? Carbon::parse($consulta->min('inicio')) : null,
        ];
    }

    /**
     * @return array{total: int, de: int, porcentaje: int}
     */
    private function inasistenciasDelMes(): array
    {
        $mes = Cita::whereBetween('inicio', [Carbon::today()->startOfMonth(), Carbon::now()])
            ->whereIn('estado', [Cita::ATENDIDA, Cita::NO_ASISTIO]);

        $cerradas = (clone $mes)->count();
        $faltas = (clone $mes)->where('estado', Cita::NO_ASISTIO)->count();

        return [
            'total' => $faltas,
            'de' => $cerradas,
            'porcentaje' => $cerradas === 0 ? 0 : (int) round($faltas * 100 / $cerradas),
        ];
    }

    /**
     * Turnos ocupados de lunes a sábado de esta semana.
     *
     * @return array{ocupados: int, turnos: int, porcentaje: int}
     */
    private function ocupacionSemana($profesionales): array
    {
        $lunes = Carbon::today()->startOfWeek();
        $turnos = 0;
        $ocupados = 0;

        foreach ($profesionales as $profesional) {
            for ($i = 0; $i < 6; $i++) {
                $turnos += $this->turnos->turnosDelDia($profesional, $lunes->copy()->addDays($i))->count();
            }

            $ocupados += $profesional->citas()->activas()
                ->whereBetween('inicio', [$lunes, $lunes->copy()->addDays(5)->endOfDay()])
                ->count();
        }

        return [
            'ocupados' => $ocupados,
            'turnos' => $turnos,
            'porcentaje' => $turnos === 0 ? 0 : (int) round($ocupados * 100 / $turnos),
        ];
    }

    /**
     * Citas atendidas por día en los últimos 30 días, sin domingos (no se atiende), una serie por profesional.
     *
     * @return array{etiquetas: array<int, string>, fechas: array<int, string>, series: array<int, array{nombre: string, especialidad: string, datos: array<int, int>, total: int}>, total: int}
     */
    private function atendidasPorDia($profesionales): array
    {
        $desde = Carbon::today()->subDays(self::DIAS_GRAFICO - 1);
        $dias = collect(range(0, self::DIAS_GRAFICO - 1))
            ->map(fn ($i) => $desde->copy()->addDays($i))
            ->reject(fn (Carbon $d) => $d->isSunday())
            ->values();

        $conteos = Cita::where('estado', Cita::ATENDIDA)
            ->where('inicio', '>=', $desde)
            ->get(['profesional_id', 'inicio'])
            ->groupBy(fn (Cita $c) => $c->profesional_id.'|'.$c->inicio->toDateString())
            ->map->count();

        $series = $profesionales->map(function (Profesional $p) use ($dias, $conteos) {
            $datos = $dias->map(fn (Carbon $d) => $conteos->get($p->id.'|'.$d->toDateString(), 0))->all();

            return [
                'nombre' => $p->nombre,
                'especialidad' => $p->especialidad,
                'datos' => $datos,
                'total' => array_sum($datos),
            ];
        })->all();

        return [
            'etiquetas' => $dias->map(fn (Carbon $d) => $d->locale('es')->isoFormat('D MMM'))->all(),
            'fechas' => $dias->map(fn (Carbon $d) => ucfirst($d->locale('es')->isoFormat('ddd D [de] MMMM')))->all(),
            'series' => $series,
            'total' => array_sum(array_column($series, 'total')),
        ];
    }
}
