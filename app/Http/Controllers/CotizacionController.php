<?php

namespace App\Http\Controllers;

use App\Http\Requests\CotizacionRequest;
use App\Models\Cotizacion;
use App\Services\CarritoService;
use App\Services\CotizacionService;
use App\Support\ClienteInvitado;
use App\Support\ClienteRecordado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Solicitud de cotización desde el carrito y su descarga en PDF.
 *
 * Privacidad: un invitado solo puede ver su cotización mediante un enlace
 * FIRMADO (URL::signedRoute); un cliente registrado solo ve las suyas;
 * nadie puede ver cotizaciones ajenas cambiando el número en la URL.
 */
class CotizacionController extends Controller
{
    public function __construct(
        private readonly CarritoService $carrito,
        private readonly CotizacionService $cotizaciones,
    ) {
    }

    public function store(CotizacionRequest $request): RedirectResponse
    {
        $lineas = $this->carrito->lineas();

        if ($lineas->isEmpty()) {
            throw ValidationException::withMessages(['carrito' => 'Tu cotización está vacía. Agrega productos desde el catálogo.']);
        }

        // Revalida stock al momento de cotizar (pudo cambiar desde que se agregó)
        foreach ($lineas as $linea) {
            if ($linea['cantidad'] > $linea['producto']->stock) {
                throw ValidationException::withMessages([
                    'carrito' => "Ya no hay suficiente stock de {$linea['producto']->codigo_fabrica} (quedan {$linea['producto']->stock}). Ajusta la cantidad.",
                ]);
            }
        }

        if ($request->user()?->cliente) {
            [$cliente, $notaContacto] = [$request->user()->cliente, null];
        } else {
            [$cliente, $notaContacto] = ClienteInvitado::resolver($request);
        }

        $observaciones = implode("\n", array_filter([$request->input('observaciones'), $notaContacto])) ?: null;
        $cotizacion = $this->cotizaciones->crear($cliente, $lineas, $observaciones, $this->carrito->moneda());
        $this->carrito->vaciar();

        // Invitado: recordar sus datos en este teléfono solo si lo pidió; si desmarcó la casilla, se borran
        if (! $request->user()?->cliente) {
            $request->boolean('recordar')
                ? ClienteRecordado::guardar($request->only(ClienteRecordado::CAMPOS))
                : ClienteRecordado::olvidar();
        }

        return redirect($this->enlaceSeguro('cotizaciones.show', $cotizacion))
            ->with('exito', "Tu cotización {$cotizacion->codigo} fue enviada. Un vendedor la revisará pronto.");
    }

    /** "Borrar mis datos de este teléfono" (por ejemplo, en un teléfono prestado). */
    public function olvidarDatos(): RedirectResponse
    {
        ClienteRecordado::olvidar();

        return redirect()->route('carrito.index')->with('exito', 'Borramos tus datos de este teléfono.');
    }

    public function show(Request $request, Cotizacion $cotizacion): View
    {
        $this->autorizar($request, $cotizacion);
        $cotizacion->load(['cliente', 'detalles.producto']);

        return view('cotizaciones.show', [
            'cotizacion' => $cotizacion,
            'urlPdf' => $this->enlaceSeguro('cotizaciones.pdf', $cotizacion),
        ]);
    }

    public function pdf(Request $request, Cotizacion $cotizacion): Response
    {
        $this->autorizar($request, $cotizacion);
        $cotizacion->load(['cliente', 'detalles.producto']);

        return Pdf::loadView('cotizaciones.pdf', compact('cotizacion'))
            ->setPaper('letter')
            ->download("{$cotizacion->codigo}.pdf");
    }

    /**
     * Acceso a una cotización: enlace firmado, personal de la empresa, o el cliente dueño.
     * Una cuenta solo es "dueña" de una ficha con historial después de que el personal
     * verificó por teléfono que es la misma persona (ver RegistroController).
     */
    private function autorizar(Request $request, Cotizacion $cotizacion): void
    {
        $usuario = $request->user();
        $esDueno = $usuario && $usuario->cliente?->id === $cotizacion->id_cliente;

        $permitido = $request->hasValidSignature() || $usuario?->esPersonal() || $esDueno;

        abort_unless($permitido, 403, 'No tienes acceso a esta cotización.');
    }

    /** Enlace firmado válido por 30 días (lo puede guardar o compartir por WhatsApp). */
    private function enlaceSeguro(string $ruta, Cotizacion $cotizacion): string
    {
        return URL::temporarySignedRoute($ruta, now()->addDays(30), ['cotizacion' => $cotizacion->id]);
    }
}
