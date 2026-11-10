<?php

namespace Tests\Feature\Panel;

use App\Models\Cierre;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Profesional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\Feature\Api\ApiTestCase;

/**
 * Mismo escenario que la API: martes 10 de noviembre de 2026, 7:00 a. m.
 */
class PanelTest extends ApiTestCase
{
    private function admin(): User
    {
        return $this->barbero->user;
    }

    private function cita(Profesional $profesional, string $inicio, array $datos = []): Cita
    {
        return Cita::factory()->enTurno(Carbon::parse($inicio))
            ->create(['profesional_id' => $profesional->id, ...$datos]);
    }

    // ---- Acceso y roles ----

    public function test_un_invitado_va_al_login(): void
    {
        $this->get('/agenda')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Panel de la barbería');
    }

    public function test_el_administrador_entra_y_llega_al_dashboard(): void
    {
        $this->post('/login', ['email' => $this->admin()->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk()->assertSee('Citas atendidas por día');
    }

    public function test_credenciales_incorrectas_muestran_error(): void
    {
        $this->from('/login')->post('/login', ['email' => $this->admin()->email, 'password' => 'otra'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_el_usuario_del_bot_no_entra_al_panel(): void
    {
        $this->post('/login', ['email' => 'bot@test.co', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Esta cuenta no tiene acceso al panel.']);
        $this->assertGuest();
    }

    public function test_un_profesional_solo_ve_su_agenda(): void
    {
        $empleado = Profesional::factory()->create([
            'nombre' => 'Empleado',
            'user_id' => User::factory()->conRol(Role::PROFESIONAL),
        ]);
        $this->cita($this->barbero, '2026-11-10 09:00', ['cliente_id' => Cliente::factory()->create(['nombre' => 'Cliente del barbero'])]);

        $this->actingAs($empleado->user);

        $this->get('/agenda')->assertOk()->assertSee('Empleado')->assertDontSee('Cliente del barbero');
        $this->get('/dashboard')->assertForbidden()->assertSee('Acceso denegado');
        $this->get('/clientes')->assertForbidden();
    }

    // ---- Agenda ----

    public function test_la_agenda_muestra_las_dos_columnas_y_filtra(): void
    {
        $this->cita($this->barbero, '2026-11-10 09:00', ['cliente_id' => Cliente::factory()->create(['nombre' => 'Pedro Pérez'])]);
        $this->actingAs($this->admin());

        $this->get('/agenda?fecha=2026-11-10')->assertOk()
            ->assertSee($this->barbero->nombre)
            ->assertSee($this->estilista->nombre)
            ->assertSee('Pedro Pérez');

        $this->get("/agenda?fecha=2026-11-10&profesional={$this->estilista->id}")->assertOk()
            ->assertViewHas('columnas', fn ($columnas) => $columnas->count() === 1
                && $columnas->first()['profesional']->is($this->estilista)
                && $columnas->first()['citas']->isEmpty());
    }

    public function test_marcar_no_asistio_dos_veces_bloquea_al_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $this->travelTo(Carbon::parse('2026-11-10 12:00'));
        $this->actingAs($this->admin());

        foreach (['2026-11-10 08:00', '2026-11-10 10:00'] as $inicio) {
            $cita = $this->cita($this->barbero, $inicio, ['cliente_id' => $cliente->id]);
            $this->patch("/citas/{$cita->id}/estado", ['estado' => Cita::NO_ASISTIO])->assertSessionHasNoErrors();
        }

        $this->assertTrue($cliente->fresh()->bloqueado);
        $this->assertSame(2, $cliente->fresh()->inasistencias);
    }

    public function test_no_se_marca_asistencia_de_una_cita_futura(): void
    {
        $cita = $this->cita($this->barbero, '2026-11-11 10:00');
        $this->actingAs($this->admin());

        $this->patch("/citas/{$cita->id}/estado", ['estado' => Cita::ATENDIDA])
            ->assertSessionHasErrors('estado');

        $this->patch("/citas/{$cita->id}/estado", ['estado' => Cita::CANCELADA])->assertSessionHasNoErrors();
        $this->assertSame(Cita::CANCELADA, $cita->fresh()->estado);
    }

    public function test_un_profesional_no_cambia_citas_de_otro(): void
    {
        $empleado = Profesional::factory()->create(['user_id' => User::factory()->conRol(Role::PROFESIONAL)]);
        $cita = $this->cita($this->barbero, '2026-11-11 10:00');

        $this->actingAs($empleado->user)
            ->patch("/citas/{$cita->id}/estado", ['estado' => Cita::CANCELADA])
            ->assertForbidden();
    }

    public function test_agenda_una_cita_desde_el_panel_con_las_mismas_reglas(): void
    {
        $cliente = Cliente::factory()->create();
        $this->actingAs($this->admin());
        $datos = ['cliente_id' => $cliente->id, 'profesional_id' => $this->barbero->id, 'fecha' => '2026-11-11', 'hora' => '10:00'];

        $this->post('/citas', $datos)->assertRedirect('/agenda?fecha=2026-11-11')->assertSessionHasNoErrors();
        $this->post('/citas', $datos)->assertSessionHasErrors(['inicio' => 'Ese turno acaba de ser tomado, elige otro.']);
    }

    // ---- CRUD de profesionales ----

    public function test_crea_un_profesional_con_cuenta_y_horario(): void
    {
        $this->actingAs($this->admin());

        $this->post('/profesionales', [
            'nombre' => 'Camilo',
            'especialidad' => 'barberia',
            'activo' => '1',
            'email' => 'camilo@test.co',
            'password' => 'secreta123',
            'rol' => Role::PROFESIONAL,
            'horarios' => [
                1 => ['atiende' => '1', 'apertura' => '09:00', 'cierre' => '13:00'],
                2 => ['atiende' => '0', 'apertura' => '09:00', 'cierre' => '13:00'],
            ],
        ])->assertRedirect('/profesionales');

        $camilo = Profesional::where('nombre', 'Camilo')->with('user.role', 'horarios')->sole();
        $this->assertSame(Role::PROFESIONAL, $camilo->user->role->nombre);
        $this->assertCount(1, $camilo->horarios);

        $this->get('/profesionales?q=Camilo')->assertOk()->assertSee('camilo@test.co');
    }

    public function test_valida_el_horario_del_profesional(): void
    {
        $this->actingAs($this->admin());

        $this->put("/profesionales/{$this->estilista->id}", [
            'nombre' => $this->estilista->nombre,
            'especialidad' => 'belleza',
            'email' => $this->estilista->user->email,
            'rol' => Role::ADMINISTRADOR,
            'horarios' => [1 => ['atiende' => '1', 'apertura' => '18:00', 'cierre' => '08:00']],
        ])->assertSessionHasErrors('horarios.1');
    }

    public function test_no_elimina_un_profesional_con_citas(): void
    {
        $this->cita($this->estilista, '2026-11-11 10:00');
        $this->actingAs($this->admin());

        $this->delete("/profesionales/{$this->estilista->id}")->assertSessionHasErrors('profesional');
        $this->assertModelExists($this->estilista);
    }

    // ---- CRUD de cierres ----

    public function test_un_cierre_creado_en_el_panel_quita_los_turnos_del_bot(): void
    {
        $this->actingAs($this->admin());

        $this->post('/cierres', [
            'fecha' => '2026-11-12',
            'todo_el_dia' => '0',
            'hora_inicio' => '14:00',
            'hora_fin' => '16:00',
            'motivo' => 'Cita médica',
            'profesionales' => [$this->barbero->id],
        ])->assertRedirect('/cierres');

        $this->assertSame(1, Cierre::count());
        $this->get('/cierres')->assertSee('Cita médica');

        $horas = collect($this->comoBot()->getJson("/api/turnos?profesional={$this->barbero->id}&fecha=2026-11-12")->json('data'))
            ->map(fn ($t) => Carbon::parse($t['inicio'])->format('H:i'));
        $this->assertNotContains('14:00', $horas);
        $this->assertContains('16:00', $horas);
    }

    public function test_un_cierre_exige_a_quien_afecta(): void
    {
        $this->actingAs($this->admin());

        $this->post('/cierres', ['fecha' => '2026-11-12', 'todo_el_dia' => '1', 'motivo' => 'Festivo'])
            ->assertSessionHasErrors(['profesionales' => 'Elige al menos un profesional afectado.']);
    }

    // ---- CRUD de clientes ----

    public function test_crea_busca_y_desbloquea_clientes(): void
    {
        $this->actingAs($this->admin());

        $this->post('/clientes', ['nombre' => 'Laura Gómez', 'telefono' => '300 123 4567'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('clientes', ['telefono' => '3001234567', 'telegram_id' => null]);

        $bloqueado = Cliente::factory()->create(['bloqueado' => true, 'inasistencias' => 2]);

        $this->get('/clientes?q=Laura')->assertOk()->assertSee('Laura Gómez');
        $this->get('/clientes?estado=bloqueados')->assertOk()->assertSee($bloqueado->nombre)->assertDontSee('Laura Gómez');

        $this->patch("/clientes/{$bloqueado->id}/desbloquear")->assertSessionHasNoErrors();
        $this->assertFalse($bloqueado->fresh()->bloqueado);
        $this->assertSame(0, $bloqueado->fresh()->inasistencias);
    }

    public function test_un_telefono_repetido_o_invalido_se_rechaza(): void
    {
        Cliente::factory()->create(['telefono' => '3001234567']);
        $this->actingAs($this->admin());

        $this->post('/clientes', ['nombre' => 'Otro', 'telefono' => '3001234567'])
            ->assertSessionHasErrors(['telefono' => 'Ya hay un cliente con ese teléfono.']);
        $this->post('/clientes', ['nombre' => 'Otro', 'telefono' => '12345'])->assertSessionHasErrors('telefono');
    }

    public function test_el_detalle_muestra_el_historial_y_no_borra_clientes_con_citas(): void
    {
        $cliente = Cliente::factory()->create();
        $this->cita($this->barbero, '2026-11-03 10:00', ['cliente_id' => $cliente->id, 'estado' => Cita::ATENDIDA]);
        $this->actingAs($this->admin());

        $this->get("/clientes/{$cliente->id}")->assertOk()->assertSee('Historial de citas')->assertSee('Atendida');
        $this->delete("/clientes/{$cliente->id}")->assertSessionHasErrors('cliente');
        $this->assertModelExists($cliente);
    }

    // ---- Accesibilidad ----

    public function test_los_botones_de_solo_icono_tienen_nombre_accesible(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Laura Gómez', 'bloqueado' => true]);
        $this->actingAs($this->admin());

        $this->get('/clientes')->assertOk()
            ->assertSee('aria-label="Editar a Laura Gómez"', false)
            ->assertSee('aria-label="Eliminar a Laura Gómez"', false)
            ->assertSee('aria-label="Desbloquear a Laura Gómez"', false);

        $this->get('/agenda')->assertOk()
            ->assertSee('aria-label="Día anterior"', false)
            ->assertSee('Saltar al contenido')
            ->assertSee('aria-current="page"', false);
    }

    public function test_el_error_de_login_aparece_junto_a_su_campo(): void
    {
        $this->from('/login')->followingRedirects()
            ->post('/login', ['email' => $this->admin()->email, 'password' => 'otra'])
            ->assertSee('aria-describedby="email-error"', false)
            ->assertSee('id="email-error"', false);
    }

    public function test_si_la_cita_falla_el_formulario_se_reabre_con_el_error(): void
    {
        $cliente = Cliente::factory()->create();
        $this->actingAs($this->admin());
        $datos = ['cliente_id' => $cliente->id, 'profesional_id' => $this->barbero->id, 'fecha' => '2026-11-11', 'hora' => '10:00'];
        $this->post('/citas', $datos);

        $this->from('/agenda?fecha=2026-11-11')->followingRedirects()->post('/citas', $datos)
            ->assertSee('Ese turno acaba de ser tomado, elige otro.')
            ->assertSee("getElementById('nuevaCita')", false);
    }

    // ---- Páginas de error ----

    public function test_las_paginas_de_error_son_propias(): void
    {
        $this->actingAs($this->admin());

        $this->get('/no-existe')->assertNotFound()->assertSee('Página no encontrada');
        $this->get('/clientes/999999')->assertNotFound()->assertSee('Volver al inicio');
    }
}
