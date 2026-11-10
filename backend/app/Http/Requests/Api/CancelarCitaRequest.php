<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CancelarCitaRequest extends FormRequest
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
            'telegram_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'telegram_id.required' => 'Falta el identificador de Telegram.',
            'telegram_id.integer' => 'El identificador de Telegram no es válido.',
        ];
    }
}
