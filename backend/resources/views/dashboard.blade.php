@extends('layouts.app')

@section('titulo', 'Dashboard')

@push('estilos')
    <style>
        .card-enlace { transition: box-shadow .15s ease; }
        .card-enlace:hover { box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .1); }
        .card-enlace:focus-visible { outline: 3px solid var(--tb-dorado); outline-offset: 2px; }
        .card-enlace .ver { color: #6c757d; font-size: .8rem; }
        .card-enlace:hover .ver { color: var(--tb-oscuro); }
    </style>
@endpush

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Dashboard</h1>
            <div class="text-secondary">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM · h:mm a')) }}</div>
        </div>
        <a href="{{ route('agenda') }}" class="btn btn-tb"><i class="bi bi-calendar3"></i> Ver agenda de hoy</a>
    </div>

    {{-- Lo primero: citas que ya pasaron y nadie marcó. Sin marcarlas, las inasistencias no cuentan (RN4). --}}
    @if ($porMarcar['total'] > 0)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
            <div>
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>{{ $porMarcar['total'] }} {{ $porMarcar['total'] === 1 ? 'cita ya pasó y sigue' : 'citas ya pasaron y siguen' }} sin marcar.</strong>
                Márcalas como atendidas o no asistió para que las inasistencias se cuenten.
            </div>
            <a href="{{ route('agenda', ['fecha' => $porMarcar['desde']->toDateString()]) }}" class="btn btn-sm btn-dark">
                Marcar en la agenda <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    @endif

    <h2 class="visually-hidden">Resumen</h2>
    <div class="row g-3 mb-4">
        @forelse ($hoy as $dia)
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('agenda', ['profesional' => $dia['profesional']->id]) }}" class="card card-enlace h-100 text-decoration-none text-body">
                    <div class="card-body">
                        <div class="text-secondary small">Citas hoy · {{ $dia['profesional']->nombre }}</div>
                        <div class="metrica">{{ $dia['total'] }}</div>
                        <div class="small">
                            {{ $dia['atendidas'] }} {{ $dia['atendidas'] === 1 ? 'atendida' : 'atendidas' }} · {{ $dia['por_venir'] }} por venir
                            @if ($dia['no_asistio'] > 0)
                                · {{ $dia['no_asistio'] }} no asistió
                            @endif
                        </div>
                        @if ($dia['por_marcar'] > 0)
                            <div class="small text-warning-emphasis fw-semibold"><i class="bi bi-exclamation-circle"></i> {{ $dia['por_marcar'] }} por marcar</div>
                        @endif
                        <div class="ver mt-1">Ver su agenda <i class="bi bi-chevron-right"></i></div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-lg-6">
                <div class="card h-100"><div class="card-body text-secondary">
                    <i class="bi bi-person-x"></i> No hay profesionales activos.
                    <a href="{{ route('profesionales.index') }}">Revisar profesionales</a>
                </div></div>
            </div>
        @endforelse

        <div class="col-sm-4 col-lg-2">
            <a href="{{ route('agenda') }}" class="card card-enlace h-100 text-decoration-none text-body">
                <div class="card-body">
                    <div class="text-secondary small">Ocupación de la semana</div>
                    <div class="metrica">{{ $ocupacion['porcentaje'] }}%</div>
                    <div class="small">{{ $ocupacion['ocupados'] }} de {{ $ocupacion['turnos'] }} turnos</div>
                    <div class="ver mt-1">Ver agenda <i class="bi bi-chevron-right"></i></div>
                </div>
            </a>
        </div>
        <div class="col-sm-4 col-lg-2">
            <a href="{{ route('clientes.index', ['estado' => 'con_inasistencias']) }}" class="card card-enlace h-100 text-decoration-none text-body">
                <div class="card-body">
                    <div class="text-secondary small">Inasistencias del mes</div>
                    <div class="metrica @if($inasistencias['total'] > 0) text-danger @endif">
                        @if ($inasistencias['total'] > 0)<i class="bi bi-exclamation-triangle fs-5 align-middle" aria-hidden="true"></i>@endif
                        {{ $inasistencias['total'] }}
                    </div>
                    <div class="small">
                        @if ($inasistencias['de'] > 0)
                            {{ $inasistencias['porcentaje'] }}% de {{ $inasistencias['de'] }} citas
                        @else
                            Sin citas cerradas este mes
                        @endif
                    </div>
                    <div class="ver mt-1">Ver clientes <i class="bi bi-chevron-right"></i></div>
                </div>
            </a>
        </div>
        <div class="col-sm-4 col-lg-2">
            <a href="{{ route('clientes.index', ['estado' => 'bloqueados']) }}" class="card card-enlace h-100 text-decoration-none text-body">
                <div class="card-body">
                    <div class="text-secondary small">Clientes bloqueados</div>
                    <div class="metrica">{{ $clientesBloqueados }}</div>
                    <div class="small">No pueden agendar por el bot</div>
                    <div class="ver mt-1">Revisar <i class="bi bi-chevron-right"></i></div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3">
        {{-- En celular la lista accionable va antes que el gráfico. --}}
        <div class="col-lg-4 order-lg-2">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Próximas citas</h2>
                    <ul class="list-group list-group-flush">
                        @forelse ($proximas as $cita)
                            <li class="list-group-item px-0">
                                <a href="{{ route('agenda', ['fecha' => $cita->inicio->toDateString()]) }}" class="text-decoration-none text-body d-block">
                                    <div class="fw-semibold">{{ $cita->cliente->nombre }}</div>
                                    <div class="small text-secondary">
                                        {{ $cita->inicio->locale('es')->isoFormat('ddd D MMM · h:mm a') }} · {{ $cita->profesional->nombre }}
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-secondary">No hay citas pendientes.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8 order-lg-1">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-0">Citas atendidas por día</h2>
                    <p class="text-secondary small mb-2">Últimos 30 días, de lunes a sábado</p>

                    @if ($grafico['total'] === 0)
                        <div class="text-center text-secondary py-5">
                            <i class="bi bi-bar-chart fs-2"></i>
                            <p class="mb-0 mt-2">Todavía no hay citas atendidas en los últimos 30 días.</p>
                        </div>
                    @else
                        <ul class="list-inline small mb-2" aria-label="Total del periodo">
                            @foreach ($grafico['series'] as $serie)
                                <li class="list-inline-item me-3">
                                    <span class="d-inline-block rounded-1 align-middle me-1" style="width:.75rem;height:.75rem;background:{{ $serie['especialidad'] === 'barberia' ? '#1f2433' : '#c8963e' }}" aria-hidden="true"></span>
                                    {{ $serie['nombre'] }}: <strong>{{ $serie['total'] }}</strong> atendidas
                                </li>
                            @endforeach
                        </ul>

                        <div style="height: 280px" aria-hidden="true"><canvas id="graficoAtendidas"></canvas></div>
                        <p id="graficoSinCargar" class="text-secondary small d-none">
                            <i class="bi bi-wifi-off"></i> No se pudo cargar el gráfico. Los totales de arriba están actualizados.
                        </p>

                        {{-- Las tablas ignoran el ancho de 1 px de .visually-hidden: se oculta el contenedor. --}}
                        <div class="visually-hidden">
                        <table>
                            <caption>Citas atendidas por día en los últimos 30 días, sin domingos</caption>
                            <thead>
                            <tr>
                                <th scope="col">Día</th>
                                @foreach ($grafico['series'] as $serie)
                                    <th scope="col">{{ $serie['nombre'] }}</th>
                                @endforeach
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($grafico['fechas'] as $i => $fecha)
                                <tr>
                                    <th scope="row">{{ $fecha }}</th>
                                    @foreach ($grafico['series'] as $serie)
                                        <td>{{ $serie['datos'][$i] }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@if ($grafico['total'] > 0)
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (() => {
                const lienzo = document.getElementById('graficoAtendidas');

                // Si el CDN no carga, se avisa en lugar de dejar un recuadro vacío.
                if (typeof Chart === 'undefined') {
                    lienzo.parentElement.classList.add('d-none');
                    document.getElementById('graficoSinCargar').classList.remove('d-none');
                    return;
                }

                const grafico = @json($grafico);
                const colores = { barberia: '#1f2433', belleza: '#c8963e' };
                const angosto = window.matchMedia('(max-width: 576px)').matches;

                new Chart(lienzo, {
                    type: 'bar',
                    data: {
                        labels: grafico.etiquetas,
                        datasets: grafico.series.map((s) => ({
                            label: s.nombre,
                            data: s.datos,
                            backgroundColor: colores[s.especialidad] ?? '#6c757d',
                            borderRadius: 3,
                        })),
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { title: (items) => grafico.fechas[items[0].dataIndex] } },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: angosto ? 5 : 10 } },
                            y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: !angosto, text: 'Citas atendidas' } },
                        },
                    },
                });
            })();
        </script>
    @endpush
@endif
