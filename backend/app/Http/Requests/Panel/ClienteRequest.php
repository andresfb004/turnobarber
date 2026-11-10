<?php

namespace App\Http\Requests\Panel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['telefono' => preg_replace('/\D/', '', (string) $this->input('telefono'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'telefono' => ['required', 'regex:/^3\d{9}$/', Rule::unique('clientes', 'telefono')->ignore($this->route('cliente'))],
        ];
    }

    public function messages(): array
    {
        return [
            'telefono.regex' => 'El teléfono debe ser un celular colombiano de 10 dígitos que empiece por 3.',
            'telefono.unique' => 'Ya hay un cliente con ese teléfono.',
        ];
    }
}
