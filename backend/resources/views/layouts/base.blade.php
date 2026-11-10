<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') · {{ config('marca.nombre') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600&family=Barlow:wght@400;500;600&family=Mr+Dafoe&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    @stack('estilos')
</head>
<body>
@yield('cuerpo')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    // Al enviar: el botón muestra que está trabajando y no admite un segundo toque.
    document.addEventListener('submit', (evento) => {
        const formulario = evento.target;
        if (evento.defaultPrevented || formulario.method.toLowerCase() === 'get') return;

        const boton = evento.submitter ?? formulario.querySelector('button:not([type=button])');
        if (!boton) return;

        boton.disabled = true;
        boton.setAttribute('aria-busy', 'true');
        boton.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>');
    });

    // Al volver con el botón "atrás", el formulario queda utilizable.
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[aria-busy="true"]').forEach((boton) => {
            boton.disabled = false;
            boton.removeAttribute('aria-busy');
            boton.querySelector('.spinner-border')?.remove();
        });
    });
</script>
@stack('scripts')
</body>
</html>
