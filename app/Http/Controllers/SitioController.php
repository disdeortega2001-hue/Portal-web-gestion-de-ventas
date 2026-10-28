<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Support\Contacto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Archivos para buscadores: el mapa del sitio (sitemap.xml) con las páginas públicas y cada
 * producto visible, y robots.txt, que aparta de Google las zonas privadas y apunta al mapa.
 */
class SitioController extends Controller
{
    public function sitemap(): Response
    {
        $paginas = collect([
            ['url' => route('inicio'), 'prioridad' => '1.0', 'frecuencia' => 'daily'],
            ['url' => route('catalogo.index'), 'prioridad' => '0.9', 'frecuencia' => 'daily'],
            ['url' => route('nosotros'), 'prioridad' => '0.6', 'frecuencia' => 'monthly'],
            ['url' => route('contacto'), 'prioridad' => '0.6', 'frecuencia' => 'monthly'],
            ['url' => route('legal.privacidad'), 'prioridad' => '0.2', 'frecuencia' => 'yearly'],
            ['url' => route('legal.terminos'), 'prioridad' => '0.2', 'frecuencia' => 'yearly'],
        ]);

        $productos = Producto::activos()->orderBy('id')->get(['id', 'updated_at'])->map(fn ($p) => [
            'url' => route('catalogo.show', $p),
            'modificado' => $p->updated_at?->toAtomString(),
            'prioridad' => '0.8',
            'frecuencia' => 'weekly',
        ]);

        return response()
            ->view('sitio.sitemap', ['paginas' => $paginas->concat($productos)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** Formulario de contacto: arma el mensaje y lleva al chat de WhatsApp de la empresa. */
    public function contactoWhatsapp(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'codigo' => ['nullable', 'string', 'max:60'],
            'mensaje' => ['required', 'string', 'max:600'],
        ]);

        $texto = 'Hola, soy ' . trim(strip_tags($datos['nombre'])) . '.'
            . (filled($datos['codigo'] ?? null) ? ' Repuesto: ' . trim(strip_tags($datos['codigo'])) . '.' : '')
            . "\n" . trim(strip_tags($datos['mensaje']));

        $enlace = Contacto::whatsapp($texto);
        abort_unless($enlace, 404);

        return redirect()->away($enlace);
    }

    public function robots(): Response
    {
        $lineas = [
            'User-agent: *',
            'Disallow: /gestion',
            'Disallow: /admin',
            'Disallow: /mi-cuenta',
            'Disallow: /carrito',
            'Disallow: /cotizaciones',
            'Disallow: /ingresar',
            'Disallow: /registro',
            'Disallow: /recuperar-contrasena',
            '',
            'Sitemap: ' . route('sitemap'),
        ];

        return response(implode("\n", $lineas) . "\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
