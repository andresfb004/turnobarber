<?php

namespace App\Http\Requests\Panel;

use App\Models\Horario;
use App\Models\Profesional;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProfesionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Profesional|null $profesional */
        $profesional = $this->route('profesional');

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'especialidad' => ['required', Rule::in([Profesional::BARBERIA, Profesional::BELLEZA])],
            'activo' => ['boolean'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($profesional?->user_id)],
            'password' => [$profesional ? 'nullable' : 'required', 'string', 'min:8'],
            'rol' => ['required', Rule::in([Role::ADMINISTRADOR, Role::PROFESIONAL])],
            'horarios' => ['array'],
            'horarios.*.atiende' => ['boolean'],
            'horarios.*.apertura' => ['nullable', 'date_format:H:i'],
            'horarios.*.cierre' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach (Horario::DIAS as $dia => $nombre) {
                $horario = $this->input("horarios.$dia", []);

                if (empty($horario['atiende'])) {
                    continue;
                }

                if (empty($horario['apertura']) || empty($horario['cierre'])) {
                    $validator->errors()->add("horarios.$dia", "$nombre: indica la hora de apertura y de cierre.");
                } elseif ($horario['apertura'] >= $horario['cierre']) {
                    $validator->errors()->add("horarios.$dia", "$nombre: la apertura debe ser antes del cierre.");
                }
            }
        });
    }

    public function attributes(): array
    {
        return ['email' => 'correo', 'password' => 'contraseña'];
    }

    /**
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return [
            ...$this->safe()->only(['nombre', 'especialidad', 'email', 'password', 'rol']),
            'activo' => $this->boolean('activo'),
            'horarios' => $this->input('horarios', []),
        ];
    }
}
