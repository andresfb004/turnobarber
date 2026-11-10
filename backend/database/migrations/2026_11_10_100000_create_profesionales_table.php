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
        Schema::create('profesionales', function (Blueprint $table) {
            $table->id();
            // 1:1 con users: cada profesional entra al panel con su propia cuenta.
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('nombre', 100);
            $table->enum('especialidad', ['barberia', 'belleza']);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profesionales');
    }
};
