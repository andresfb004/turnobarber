@extends('layouts.app')

@section('titulo', 'Clientes')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Clientes</h1>
        <a href="{{ route('clientes.create') }}" class="btn btn-tb"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo cliente</a>
    </div>

    @include('partials.buscador', [
        'ruta' => route('clientes.index'),
        'placeholder' => 'Buscar por nombre o teléfono',
        'filtro' => ['nombre' => 'estado', 'opciones' => ['' => 'Todos', 'telegram' => 'Registrados por Telegram', 'con_inasistencias' => 'Con inasistencias', 'bloqueados' => 'Bloqueados']],
    ])

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th class="d-none d-md-table-cell">Citas</th>
                    <th>Inasistencias</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($clientes as $cliente)
                    <tr>
                        <td>
                            <a href="{{ route('clientes.show', $cliente) }}" class="fw-semibold text-decoration-none">{{ $cliente->nombre }}</a>
                            @if ($cliente->telegram_id)
                                <i class="bi bi-telegram text-primary" role="img" aria-label="Usa el bot de Telegram" title="Usa el bot de Telegram"></i>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $cliente->telefono }}</td>
                        <td class="d-none d-md-table-cell">{{ $cliente->citas_count }}</td>
                        <td>
                            {{ $cliente->inasistencias }}
                            @if ($cliente->bloqueado)
                                <span class="badge text-bg-danger" @if ($cliente->bloqueado_hasta) title="Hasta el {{ $cliente->bloqueado_hasta->locale('es')->isoFormat('D [de] MMMM') }}" @endif>Bloqueado</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            @if ($cliente->bloqueado)
                                <form method="POST" action="{{ route('clientes.desbloquear', $cliente) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-success" title="Desbloquear" aria-label="Desbloquear a {{ $cliente->nombre }}"><i class="bi bi-unlock" aria-hidden="true"></i></button>
                                </form>
                            @endif
                            <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-sm btn-outline-secondary" title="Editar" aria-label="Editar a {{ $cliente->nombre }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                            @include('partials.eliminar', [
                                'ruta' => route('clientes.destroy', $cliente),
                                'nombre' => 'Eliminar a '.$cliente->nombre,
                                'pregunta' => $cliente->citas_count > 0
                                    ? 'Este cliente tiene citas registradas y no se puede eliminar.'
                                    : '¿Eliminar a '.$cliente->nombre.'?',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No hay clientes{{ $busqueda ? ' que coincidan con la búsqueda' : '' }}.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $clientes->links() }}</div>
@endsection
