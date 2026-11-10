<?php

namespace Tests\Feature\Panel;

use App\Models\Cita;
use App\Models\Profesional;
use Illuminate\Support\Carbon;
use Tests\Feature\Api\ApiTestCase;

/**
 * Escenario: martes 10 de noviembre de 2026, dos profesionales de 8:00 a 18:00.
 */
class DashboardTest extends ApiTestCase
{
    private function cita(Profesional $profesional, string $inicio, string $estado): Cita
    {
        return Cita::factory()->enTurno(Carbon::parse($inicio))->conEstado($estado)
            ->create(['profesional_id' => $profesional->id]);
    }

    private function verDashboard()
    {
        return $this->actingAs($this->barbero->user)->get('/dashboard')->assertOk();
    }

    public function test_avisa_las_citas_que_pasaron_sin_marcar_y_lleva_a_la_agenda_de_ese_dia(): void
    {
        $this->cita($this->barbero, '2026-11-09 10:00', Cita::PENDIENTE);
        $this->cita($this->estilista, '2026-11-11 10:00', Cita::PENDIENTE);

        $this->verDashboard()
            ->assertSee('1 cita ya pasó y sigue sin marcar.')
            ->assertSee(route('agenda', ['fecha' => '2026-11-09']), false);
    }

    public function test_sin_citas_por_marcar_no_hay_aviso(): void
    {
        $this->cita($this->barbero, '2026-11-09 10:00', Cita::ATENDIDA);

        $this->verDashboard()->assertDontSee('sin marcar');
    }

    public function test_desglosa_las_citas_de_hoy_por_profesional(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 12:00'));
        $this->cita($this->barbero, '2026-11-10 08:00', Cita::ATENDIDA);
        $this->cita($this->barbero, '2026-11-10 10:00', Cita::PENDIENTE);
        $this->cita($this->barbero, '2026-11-10 15:00', Cita::PENDIENTE);

        $this->verDashboard()
            ->assertViewHas('hoy', fn ($hoy) => $hoy->first()['total'] === 3
                && $hoy->first()['atendidas'] === 1
                && $hoy->first()['por_venir'] === 1
                && $hoy->first()['por_marcar'] === 1)
            ->assertSee('1 atendida, 1 por venir')
            ->assertSee('1 por marcar');
    }

    public function test_las_inasistencias_y_la_ocupacion_tienen_contexto(): void
    {
        $this->cita($this->barbero, '2026-11-03 10:00', Cita::ATENDIDA);
        $this->cita($this->barbero, '2026-11-04 10:00', Cita::NO_ASISTIO);

        $this->verDashboard()
            ->assertSee('50% de 2 citas')
            ->assertSee('de 120 turnos')
            ->assertSee(route('clientes.index', ['estado' => 'con_inasistencias']), false);
    }

    public function test_el_grafico_omite_los_domingos_y_muestra_totales_y_tabla_accesible(): void
    {
        $this->cita($this->barbero, '2026-11-03 10:00', Cita::ATENDIDA);
        $this->cita($this->barbero, '2026-11-04 10:00', Cita::ATENDIDA);

        $this->verDashboard()
            ->assertViewHas('grafico', fn ($g) => count($g['etiquetas']) === 26
                && ! in_array('8 nov', $g['etiquetas'], true)
                && $g['series'][0]['total'] === 2)
            ->assertSee('<strong>2</strong> atendidas', false)
            ->assertSee('<caption>Citas atendidas por día', false);
    }

    public function test_sin_datos_muestra_un_estado_vacio_y_no_carga_el_grafico(): void
    {
        $this->verDashboard()
            ->assertSee('Todavía no hay citas atendidas en los últimos 30 días.')
            ->assertDontSee('chart.umd.min.js', false);
    }

    public function test_la_lista_de_clientes_filtra_los_que_tienen_inasistencias(): void
    {
        $con = $this->cliente(['nombre' => 'Con Faltas', 'inasistencias' => 1]);
        $this->cliente(['nombre' => 'Sin Faltas', 'inasistencias' => 0]);

        $this->actingAs($this->barbero->user)->get('/clientes?estado=con_inasistencias')
            ->assertOk()->assertSee($con->nombre)->assertDontSee('Sin Faltas');
    }
}
