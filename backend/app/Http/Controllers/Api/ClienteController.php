<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VincularClienteRequest;
use App\Http\Resources\CitaResource;
use App\Http\Resources\ClienteResource;
use App\Repositories\ClienteRepository;
use App\Services\CitaService;
use App\Services\ClienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClienteController extends Controller
{
    /**
     * Registra al cliente que compartió su número en el bot (201), o lo
     * encuentra si ya existía (200).
     */
    public function vincular(VincularClienteRequest $request, ClienteService $clientes): JsonResponse
    {
        [$cliente, $creado] = $clientes->vincular(
            (int) $request->validated('telegram_id'),
            $request->validated('telefono'),
            $request->validated('nombre'),
        );

        return (new ClienteResource($cliente))
            ->response()
            ->setStatusCode($creado ? 201 : 200);
    }

    /**
     * Próximas citas pendientes del cliente.
     */
    public function citas(int $telegramId, ClienteRepository $clientes, CitaService $citas): AnonymousResourceCollection
    {
        $cliente = $clientes->porTelegramIdOFallar($telegramId);

        return CitaResource::collection($citas->proximasDelCliente($cliente))
            ->additional(['cliente' => new ClienteResource($cliente)]);
    }
}
