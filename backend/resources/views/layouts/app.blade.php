@extends('layouts.base')

@section('cuerpo')
    @php($admin = auth()->user()->esAdministrador())
    <a href="#contenido" class="saltar">Saltar al contenido</a>
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
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('dashboard')) active @endif" @if(request()->routeIs('dashboard')) aria-current="page" @endif href="{{ route('dashboard') }}"><i class="bi bi-graph-up" aria-hidden="true"></i> Dashboard</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs('agenda')) active @endif" @if(request()->routeIs('agenda')) aria-current="page" @endif href="{{ route('agenda') }}"><i class="bi bi-calendar3" aria-hidden="true"></i> Agenda</a></li>
                    @if ($admin)
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('clientes.*')) active @endif" @if(request()->routeIs('clientes.*')) aria-current="page" @endif href="{{ route('clientes.index') }}"><i class="bi bi-people" aria-hidden="true"></i> Clientes</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('profesionales.*')) active @endif" @if(request()->routeIs('profesionales.*')) aria-current="page" @endif href="{{ route('profesionales.index') }}"><i class="bi bi-person-badge" aria-hidden="true"></i> Profesionales</a></li>
                        <li class="nav-item"><a class="nav-link @if(request()->routeIs('cierres.*')) active @endif" @if(request()->routeIs('cierres.*')) aria-current="page" @endif href="{{ route('cierres.index') }}"><i class="bi bi-calendar-x" aria-hidden="true"></i> Cierres</a></li>
                    @endif
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white-50 small">
                        <i class="bi bi-person-circle" aria-hidden="true"></i> {{ auth()->user()->name }}
                        <span class="badge text-bg-secondary">{{ auth()->user()->role->nombre }}</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Salir</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    <div class="poste" aria-hidden="true"></div>

    <main id="contenido" tabindex="-1" class="container-xl py-4">
        @include('partials.alertas')
        @yield('contenido')
    </main>
@endsection
