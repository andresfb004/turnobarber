<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo a usuarios con alguno de los roles indicados: `rol:Bot,Administrador`.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $rol = $request->user()?->role?->nombre;

        if (! in_array($rol, $roles, true)) {
            abort(403, 'No tienes permiso para esta acción.');
        }

        return $next($request);
    }
}
