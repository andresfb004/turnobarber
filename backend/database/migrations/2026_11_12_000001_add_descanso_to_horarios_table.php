<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Descanso de mediodía dentro del horario (entrevista: 7:00–12:00 y 2:00–7:00 p. m.).
     */
    public function up(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->time('descanso_inicio')->nullable()->after('hora_apertura');
            $table->time('descanso_fin')->nullable()->after('descanso_inicio');
        });

        // Los horarios de prueba (8:00–18:00) pasan al horario real que dio el barbero.
        DB::table('horarios')
            ->where('hora_apertura', '08:00:00')
            ->where('hora_cierre', '18:00:00')
            ->update([
                'hora_apertura' => '07:00:00',
                'descanso_inicio' => '12:00:00',
                'descanso_fin' => '14:00:00',
                'hora_cierre' => '19:00:00',
            ]);
    }

    public function down(): void
    {
        Schema::table('horarios', function (Blueprint $table) {
            $table->dropColumn(['descanso_inicio', 'descanso_fin']);
        });
    }
};
