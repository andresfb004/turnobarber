@extends('layouts.app')

@section('titulo', $profesional->exists ? 'Editar profesional' : 'Nuevo profesional')

@section('contenido')
    @php
        $horarios = $profesional->horarios?->keyBy('dia_semana') ?? collect();
    @endphp

    <a href="{{ route('profesionales.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left" aria-hidden="true"></i> Profesionales</a>
    <h1 class="h3 mt-1 mb-3">{{ $profesional->exists ? 'Editar '.$profesional->nombre : 'Nuevo profesional' }}</h1>

    <form method="POST" action="{{ $profesional->exists ? route('profesionales.update', $profesional) : route('profesionales.store') }}">
        @csrf
        @if ($profesional->exists) @method('PUT') @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-body">
                    <h2 class="h6 mb-3">Datos</h2>
                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control @error('nombre') is-invalid @enderror" @error('nombre') aria-invalid="true" aria-describedby="nombre-error" @enderror id="nombre" name="nombre" value="{{ old('nombre', $profesional->nombre) }}" required maxlength="100">
                        @error('nombre')<div class="invalid-feedback" id="nombre-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="especialidad">Especialidad</label>
                        <select class="form-select @error('especialidad') is-invalid @enderror" @error('especialidad') aria-invalid="true" aria-describedby="especialidad-error" @enderror id="especialidad" name="especialidad">
                            <option value="barberia" @selected(old('especialidad', $profesional->especialidad) === 'barberia')>Barbería</option>
                            <option value="belleza" @selected(old('especialidad', $profesional->especialidad) === 'belleza')>Belleza</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input type="hidden" name="activo" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" @checked(old('activo', $profesional->activo))>
                        <label class="form-check-label" for="activo">Activo (recibe citas por el bot)</label>
                    </div>

                    <h2 class="h6 mb-3">Cuenta del panel</h2>
                    <div class="mb-3">
                        <label class="form-label" for="email">Correo</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror id="email" name="email" value="{{ old('email', $profesional->user?->email) }}" required>
                        @error('email')<div class="invalid-feedback" id="email-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror id="password" name="password" autocomplete="new-password" @required(! $profesional->exists) minlength="8">
                        <div class="form-text">{{ $profesional->exists ? 'Déjala vacía para no cambiarla.' : 'Mínimo 8 caracteres.' }}</div>
                        @error('password')<div class="invalid-feedback" id="password-error">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="form-label" for="rol">Rol</label>
                        <select class="form-select" id="rol" name="rol">
                            @foreach ($roles as $rol)
                                <option value="{{ $rol }}" @selected(old('rol', $profesional->user?->role?->nombre ?? 'Profesional') === $rol)>{{ $rol }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Administrador: todo el panel. Profesional: solo su agenda.</div>
                    </div>
                </div></div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100"><div class="card-body">
                    <h2 class="h6 mb-1">Horario</h2>
                    <p class="small text-secondary">Turnos de 1 hora desde la apertura; el último empieza 1 hora antes del cierre. En el descanso no se dan turnos. El domingo no se atiende.</p>
                    @foreach (\App\Models\Horario::DIAS as $dia => $nombre)
                        @php($h = $horarios->get($dia))
                        <div class="row g-2 align-items-center mb-2">
                            <div class="col-4">
                                <div class="form-check">
                                    <input type="hidden" name="horarios[{{ $dia }}][atiende]" value="0">
                                    <input class="form-check-input" type="checkbox" id="dia{{ $dia }}" name="horarios[{{ $dia }}][atiende]" value="1"
                                        @checked(old("horarios.$dia.atiende", $profesional->exists ? (bool) $h : true))>
                                    <label class="form-check-label" for="dia{{ $dia }}">{{ $nombre }}</label>
                                </div>
                            </div>
                            <div class="col-4">
                                <input type="time" step="3600" class="form-control form-control-sm" name="horarios[{{ $dia }}][apertura]" aria-label="Apertura del {{ $nombre }}"
                                       value="{{ old("horarios.$dia.apertura", $h ? substr($h->hora_apertura, 0, 5) : '07:00') }}">
                            </div>
                            <div class="col-4">
                                <input type="time" step="3600" class="form-control form-control-sm" name="horarios[{{ $dia }}][cierre]" aria-label="Cierre del {{ $nombre }}"
                                       value="{{ old("horarios.$dia.cierre", $h ? substr($h->hora_cierre, 0, 5) : '19:00') }}">
                            </div>
                            <div class="col-4 small text-secondary text-end">Descanso</div>
                            <div class="col-4">
                                <input type="time" step="3600" class="form-control form-control-sm" name="horarios[{{ $dia }}][descanso_inicio]" aria-label="Inicio del descanso del {{ $nombre }}" title="Inicio del descanso"
                                       value="{{ old("horarios.$dia.descanso_inicio", $h ? substr((string) $h->descanso_inicio, 0, 5) : '12:00') }}">
                            </div>
                            <div class="col-4">
                                <input type="time" step="3600" class="form-control form-control-sm" name="horarios[{{ $dia }}][descanso_fin]" aria-label="Fin del descanso del {{ $nombre }}" title="Fin del descanso"
                                       value="{{ old("horarios.$dia.descanso_fin", $h ? substr((string) $h->descanso_fin, 0, 5) : '14:00') }}">
                            </div>
                            @error("horarios.$dia")<div class="col-12 text-danger small">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div></div>
            </div>
        </div>

        <div class="mt-3 d-flex gap-2">
            <button class="btn btn-tb">Guardar</button>
            <a href="{{ route('profesionales.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
@endsection
