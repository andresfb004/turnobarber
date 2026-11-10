@extends('layouts.base')

@section('titulo', 'Iniciar sesión')

@section('cuerpo')
    <div class="min-vh-100 d-flex align-items-center justify-content-center px-3" style="background: var(--tb-oscuro)">
        <div class="card w-100" style="max-width: 400px">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <i class="bi bi-scissors fs-1" style="color: var(--tb-dorado)"></i>
                    <h1 class="h4 mt-2 mb-0">{{ config('app.name') }}</h1>
                    <p class="text-secondary small">Panel de la barbería</p>
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
    </div>
@endsection
