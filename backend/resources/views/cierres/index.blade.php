@extends('layouts.app')

@section('titulo', 'Cierres')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
        <h1 class="h3 mb-0">Cierres</h1>
        <a href="{{ route('cierres.create') }}" class="btn btn-tb"><i class="bi bi-plus-lg"></i> Nuevo cierre</a>
    </div>
    <p class="text-secondary">Festivos, vacaciones o diligencias: el bot no ofrece turnos en esos días u horas.</p>

    @include('partials.buscador', [
        'ruta' => route('cierres.index'),
        'placeholder' => 'Buscar por motivo',
        'filtro' => ['nombre' => 'periodo', 'defecto' => 'proximos', 'opciones' => ['proximos' => 'Próximos', 'pasados' => 'Pasados', 'todos' => 'Todos']],
    ])

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Horas</th>
                    <th>Motivo</th>
                    <th>Afecta a</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($cierres as $cierre)
                    <tr>
                        <td class="text-nowrap">{{ ucfirst($cierre->fecha->locale('es')->isoFormat('ddd D MMM YYYY')) }}</td>
                        <td class="text-nowrap">{{ $cierre->esTodoElDia() ? 'Todo el día' : substr($cierre->hora_inicio, 0, 5).'–'.substr($cierre->hora_fin, 0, 5) }}</td>
                        <td>{{ $cierre->motivo }}</td>
                        <td>
                            @foreach ($cierre->profesionales as $p)
                                <span class="badge text-bg-light">{{ $p->nombre }}</span>
                            @endforeach
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('cierres.edit', $cierre) }}" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @include('partials.eliminar', ['ruta' => route('cierres.destroy', $cierre), 'pregunta' => '¿Eliminar este cierre? Esos turnos volverán a estar disponibles.'])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-4">No hay cierres en este periodo.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $cierres->links() }}</div>
@endsection
