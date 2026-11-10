@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="mb-0">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }}</h1>
            <div class="text-secondary">Resumen de la barbería a las {{ now()->locale('es')->isoFormat('h:mm a') }}</div>
        </div>
        <a href="{{ route('agenda') }}" class="btn btn-tb"><i class="bi bi-calendar3" aria-hidden="true"></i> Ver agenda de hoy</a>
    </div>

    {{-- Lo primero: citas que ya pasaron y nadie marcó. Sin marcarlas, las inasistencias no cuentan (RN4). --}}
    @if ($porMarcar['total'] > 0)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
            <div>
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                <strong>{{ $porMarcar['total'] }} {{ $porMarcar['total'] === 1 ? 'cita ya pasó y sigue' : 'citas ya pasaron y siguen' }} sin marcar.</strong>
                Márcalas como atendidas o no asistió para que las inasistencias se cuenten.
            </div>
            <a href="{{ route('agenda', ['fecha' => $porMarcar['desde']->toDateString()]) }}" class="btn btn-sm btn-tb">
                Marcar en la agenda
            </a>
        </div>
    @endif

    <h2 class="visually-hidden">Resumen</h2>
    <div class="row g-0 libro mb-4">
        @forelse ($hoy as $dia)
            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('agenda', ['profesional' => $dia['profesional']->id]) }}" class="libro-celda">
                    <div class="text-secondary small">Citas de hoy con {{ $dia['profesional']->nombre }}</div>
                    <div class="metrica">{{ $dia['total'] }}</div>
                    <div class="small">
                        {{ collect([
                            $dia['atendidas'].' '.($dia['atendidas'] === 1 ? 'atendida' : 'atendidas'),
                            $dia['por_venir'].' por venir',
                            $dia['no_asistio'] > 0 ? $dia['no_asistio'].' no asistió' : null,
                        ])->filter()->join(', ') }}
                    </div>
                    @if ($dia['por_marcar'] > 0)
                        <div class="small fw-semibold" style="color: var(--do-cobre)"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $dia['por_marcar'] }} por marcar</div>
                    @endif
                    <div class="ver mt-1">Ver su agenda</div>
                </a>
            </div>
        @empty
            <div class="col-lg-6">
                <div class="libro-celda text-secondary">
                    No hay profesionales activos. <a href="{{ route('profesionales.index') }}">Revisar profesionales</a>
                </div>
            </div>
        @endforelse

        <div class="col-sm-4 col-lg-2">
            <a href="{{ route('agenda') }}" class="libro-celda">
                <div class="text-secondary small">Ocupación de la semana</div>
                <div class="metrica">{{ $ocupacion['porcentaje'] }}%</div>
                <div class="small">{{ $ocupacion['ocupados'] }} de {{ $ocupacion['turnos'] }} turnos</div>
                <div class="ver mt-1">Ver agenda</div>
            </a>
        </div>
        <div class="col-sm-4 col-lg-2">
            <a href="{{ route('clientes.index', ['estado' => 'con_inasistencias']) }}" class="libro-celda">
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
                <div class="ver mt-1">Ver clientes</div>
            </a>
        </div>
        <div class="col-sm-4 col-lg-2">
            <a href="{{ route('clientes.index', ['estado' => 'bloqueados']) }}" class="libro-celda">
                <div class="text-secondary small">Clientes bloqueados</div>
                <div class="metrica">{{ $clientesBloqueados }}</div>
                <div class="small">No pueden agendar por el bot</div>
                <div class="ver mt-1">Revisar</div>
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
                                        {{ ucfirst($cita->inicio->locale('es')->isoFormat('ddd D MMM, h:mm a')) }}, con {{ $cita->profesional->nombre }}
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
                            <i class="bi bi-bar-chart fs-2" aria-hidden="true"></i>
                            <p class="mb-0 mt-2">Todavía no hay citas atendidas en los últimos 30 días.</p>
                        </div>
                    @else
                        <ul class="list-inline small mb-2" aria-label="Total del periodo">
                            @foreach ($grafico['series'] as $serie)
                                <li class="list-inline-item me-3">
                                    <span class="d-inline-block rounded-1 align-middle me-1" style="width:.75rem;height:.75rem;background:{{ $serie['especialidad'] === 'barberia' ? '#3a2a1f' : '#c0813f' }}" aria-hidden="true"></span>
                                    {{ $serie['nombre'] }}: <strong>{{ $serie['total'] }}</strong> atendidas
                                </li>
                            @endforeach
                        </ul>

                        <div style="height: 280px" aria-hidden="true"><canvas id="graficoAtendidas"></canvas></div>
                        <p id="graficoSinCargar" class="text-secondary small d-none">
                            <i class="bi bi-wifi-off" aria-hidden="true"></i> No se pudo cargar el gráfico. Los totales de arriba están actualizados.
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
                const colores = { barberia: '#3a2a1f', belleza: '#c0813f' };
                Chart.defaults.font.family = 'Barlow, sans-serif';
                Chart.defaults.color = '#5a4835';
                const angosto = window.matchMedia('(max-width: 576px)').matches;

                new Chart(lienzo, {
                    type: 'bar',
                    data: {
                        labels: grafico.etiquetas,
                        datasets: grafico.series.map((s) => ({
                            label: s.nombre,
                            data: s.datos,
                            backgroundColor: colores[s.especialidad] ?? '#5a4835',
                            borderRadius: 1,
                        })),
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { title: (items) => grafico.fechas[items[0].dataIndex] } },
                        },
                        scales: {
                            x: { grid: { display: false }, border: { color: '#b9a27c' }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: angosto ? 5 : 10 } },
                            y: { beginAtZero: true, grid: { color: '#b4c2d6' }, border: { display: false }, ticks: { precision: 0 }, title: { display: !angosto, text: 'Citas atendidas' } },
                        },
                    },
                });
            })();
        </script>
    @endpush
@endif
