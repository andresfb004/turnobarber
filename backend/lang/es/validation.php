<?php

// Mensajes en español para las reglas que usa el proyecto. Las demás usan el inglés de respaldo.
return [
    'accepted' => 'Debes aceptar :attribute.',
    'after' => ':Attribute debe ser posterior a :date.',
    'after_or_equal' => ':Attribute debe ser igual o posterior a :date.',
    'array' => ':Attribute no es válido.',
    'before_or_equal' => ':Attribute debe ser igual o anterior a :date.',
    'boolean' => ':Attribute debe ser sí o no.',
    'date' => ':Attribute no es una fecha válida.',
    'date_format' => ':Attribute debe tener el formato :format.',
    'email' => ':Attribute debe ser un correo válido.',
    'exists' => ':Attribute no existe.',
    'in' => ':Attribute no es válido.',
    'integer' => ':Attribute debe ser un número entero.',
    'max' => [
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
        'array' => ':Attribute no puede tener más de :max elementos.',
    ],
    'min' => [
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
        'array' => ':Attribute debe tener al menos :min elemento.',
    ],
    'regex' => ':Attribute no tiene un formato válido.',
    'required' => ':Attribute es obligatorio.',
    'string' => ':Attribute debe ser un texto.',
    'unique' => 'Ya existe un registro con ese :attribute.',

    'attributes' => [
        'nombre' => 'nombre',
        'email' => 'correo',
        'password' => 'contraseña',
        'telefono' => 'teléfono',
        'fecha' => 'fecha',
        'hora' => 'turno',
        'motivo' => 'motivo',
        'especialidad' => 'especialidad',
        'rol' => 'rol',
        'estado' => 'estado',
    ],
];
