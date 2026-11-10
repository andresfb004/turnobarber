@extends('layouts.app')

@section('titulo', $cliente->exists ? 'Editar cliente' : 'Nuevo cliente')

@section('contenido')
    <a href="{{ route('clientes.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left" aria-hidden="true"></i> Clientes</a>
    <h1 class="h3 mt-1 mb-3">{{ $cliente->exists ? 'Editar '.$cliente->nombre : 'Nuevo cliente' }}</h1>

    <div class="card" style="max-width: 560px">
        <div class="card-body">
            @unless ($cliente->exists)
                <p class="small text-secondary">Para clientes que llaman o llegan en persona. Si después escriben al bot y comparten este mismo número, quedan vinculados automáticamente.</p>
            @endunless

            <form method="POST" action="{{ $cliente->exists ? route('clientes.update', $cliente) : route('clientes.store') }}">
                @csrf
                @if ($cliente->exists) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input class="form-control @error('nombre') is-invalid @enderror" @error('nombre') aria-invalid="true" aria-describedby="nombre-error" @enderror id="nombre" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required maxlength="100">
                    @error('nombre')<div class="invalid-feedback" id="nombre-error">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4">
                    <label class="form-label" for="telefono">Celular</label>
                    <input type="tel" class="form-control @error('telefono') is-invalid @enderror" @error('telefono') aria-invalid="true" aria-describedby="telefono-error" @enderror id="telefono" name="telefono" value="{{ old('telefono', $cliente->telefono) }}" required placeholder="3001234567" inputmode="numeric">
                    @error('telefono')<div class="invalid-feedback" id="telefono-error">{{ $message }}</div>@enderror
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-tb">Guardar</button>
                    <a href="{{ $cliente->exists ? route('clientes.show', $cliente) : route('clientes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
