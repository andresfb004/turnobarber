<form method="GET" action="{{ $ruta }}" class="row g-2 mb-3" role="search">
    <div class="col-sm-8 col-lg-6">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}">
        </div>
    </div>
    @isset($filtro)
        <div class="col-sm-4 col-lg-3">
            <select name="{{ $filtro['nombre'] }}" class="form-select" aria-label="Filtrar" onchange="this.form.submit()">
                @foreach ($filtro['opciones'] as $valor => $texto)
                    <option value="{{ $valor }}" @selected(request($filtro['nombre'], $filtro['defecto'] ?? '') === (string) $valor)>{{ $texto }}</option>
                @endforeach
            </select>
        </div>
    @endisset
    <div class="col-auto">
        <button class="btn btn-outline-secondary">Buscar</button>
        @if (request()->hasAny(['q', 'estado', 'periodo']))
            <a href="{{ $ruta }}" class="btn btn-link">Limpiar</a>
        @endif
    </div>
</form>
