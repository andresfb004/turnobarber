<?php

namespace Tests\Feature\Api;

use App\Models\Cita;
use App\Models\Cliente;
use Illuminate\Support\Carbon;

class ClienteYRecordatorioTest extends ApiTestCase
{
    public function test_vincular_crea_al_cliente_y_normaliza_el_telefono(): void
    {
        $this->comoBot()->postJson('/api/clientes/vincular', [
            'telegram_id' => 555001,
            'telefono' => '+57 300 123 4567',
            'nombre' => 'Laura',
        ])->assertCreated()->assertJsonPath('data.telefono', '3001234567');
    }

    public function test_vincular_un_cliente_existente_responde_200(): void
    {
        $existente = $this->cliente(['telegram_id' => null, 'telefono' => '3001234567']);

        $this->comoBot()->postJson('/api/clientes/vincular', [
            'telegram_id' => 555001,
            'telefono' => '573001234567',
            'nombre' => 'Otro nombre',
        ])->assertOk()->assertJsonPath('data.id', $existente->id);

        $this->assertSame(555001, $existente->fresh()->telegram_id);
        $this->assertSame(1, Cliente::count());
    }

    public function test_vincular_rechaza_un_telefono_de_otra_cuenta_de_telegram(): void
    {
        $this->cliente(['telegram_id' => 111, 'telefono' => '3001234567']);

        $this->comoBot()->postJson('/api/clientes/vincular', [
            'telegram_id' => 222,
            'telefono' => '3001234567',
            'nombre' => 'Intruso',
        ])->assertUnprocessable()->assertJsonValidationErrors('telefono');
    }

    public function test_vincular_valida_los_datos(): void
    {
        $this->comoBot()->postJson('/api/clientes/vincular', ['telefono' => '12345'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['telegram_id', 'telefono', 'nombre']);
    }

    public function test_recordatorios_pendientes_y_marcar_enviado(): void
    {
        $cliente = $this->cliente();
        $pronto = Cita::factory()->enTurno(Carbon::parse('2026-11-10 08:00'))
            ->create(['cliente_id' => $cliente->id, 'profesional_id' => $this->barbero->id]);
        Cita::factory()->enTurno(Carbon::parse('2026-11-10 13:00'))
            ->create(['cliente_id' => $cliente->id, 'profesional_id' => $this->barbero->id]);
        Cita::factory()->enTurno(Carbon::parse('2026-11-10 09:00'))->conEstado(Cita::CANCELADA)
            ->create(['cliente_id' => $cliente->id, 'profesional_id' => $this->estilista->id]);

        $this->comoBot()->getJson('/api/recordatorios/pendientes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pronto->id)
            ->assertJsonPath('data.0.cliente.telegram_id', $cliente->telegram_id);

        $this->patchJson("/api/recordatorios/{$pronto->id}/enviado")->assertNoContent();

        $this->getJson('/api/recordatorios/pendientes')->assertOk()->assertJsonCount(0, 'data');
        $this->patchJson('/api/recordatorios/999/enviado')->assertNotFound();
    }
}
