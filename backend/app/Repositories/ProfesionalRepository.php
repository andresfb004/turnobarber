<?php

namespace App\Repositories;

use App\Models\Horario;
use App\Models\Profesional;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ProfesionalRepository
{
    public function activos(): Collection
    {
        return Profesional::where('activo', true)->orderBy('id')->get();
    }

    public function encontrarOFallar(int $id): Profesional
    {
        return Profesional::findOrFail($id);
    }

    public function horarioDelDia(Profesional $profesional, Carbon $fecha): ?Horario
    {
        return $profesional->horarios()->where('dia_semana', $fecha->dayOfWeekIso)->first();
    }

    public function cierresDelDia(Profesional $profesional, Carbon $fecha): Collection
    {
        return $profesional->cierres()->whereDate('fecha', $fecha->toDateString())->get();
    }
}
