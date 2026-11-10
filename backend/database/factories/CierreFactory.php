<?php

namespace Database\Factories;

use App\Models\Cierre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cierre>
 */
class CierreFactory extends Factory
{
    /**
     * Por defecto, un cierre de todo el día en las próximas semanas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'hora_inicio' => null,
            'hora_fin' => null,
            'motivo' => fake()->randomElement(['Festivo', 'Vacaciones', 'Diligencia personal', 'Cita médica']),
        ];
    }

    /**
     * Cierre de unas horas, no del día completo.
     */
    public function parcial(string $desde, string $hasta): static
    {
        return $this->state(fn (array $attributes) => [
            'hora_inicio' => $desde,
            'hora_fin' => $hasta,
        ]);
    }
}
