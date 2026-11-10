<?php

namespace Database\Seeders;

use App\Models\Horario;
use App\Models\Profesional;
use Illuminate\Database\Seeder;

class HorarioSeeder extends Seeder
{
    /**
     * Valores de prueba: el horario real está pendiente de confirmar con el barbero.
     * Cuando lo tengas, cambia solo estas dos horas (o el día que sea distinto).
     */
    private const APERTURA = '08:00';

    private const CIERRE = '18:00';

    public function run(): void
    {
        foreach (Profesional::all() as $profesional) {
            foreach (array_keys(Horario::DIAS) as $dia) {
                Horario::create([
                    'profesional_id' => $profesional->id,
                    'dia_semana' => $dia,
                    'hora_apertura' => self::APERTURA,
                    'hora_cierre' => self::CIERRE,
                ]);
            }
        }
    }
}
