<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RN4: el bloqueo por inasistencias dura 14 días y luego se levanta solo.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dateTime('bloqueado_hasta')->nullable()->after('bloqueado');
            $table->index(['bloqueado', 'bloqueado_hasta']);
        });

        foreach (DB::table('clientes')->where('bloqueado', true)->get(['id', 'updated_at']) as $cliente) {
            DB::table('clientes')->where('id', $cliente->id)->update([
                'bloqueado_hasta' => Carbon::parse($cliente->updated_at ?? now())->addDays(14),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['bloqueado', 'bloqueado_hasta']);
            $table->dropColumn('bloqueado_hasta');
        });
    }
};
