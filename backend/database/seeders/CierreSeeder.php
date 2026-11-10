<?php

namespace Database\Seeders;

use App\Models\Cierre;
use App\Models\Profesional;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CierreSeeder extends Seeder
{
    /**
     * Dos cierres de prueba: uno que afecta a los dos y otro solo a la estilista.
     */
    public function run(): void
    {
        $barbero = Profesional::where('especialidad', Profesional::BARBERIA)->first();
        $estilista = Profesional::where('especialidad', Profesional::BELLEZA)->first();

        $festivo = Cierre::factory()->create([
            'fecha' => $this->proximoDiaHabil(Carbon::today()->addDays(9)),
            'motivo' => 'Festivo (prueba)',
        ]);
        $festivo->profesionales()->attach([$barbero->id, $estilista->id]);

        $diligencia = Cierre::factory()->parcial('14:00', '18:00')->create([
            'fecha' => $this->proximoDiaHabil(Carbon::today()->addDays(3)),
            'motivo' => 'Diligencia personal (prueba)',
        ]);
        $diligencia->profesionales()->attach($estilista->id);
    }

    private function proximoDiaHabil(Carbon $fecha): string
    {
        return ($fecha->isSunday() ? $fecha->addDay() : $fecha)->toDateString();
    }
}
