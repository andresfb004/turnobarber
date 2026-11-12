<?php

namespace App\Services;

use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use Illuminate\Validation\ValidationException;

class ClienteService
{
    public function __construct(private ClienteRepository $clientes) {}

    /**
     * Asocia una cuenta de Telegram a un cliente: lo encuentra por telegram_id o por
     * teléfono (un cliente creado desde el panel), o lo crea.
     *
     * @return array{0: Cliente, 1: bool} El cliente y si se acaba de crear.
     */
    public function vincular(int $telegramId, string $telefono, string $nombre): array
    {
        $porTelegram = $this->clientes->porTelegramId($telegramId);
        $porTelefono = $this->clientes->porTelefono($telefono);

        if ($porTelefono && $porTelefono->telegram_id && $porTelefono->telegram_id !== $telegramId) {
            throw ValidationException::withMessages([
                'telefono' => 'Ese teléfono ya está vinculado a otra cuenta de Telegram.',
            ]);
        }

        if ($porTelegram && $porTelefono && $porTelegram->isNot($porTelefono)) {
            throw ValidationException::withMessages([
                'telefono' => 'Ese teléfono pertenece a otro cliente. Comunícate con la barbería.',
            ]);
        }

        $cliente = $porTelegram ?? $porTelefono;

        if ($cliente) {
            return [$this->clientes->actualizar($cliente, [
                'telegram_id' => $telegramId,
                'telefono' => $telefono,
            ]), false];
        }

        return [$this->clientes->crear([
            'telegram_id' => $telegramId,
            'telefono' => $telefono,
            'nombre' => $nombre,
        ]), true];
    }

    /**
     * RN4: el administrador puede desbloquear antes de los 14 días; el cliente empieza de cero.
     */
    public function desbloquear(Cliente $cliente): Cliente
    {
        return $this->clientes->actualizar($cliente, ['inasistencias' => 0, 'bloqueado' => false, 'bloqueado_hasta' => null]);
    }

    public function eliminar(Cliente $cliente): void
    {
        if ($cliente->citas()->exists()) {
            throw ValidationException::withMessages([
                'cliente' => 'No se puede eliminar un cliente que tiene citas registradas.',
            ]);
        }

        $cliente->delete();
    }
}
