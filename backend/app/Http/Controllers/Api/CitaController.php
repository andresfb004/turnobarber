<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CancelarCitaRequest;
use App\Http\Requests\Api\StoreCitaRequest;
use App\Http\Resources\CitaResource;
use App\Models\Cita;
use App\Repositories\ClienteRepository;
use App\Repositories\ProfesionalRepository;
use App\Services\CitaService;
use Illuminate\Http\JsonResponse;

class CitaController extends Controller
{
    public function __construct(private CitaService $citas) {}

    /**
     * Agenda un turno (RN2, RN3 y RN4).
     */
    public function store(
        StoreCitaRequest $request,
        ClienteRepository $clientes,
        ProfesionalRepository $profesionales,
    ): JsonResponse {
        $cita = $this->citas->agendar(
            $clientes->porTelegramIdOFallar($request->validated('telegram_id')),
            $profesionales->encontrarOFallar($request->validated('profesional_id')),
            $request->inicio(),
        );

        return (new CitaResource($cita->load('profesional', 'cliente')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Cita $cita): CitaResource
    {
        return new CitaResource($cita->load('profesional', 'cliente'));
    }

    /**
     * Cancela la cita del cliente. Con menos de 2 h cuenta como inasistencia (RN3).
     */
    public function cancelar(Cita $cita, CancelarCitaRequest $request, ClienteRepository $clientes): CitaResource
    {
        $cliente = $clientes->porTelegramIdOFallar($request->validated('telegram_id'));

        [$cita, $cuentaComoFalta] = $this->citas->cancelar($cita, $cliente);

        return (new CitaResource($cita->load('profesional')))->additional([
            'cuenta_como_inasistencia' => $cuentaComoFalta,
            'message' => $cuentaComoFalta
                ? 'Cita cancelada. Como faltaban menos de 2 horas, cuenta como inasistencia.'
                : 'Cita cancelada.',
        ]);
    }
}
