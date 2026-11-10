<?php

namespace App\Models;

use Database\Factories\CierreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['fecha', 'hora_inicio', 'hora_fin', 'motivo'])]
class Cierre extends Model
{
    /** @use HasFactory<CierreFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Profesionales a los que afecta (N:M).
     */
    public function profesionales(): BelongsToMany
    {
        return $this->belongsToMany(Profesional::class, 'cierre_profesional')->withTimestamps();
    }

    public function esTodoElDia(): bool
    {
        return $this->hora_inicio === null && $this->hora_fin === null;
    }
}
