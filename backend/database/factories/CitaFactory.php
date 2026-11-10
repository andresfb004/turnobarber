<?php

namespace Database\Factories;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Profesional;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Cita>
 */
class CitaFactory extends Factory
{
    /**
     * Una cita pendiente en un turno en punto de los próximos días, de lunes a sábado.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = Carbon::instance(fake()->dateTimeBetween('+1 day', '+14 days'))
            ->setTime(fake()->numberBetween(8, 17), 0);

        if ($inicio->isSunday()) {
            $inicio->addDay();
        }

        return [
            'cliente_id' => Cliente::factory(),
            'profesional_id' => Profesional::factory(),
            'inicio' => $inicio,
            'fin' => $inicio->copy()->addMinutes(Cita::DURACION_MINUTOS),
            'estado' => Cita::PENDIENTE,
        ];
    }

    /**
     * Cita en el turno indicado (debe ser una hora en punto).
     */
    public function enTurno(Carbon $inicio): static
    {
        return $this->state(fn (array $attributes) => [
            'inicio' => $inicio,
            'fin' => $inicio->copy()->addMinutes(Cita::DURACION_MINUTOS),
        ]);
    }

    public function conEstado(string $estado): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => $estado,
        ]);
    }
}
