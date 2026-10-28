<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompraRequest;
use App\Models\Pedido;
use App\Services\CarritoService;
use App\Services\PagoService;
use App\Services\PedidoService;
use App\Support\ClienteInvitado;
use App\Support\ClienteRecordado;
use App\Support\MetodosPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use LogicException;

/**
 * Compra en el portal. Crear cuenta es opcional: quien no la tiene escribe sus datos básicos
 * (cédula o RIF, nombre, teléfono y dirección) y sigue su pedido por un enlace firmado que
 * nadie más puede adivinar. Quien tiene cuenta compra con sus datos guardados y ve el pedido
 * en «Mis pedidos». El pedido queda pendiente hasta que el personal verifica el pago.
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
            'cliente' => $request->user()?->cliente,
            'recordado' => $request->user() ? [] : ClienteRecordado::leer(),
            'metodos' => MetodosPago::disponibles(),
        ]);
    }

    public function store(CompraRequest $request, PedidoService $pedidos): RedirectResponse
    {
        if ($redireccion = $this->sinCompraPosible($request)) {
            return $redireccion;
        }

        $datos = $request->validated();

        // Revalida la existencia al momento de comprar (pudo cambiar desde que se agregó)
        $lineas = $this->carrito->lineas();
        foreach ($lineas as $linea) {
            if ($linea['cantidad'] > $linea['producto']->stock) {
                throw ValidationException::withMessages([
                    'carrito' => "Ya no hay suficiente existencia de {$linea['producto']->codigo_fabrica} (quedan {$linea['producto']->stock}). Ajusta la cantidad.",
                ]);
            }
        }

        $conCuenta = (bool) $request->user()?->cliente;
        [$cliente, $notaContacto] = $conCuenta ? [$request->user()->cliente, null] : ClienteInvitado::resolver($request);
        $observaciones = implode("\n", array_filter([$datos['observaciones'] ?? null, $notaContacto])) ?: null;

        $pedido = $pedidos->crearEnLinea($cliente, $lineas, $datos['metodo_pago'], $datos['entrega'], $observaciones);
        $this->carrito->vaciar();

        if (! $conCuenta) {
            $request->boolean('recordar')
                ? ClienteRecordado::guardar($request->only(ClienteRecordado::CAMPOS))
                : ClienteRecordado::olvidar();
        }

        $siguiente = MetodosPago::enLinea($pedido->metodo_pago)
            ? 'Paga y repórtalo aquí mismo con la referencia.'
            : 'Pagas al retirar en la tienda.';

        $destino = $conCuenta ? route('cuenta.pedidos.show', $pedido) : self::enlaceSeguimiento($pedido);

        return redirect($destino)->with('exito', "Recibimos tu pedido {$pedido->codigo}. {$siguiente}");
    }

    /** Pedido hecho sin cuenta: se consulta con el enlace firmado que recibió al comprar. */
    public function seguimiento(Pedido $pedido): View
    {
        return CuentaController::vistaPedido($pedido, sinCuenta: true);
    }

    /**
     * Enlace firmado del pedido sin cuenta, válido 90 días: lo guarda o lo comparte por WhatsApp.
     * Los formularios de pago y de cancelación usan enlaces firmados del mismo pedido.
     */
    public static function enlaceSeguimiento(Pedido $pedido, string $ruta = 'pedidos.seguimiento'): string
    {
        return URL::temporarySignedRoute($ruta, $pedido->fecha->copy()->addDays(90), ['pedido' => $pedido->id]);
    }

    /** El cliente reporta su pago a distancia con la referencia y el comprobante. */
    public function reportarPago(Request $request, Pedido $pedido, PagoService $pagos): RedirectResponse
    {
        $this->autorizar($request, $pedido);

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

    /**
     * Cancelar o devolver un pedido propio. Si está pendiente y sin pagar, se cancela al instante;
     * si ya se pagó o se confirmó, queda la solicitud con su motivo y el personal la atiende
     * (anula, repone la existencia y devuelve el dinero).
     */
    public function solicitarDevolucion(Request $request, Pedido $pedido, PedidoService $pedidos): RedirectResponse
    {
        $this->autorizar($request, $pedido);

        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:500']], [
            'motivo.required' => 'Cuéntanos por qué quieres cancelar o devolver el pedido.',
            'motivo.min' => 'Explica un poco más el motivo.',
        ]);

        if ($pedido->estado === Pedido::ANULADO) {
            return back()->with('error', "El pedido {$pedido->codigo} ya está anulado.");
        }

        if ($pedido->clientePuedeCancelar()) {
            $pedidos->anular($pedido, $request->user(), 'Cancelado por el cliente: ' . $datos['motivo']);

            return back()->with('exito', "Cancelaste el pedido {$pedido->codigo}.");
        }

        $pedido->update(['devolucion_motivo' => $datos['motivo'], 'devolucion_solicitada_en' => now()]);

        return back()->with('exito', 'Recibimos tu solicitud. Te contactaremos para coordinar la devolución de los productos y de tu dinero.');
    }

    /** El dueño con su cuenta, o quien tiene el enlace firmado del pedido (compra sin cuenta). */
    private function autorizar(Request $request, Pedido $pedido): void
    {
        $esDueno = $request->user() && $pedido->id_cliente === $request->user()->cliente?->id;

        abort_unless($esDueno || $request->hasValidSignature(), 403);
    }

    /** Carrito vacío, personal de la empresa o cuenta aún en verificación: no hay compra posible. */
    private function sinCompraPosible(Request $request): ?RedirectResponse
    {
        // El personal registra las ventas desde el panel (venta de mostrador), no desde el portal
        abort_if($request->user()?->esPersonal(), 403);

        if ($this->carrito->estaVacio()) {
            return redirect()->route('carrito.index')->with('error', 'Tu lista está vacía. Agrega productos desde el catálogo.');
        }

        if ($request->user() && ! $request->user()->cliente) {
            return redirect()->route('carrito.index')->with('error',
                'Tu cuenta está en verificación: un vendedor te llamará para confirmar tus datos. Mientras tanto puedes enviar la cotización.');
        }

        return null;
    }
}
