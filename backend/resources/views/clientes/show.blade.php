@extends('layouts.app')

@section('titulo', $cliente->nombre)

@section('contenido')
    <a href="{{ route('clientes.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Clientes</a>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-1 mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $cliente->nombre }}</h1>
            <div class="text-secondary">
                <i class="bi bi-telephone"></i> {{ $cliente->telefono }}
                @if ($cliente->telegram_id)
                    · <i class="bi bi-telegram text-primary"></i> Registrado en el bot
                @else
                    · Aún no usa el bot
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @if ($cliente->bloqueado)
                <form method="POST" action="{{ route('clientes.desbloquear', $cliente) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-success"><i class="bi bi-unlock"></i> Desbloquear</button>
                </form>
            @endif
            <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Editar</a>
        </div>
    </div>

    @if ($cliente->bloqueado)
        <div class="alert alert-danger"><i class="bi bi-lock"></i> Bloqueado por {{ $cliente->inasistencias }} inasistencias: no puede agendar por el bot.</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-4"><div class="card"><div class="card-body"><div class="text-secondary small">Citas</div><div class="metrica">{{ $citas->total() }}</div></div></div></div>
        <div class="col-4"><div class="card"><div class="card-body"><div class="text-secondary small">Inasistencias</div><div class="metrica">{{ $cliente->inasistencias }}</div></div></div></div>
        <div class="col-4"><div class="card"><div class="card-body"><div class="text-secondary small">Cliente desde</div><div class="fw-semibold mt-2">{{ $cliente->created_at->locale('es')->isoFormat('MMM YYYY') }}</div></div></div></div>
    </div>

    <div class="card">
        <div class="card-body pb-0"><h2 class="h6">Historial de citas</h2></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Fecha</th><th>Hora</th><th>Profesional</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse ($citas as $cita)
                    <tr>
                        <td>{{ ucfirst($cita->inicio->locale('es')->isoFormat('ddd D MMM YYYY')) }}</td>
                        <td>{{ $cita->inicio->locale('es')->isoFormat('h:mm a') }}</td>
                        <td>{{ $cita->profesional->nombre }}</td>
                        <td>@include('partials.estado', ['estado' => $cita->estado])</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-secondary py-4">Todavía no tiene citas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $citas->links() }}</div>
@endsection
