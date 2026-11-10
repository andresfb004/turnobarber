<?php

namespace App\Models;

use Database\Factories\CitaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cliente_id', 'profesional_id', 'inicio', 'fin', 'estado', 'recordatorio_enviado_at'])]
class Cita extends Model
{
    /** @use HasFactory<CitaFactory> */
    use HasFactory;

    public const PENDIENTE = 'pendiente';

    public const ATENDIDA = 'atendida';

    public const CANCELADA = 'cancelada';

    public const NO_ASISTIO = 'no_asistio';

    public const ESTADOS = [self::PENDIENTE, self::ATENDIDA, self::CANCELADA, self::NO_ASISTIO];

    /**
     * Duración fija de un turno, en minutos.
     */
    public const DURACION_MINUTOS = 60;

    /**
     * RN3: se agenda desde 1 h y hasta 14 días adelante; cancelar con menos
     * de 2 h cuenta como inasistencia.
     */
    public const ANTICIPACION_MINIMA_MINUTOS = 60;

    public const ANTICIPACION_MAXIMA_DIAS = 14;

    public const CANCELACION_MINIMA_MINUTOS = 120;

    /**
     * Ventana en la que el bot envía el recordatorio antes de la cita.
     */
    public const RECORDATORIO_MINUTOS = 120;

    protected function casts(): array
    {
        return [
            'inicio' => 'datetime',
            'fin' => 'datetime',
            'recordatorio_enviado_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function profesional(): BelongsTo
    {
        return $this->belongsTo(Profesional::class);
    }

    /**
     * Citas que ocupan su turno (todas menos las canceladas).
     */
    public function scopeActivas(Builder $query): void
    {
        $query->where('estado', '!=', self::CANCELADA);
    }
}
