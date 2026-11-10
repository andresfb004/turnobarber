<?php

namespace Database\Factories;

use App\Models\Profesional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profesional>
 */
class ProfesionalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nombre' => fake('es_ES')->firstName(),
            'especialidad' => fake()->randomElement([Profesional::BARBERIA, Profesional::BELLEZA]),
            'activo' => true,
        ];
    }
}
