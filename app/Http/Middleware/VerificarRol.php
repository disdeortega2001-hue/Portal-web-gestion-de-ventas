<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Control de acceso por rol. Uso en rutas: ->middleware('rol:vendedor,administrador')
 * También expulsa a usuarios desactivados aunque tengan la sesión abierta.
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->route('login');
        }

        if ($usuario->estado !== 'activo') {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['correo' => 'Tu cuenta está desactivada. Comunícate con la empresa.']);
        }

        if (! $usuario->tieneRol(...$roles)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
