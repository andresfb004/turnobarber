<?php

namespace App\Repositories;

use App\Models\Cliente;

class ClienteRepository
{
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
