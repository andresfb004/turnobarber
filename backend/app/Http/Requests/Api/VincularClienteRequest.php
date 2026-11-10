<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VincularClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Telegram entrega el número como "+573001234567" o "573001234567":
     * se deja solo el celular de 10 dígitos.
     */
    protected function prepareForValidation(): void
    {
        $telefono = preg_replace('/\D/', '', (string) $this->input('telefono'));

        if (strlen($telefono) === 12 && str_starts_with($telefono, '57')) {
            $telefono = substr($telefono, 2);
        }

        $this->merge([
            'telefono' => $telefono,
            'nombre' => trim((string) $this->input('nombre')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'telegram_id' => ['required', 'integer', 'min:1'],
            'telefono' => ['required', 'regex:/^3\d{9}$/'],
            'nombre' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'telegram_id.required' => 'Falta el identificador de Telegram.',
            'telegram_id.integer' => 'El identificador de Telegram no es válido.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.regex' => 'El teléfono debe ser un celular colombiano de 10 dígitos.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede pasar de 100 caracteres.',
        ];
    }
}
