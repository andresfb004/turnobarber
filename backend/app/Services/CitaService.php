<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Profesional;
use App\Repositories\CitaRepository;
use App\Repositories\ClienteRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RN2 · No cruce por profesional, RN3 · Anticipación y RN4 · Bloqueo.
 */
class CitaService
{
    public function __construct(
        private CitaRepository $citas,
        private ClienteRepository $clientes,
        private TurnoService $turnos,
    ) {}

    public function agendar(Cliente $cliente, Profesional $profesional, Carbon $inicio): Cita
    {
        if ($cliente->bloqueado) {
            $this->rechazar('cliente', 'Tu cuenta está bloqueada por inasistencias. Comunícate con la barbería.');
        }

        if (! $profesional->activo) {
            $this->rechazar('profesional_id', 'Ese profesional no está recibiendo citas.');
        }

        $ahora = Carbon::now();

        if ($inicio < $ahora->copy()->addMinutes(Cita::ANTICIPACION_MINIMA_MINUTOS)) {
            $this->rechazar('inicio', 'Debes agendar con al menos 1 hora de anticipación.');
        }

        if ($inicio > $ahora->copy()->addDays(Cita::ANTICIPACION_MAXIMA_DIAS)->endOfDay()) {
            $this->rechazar('inicio', 'Solo puedes agendar hasta '.Cita::ANTICIPACION_MAXIMA_DIAS.' días adelante.');
        }

        if (! $this->turnos->esTurnoDelHorario($profesional, $inicio)) {
            $this->rechazar('inicio', 'Ese turno no está dentro del horario de atención.');
        }

        // RN2: dos clientes pueden ver el mismo turno libre al tiempo, así que se
        // valida otra vez dentro de la transacción con las filas bloqueadas.
        return DB::transaction(function () use ($cliente, $profesional, $inicio) {
            if ($this->citas->turnoOcupadoConBloqueo($profesional->id, $inicio)) {
                $this->rechazar('inicio', 'Ese turno acaba de ser tomado, elige otro.');
            }

            return $this->citas->crear([
                'cliente_id' => $cliente->id,
                'profesional_id' => $profesional->id,
                'inicio' => $inicio,
                'fin' => $inicio->copy()->addMinutes(Cita::DURACION_MINUTOS),
                'estado' => Cita::PENDIENTE,
            ]);
        });
    }

    /**
     * Cancela la cita del cliente. Con menos de 2 h de anticipación cuenta como inasistencia (RN3).
     *
     * @return array{0: Cita, 1: bool} La cita y si la cancelación contó como inasistencia.
     */
    public function cancelar(Cita $cita, Cliente $cliente): array
    {
        // Una cita de otro cliente se trata como inexistente para no revelar datos.
        if ($cita->cliente_id !== $cliente->id) {
            throw (new ModelNotFoundException)->setModel(Cita::class, [$cita->id]);
        }

        if ($cita->estado !== Cita::PENDIENTE) {
            $this->rechazar('cita', 'Esta cita ya no se puede cancelar.');
        }

        $ahora = Carbon::now();

        if ($cita->inicio <= $ahora) {
            $this->rechazar('cita', 'La hora de esta cita ya pasó.');
        }

        $cuentaComoFalta = $ahora->diffInMinutes($cita->inicio) < Cita::CANCELACION_MINIMA_MINUTOS;

        DB::transaction(function () use ($cita, $cliente, $cuentaComoFalta) {
            $this->citas->actualizar($cita, ['estado' => Cita::CANCELADA]);

            if ($cuentaComoFalta) {
                $this->registrarInasistencia($cliente);
            }
        });

        return [$cita, $cuentaComoFalta];
    }

    /**
     * Desde el panel: marca una cita pendiente como atendida, no asistió o cancelada.
     * La asistencia solo se registra cuando la cita ya empezó; cancelar, solo antes.
     */
    public function cambiarEstado(Cita $cita, string $estado): Cita
    {
        if ($cita->estado !== Cita::PENDIENTE) {
            $this->rechazar('estado', 'Solo se puede cambiar una cita pendiente.');
        }

        $yaEmpezo = $cita->inicio <= Carbon::now();

        if (in_array($estado, [Cita::ATENDIDA, Cita::NO_ASISTIO], true) && ! $yaEmpezo) {
            $this->rechazar('estado', 'La asistencia se marca cuando la cita ya empezó.');
        }

        if ($estado === Cita::CANCELADA && $yaEmpezo) {
            $this->rechazar('estado', 'No se puede cancelar una cita que ya empezó.');
        }

        DB::transaction(function () use ($cita, $estado) {
            $this->citas->actualizar($cita, ['estado' => $estado]);

            if ($estado === Cita::NO_ASISTIO) {
                $this->registrarInasistencia($cita->cliente);
            }
        });

        return $cita;
    }

    public function proximasDelCliente(Cliente $cliente): Collection
    {
        return $this->citas->proximasDelCliente($cliente);
    }

    public function recordatoriosPendientes(): Collection
    {
        return $this->citas->recordatoriosPendientes(
            Carbon::now()->addMinutes(Cita::RECORDATORIO_MINUTOS),
        );
    }

    public function marcarRecordatorioEnviado(Cita $cita): void
    {
        $this->citas->actualizar($cita, ['recordatorio_enviado_at' => Carbon::now()]);
    }

    /**
     * RN4: suma una inasistencia y bloquea al cliente al llegar al máximo.
     */
    private function registrarInasistencia(Cliente $cliente): void
    {
        $inasistencias = $cliente->inasistencias + 1;

        $this->clientes->actualizar($cliente, [
            'inasistencias' => $inasistencias,
            'bloqueado' => $cliente->bloqueado || $inasistencias >= Cliente::MAX_INASISTENCIAS,
        ]);
    }

    private function rechazar(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
