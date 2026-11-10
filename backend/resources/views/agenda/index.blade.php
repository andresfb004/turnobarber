@extends('layouts.app')

@section('titulo', 'Agenda')

@section('contenido')
    @php
        $admin = auth()->user()->esAdministrador();
        $consulta = fn (array $cambios) => route('agenda', array_filter(['fecha' => $fecha->toDateString(), 'profesional' => $filtro] + $cambios));
        $ahora = now();
    @endphp

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h1 class="h3 mb-0">Agenda</h1>
            <div class="text-secondary">{{ ucfirst($fecha->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')) }}</div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <div class="btn-group" role="group" aria-label="Cambiar día">
                <a class="btn btn-outline-secondary" href="{{ $consulta(['fecha' => $fecha->copy()->subDay()->toDateString()]) }}" title="Día anterior" aria-label="Día anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                <a class="btn btn-outline-secondary" href="{{ $consulta(['fecha' => today()->toDateString()]) }}">Hoy</a>
                <a class="btn btn-outline-secondary" href="{{ $consulta(['fecha' => $fecha->copy()->addDay()->toDateString()]) }}" title="Día siguiente" aria-label="Día siguiente"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </div>
            <form method="GET" action="{{ route('agenda') }}" class="d-flex gap-2">
                @if ($filtro)
                    <input type="hidden" name="profesional" value="{{ $filtro }}">
                @endif
                <input type="date" name="fecha" value="{{ $fecha->toDateString() }}" class="form-control" aria-label="Elegir fecha">
                <button class="btn btn-outline-secondary">Ir</button>
            </form>
            @if ($admin)
                <button class="btn btn-tb" data-bs-toggle="modal" data-bs-target="#nuevaCita"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva cita</button>
            @endif
        </div>
    </div>

    @if ($admin)
        <ul class="nav nav-pills mb-3">
            <li class="nav-item">
                <a class="nav-link @if($filtro === 'todos') active @endif" @if($filtro === 'todos') aria-current="true" @endif href="{{ $consulta(['profesional' => 'todos']) }}">Todos</a>
            </li>
            @foreach ($profesionales as $p)
                <li class="nav-item">
                    <a class="nav-link @if((string) $filtro === (string) $p->id) active @endif" @if((string) $filtro === (string) $p->id) aria-current="true" @endif href="{{ $consulta(['profesional' => $p->id]) }}">
                        {{ $p->nombre }} ({{ $p->especialidad === 'barberia' ? 'barbería' : 'belleza' }})
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($columnas->isEmpty())
        <div class="alert alert-info">No hay una agenda asociada a tu cuenta.</div>
    @elseif ($fecha->isSunday())
        <div class="card"><div class="card-body text-center text-secondary py-5"><i class="bi bi-moon fs-2" aria-hidden="true"></i><p class="mb-0 mt-2">Los domingos no se atiende.</p></div></div>
    @else
        @foreach ($columnas->filter(fn ($c) => $c['cierres']->isNotEmpty()) as $c)
            @foreach ($c['cierres'] as $cierre)
                <div class="alert alert-warning py-2">
                    <i class="bi bi-calendar-x" aria-hidden="true"></i> <strong>{{ $c['profesional']->nombre }}</strong>: {{ $cierre->motivo }}
                    ({{ $cierre->esTodoElDia() ? 'todo el día' : substr($cierre->hora_inicio, 0, 5).' a '.substr($cierre->hora_fin, 0, 5) }})
                </div>
            @endforeach
        @endforeach

        <div class="cuaderno">
            <div class="table-responsive">
                <table class="table tabla-agenda mb-0">
                    <thead>
                    <tr>
                        <th class="hora">Hora</th>
                        @foreach ($columnas as $c)
                            <th>
                                {{ $c['profesional']->nombre }}
                                <small>{{ $c['citas']->count() }} {{ $c['citas']->count() === 1 ? 'cita' : 'citas' }}</small>
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($horas as $hora)
                        <tr>
                            <th class="hora">{{ \Illuminate\Support\Carbon::parse($hora)->locale('es')->isoFormat('h:mm a') }}</th>
                            @foreach ($columnas as $c)
                                @php($cita = $c['citas']->get($hora))
                                @if ($cita)
                                    <td>
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                            <div>
                                                <div class="fw-semibold">
                                                    @if ($admin)
                                                        <a href="{{ route('clientes.show', $cita->cliente) }}" class="text-decoration-none">{{ $cita->cliente->nombre }}</a>
                                                    @else
                                                        {{ $cita->cliente->nombre }}
                                                    @endif
                                                    @if ($cita->cliente->telegram_id)
                                                        <i class="bi bi-telegram text-primary" role="img" aria-label="Agendó por Telegram" title="Agendó por Telegram"></i>
                                                    @endif
                                                </div>
                                                <div class="small text-secondary"><i class="bi bi-telephone" aria-hidden="true"></i> {{ $cita->cliente->telefono }}</div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                @include('partials.estado', ['estado' => $cita->estado])
                                                @if ($cita->estado === 'pendiente')
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Marcar</button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            @if ($cita->inicio <= $ahora)
                                                                <li>@include('agenda.boton-estado', ['estado' => 'atendida', 'texto' => 'Atendida', 'icono' => 'check-circle text-success'])</li>
                                                                <li>@include('agenda.boton-estado', ['estado' => 'no_asistio', 'texto' => 'No asistió', 'icono' => 'x-circle text-danger'])</li>
                                                            @else
                                                                <li>@include('agenda.boton-estado', ['estado' => 'cancelada', 'texto' => 'Cancelar cita', 'icono' => 'slash-circle text-secondary'])</li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @elseif (in_array($hora, $c['turnos'], true))
                                    <td class="celda-libre small">Libre</td>
                                @else
                                    <td class="celda-cerrada small">—</td>
                                @endif
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ $columnas->count() + 1 }}" class="text-center text-secondary py-5">No hay turnos este día.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($admin)
        <div class="modal fade" id="nuevaCita" tabindex="-1" aria-labelledby="nuevaCitaTitulo" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('citas.store') }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="nuevaCitaTitulo">Nueva cita</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('cliente_id') !== null)
                            <div class="alert alert-danger py-2" role="alert">{{ $errors->first() }}</div>
                        @endif
                        <p class="small text-secondary">Para clientes que llaman o llegan en persona. Se aplican las mismas reglas que en el bot.</p>
                        <div class="mb-3">
                            <label class="form-label" for="cliente_id">Cliente</label>
                            <select class="form-select" id="cliente_id" name="cliente_id" required>
                                <option value="">Elige un cliente…</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nombre }} ({{ $cliente->telefono }})</option>
                                @endforeach
                            </select>
                            <div class="form-text"><a href="{{ route('clientes.create') }}">Registrar un cliente nuevo</a></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="profesional_id">Profesional</label>
                            <select class="form-select" id="profesional_id" name="profesional_id" required>
                                @foreach ($profesionales as $p)
                                    <option value="{{ $p->id }}" @selected(old('profesional_id', $filtro) == $p->id)>{{ $p->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-7">
                                <label class="form-label" for="fecha">Fecha</label>
                                <input type="date" class="form-control" id="fecha" name="fecha" value="{{ old('fecha', $fecha->toDateString()) }}" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label" for="hora">Turno</label>
                                <select class="form-select" id="hora" name="hora" required>
                                    @for ($h = 6; $h <= 21; $h++)
                                        @php($valor = sprintf('%02d:00', $h))
                                        <option value="{{ $valor }}" @selected(old('hora', '08:00') === $valor)>{{ \Illuminate\Support\Carbon::parse($valor)->locale('es')->isoFormat('h:mm a') }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-tb">Agendar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

{{-- Si la cita no se pudo crear, el formulario vuelve a abrirse con lo escrito. --}}
@if (auth()->user()->esAdministrador() && $errors->any() && old('cliente_id') !== null)
    @push('scripts')
        <script>new bootstrap.Modal(document.getElementById('nuevaCita')).show();</script>
    @endpush
@endif
