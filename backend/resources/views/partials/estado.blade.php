@php
    $textos = [
        'pendiente' => 'Pendiente',
        'atendida' => 'Atendida',
        'no_asistio' => 'No asistió',
        'cancelada' => 'Cancelada',
    ];
@endphp
<span class="sello sello-{{ $estado }}">{{ $textos[$estado] ?? $estado }}</span>
