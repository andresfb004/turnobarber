<?php

namespace Tests\Feature\Api;

use App\Models\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AuthTest extends ApiTestCase
{
    public function test_el_bot_inicia_sesion_y_recibe_un_token(): void
    {
        $respuesta = $this->postJson('/api/login', [
            'email' => 'bot@test.co',
            'password' => 'password',
            'device_name' => 'bot-telegram',
        ]);

        $respuesta->assertOk()
            ->assertJsonStructure(['token', 'usuario' => ['nombre', 'rol']])
            ->assertJsonPath('usuario.rol', Role::BOT);

        $this->withToken($respuesta->json('token'))
            ->getJson('/api/profesionales')
            ->assertOk();
    }

    public function test_volver_a_iniciar_sesion_reemplaza_el_token_del_dispositivo(): void
    {
        $datos = ['email' => 'bot@test.co', 'password' => 'password', 'device_name' => 'bot-telegram'];

        $this->postJson('/api/login', $datos)->assertOk();
        $this->postJson('/api/login', $datos)->assertOk();

        $this->assertSame(1, $this->bot->tokens()->count());
    }

    public function test_credenciales_invalidas_responden_401(): void
    {
        $this->postJson('/api/login', ['email' => 'bot@test.co', 'password' => 'otra'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciales inválidas.');
    }

    public function test_login_sin_datos_responde_422(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_sin_token_responde_401_en_json(): void
    {
        $this->getJson('/api/profesionales')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado. Inicia sesión de nuevo.');

        // Sin la cabecera Accept también debe ser JSON, no una redirección al login.
        $this->get('/api/profesionales')->assertUnauthorized();
    }

    public function test_un_usuario_sin_rol_de_api_responde_403(): void
    {
        Sanctum::actingAs(User::factory()->conRol(Role::PROFESIONAL)->create());

        $this->getJson('/api/profesionales')->assertForbidden();
    }

    public function test_logout_invalida_el_token(): void
    {
        $token = $this->postJson('/api/login', ['email' => 'bot@test.co', 'password' => 'password'])
            ->json('token');

        $this->withToken($token)->postJson('/api/logout')->assertNoContent();

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/profesionales')->assertUnauthorized();
    }
}
