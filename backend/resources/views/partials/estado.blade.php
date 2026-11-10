@php
    $estilos = [
        'pendiente' => ['primary', 'Pendiente'],
        'atendida' => ['success', 'Atendida'],
        'no_asistio' => ['danger', 'No asistió'],
        'cancelada' => ['secondary', 'Cancelada'],
    ];
    [$color, $texto] = $estilos[$estado] ?? ['light', $estado];
@endphp
<span class="badge text-bg-{{ $color }}">{{ $texto }}</span>
