<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre'])]
class Role extends Model
{
    public const ADMINISTRADOR = 'Administrador';

    public const PROFESIONAL = 'Profesional';

    public const BOT = 'Bot';

    /**
     * Usuarios con este rol (1:N).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
