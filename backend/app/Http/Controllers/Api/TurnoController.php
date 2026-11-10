<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TurnosRequest;
use App\Http\Resources\ProfesionalResource;
use App\Http\Resources\TurnoResource;
use App\Repositories\ProfesionalRepository;
use App\Services\TurnoService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TurnoController extends Controller
{
    /**
     * Turnos libres de un profesional en una fecha (RN1).
     */
    public function __invoke(
        TurnosRequest $request,
        ProfesionalRepository $profesionales,
        TurnoService $turnos,
    ): AnonymousResourceCollection {
        $profesional = $profesionales->encontrarOFallar($request->validated('profesional'));
        $fecha = $request->fecha();

        return TurnoResource::collection($turnos->disponibles($profesional, $fecha))
            ->additional([
                'fecha' => $fecha->toDateString(),
                'profesional' => new ProfesionalResource($profesional),
            ]);
    }
}
