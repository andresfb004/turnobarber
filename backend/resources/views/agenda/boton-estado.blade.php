<form method="POST" action="{{ route('citas.estado', $cita) }}">
    @csrf
    @method('PATCH')
    <input type="hidden" name="estado" value="{{ $estado }}">
    <button class="dropdown-item" @if($estado !== 'atendida') onclick="return confirm('¿Seguro? {{ $estado === 'no_asistio' ? 'Se sumará una inasistencia al cliente.' : 'El turno quedará libre.' }}')" @endif>
        <i class="bi bi-{{ $icono }}"></i> {{ $texto }}
    </button>
</form>
