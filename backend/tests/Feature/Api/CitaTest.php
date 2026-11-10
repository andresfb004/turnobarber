<?php

namespace Tests\Feature\Api;

use App\Models\Cierre;
use App\Models\Cita;
use App\Models\Cliente;
use Illuminate\Support\Carbon;

class CitaTest extends ApiTestCase
{
    private function agendar(Cliente $cliente, int $profesionalId, string $inicio)
    {
        return $this->comoBot()->postJson('/api/citas', [
            'telegram_id' => $cliente->telegram_id,
            'profesional_id' => $profesionalId,
            'inicio' => $inicio,
        ]);
    }

    public function test_agenda_un_turno_libre(): void
    {
        $cliente = $this->cliente();

        $this->agendar($cliente, $this->barbero->id, '2026-11-11 10:00')
            ->assertCreated()
            ->assertJsonPath('data.estado', Cita::PENDIENTE)
            ->assertJsonPath('data.hora_texto', '10:00 a. m.')
            ->assertJsonPath('data.profesional.id', $this->barbero->id);

        $cita = Cita::sole();
        $this->assertSame('2026-11-11 11:00:00', $cita->fin->format('Y-m-d H:i:s'));
    }

    public function test_acepta_el_inicio_en_formato_iso_que_devuelve_la_api_de_turnos(): void
    {
        $this->agendar($this->cliente(), $this->barbero->id, '2026-11-11T10:00:00-05:00')->assertCreated();

        $this->assertSame('10:00', Cita::sole()->inicio->format('H:i'));
    }

    public function test_rn2_el_mismo_turno_con_el_mismo_profesional_responde_422(): void
    {
        $this->agendar($this->cliente(), $this->barbero->id, '2026-11-11 10:00')->assertCreated();

        $this->agendar($this->cliente(), $this->barbero->id, '2026-11-11 10:00')
            ->assertUnprocessable()
            ->assertJsonPath('errors.inicio.0', 'Ese turno acaba de ser tomado, elige otro.');
    }

    public function test_rn2_el_mismo_turno_con_otro_profesional_si_se_agenda(): void
    {
        $this->agendar($this->cliente(), $this->barbero->id, '2026-11-11 10:00')->assertCreated();
        $this->agendar($this->cliente(), $this->estilista->id, '2026-11-11 10:00')->assertCreated();

        $this->assertSame(2, Cita::count());
    }

    public function test_rn3_exige_una_hora_de_anticipacion(): void
    {
        $this->agendar($this->cliente(), $this->barbero->id, '2026-11-10 07:00')
            ->assertUnprocessable()
            ->assertJsonPath('errors.inicio.0', 'Debes agendar con al menos 1 hora de anticipación.');

        $this->agendar($this->cliente(), $this->barbero->id, '2026-11-10 08:00')->assertCreated();
    }

    public function test_rn3_no_permite_agendar_a_mas_de_14_dias(): void
    {
        $this->agendar($this->cliente(), $this->barbero->id, '2026-11-26 10:00')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('inicio');
    }

    public function test_rechaza_horas_fuera_del_horario_o_que_no_son_en_punto(): void
    {
        $cliente = $this->cliente();

        foreach (['2026-11-11 07:00', '2026-11-11 18:00', '2026-11-11 10:30', '2026-11-15 10:00'] as $inicio) {
            $this->agendar($cliente, $this->barbero->id, $inicio)
                ->assertUnprocessable()
                ->assertJsonPath('errors.inicio.0', 'Ese turno no está dentro del horario de atención.');
        }
    }

    public function test_rechaza_un_turno_dentro_de_un_cierre(): void
    {
        Cierre::factory()->create(['fecha' => '2026-11-16'])->profesionales()->attach($this->barbero);

        $this->agendar($this->cliente(), $this->barbero->id, '2026-11-16 10:00')->assertUnprocessable();
    }

    public function test_rn4_un_cliente_bloqueado_no_puede_agendar(): void
    {
        $this->agendar($this->cliente(['bloqueado' => true, 'inasistencias' => 2]), $this->barbero->id, '2026-11-11 10:00')
            ->assertUnprocessable()
            ->assertJsonPath('errors.cliente.0', 'Tu cuenta está bloqueada por inasistencias. Comunícate con la barbería.');
    }

    public function test_un_cliente_no_registrado_recibe_422(): void
    {
        $this->comoBot()->postJson('/api/citas', [
            'telegram_id' => 123456,
            'profesional_id' => $this->barbero->id,
            'inicio' => '2026-11-11 10:00',
        ])->assertUnprocessable()->assertJsonPath('errors.telegram_id.0', 'Primero comparte tu número para registrarte.');
    }

    public function test_lista_las_proximas_citas_del_cliente(): void
    {
        $cliente = $this->cliente();
        $this->agendar($cliente, $this->estilista->id, '2026-11-12 09:00');
        $this->agendar($cliente, $this->barbero->id, '2026-11-11 15:00');
        Cita::factory()->enTurno(Carbon::parse('2026-11-03 10:00'))->conEstado(Cita::ATENDIDA)
            ->create(['cliente_id' => $cliente->id, 'profesional_id' => $this->barbero->id]);

        $this->getJson("/api/clientes/{$cliente->telegram_id}/citas")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.hora_texto', '3:00 p. m.');

        $this->getJson('/api/clientes/999/citas')->assertNotFound();
    }

    public function test_cancela_con_tiempo_sin_contar_inasistencia(): void
    {
        $cliente = $this->cliente();
        $id = $this->agendar($cliente, $this->barbero->id, '2026-11-11 10:00')->json('data.id');

        $this->patchJson("/api/citas/{$id}/cancelar", ['telegram_id' => $cliente->telegram_id])
            ->assertOk()
            ->assertJsonPath('data.estado', Cita::CANCELADA)
            ->assertJsonPath('cuenta_como_inasistencia', false);

        $this->assertSame(0, $cliente->fresh()->inasistencias);
    }

    public function test_rn3_y_rn4_cancelar_tarde_dos_veces_bloquea_al_cliente(): void
    {
        $cliente = $this->cliente();

        // A las 7:00, el turno de las 8:00 está a menos de 2 h.
        foreach ([$this->barbero->id, $this->estilista->id] as $profesionalId) {
            $id = $this->agendar($cliente, $profesionalId, '2026-11-10 08:00')->json('data.id');

            $this->patchJson("/api/citas/{$id}/cancelar", ['telegram_id' => $cliente->telegram_id])
                ->assertOk()
                ->assertJsonPath('cuenta_como_inasistencia', true);
        }

        $this->assertTrue($cliente->fresh()->bloqueado);
        $this->agendar($cliente, $this->barbero->id, '2026-11-11 10:00')->assertUnprocessable();
    }

    public function test_no_se_puede_cancelar_dos_veces_ni_la_cita_de_otro(): void
    {
        $cliente = $this->cliente();
        $id = $this->agendar($cliente, $this->barbero->id, '2026-11-11 10:00')->json('data.id');

        $this->patchJson("/api/citas/{$id}/cancelar", ['telegram_id' => $this->cliente()->telegram_id])
            ->assertNotFound();

        $this->patchJson("/api/citas/{$id}/cancelar", ['telegram_id' => $cliente->telegram_id])->assertOk();
        $this->patchJson("/api/citas/{$id}/cancelar", ['telegram_id' => $cliente->telegram_id])
            ->assertUnprocessable();

        $this->patchJson('/api/citas/999/cancelar', ['telegram_id' => $cliente->telegram_id])->assertNotFound();
    }
}
