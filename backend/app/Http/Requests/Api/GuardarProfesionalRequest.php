<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Panel\ProfesionalRequest;

/**
 * Crear o editar un profesional desde la API: mismas reglas que el formulario del panel.
 *
 * Los horarios van por día de la semana (1 = lunes … 6 = sábado):
 * {"1": {"atiende": true, "apertura": "08:00", "cierre": "18:00"}, ...}
 */
class GuardarProfesionalRequest extends ProfesionalRequest {}
