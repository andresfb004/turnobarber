<?php

namespace App\Models;

use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['telegram_id', 'nombre', 'telefono', 'inasistencias', 'bloqueado', 'bloqueado_hasta'])]
class Cliente extends Model
{
    /** @use HasFactory<ClienteFactory> */
    use HasFactory;

    /**
     * Con este número de inasistencias el cliente queda bloqueado (RN4).
     */
    public const MAX_INASISTENCIAS = 2;

    /**
     * Días que dura el bloqueo; después se levanta solo (RN4).
     */
    public const DIAS_BLOQUEO = 14;

    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'inasistencias' => 'integer',
            'bloqueado' => 'boolean',
            'bloqueado_hasta' => 'datetime',
        ];
    }

    /**
     * Citas que ha pedido (1:N).
     */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class);
    }
}
