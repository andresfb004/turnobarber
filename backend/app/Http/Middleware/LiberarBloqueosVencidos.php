<?php

namespace App\Http\Middleware;

use App\Repositories\ClienteRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RN4: en cada petición autenticada del panel o la API levanta los bloqueos que ya
 * cumplieron 14 días. Así no hace falta un proceso programado en el servidor.
 */
class LiberarBloqueosVencidos
{
    public function __construct(private ClienteRepository $clientes) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->clientes->liberarBloqueosVencidos();

        return $next($request);
    }
}
