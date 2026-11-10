<?php

namespace App\Repositories;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Profesional;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class CitaRepository
{
    /**
     * Horas de inicio ya tomadas por el profesional ese día, como 'Y-m-d H:i:s'.
     *
     * @return array<int, string>
     */
    public function iniciosOcupados(Profesional $profesional, Carbon $fecha): array
    {
        return Cita::activas()
            ->where('profesional_id', $profesional->id)
            ->whereBetween('inicio', [$fecha->copy()->startOfDay(), $fecha->copy()->endOfDay()])
            ->pluck('inicio')
            ->map(fn ($inicio) => Carbon::parse($inicio)->format('Y-m-d H:i:s'))
            ->all();
    }

    /**
     * Revisa si el turno ya está tomado y bloquea esas filas hasta que termine la transacción.
     */
    public function turnoOcupadoConBloqueo(int $profesionalId, Carbon $inicio): bool
    {
        return Cita::activas()
            ->where('profesional_id', $profesionalId)
            ->where('inicio', $inicio)
            ->lockForUpdate()
            ->exists();
    }

    public function crear(array $datos): Cita
    {
        return Cita::create($datos);
    }

    public function actualizar(Cita $cita, array $datos): Cita
    {
        $cita->update($datos);

        return $cita;
    }

    /**
     * Citas pendientes del cliente desde ahora, la más próxima primero.
     */
    public function proximasDelCliente(Cliente $cliente): Collection
    {
        return $cliente->citas()
            ->with('profesional')
            ->where('estado', Cita::PENDIENTE)
            ->where('inicio', '>=', Carbon::now())
            ->orderBy('inicio')
            ->get();
    }

    /**
     * Citas pendientes que empiezan antes de $hasta y cuyo cliente aún no recibió aviso.
     */
    public function recordatoriosPendientes(Carbon $hasta): Collection
    {
        return Cita::with(['cliente', 'profesional'])
            ->where('estado', Cita::PENDIENTE)
            ->whereNull('recordatorio_enviado_at')
            ->whereBetween('inicio', [Carbon::now(), $hasta])
            ->whereHas('cliente', fn ($q) => $q->whereNotNull('telegram_id'))
            ->orderBy('inicio')
            ->get();
    }
}
