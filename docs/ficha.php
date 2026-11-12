<?php

$ficha = [
    'nombre' => 'TurnoBarber: agenda de turnos con bot de Telegram para David Orozco Barbería y Peluquería',

    'integrantes' => [
        'Juan Andrés Forero Becerra',
    ],

    'usuario_real' => 'David Orozco, dueño y barbero de David Orozco Barbería y Peluquería (Calle 41 # 29-17, '
        . 'Bucaramanga, Santander), y la estilista, su esposa, que atiende los servicios de belleza y a los niños. '
        . 'También sus clientes, que hoy piden turno en persona.',

    'problema' => 'Los turnos se anotan a mano en un cuaderno que lleva la estilista; atienden unos 5 clientes al día '
        . 'en turnos de 1 hora. El cliente solo puede pedir turno hablando con quien tenga el cuaderno y no ve qué horas '
        . 'quedan libres. Nada impide anotar dos clientes a la misma hora o en el almuerzo, y nadie lleva la cuenta de '
        . 'las inasistencias. Necesitan que el cliente agende solo, sin cruces, y ver la agenda y los números en un panel.',

    'evidencia' => 'Entrevista en video con David y la estilista, hecha el 7 de noviembre de 2026 en la barbería: '
        . 'https://drive.google.com/file/d/1uRuFmaUyMb_N9rHENk5m-JP3yCst2c9q/view?usp=drivesdk . Confirmaron el cuaderno de papel, unos 5 clientes al día, '
        . 'turnos de 1 hora, horario de 7:00 a 12:00 y de 2:00 a 7:00 p. m. de lunes a sábado, bloqueo tras 2 faltas '
        . 'por unas 2 semanas, 25 minutos de tolerancia, pago en efectivo o transferencia y corte de caballero a $45.000 con barba.',

    'reglas' => [
        'RN1 Disponibilidad: solo se ofrecen turnos de 1 hora en punto dentro del horario del profesional, fuera del '
            . 'descanso de 12:00 a 14:00 y de los cierres registrados.',
        'RN2 Sin cruces: al crear o reprogramar, la hora se valida otra vez dentro de una transacción con bloqueo; '
            . 'si otro cliente la tomó, la API responde 422.',
        'RN3 Anticipación: se agenda con al menos 1 hora y hasta 14 días adelante; cancelar con menos de 2 horas '
            . 'cuenta como inasistencia.',
        'RN4 Bloqueo: con 2 inasistencias el cliente queda bloqueado 14 días y no puede agendar (422); al vencer '
            . 'el plazo se desbloquea solo, o antes si el administrador lo desbloquea.',
        'RN5 Cierres: el administrador registra cierres de un día o de unas horas, para uno o ambos profesionales, '
            . 'y esas horas dejan de ofrecerse.',
    ],

    'entidades' => [
        'roles',               // 1:N users
        'users',               // 1:1 profesionales
        'profesionales',       // 1:N horarios, 1:N citas
        'horarios',            // jornada y descanso por día
        'cierres',             // N:M profesionales
        'cierre_profesional',  // pivote N:M cierres–profesionales
        'clientes',            // 1:N citas
        'citas',
    ],

    'frontend' => 'Panel en Laravel con Blade, Bootstrap 5 y Chart.js, responsive, con páginas 404 y 500 propias. '
        . 'Dos roles: Administrador (David: dashboard con métricas y gráfico de citas atendidas por día; CRUD de '
        . 'profesionales con horario y descanso, cierres y clientes con búsqueda y paginación; desbloqueo de clientes) '
        . 'y Profesional (la estilista: ve su agenda y marca cada cita como atendida o como inasistencia). Las citas y '
        . 'los clientes que llegan desde el bot aparecen en el panel.',

    'cliente_externo' => 'Bot de Telegram en Node.js con Telegraf (carpeta /cliente, fuera de Laravel). Inicia sesión '
        . 'en la API pública y usa el token Sanctum. Registra al cliente con su número, consulta turnos libres '
        . '(GET /api/turnos), agenda, consulta y cancela citas (POST /api/citas, '
        . 'GET /api/clientes/{telegramId}/citas, PATCH /api/citas/{id}/cancelar) y cada 10 minutos envía recordatorios. Entiende texto libre con IA local '
        . '(Ollama) y, si falla, sigue con botones. Si la API no responde avisa sin caerse, ante un 401 vuelve a '
        . 'iniciar sesión y muestra al cliente el mensaje de los 422.',

    'repositorio' => 'github.com/andresfb004/turnobarber',
];
