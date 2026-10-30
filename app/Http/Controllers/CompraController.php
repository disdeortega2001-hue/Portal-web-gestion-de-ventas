<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompraRequest;
use App\Models\Pedido;
use App\Services\CarritoService;
use App\Services\ComprobanteService;
use App\Services\PagoService;
use App\Services\PedidoService;
use App\Support\ClienteInvitado;
use App\Support\ClienteRecordado;
use App\Support\Credito;
use App\Support\MetodosPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
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

        $cliente = $request->user()?->cliente;
        // Un resumen por moneda: en dólares lleva IGTF y en bolívares IVA; el método elegido decide cuál aplica
        $resumenes = ['USD' => $this->carrito->resumen('USD'), 'VES' => $this->carrito->resumen('VES')];

        return view('compra.create', [
            'resumenes' => $resumenes,
            'cliente' => $cliente,
            'recordado' => $request->user() ? [] : ClienteRecordado::leer(),
            'metodos' => MetodosPago::disponibles(),
            'credito' => self::opcionesCredito($cliente, $resumenes),
        ]);
    }

    public function store(CompraRequest $request, PedidoService $pedidos): RedirectResponse
    {
        if ($redireccion = $this->sinCompraPosible($request)) {
            return $redireccion;
        }

        $datos = $request->validated();

        // El crédito es solo para clientes autorizados y dentro de su límite
        if (Credito::esOpcion($datos['metodo_pago'])) {
            $cliente = $request->user()?->cliente;
            $total = (float) $this->carrito->resumen(Credito::moneda($datos['metodo_pago']))['total'];
            if (! $cliente || ! Credito::permite($cliente, $total)) {
                throw ValidationException::withMessages(['metodo_pago' => $cliente?->credito_habilitado
                    ? 'Este pedido pasa de tu crédito disponible (USD ' . number_format((float) Credito::disponibleUsd($cliente), 2, ',', '.') . '). Elige otro método o quita productos.'
                    : 'Tu cuenta no tiene crédito autorizado. Pregúntanos por WhatsApp cómo solicitarlo.']);
            }
        }

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

        $siguiente = match (true) {
            $pedido->a_credito => 'Es a crédito: lo confirmamos y te avisamos para retirarlo. Abonas cuando quieras desde aquí.',
            MetodosPago::enLinea($pedido->metodo_pago) => 'Paga y repórtalo aquí mismo con la referencia.',
            default => 'Pagas al retirar en la tienda.',
        };

        $destino = $conCuenta ? route('cuenta.pedidos.show', $pedido) : self::enlaceSeguimiento($pedido);

        return redirect($destino)->with('exito', "Recibimos tu pedido {$pedido->codigo}. {$siguiente}");
    }

    /**
     * Opciones de crédito para un cliente autorizado: cada una dice si el pedido cabe en su límite.
     *
     * @return array{disponible: ?float, deuda: float, opciones: array}|null
     */
    private static function opcionesCredito(?\App\Models\Cliente $cliente, array $resumenes): ?array
    {
        if (! $cliente?->credito_habilitado) {
            return null;
        }

        $opciones = [];
        foreach (Credito::OPCIONES as $clave => $opcion) {
            $opciones[$clave] = $opcion + ['alcanza' => Credito::permite($cliente, (float) $resumenes[$opcion['moneda']]['total'])];
        }

        return ['disponible' => Credito::disponibleUsd($cliente), 'deuda' => Credito::deudaUsd($cliente), 'opciones' => $opciones];
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

    /** Comprobante de pago en PDF: solo cuando el pago del pedido está verificado. */
    public function comprobante(Request $request, Pedido $pedido, ComprobanteService $comprobantes): Response
    {
        $this->autorizar($request, $pedido);
        abort_unless($pedido->tieneComprobante(), 404, 'El comprobante estará disponible cuando verifiquemos tu pago.');

        return $comprobantes->pdf($pedido)->download($comprobantes->nombreArchivo($pedido));
    }

    /** El cliente reporta su pago a distancia con la referencia y el comprobante. */
    public function reportarPago(Request $request, Pedido $pedido, PagoService $pagos): RedirectResponse
    {
        $this->autorizar($request, $pedido);

        $datos = $request->validate([
            'metodo' => [$pedido->a_credito ? 'required' : 'prohibited', 'nullable',
                Rule::in(array_keys(array_filter(MetodosPago::disponibles(), fn ($m) => $m['en_linea'])))],
            'referencia' => ['required', 'string', 'min:4', 'max:60'],
            'fecha_pago' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:' . $pedido->fecha->toDateString()],
            'monto' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'metodo.required' => 'Elige con qué pagaste.',
            'metodo.in' => 'Ese método de pago no está disponible.',
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

        return back()->with('exito', $pedido->a_credito
            ? 'Recibimos tu abono. Cuando lo verifiquemos se descuenta de tu saldo.'
            : 'Recibimos tu reporte de pago. Te avisamos cuando lo verifiquemos.');
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
