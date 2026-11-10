@extends('layouts.base')

@section('titulo', 'Iniciar sesión')

@push('estilos')
    <style>
        .entrada { min-height: 100vh; background: var(--do-cuero); }
        .placa { display: flex; width: 100%; max-width: 430px; background: var(--do-hoja); border: 1px solid var(--do-tinta); outline: 1px solid var(--do-regla); outline-offset: 5px; }
        /* El poste, de pie, como en el logo. */
        .placa .poste { flex: 0 0 18px; height: auto; background: repeating-linear-gradient(35deg, var(--do-rojo) 0 14px, var(--do-hoja) 14px 28px, var(--do-azul) 28px 42px, var(--do-hoja) 42px 56px); border-right: 1px solid var(--do-tinta); }
        .placa .firma { font-size: 3.4rem; }
        .placa .rotulo { font-family: "Barlow Condensed", sans-serif; font-size: 1.15rem; font-weight: 500; color: var(--do-suave); }
    </style>
@endpush

@section('cuerpo')
    <main class="entrada d-flex align-items-center justify-content-center p-4">
        <div class="placa">
            <div class="poste" aria-hidden="true"></div>
            <div class="flex-grow-1 p-4 p-sm-5">
                <div class="text-center mb-4">
                    <h1 class="firma mb-1">{{ config('marca.nombre') }}</h1>
                    <div class="rotulo">{{ config('marca.subtitulo') }}</div>
                    <p class="text-secondary small mt-3 mb-0">Panel de la barbería</p>
                </div>

                @include('partials.alertas')

                <form method="POST" action="{{ route('login.intentar') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="form-control" required autofocus autocomplete="username">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="recordar" name="recordar" value="1">
                        <label class="form-check-label" for="recordar">Recordarme</label>
                    </div>
                    <button class="btn btn-tb w-100">Entrar</button>
                </form>
            </div>
        </div>
    </main>
@endsection
