<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;

class CierreRequest extends FormRequest
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
        return [
            'fecha' => ['required', 'date_format:Y-m-d', $this->route('cierre') ? 'date' : 'after_or_equal:today'],
            'todo_el_dia' => ['boolean'],
            'hora_inicio' => ['exclude_if:todo_el_dia,1', 'required', 'date_format:H:i'],
            'hora_fin' => ['exclude_if:todo_el_dia,1', 'required', 'date_format:H:i', 'after:hora_inicio'],
            'motivo' => ['required', 'string', 'max:120'],
            'profesionales' => ['required', 'array', 'min:1'],
            'profesionales.*' => ['integer', 'exists:profesionales,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hora_inicio' => 'hora de inicio',
            'hora_fin' => 'hora de fin',
            'profesionales' => 'a quién afecta',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.after_or_equal' => 'La fecha del cierre no puede ser pasada.',
            'profesionales.required' => 'Elige al menos un profesional afectado.',
        ];
    }

    /**
     * @return array{fecha: string, hora_inicio: ?string, hora_fin: ?string, motivo: string}
     */
    public function datosCierre(): array
    {
        $todoElDia = $this->boolean('todo_el_dia');

        return [
            'fecha' => $this->validated('fecha'),
            'hora_inicio' => $todoElDia ? null : $this->validated('hora_inicio'),
            'hora_fin' => $todoElDia ? null : $this->validated('hora_fin'),
            'motivo' => $this->validated('motivo'),
        ];
    }
}
