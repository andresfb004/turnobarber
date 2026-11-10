<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreCitaRequest extends FormRequest
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
            'telegram_id' => ['required', 'integer', 'exists:clientes,telegram_id'],
            'profesional_id' => ['required', 'integer', 'exists:profesionales,id'],
            'inicio' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'telegram_id.required' => 'Falta el identificador de Telegram.',
            'telegram_id.exists' => 'Primero comparte tu número para registrarte.',
            'profesional_id.required' => 'Elige con quién quieres la cita.',
            'profesional_id.exists' => 'Ese profesional no existe.',
            'inicio.required' => 'Elige el turno.',
            'inicio.date' => 'El turno no tiene una fecha válida.',
        ];
    }

    /**
     * Hora de inicio en la zona horaria de la barbería, aunque llegue con otro desfase.
     */
    public function inicio(): Carbon
    {
        return Carbon::parse($this->validated('inicio'))->setTimezone(config('app.timezone'));
    }
}
