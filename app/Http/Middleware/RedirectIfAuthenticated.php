<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware "guest" (ingresar, registro, recuperar contraseña).
 *
 * El de Laravel envía al usuario ya conectado a RouteServiceProvider::HOME ("/home"),
 * ruta que este portal no tiene: abrir "Ingresar" con la sesión abierta daba error 404.
 * Aquí se envía a la página de inicio de su perfil.
 */
class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        foreach (empty($guards) ? [null] : $guards as $guard) {
            $usuario = Auth::guard($guard)->user();

            if ($usuario) {
                return redirect()->route($usuario->esPersonal() ? 'gestion.tablero' : 'catalogo.index');
            }
        }

        return $next($request);
    }
}
