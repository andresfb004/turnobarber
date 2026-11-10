<?php

namespace Tests\Feature\Api;

use App\Models\Cliente;
use App\Models\Horario;
use App\Models\Profesional;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Escenario fijo: martes 10 de noviembre de 2026 a las 7:00 a. m., dos profesionales
 * que atienden de lunes a sábado de 8:00 a 18:00 y el usuario del bot.
 */
abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected Profesional $barbero;

    protected Profesional $estilista;

    protected User $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-10 07:00:00'));
        $this->seed(RoleSeeder::class);

        $this->barbero = $this->crearProfesional(Profesional::BARBERIA);
        $this->estilista = $this->crearProfesional(Profesional::BELLEZA);
        $this->bot = User::factory()->conRol(Role::BOT)->create(['email' => 'bot@test.co']);
    }

    protected function comoBot(): static
    {
        Sanctum::actingAs($this->bot);

        return $this;
    }

    protected function cliente(array $datos = []): Cliente
    {
        return Cliente::factory()->create($datos);
    }

    private function crearProfesional(string $especialidad): Profesional
    {
        $profesional = Profesional::factory()->create([
            'user_id' => User::factory()->conRol(Role::ADMINISTRADOR),
            'especialidad' => $especialidad,
        ]);

        foreach (array_keys(Horario::DIAS) as $dia) {
            Horario::create([
                'profesional_id' => $profesional->id,
                'dia_semana' => $dia,
                'hora_apertura' => '08:00',
                'hora_cierre' => '18:00',
            ]);
        }

        return $profesional;
    }
}
