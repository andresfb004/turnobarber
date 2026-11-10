<?php

namespace App\Http\Resources;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cliente
 */
class ClienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'telegram_id' => $this->telegram_id,
            'nombre' => $this->nombre,
            'telefono' => $this->telefono,
            'inasistencias' => $this->inasistencias,
            'bloqueado' => $this->bloqueado,
        ];
    }
}
