@extends('layouts.app')

@section('titulo', 'Profesionales')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Profesionales</h1>
        <a href="{{ route('profesionales.create') }}" class="btn btn-tb"><i class="bi bi-plus-lg"></i> Nuevo profesional</a>
    </div>

    @include('partials.buscador', ['ruta' => route('profesionales.index'), 'placeholder' => 'Buscar por nombre o correo'])

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Especialidad</th>
                    <th class="d-none d-md-table-cell">Cuenta</th>
                    <th class="d-none d-lg-table-cell">Horario</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($profesionales as $profesional)
                    <tr>
                        <td class="fw-semibold">{{ $profesional->nombre }}</td>
                        <td>{{ $profesional->especialidad === 'barberia' ? 'Barbería' : 'Belleza' }}</td>
                        <td class="d-none d-md-table-cell small">
                            {{ $profesional->user->email }}<br>
                            <span class="badge text-bg-light">{{ $profesional->user->role->nombre }}</span>
                        </td>
                        <td class="d-none d-lg-table-cell small text-secondary">
                            {{ $profesional->horarios->count() }} días
                            @if ($h = $profesional->horarios->first())
                                · {{ substr($h->hora_apertura, 0, 5) }}–{{ substr($h->hora_cierre, 0, 5) }}
                            @endif
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $profesional->activo ? 'success' : 'secondary' }}">{{ $profesional->activo ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('profesionales.edit', $profesional) }}" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @include('partials.eliminar', [
                                'ruta' => route('profesionales.destroy', $profesional),
                                'pregunta' => $profesional->citas_count > 0
                                    ? 'Este profesional tiene citas: no se puede eliminar, desactívalo.'
                                    : '¿Eliminar a '.$profesional->nombre.' y su cuenta?',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-secondary py-4">No hay profesionales{{ $busqueda ? ' que coincidan con la búsqueda' : '' }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $profesionales->links() }}</div>
@endsection
