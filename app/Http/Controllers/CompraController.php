<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Services\CarritoService;
use App\Services\PagoService;
use App\Services\PedidoService;
use App\Support\MetodosPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use LogicException;

/**
 * Compra en el portal. El catálogo y la cotización son libres; para comprar, el cliente entra
 * con su cuenta (o la crea en ese momento: el registro lo devuelve aquí). Elige cómo pagar y
 * cómo recibir; el pedido queda pendiente hasta que el personal verifica el pago.
 */
class CompraController extends Controller
{
    public function __construct(private readonly CarritoService $carrito)
    {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($redireccion = $this->sinCompraPosible($request)) {
            return $redireccion;
        }

        return view('compra.create', [
            // Un resumen por moneda: en dólares lleva IGTF y en bolívares IVA; el método elegido decide cuál aplica
            'resumenes' => ['USD' => $this->carrito->resumen('USD'), 'VES' => $this->carrito->resumen('VES')],
            'cliente' => $request->user()->cliente,
            'metodos' => MetodosPago::disponibles(),
        ]);
    }

    public function store(Request $request, PedidoService $pedidos): RedirectResponse
    {
        if ($redireccion = $this->sinCompraPosible($request)) {
            return $redireccion;
        }

        $datos = $request->validate([
            'metodo_pago' => ['required', Rule::in(array_keys(MetodosPago::disponibles()))],
            'entrega' => ['required', 'in:retiro,envio'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ], [
            'metodo_pago.required' => 'Elige cómo vas a pagar.',
            'metodo_pago.in' => 'Ese método de pago no está disponible.',
            'entrega.required' => 'Elige cómo recibes tu pedido.',
        ]);

        // Revalida la existencia al momento de comprar (pudo cambiar desde que se agregó)
        $lineas = $this->carrito->lineas();
        foreach ($lineas as $linea) {
            if ($linea['cantidad'] > $linea['producto']->stock) {
                throw ValidationException::withMessages([
                    'carrito' => "Ya no hay suficiente existencia de {$linea['producto']->codigo_fabrica} (quedan {$linea['producto']->stock}). Ajusta la cantidad.",
                ]);
            }
        }

        $pedido = $pedidos->crearEnLinea(
            $request->user()->cliente, $lineas, $datos['metodo_pago'], $datos['entrega'], $datos['observaciones'] ?? null
        );
        $this->carrito->vaciar();

        $siguiente = MetodosPago::enLinea($pedido->metodo_pago)
            ? 'Paga y repórtalo aquí mismo con la referencia.'
            : 'Pagas al retirar en la tienda.';

        return redirect()->route('cuenta.pedidos.show', $pedido)->with('exito', "Recibimos tu pedido {$pedido->codigo}. {$siguiente}");
    }

    /** El cliente reporta su pago a distancia con la referencia y el comprobante. */
    public function reportarPago(Request $request, Pedido $pedido, PagoService $pagos): RedirectResponse
    {
        abort_unless($pedido->id_cliente === $request->user()->cliente?->id, 403);

        $datos = $request->validate([
            'referencia' => ['required', 'string', 'min:4', 'max:60'],
            'fecha_pago' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:' . $pedido->fecha->toDateString()],
            'monto' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'referencia.required' => 'Escribe el número de referencia del pago.',
            'referencia.min' => 'La referencia debe tener al menos 4 caracteres.',
            'fecha_pago.required' => 'Indica la fecha del pago.',
            'fecha_pago.before_or_equal' => 'La fecha del pago no puede ser futura.',
            'fecha_pago.after_or_equal' => 'La fecha del pago no puede ser anterior al pedido.',
            'monto.required' => 'Indica el monto que pagaste.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'comprobante.mimes' => 'El comprobante debe ser una foto (JPG, PNG o WEBP) o un PDF.',
            'comprobante.max' => 'El comprobante no puede pesar más de 5 MB.',
        ]);

        try {
            $pagos->reportar($pedido, $datos, $request->file('comprobante'));
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', 'Recibimos tu reporte de pago. Te avisamos cuando lo verifiquemos.');
    }

    /** Carrito vacío o cuenta aún en verificación: no hay compra posible todavía. */
    private function sinCompraPosible(Request $request): ?RedirectResponse
    {
        if ($this->carrito->estaVacio()) {
            return redirect()->route('carrito.index')->with('error', 'Tu lista está vacía. Agrega productos desde el catálogo.');
        }

        if (! $request->user()->cliente) {
            return redirect()->route('carrito.index')->with('error',
                'Tu cuenta está en verificación: un vendedor te llamará para confirmar tus datos. Mientras tanto puedes enviar la cotización.');
        }

        return null;
    }
}
