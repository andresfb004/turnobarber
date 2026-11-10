@extends('layouts.base')

@section('titulo', $titulo)

@section('cuerpo')
    <div class="min-vh-100 d-flex align-items-center justify-content-center px-3 text-center">
        <div style="max-width: 460px">
            <i class="bi bi-scissors fs-1" style="color: var(--tb-dorado)"></i>
            <div class="display-1 fw-bold" style="color: var(--tb-oscuro)">{{ $codigo }}</div>
            <h1 class="h4">{{ $titulo }}</h1>
            <p class="text-secondary">{{ $mensaje }}</p>
            <a href="{{ url('/') }}" class="btn btn-tb mt-2"><i class="bi bi-house"></i> Volver al inicio</a>
        </div>
    </div>
@endsection
