<?php

namespace App\Http\Requests\Api;

use App\Models\Cita;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class TurnosRequest extends FormRequest
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
        $limite = Carbon::today()->addDays(Cita::ANTICIPACION_MAXIMA_DIAS)->toDateString();

        return [
            'profesional' => ['required', 'integer'],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.$limite],
        ];
    }

    public function messages(): array
    {
        return [
            'profesional.required' => 'Indica el profesional.',
            'profesional.integer' => 'El profesional no es válido.',
            'fecha.required' => 'Indica la fecha.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'fecha.after_or_equal' => 'No puedes consultar turnos de días pasados.',
            'fecha.before_or_equal' => 'Solo puedes consultar hasta '.Cita::ANTICIPACION_MAXIMA_DIAS.' días adelante.',
        ];
    }

    public function fecha(): Carbon
    {
        return Carbon::parse($this->validated('fecha'))->startOfDay();
    }
}
