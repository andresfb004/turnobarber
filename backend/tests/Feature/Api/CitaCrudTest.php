<?php

namespace Tests\Feature\Api;

use App\Models\Cita;
use App\Models\Cliente;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

class CitaCrudTest extends ApiTestCase
{
    private function citaDe(Cliente $cliente, string $inicio = '2026-11-11 10:00', array $datos = []): Cita
    {
        return Cita::factory()->create([
            'cliente_id' => $cliente->id,
            'profesional_id' => $this->barbero->id,
            'inicio' => $inicio,
            'fin' => Carbon::parse($inicio)->addHour(),
            'estado' => Cita::PENDIENTE,
            ...$datos,
        ]);
    }

    private function reprogramar(Cita $cita, Cliente $cliente, string $inicio, ?int $profesionalId = null)
    {
        return $this->comoBot()->putJson("/api/citas/{$cita->id}", [
            'telegram_id' => $cliente->telegram_id,
            'profesional_id' => $profesionalId ?? $this->barbero->id,
            'inicio' => $inicio,
        ]);
    }

    public function test_reprograma_la_cita_a_otro_turno_libre(): void
    {
        $cliente = $this->cliente();
        $cita = $this->citaDe($cliente, datos: ['recordatorio_enviado_at' => now()]);

        $this->reprogramar($cita, $cliente, '2026-11-12 15:00', $this->estilista->id)
            ->assertOk()
            ->assertJsonPath('data.hora_texto', '3:00 p. m.')
            ->assertJsonPath('data.profesional.id', $this->estilista->id);

        $cita->refresh();
        $this->assertSame('2026-11-12 16:00', $cita->fin->format('Y-m-d H:i'));
        $this->assertNull($cita->recordatorio_enviado_at);
    }

    public function test_puede_reprogramar_a_la_misma_hora_con_el_mismo_profesional(): void
    {
        $cliente = $this->cliente();
        $cita = $this->citaDe($cliente);

        $this->reprogramar($cita, $cliente, '2026-11-11 10:00')->assertOk();
    }

    public function test_rn2_no_reprograma_a_un_turno_ocupado(): void
    {
        $cliente = $this->cliente();
        $cita = $this->citaDe($cliente);
        $this->citaDe($this->cliente(), '2026-11-11 11:00');

        $this->reprogramar($cita, $cliente, '2026-11-11 11:00')
            ->assertUnprocessable()
            ->assertJsonPath('errors.inicio.0', 'Ese turno acaba de ser tomado, elige otro.');
    }

    public function test_rn3_aplica_las_reglas_de_horario_al_reprogramar(): void
    {
        $cliente = $this->cliente();

        $this->reprogramar($this->citaDe($cliente), $cliente, '2026-11-11 19:00')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('inicio');
    }

    public function test_no_reprograma_una_cita_cancelada(): void
    {
        $cliente = $this->cliente();
        $cita = $this->citaDe($cliente, datos: ['estado' => Cita::CANCELADA]);

        $this->reprogramar($cita, $cliente, '2026-11-12 10:00')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cita');
    }

    public function test_la_cita_de_otro_cliente_responde_404(): void
    {
        $cita = $this->citaDe($this->cliente());

        $this->reprogramar($cita, $this->cliente(), '2026-11-12 10:00')->assertNotFound();
    }

    public function test_el_administrador_elimina_una_cita_con_204(): void
    {
        $cita = $this->citaDe($this->cliente());
        Sanctum::actingAs($this->barbero->user);

        $this->deleteJson("/api/citas/{$cita->id}")->assertNoContent();

        $this->assertModelMissing($cita);
    }

    public function test_no_elimina_citas_que_son_historial(): void
    {
        $cita = $this->citaDe($this->cliente(), datos: ['estado' => Cita::NO_ASISTIO]);
        Sanctum::actingAs($this->barbero->user);

        $this->deleteJson("/api/citas/{$cita->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cita');
    }

    public function test_el_bot_no_puede_eliminar_citas(): void
    {
        $cita = $this->citaDe($this->cliente());

        $this->comoBot()->deleteJson("/api/citas/{$cita->id}")->assertForbidden();
        $this->assertModelExists($cita);
    }
}
