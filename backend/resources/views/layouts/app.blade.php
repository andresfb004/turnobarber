@extends('layouts.base')

@section('cuerpo')
    @php($admin = auth()->user()->esAdministrador())
    <nav class="navbar navbar-expand-lg navbar-dark navbar-tb">
        <div class="container-xl">
            <a class="navbar-brand" href="{{ route('inicio') }}">
                <span class="firma">{{ config('marca.nombre') }}</span>
                <small>{{ config('marca.subtitulo') }}</small>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                    aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav me-auto">
                    @if ($admin)
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('dashboard')) active @endif" href="{{ route('dashboard') }}"><i class="bi bi-graph-up"></i> Dashboard</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('agenda')) active @endif" href="{{ route('agenda') }}"><i class="bi bi-calendar3"></i> Agenda</a></li>
                    @if ($admin)
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('clientes.*')) active @endif" href="{{ route('clientes.index') }}"><i class="bi bi-people"></i> Clientes</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('profesionales.*')) active @endif" href="{{ route('profesionales.index') }}"><i class="bi bi-person-badge"></i> Profesionales</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('cierres.*')) active @endif" href="{{ route('cierres.index') }}"><i class="bi bi-calendar-x"></i> Cierres</a></li>
                    @endif
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white-50 small">
                        <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                        <span class="badge text-bg-secondary">{{ auth()->user()->role->nombre }}</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> Salir</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    <div class="poste" aria-hidden="true"></div>

    <main class="container-xl py-4">
        @include('partials.alertas')
        @yield('contenido')
    </main>
@endsection
