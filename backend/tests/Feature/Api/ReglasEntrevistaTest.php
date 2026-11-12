<?php

namespace Tests\Feature\Api;

use App\Models\Cita;
use App\Models\Horario;
use Illuminate\Support\Carbon;

/**
 * Reglas confirmadas en la entrevista con el barbero.
 */
class ReglasEntrevistaTest extends ApiTestCase
{
    public function test_rn1_no_ofrece_turnos_en_el_descanso_de_mediodia(): void
    {
        Horario::where('profesional_id', $this->barbero->id)->update([
            'hora_apertura' => '07:00',
            'descanso_inicio' => '12:00',
            'descanso_fin' => '14:00',
            'hora_cierre' => '19:00',
        ]);

        $horas = collect($this->comoBot()->getJson("/api/turnos?profesional={$this->barbero->id}&fecha=2026-11-11")
            ->assertOk()
            ->json('data'))
            ->map(fn ($turno) => Carbon::parse($turno['inicio'])->format('H:i'));

        $this->assertSame(['07:00', '08:00', '09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00', '18:00'], $horas->all());

        $this->postJson('/api/citas', [
            'telegram_id' => $this->cliente()->telegram_id,
            'profesional_id' => $this->barbero->id,
            'inicio' => '2026-11-11 12:00',
        ])->assertUnprocessable()->assertJsonValidationErrors('inicio');
    }

    public function test_rn4_la_segunda_inasistencia_bloquea_por_14_dias(): void
    {
        $cliente = $this->cliente(['inasistencias' => 1]);
        $cita = Cita::factory()->create([
            'cliente_id' => $cliente->id,
            'profesional_id' => $this->barbero->id,
            'inicio' => '2026-11-10 08:00',
            'fin' => '2026-11-10 09:00',
            'estado' => Cita::PENDIENTE,
        ]);

        // Cancelar con menos de 2 h cuenta como inasistencia.
        $this->comoBot()->patchJson("/api/citas/{$cita->id}/cancelar", ['telegram_id' => $cliente->telegram_id])->assertOk();

        $cliente->refresh();
        $this->assertTrue($cliente->bloqueado);
        $this->assertSame('2026-11-24', $cliente->bloqueado_hasta->toDateString());

        $this->postJson('/api/citas', [
            'telegram_id' => $cliente->telegram_id,
            'profesional_id' => $this->barbero->id,
            'inicio' => '2026-11-11 10:00',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.cliente.0', 'Tu cuenta está bloqueada por inasistencias hasta el martes 24 de noviembre. Comunícate con la barbería.');
    }

    public function test_rn4_pasados_14_dias_se_desbloquea_solo_y_empieza_de_cero(): void
    {
        $cliente = $this->cliente([
            'inasistencias' => 2,
            'bloqueado' => true,
            'bloqueado_hasta' => Carbon::parse('2026-11-12 07:00'),
        ]);

        $this->travelTo(Carbon::parse('2026-11-12 07:00'));

        $this->comoBot()->postJson('/api/citas', [
            'telegram_id' => $cliente->telegram_id,
            'profesional_id' => $this->barbero->id,
            'inicio' => '2026-11-13 10:00',
        ])->assertCreated();

        $cliente->refresh();
        $this->assertFalse($cliente->bloqueado);
        $this->assertNull($cliente->bloqueado_hasta);
        $this->assertSame(0, $cliente->inasistencias);
    }

    public function test_rn4_antes_del_plazo_sigue_bloqueado(): void
    {
        $cliente = $this->cliente(['inasistencias' => 2, 'bloqueado' => true, 'bloqueado_hasta' => Carbon::parse('2026-11-20')]);

        $this->comoBot()->getJson("/api/clientes/{$cliente->telegram_id}/citas")->assertOk();

        $this->assertTrue($cliente->fresh()->bloqueado);
    }
}
