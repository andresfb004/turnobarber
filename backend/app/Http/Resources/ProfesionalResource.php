<?php

namespace App\Http\Resources;

use App\Models\Horario;
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
            'activo' => $this->activo,
            'horarios' => $this->whenLoaded('horarios', fn () => $this->horarios
                ->sortBy('dia_semana')
                ->map(fn (Horario $horario) => [
                    'dia_semana' => $horario->dia_semana,
                    'dia' => Horario::DIAS[$horario->dia_semana] ?? null,
                    'apertura' => substr((string) $horario->hora_apertura, 0, 5),
                    'cierre' => substr((string) $horario->hora_cierre, 0, 5),
                ])
                ->values()),
        ];
    }
}
