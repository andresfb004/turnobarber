<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cierres', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            // Ambas nulas = cerrado todo el día.
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->string('motivo', 120);
            $table->timestamps();

            $table->index('fecha');
        });

        // N:M: un cierre puede afectar a uno o a los dos profesionales.
        Schema::create('cierre_profesional', function (Blueprint $table) {
            $table->foreignId('cierre_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profesional_id')->constrained('profesionales')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['cierre_id', 'profesional_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cierre_profesional');
        Schema::dropIfExists('cierres');
    }
};
