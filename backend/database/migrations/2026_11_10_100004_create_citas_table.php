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
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profesional_id')->constrained('profesionales')->restrictOnDelete();
            // Turnos de 1 h en punto: fin = inicio + 1 h.
            $table->dateTime('inicio');
            $table->dateTime('fin');
            $table->enum('estado', ['pendiente', 'atendida', 'cancelada', 'no_asistio'])
                ->default('pendiente');
            $table->dateTime('recordatorio_enviado_at')->nullable();
            $table->timestamps();

            $table->index(['profesional_id', 'inicio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
