<?php

namespace App\Http\Resources;

use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cita
 */
class CitaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $inicio = $this->inicio->copy()->locale('es');

        return [
            'id' => $this->id,
            'inicio' => $this->inicio->toIso8601String(),
            'fin' => $this->fin->toIso8601String(),
            'fecha_texto' => $inicio->isoFormat('dddd D [de] MMMM'),
            'hora_texto' => $inicio->isoFormat('h:mm a'),
            'estado' => $this->estado,
            'profesional' => new ProfesionalResource($this->whenLoaded('profesional')),
            'cliente' => new ClienteResource($this->whenLoaded('cliente')),
        ];
    }
}
