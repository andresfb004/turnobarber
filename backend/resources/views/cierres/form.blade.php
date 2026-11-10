@extends('layouts.app')

@section('titulo', $cierre->exists ? 'Editar cierre' : 'Nuevo cierre')

@section('contenido')
    @php
        $todoElDia = (bool) old('todo_el_dia', $cierre->exists ? $cierre->esTodoElDia() : true);
        $afectados = old('profesionales', $cierre->exists ? $cierre->profesionales->pluck('id')->all() : $profesionales->pluck('id')->all());
    @endphp

    <a href="{{ route('cierres.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Cierres</a>
    <h1 class="h3 mt-1 mb-3">{{ $cierre->exists ? 'Editar cierre' : 'Nuevo cierre' }}</h1>

    <div class="card" style="max-width: 640px">
        <div class="card-body">
            <form method="POST" action="{{ $cierre->exists ? route('cierres.update', $cierre) : route('cierres.store') }}">
                @csrf
                @if ($cierre->exists) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label" for="fecha">Fecha</label>
                    <input type="date" class="form-control @error('fecha') is-invalid @enderror" id="fecha" name="fecha" value="{{ old('fecha', $cierre->fecha?->toDateString()) }}" required>
                    @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="todo_el_dia" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="todo_el_dia" name="todo_el_dia" value="1" @checked($todoElDia)
                           onchange="document.getElementById('horas').classList.toggle('d-none', this.checked)">
                    <label class="form-check-label" for="todo_el_dia">Todo el día</label>
                </div>

                <div id="horas" class="row g-2 mb-3 @if($todoElDia) d-none @endif">
                    <div class="col-6">
                        <label class="form-label" for="hora_inicio">Desde</label>
                        <input type="time" class="form-control @error('hora_inicio') is-invalid @enderror" id="hora_inicio" name="hora_inicio" value="{{ old('hora_inicio', $cierre->hora_inicio ? substr($cierre->hora_inicio, 0, 5) : '14:00') }}">
                        @error('hora_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="hora_fin">Hasta</label>
                        <input type="time" class="form-control @error('hora_fin') is-invalid @enderror" id="hora_fin" name="hora_fin" value="{{ old('hora_fin', $cierre->hora_fin ? substr($cierre->hora_fin, 0, 5) : '18:00') }}">
                        @error('hora_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="motivo">Motivo</label>
                    <input class="form-control @error('motivo') is-invalid @enderror" id="motivo" name="motivo" value="{{ old('motivo', $cierre->motivo) }}" maxlength="120" required placeholder="Festivo, vacaciones, diligencia…">
                    @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <fieldset class="mb-4">
                    <legend class="form-label fs-6">Afecta a</legend>
                    @foreach ($profesionales as $p)
                        <div class="form-check">
                            <input class="form-check-input @error('profesionales') is-invalid @enderror" type="checkbox" id="prof{{ $p->id }}" name="profesionales[]" value="{{ $p->id }}" @checked(in_array($p->id, array_map('intval', $afectados), true))>
                            <label class="form-check-label" for="prof{{ $p->id }}">{{ $p->nombre }} <span class="text-secondary">({{ $p->especialidad === 'barberia' ? 'Barbería' : 'Belleza' }})</span></label>
                        </div>
                    @endforeach
                    @error('profesionales')<div class="text-danger small">{{ $message }}</div>@enderror
                </fieldset>

                <div class="d-flex gap-2">
                    <button class="btn btn-tb">Guardar</button>
                    <a href="{{ route('cierres.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
