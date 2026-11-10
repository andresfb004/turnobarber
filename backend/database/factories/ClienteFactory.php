<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telegram_id' => fake()->unique()->numberBetween(100000000, 9999999999),
            'nombre' => fake('es_ES')->firstName().' '.fake('es_ES')->lastName(),
            // Celular colombiano: 10 dígitos que empiezan por 3.
            'telefono' => '3'.fake()->unique()->numerify('#########'),
            'inasistencias' => 0,
            'bloqueado' => false,
        ];
    }

    /**
     * Cliente creado desde el panel, que todavía no usa el bot.
     */
    public function sinTelegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'telegram_id' => null,
        ]);
    }
}
