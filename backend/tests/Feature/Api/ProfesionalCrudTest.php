<?php

namespace Tests\Feature\Api;

use App\Models\Cita;
use App\Models\Profesional;
use App\Models\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ProfesionalCrudTest extends ApiTestCase
{
    private function comoAdministrador(): static
    {
        Sanctum::actingAs($this->barbero->user);

        return $this;
    }

    private function datos(array $cambios = []): array
    {
        return [
            'nombre' => 'Laura Gómez',
            'especialidad' => Profesional::BELLEZA,
            'activo' => true,
            'email' => 'laura@turnobarber.test',
            'password' => 'clave-segura-1',
            'rol' => Role::PROFESIONAL,
            'horarios' => [
                1 => ['atiende' => true, 'apertura' => '09:00', 'cierre' => '17:00'],
                6 => ['atiende' => true, 'apertura' => '08:00', 'cierre' => '12:00'],
            ],
            ...$cambios,
        ];
    }

    public function test_el_bot_lista_y_ve_un_profesional_con_su_horario(): void
    {
        $this->comoBot()->getJson('/api/profesionales')->assertOk()->assertJsonCount(2, 'data');

        $this->getJson("/api/profesionales/{$this->barbero->id}")
            ->assertOk()
            ->assertJsonPath('data.nombre', $this->barbero->nombre)
            ->assertJsonPath('data.horarios.0.dia', 'Lunes')
            ->assertJsonPath('data.horarios.0.apertura', '08:00');
    }

    public function test_el_administrador_crea_un_profesional_con_cuenta_y_horario(): void
    {
        $this->comoAdministrador()->postJson('/api/profesionales', $this->datos())
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'Laura Gómez')
            ->assertJsonCount(2, 'data.horarios');

        $profesional = Profesional::where('nombre', 'Laura Gómez')->sole();
        $this->assertSame('laura@turnobarber.test', $profesional->user->email);
        $this->assertSame(Role::PROFESIONAL, $profesional->user->role->nombre);
    }

    public function test_el_administrador_actualiza_un_profesional(): void
    {
        $this->comoAdministrador()
            ->putJson("/api/profesionales/{$this->estilista->id}", $this->datos([
                'nombre' => 'Estilista editada',
                'email' => $this->estilista->user->email,
                'password' => null,
                'activo' => false,
            ]))
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Estilista editada')
            ->assertJsonPath('data.activo', false);
    }

    public function test_el_administrador_elimina_un_profesional_sin_citas_con_204(): void
    {
        $this->comoAdministrador()->deleteJson("/api/profesionales/{$this->estilista->id}")->assertNoContent();

        $this->assertModelMissing($this->estilista);
    }

    public function test_no_elimina_un_profesional_con_citas_responde_422(): void
    {
        Cita::factory()->create(['profesional_id' => $this->estilista->id]);

        $this->comoAdministrador()->deleteJson("/api/profesionales/{$this->estilista->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profesional');
    }

    public function test_valida_los_datos_con_422(): void
    {
        $this->comoAdministrador()->postJson('/api/profesionales', $this->datos([
            'nombre' => '',
            'especialidad' => 'mecanica',
            'email' => $this->estilista->user->email,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'especialidad', 'email']);
    }

    public function test_el_bot_no_puede_crear_editar_ni_eliminar_profesionales(): void
    {
        $this->comoBot()->postJson('/api/profesionales', $this->datos())->assertForbidden();
        $this->putJson("/api/profesionales/{$this->estilista->id}", $this->datos())->assertForbidden();
        $this->deleteJson("/api/profesionales/{$this->estilista->id}")->assertForbidden();
    }

    public function test_responde_404_si_el_profesional_no_existe_y_401_sin_token(): void
    {
        $this->comoBot()->getJson('/api/profesionales/9999')->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/profesionales/'.$this->barbero->id)->assertUnauthorized();
    }

    public function test_un_profesional_sin_rol_de_administrador_no_entra_a_la_api(): void
    {
        Sanctum::actingAs(User::factory()->conRol(Role::PROFESIONAL)->create());

        $this->getJson('/api/profesionales')->assertForbidden();
    }
}
