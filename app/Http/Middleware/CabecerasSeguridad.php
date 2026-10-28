<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad HTTP en todas las respuestas del portal.
 * Registrar en app/Http/Kernel.php, grupo "web" (ver README, sección 1).
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        // Un nonce por petición: solo se ejecutan los scripts del portal que lo llevan (Vite lo agrega a los suyos)
        Vite::useCspNonce();

        $respuesta = $next($request);

        // Política de contenido: todo se sirve desde el propio portal. Alpine.js necesita 'unsafe-eval'
        // para sus expresiones; los formularios de contacto pueden abrir WhatsApp.
        $nonce = Vite::cspNonce();
        $respuesta->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "form-action 'self' https://wa.me https://api.whatsapp.com",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]));
        // No se anuncia la versión de PHP
        $respuesta->headers->remove('X-Powered-By');
        header_remove('X-Powered-By');

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
