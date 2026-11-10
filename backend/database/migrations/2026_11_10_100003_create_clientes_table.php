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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            // Nulo para clientes creados desde el panel que aún no usan el bot.
            $table->unsignedBigInteger('telegram_id')->nullable()->unique();
            $table->string('nombre', 100);
            $table->string('telefono', 20)->unique();
            $table->unsignedTinyInteger('inasistencias')->default(0);
            $table->boolean('bloqueado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
