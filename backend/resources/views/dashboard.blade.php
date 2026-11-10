@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('contenido')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-0">Dashboard</h1>
            <div class="text-secondary">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }}</div>
        </div>
        <a href="{{ route('agenda') }}" class="btn btn-tb"><i class="bi bi-calendar3"></i> Ver agenda de hoy</a>
    </div>

    <div class="row g-3 mb-4">
        @foreach ($citasHoy as $fila)
            <div class="col-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-secondary small">Citas hoy · {{ $fila['profesional']->nombre }}</div>
                    <div class="metrica">{{ $fila['total'] }}</div>
                </div></div>
            </div>
        @endforeach
        <div class="col-6 col-lg-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Ocupación semana</div>
                <div class="metrica">{{ $ocupacionSemana }}%</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100"><div class="card-body">
                <div class="text-secondary small">Inasistencias del mes</div>
                <div class="metrica text-danger">{{ $inasistenciasMes }}</div>
            </div></div>
        </div>
        <div class="col-12 col-lg-2">
            <a href="{{ route('clientes.index', ['estado' => 'bloqueados']) }}" class="card h-100 text-decoration-none text-body"><div class="card-body">
                <div class="text-secondary small">Clientes bloqueados</div>
                <div class="metrica">{{ $clientesBloqueados }}</div>
            </div></a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-0">Citas atendidas por día</h2>
                    <p class="text-secondary small">Últimos 30 días, por profesional</p>
                    <div style="height: 300px"><canvas id="graficoAtendidas" aria-label="Citas atendidas por día" role="img"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Próximas citas</h2>
                    <ul class="list-group list-group-flush">
                        @forelse ($proximas as $cita)
                            <li class="list-group-item px-0">
                                <div class="fw-semibold">{{ $cita->cliente->nombre }}</div>
                                <div class="small text-secondary">
                                    {{ $cita->inicio->locale('es')->isoFormat('ddd D MMM · h:mm a') }} · {{ $cita->profesional->nombre }}
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-secondary">No hay citas pendientes.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const grafico = @json($grafico);
        const colores = { barberia: '#1f2433', belleza: '#c8963e' };

        new Chart(document.getElementById('graficoAtendidas'), {
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
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                    y: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Citas atendidas' } },
                },
            },
        });
    </script>
@endpush
