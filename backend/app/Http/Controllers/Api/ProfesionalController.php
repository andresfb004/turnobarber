<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GuardarProfesionalRequest;
use App\Http\Resources\ProfesionalResource;
use App\Models\Profesional;
use App\Repositories\ProfesionalRepository;
use App\Services\ProfesionalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProfesionalController extends Controller
{
    public function __construct(private ProfesionalService $profesionales) {}

    /**
     * Profesionales que están recibiendo citas.
     */
    public function index(ProfesionalRepository $profesionales): AnonymousResourceCollection
    {
        return ProfesionalResource::collection($profesionales->activos());
    }

    public function show(Profesional $profesional): ProfesionalResource
    {
        return new ProfesionalResource($profesional->load('horarios'));
    }

    /**
     * Crea el profesional con su cuenta del panel y su horario (solo administrador).
     */
    public function store(GuardarProfesionalRequest $request): JsonResponse
    {
        $profesional = $this->profesionales->crear($request->datos());

        return (new ProfesionalResource($profesional->load('horarios')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(GuardarProfesionalRequest $request, Profesional $profesional): ProfesionalResource
    {
        $profesional = $this->profesionales->actualizar($profesional, $request->datos());

        return new ProfesionalResource($profesional->load('horarios'));
    }

    /**
     * Con citas registradas responde 422: se debe desactivar en lugar de eliminar.
     */
    public function destroy(Request $request, Profesional $profesional): Response
    {
        $this->profesionales->eliminar($profesional, $request->user());

        return response()->noContent();
    }
}
