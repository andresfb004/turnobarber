<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --tb-oscuro: #1f2433; --tb-dorado: #c8963e; }
        body { background: #f4f5f7; }
        .navbar-tb { background: var(--tb-oscuro); }
        .navbar-tb .navbar-brand { color: #fff; font-weight: 700; letter-spacing: .02em; }
        .navbar-tb .navbar-brand i { color: var(--tb-dorado); }
        .navbar-tb .nav-link { color: rgba(255, 255, 255, .75); }
        .navbar-tb .nav-link:hover, .navbar-tb .nav-link.active { color: #fff; }
        .btn-tb { background: var(--tb-dorado); border-color: var(--tb-dorado); color: #fff; }
        .btn-tb:hover { background: #b0802f; border-color: #b0802f; color: #fff; }
        .nav-pills .nav-link { color: var(--tb-oscuro); }
        .nav-pills .nav-link.active { background: var(--tb-oscuro); }
        .card { border: 0; box-shadow: 0 1px 3px rgba(0, 0, 0, .06); }
        .metrica { font-size: 2rem; font-weight: 700; line-height: 1.1; }
        .tabla-agenda td, .tabla-agenda th { vertical-align: middle; }
        .tabla-agenda .hora { width: 7rem; white-space: nowrap; }
        .celda-libre { color: #6c757d; background: #fbfbfc; }
        .celda-cerrada { background: repeating-linear-gradient(45deg, #f1f1f3, #f1f1f3 6px, #e9e9ec 6px, #e9e9ec 12px); color: #999; }
    </style>
    @stack('estilos')
</head>
<body>
@yield('cuerpo')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>
