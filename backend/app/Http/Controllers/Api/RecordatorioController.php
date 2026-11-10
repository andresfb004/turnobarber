<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CitaResource;
use App\Models\Cita;
use App\Services\CitaService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RecordatorioController extends Controller
{
    public function __construct(private CitaService $citas) {}

    /**
     * Citas de las próximas 2 horas cuyo cliente aún no recibió el recordatorio.
     */
    public function pendientes(): AnonymousResourceCollection
    {
        return CitaResource::collection($this->citas->recordatoriosPendientes());
    }

    public function enviado(Cita $cita): Response
    {
        $this->citas->marcarRecordatorioEnviado($cita);

        return response()->noContent();
    }
}
