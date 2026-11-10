<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Profesional;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AgendaDemoSeeder extends Seeder
{
    /**
     * Probabilidad de que un turno esté ocupado, en el pasado y en los próximos días.
     */
    private const OCUPACION_PASADO = 0.45;

    private const OCUPACION_FUTURO = 0.25;

    /**
     * Crea clientes y unas cuatro semanas de citas para que el dashboard tenga datos.
     * Recorre los turnos reales de cada profesional, así que nunca genera cruces.
     */
    public function run(): void
    {
        $clientes = Cliente::factory()->count(27)->create()
            ->concat(Cliente::factory()->sinTelegram()->count(3)->create());

        $profesionales = Profesional::with(['horarios', 'cierres'])->get();
        $ahora = Carbon::now();
        $dias = CarbonPeriod::create(Carbon::today()->subWeeks(4), Carbon::today()->addDays(7));

        foreach ($dias as $fecha) {
            $dia = Carbon::instance($fecha);

            if ($dia->isSunday()) {
                continue;
            }

            foreach ($profesionales as $profesional) {
                $horario = $profesional->horarios->firstWhere('dia_semana', $dia->dayOfWeekIso);

                if (! $horario) {
                    continue;
                }

                $cierresDelDia = $profesional->cierres->filter(fn ($c) => $c->fecha->isSameDay($dia));

                $inicio = $dia->copy()->setTimeFromTimeString($horario->hora_apertura);
                $ultimoTurno = $dia->copy()->setTimeFromTimeString($horario->hora_cierre)
                    ->subMinutes(Cita::DURACION_MINUTOS);

                for (; $inicio <= $ultimoTurno; $inicio->addMinutes(Cita::DURACION_MINUTOS)) {
                    if ($this->estaCerrado($cierresDelDia, $inicio)) {
                        continue;
                    }

                    $esPasado = $inicio < $ahora;
                    $ocupacion = $esPasado ? self::OCUPACION_PASADO : self::OCUPACION_FUTURO;

                    if (fake()->randomFloat(2, 0, 1) > $ocupacion) {
                        continue;
                    }

                    Cita::factory()
                        ->enTurno($inicio->copy())
                        ->conEstado($esPasado ? $this->estadoPasado() : Cita::PENDIENTE)
                        ->create([
                            'cliente_id' => $clientes->random()->id,
                            'profesional_id' => $profesional->id,
                        ]);
                }
            }
        }

        $this->actualizarInasistencias();
    }

    private function estaCerrado($cierres, Carbon $inicio): bool
    {
        $fin = $inicio->copy()->addMinutes(Cita::DURACION_MINUTOS);

        foreach ($cierres as $cierre) {
            if ($cierre->esTodoElDia()) {
                return true;
            }

            $desde = $inicio->copy()->setTimeFromTimeString($cierre->hora_inicio);
            $hasta = $inicio->copy()->setTimeFromTimeString($cierre->hora_fin);

            if ($inicio < $hasta && $desde < $fin) {
                return true;
            }
        }

        return false;
    }

    private function estadoPasado(): string
    {
        return fake()->randomElement([
            ...array_fill(0, 17, Cita::ATENDIDA),
            Cita::NO_ASISTIO,
            Cita::CANCELADA,
            Cita::CANCELADA,
        ]);
    }

    /**
     * Cuenta las inasistencias de cada cliente y bloquea a quien llegue al máximo (RN4).
     */
    private function actualizarInasistencias(): void
    {
        Cliente::withCount(['citas as faltas' => fn ($q) => $q->where('estado', Cita::NO_ASISTIO)])
            ->get()
            ->each(fn (Cliente $cliente) => $cliente->update([
                'inasistencias' => $cliente->faltas,
                'bloqueado' => $cliente->faltas >= Cliente::MAX_INASISTENCIAS,
            ]));
    }
}
