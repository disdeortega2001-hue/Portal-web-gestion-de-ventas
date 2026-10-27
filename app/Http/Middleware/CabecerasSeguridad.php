<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad HTTP en todas las respuestas del portal.
 * Registrar en app/Http/Kernel.php, grupo "web" (ver README, sección 1).
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        // El navegador no adivina tipos de archivo (evita ejecutar como script algo subido como imagen)
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        // Nadie puede incrustar el portal en un iframe para engañar clics (clickjacking)
        $respuesta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // A otros sitios (CDN, WhatsApp) solo viaja el dominio, nunca la ruta: los enlaces
        // firmados de cotizaciones no se filtran en la cabecera Referer
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $respuesta;
    }
}
