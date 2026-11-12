<?php

namespace App\Repositories;

use App\Models\Cliente;
use Illuminate\Support\Carbon;

class ClienteRepository
{
    /**
     * RN4: levanta los bloqueos que ya cumplieron su plazo; el cliente empieza de cero.
     */
    public function liberarBloqueosVencidos(): int
    {
        return Cliente::where('bloqueado', true)
            ->whereNotNull('bloqueado_hasta')
            ->where('bloqueado_hasta', '<=', Carbon::now())
            ->update(['bloqueado' => false, 'bloqueado_hasta' => null, 'inasistencias' => 0]);
    }

    public function porTelegramId(int $telegramId): ?Cliente
    {
        return Cliente::where('telegram_id', $telegramId)->first();
    }

    public function porTelegramIdOFallar(int $telegramId): Cliente
    {
        return Cliente::where('telegram_id', $telegramId)->firstOrFail();
    }

    public function porTelefono(string $telefono): ?Cliente
    {
        return Cliente::where('telefono', $telefono)->first();
    }

    public function crear(array $datos): Cliente
    {
        return Cliente::create($datos);
    }

    public function actualizar(Cliente $cliente, array $datos): Cliente
    {
        $cliente->update($datos);

        return $cliente;
    }
}
