<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Profesional;
use App\Repositories\CitaRepository;
use App\Repositories\ProfesionalRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * RN1 · Turnos disponibles: turnos de 1 h en punto dentro del horario del
 * profesional, de lunes a sábado, sin cierres ni citas tomadas.
 */
class TurnoService
{
    public function __construct(
        private ProfesionalRepository $profesionales,
        private CitaRepository $citas,
    ) {}

    /**
     * Turnos libres del profesional en la fecha, listos para ofrecer al cliente.
     *
     * @return Collection<int, Carbon>
     */
    public function disponibles(Profesional $profesional, Carbon $fecha): Collection
    {
        $ocupados = $this->citas->iniciosOcupados($profesional, $fecha);
        $desde = Carbon::now()->addMinutes(Cita::ANTICIPACION_MINIMA_MINUTOS);

        return $this->turnosDelHorario($profesional, $fecha)
            ->reject(fn (Carbon $inicio) => in_array($inicio->format('Y-m-d H:i:s'), $ocupados, true))
            ->reject(fn (Carbon $inicio) => $inicio < $desde)
            ->values();
    }

    /**
     * Si la hora es un turno que el profesional atiende (sin mirar si ya está tomado).
     */
    public function esTurnoDelHorario(Profesional $profesional, Carbon $inicio): bool
    {
        return $this->turnosDelHorario($profesional, $inicio)
            ->contains(fn (Carbon $turno) => $turno->equalTo($inicio));
    }

    /**
     * Todos los turnos del día según el horario, quitando los que caen en un cierre.
     *
     * @return Collection<int, Carbon>
     */
    private function turnosDelHorario(Profesional $profesional, Carbon $fecha): Collection
    {
        $dia = $fecha->copy()->startOfDay();

        if (! $profesional->activo || $dia->isSunday()) {
            return collect();
        }

        $horario = $this->profesionales->horarioDelDia($profesional, $dia);
        $cierres = $this->profesionales->cierresDelDia($profesional, $dia);

        if (! $horario || $cierres->contains(fn ($cierre) => $cierre->esTodoElDia())) {
            return collect();
        }

        $turnos = collect();
        $inicio = $dia->copy()->setTimeFromTimeString($horario->hora_apertura);
        $ultimo = $dia->copy()->setTimeFromTimeString($horario->hora_cierre)
            ->subMinutes(Cita::DURACION_MINUTOS);

        for (; $inicio <= $ultimo; $inicio->addMinutes(Cita::DURACION_MINUTOS)) {
            $fin = $inicio->copy()->addMinutes(Cita::DURACION_MINUTOS);

            $cerrado = $cierres->contains(function ($cierre) use ($inicio, $fin) {
                $desde = $inicio->copy()->setTimeFromTimeString($cierre->hora_inicio);
                $hasta = $inicio->copy()->setTimeFromTimeString($cierre->hora_fin);

                return $inicio < $hasta && $desde < $fin;
            });

            if (! $cerrado) {
                $turnos->push($inicio->copy());
            }
        }

        return $turnos;
    }
}
