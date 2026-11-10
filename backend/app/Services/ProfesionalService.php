<?php

namespace App\Services;

use App\Models\Horario;
use App\Models\Profesional;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Crea y actualiza a un profesional junto con su cuenta del panel (1:1) y su horario.
 */
class ProfesionalService
{
    /**
     * @param  array{nombre: string, especialidad: string, activo: bool, email: string, password: string, rol: string, horarios: array}  $datos
     */
    public function crear(array $datos): Profesional
    {
        return DB::transaction(function () use ($datos) {
            $user = User::create([
                'role_id' => Role::where('nombre', $datos['rol'])->value('id'),
                'name' => $datos['nombre'],
                'email' => $datos['email'],
                'password' => $datos['password'],
            ]);

            $profesional = Profesional::create([
                'user_id' => $user->id,
                'nombre' => $datos['nombre'],
                'especialidad' => $datos['especialidad'],
                'activo' => $datos['activo'],
            ]);

            $this->guardarHorarios($profesional, $datos['horarios']);

            return $profesional;
        });
    }

    public function actualizar(Profesional $profesional, array $datos): Profesional
    {
        return DB::transaction(function () use ($profesional, $datos) {
            $cuenta = [
                'role_id' => Role::where('nombre', $datos['rol'])->value('id'),
                'name' => $datos['nombre'],
                'email' => $datos['email'],
            ];

            if (! empty($datos['password'])) {
                $cuenta['password'] = $datos['password'];
            }

            $profesional->user->update($cuenta);
            $profesional->update([
                'nombre' => $datos['nombre'],
                'especialidad' => $datos['especialidad'],
                'activo' => $datos['activo'],
            ]);

            $this->guardarHorarios($profesional, $datos['horarios']);

            return $profesional;
        });
    }

    /**
     * Un profesional con citas no se borra (perdería su historial): se desactiva.
     */
    public function eliminar(Profesional $profesional, User $quienElimina): void
    {
        if ($profesional->user_id === $quienElimina->id) {
            throw ValidationException::withMessages(['profesional' => 'No puedes eliminar tu propia cuenta.']);
        }

        if ($profesional->citas()->exists()) {
            throw ValidationException::withMessages([
                'profesional' => 'Este profesional tiene citas registradas. Desactívalo en lugar de eliminarlo.',
            ]);
        }

        DB::transaction(function () use ($profesional) {
            $user = $profesional->user;
            $profesional->delete();
            $user->delete();
        });
    }

    /**
     * @param  array<int, array{atiende?: bool, apertura?: string, cierre?: string}>  $horarios  Por día 1–6.
     */
    private function guardarHorarios(Profesional $profesional, array $horarios): void
    {
        $profesional->horarios()->delete();

        foreach (array_keys(Horario::DIAS) as $dia) {
            $horario = $horarios[$dia] ?? [];

            if (empty($horario['atiende'])) {
                continue;
            }

            $profesional->horarios()->create([
                'dia_semana' => $dia,
                'hora_apertura' => $horario['apertura'],
                'hora_cierre' => $horario['cierre'],
            ]);
        }
    }
}
