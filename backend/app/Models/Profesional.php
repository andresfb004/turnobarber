<?php

namespace App\Models;

use Database\Factories\ProfesionalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Laravel pluralizaría "Profesional" como "profesionals".
#[Table('profesionales')]
#[Fillable(['user_id', 'nombre', 'especialidad', 'activo'])]
class Profesional extends Model
{
    /** @use HasFactory<ProfesionalFactory> */
    use HasFactory;

    public const BARBERIA = 'barberia';

    public const BELLEZA = 'belleza';

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /**
     * Cuenta con la que entra al panel (1:1).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Horario de atención por día de la semana (1:N).
     */
    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class);
    }

    /**
     * Citas de su agenda (1:N).
     */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }

    /**
     * Días u horas en que no atiende (N:M).
     */
    public function cierres(): BelongsToMany
    {
        return $this->belongsToMany(Cierre::class, 'cierre_profesional')->withTimestamps();
    }
}
