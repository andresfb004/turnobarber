<?php

namespace Database\Seeders;

use App\Models\Horario;
use App\Models\Profesional;
use Illuminate\Database\Seeder;

class HorarioSeeder extends Seeder
{
    /**
     * Horario real según la entrevista: lunes a sábado de 7:00 a 12:00 y de 2:00 a 7:00 p. m.
     */
    private const APERTURA = '07:00';

    private const DESCANSO_INICIO = '12:00';

    private const DESCANSO_FIN = '14:00';

    private const CIERRE = '19:00';

    public function run(): void
    {
        foreach (Profesional::all() as $profesional) {
            foreach (array_keys(Horario::DIAS) as $dia) {
                Horario::create([
                    'profesional_id' => $profesional->id,
                    'dia_semana' => $dia,
                    'hora_apertura' => self::APERTURA,
                    'descanso_inicio' => self::DESCANSO_INICIO,
                    'descanso_fin' => self::DESCANSO_FIN,
                    'hora_cierre' => self::CIERRE,
                ]);
            }
        }
    }
}
