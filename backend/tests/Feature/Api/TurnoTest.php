<?php

namespace Tests\Feature\Api;

use App\Models\Cierre;
use App\Models\Cita;
use App\Models\Profesional;
use Illuminate\Support\Carbon;

class TurnoTest extends ApiTestCase
{
    private function horas(string $fecha, ?Profesional $profesional = null): array
    {
        $profesional ??= $this->barbero;

        return collect(
            $this->comoBot()
                ->getJson("/api/turnos?profesional={$profesional->id}&fecha={$fecha}")
                ->assertOk()
                ->json('data')
        )->map(fn ($turno) => Carbon::parse($turno['inicio'])->format('H:i'))->all();
    }

    public function test_lista_los_profesionales_activos(): void
    {
        Profesional::factory()->create(['activo' => false]);

        $this->comoBot()->getJson('/api/profesionales')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.especialidad_texto', 'Barbería');
    }

    public function test_ofrece_un_turno_por_hora_dentro_del_horario(): void
    {
        $this->assertSame(
            ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
            $this->horas('2026-11-11'),
        );
    }

    public function test_quita_los_turnos_tomados_solo_de_ese_profesional(): void
    {
        Cita::factory()->enTurno(Carbon::parse('2026-11-11 10:00'))->create(['profesional_id' => $this->barbero->id]);
        Cita::factory()->enTurno(Carbon::parse('2026-11-11 11:00'))->conEstado(Cita::CANCELADA)
            ->create(['profesional_id' => $this->barbero->id]);

        $this->assertNotContains('10:00', $this->horas('2026-11-11'));
        $this->assertContains('11:00', $this->horas('2026-11-11'), 'Una cita cancelada libera el turno.');
        $this->assertContains('10:00', $this->horas('2026-11-11', $this->estilista));
    }

    public function test_hoy_solo_ofrece_turnos_con_una_hora_de_anticipacion(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 09:30'));

        $this->assertSame('11:00', $this->horas('2026-11-10')[0]);
    }

    public function test_el_domingo_no_hay_turnos(): void
    {
        $this->assertSame([], $this->horas('2026-11-15'));
    }

    public function test_un_cierre_de_todo_el_dia_deja_el_dia_vacio(): void
    {
        Cierre::factory()->create(['fecha' => '2026-11-16'])->profesionales()->attach($this->barbero);

        $this->assertSame([], $this->horas('2026-11-16'));
        $this->assertCount(10, $this->horas('2026-11-16', $this->estilista));
    }

    public function test_un_cierre_parcial_quita_solo_esas_horas(): void
    {
        Cierre::factory()->parcial('14:00', '16:00')->create(['fecha' => '2026-11-12'])
            ->profesionales()->attach($this->barbero);

        $horas = $this->horas('2026-11-12');

        $this->assertNotContains('14:00', $horas);
        $this->assertNotContains('15:00', $horas);
        $this->assertContains('13:00', $horas);
        $this->assertContains('16:00', $horas);
    }

    public function test_valida_la_fecha(): void
    {
        $this->comoBot();

        $this->getJson("/api/turnos?profesional={$this->barbero->id}&fecha=2026-11-09")
            ->assertUnprocessable()->assertJsonValidationErrors('fecha');
        $this->getJson("/api/turnos?profesional={$this->barbero->id}&fecha=2026-12-30")
            ->assertUnprocessable()->assertJsonValidationErrors('fecha');
        $this->getJson("/api/turnos?profesional={$this->barbero->id}&fecha=11-11-2026")
            ->assertUnprocessable()->assertJsonValidationErrors('fecha');
    }

    public function test_un_profesional_que_no_existe_responde_404(): void
    {
        $this->comoBot()->getJson('/api/turnos?profesional=999&fecha=2026-11-11')
            ->assertNotFound()
            ->assertJsonPath('message', 'No se encontró el recurso solicitado.');
    }
}
