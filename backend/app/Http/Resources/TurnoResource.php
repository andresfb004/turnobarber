<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Un turno libre. El recurso envuelve el Carbon con la hora de inicio.
 *
 * @property Carbon $resource
 */
class TurnoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'inicio' => $this->resource->toIso8601String(),
            'hora' => $this->resource->copy()->locale('es')->isoFormat('h:mm a'),
        ];
    }
}
