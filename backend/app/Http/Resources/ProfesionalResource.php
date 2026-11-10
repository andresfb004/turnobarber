<?php

namespace App\Http\Resources;

use App\Models\Profesional;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Profesional
 */
class ProfesionalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'especialidad' => $this->especialidad,
            'especialidad_texto' => $this->especialidad === Profesional::BARBERIA ? 'Barbería' : 'Belleza',
        ];
    }
}
