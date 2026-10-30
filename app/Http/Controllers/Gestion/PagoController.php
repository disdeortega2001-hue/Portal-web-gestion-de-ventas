<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Pedido;
use App\Services\AvisoClienteService;
use App\Services\ComprobanteService;
use App\Services\PagoService;
use App\Support\MetodosPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use LogicException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pagos (administrativo y directivo): verificar o rechazar lo que reportan los clientes,
 * registrar lo que se cobra en la tienda y ver los comprobantes.
 */
class PagoController extends Controller
{
    public function __construct(private readonly PagoService $pagos, private readonly AvisoClienteService $avisos)
    {
    }

    public function index(Request $request): View
    {
        $estado = $request->query('estado', Pago::POR_VERIFICAR);

        $pagos = Pago::with(['pedido.cliente:id,identificacion,nombre,telefono', 'verificadoPor:id,nombre'])
            ->when($estado !== 'todos', fn ($q) => $q->where('estado', $estado))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $conteos = Pago::selectRaw('estado, count(*) as n')->groupBy('estado')->pluck('n', 'estado')->all();
        $conteos['todos'] = array_sum($conteos);

        return view('gestion.pagos.index', compact('pagos', 'estado', 'conteos'));
    }

    public function verificar(Request $request, Pago $pago): RedirectResponse
    {
        try {
            $aviso = $this->pagos->verificar($pago, $request->user());
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pedido = $pago->pedido->fresh();

        $mensaje = $pedido->a_credito && ! $pedido->estaPagado()
            ? "Abono verificado. Al pedido {$pedido->codigo} le quedan USD " . number_format((float) $pedido->saldoUsd(), 2, ',', '.') . '.'
            : "Pago verificado. El pedido {$pedido->codigo} quedó pagado y la venta confirmada.";

        return $this->conAvisoAlCliente(back()->with($aviso ? 'error' : 'exito', $aviso ?? $mensaje), $pedido);
    }

    public function rechazar(Request $request, Pago $pago): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']], [
            'motivo.required' => 'Explica por qué rechazas el pago: el cliente lo verá.',
            'motivo.min' => 'Explica un poco más el motivo.',
        ]);

        try {
            $this->pagos->rechazar($pago, $request->user(), $datos['motivo']);
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', "Pago rechazado. El cliente puede reportarlo de nuevo en el pedido {$pago->pedido->codigo}.");
    }

    /** Cobro en la tienda (o confirmado por el personal), desde la ficha del pedido. */
    public function registrar(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate([
            'metodo' => ['required', Rule::in(array_keys(MetodosPago::METODOS))],
            'monto' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'referencia' => ['nullable', 'string', 'max:60'],
        ], [
            'metodo.required' => 'Elige el método de pago.',
            'monto.required' => 'Indica el monto cobrado.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
        ]);

        try {
            $aviso = $this->pagos->registrarEnTienda($pedido, $datos, $request->user());
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pedido = $pedido->fresh();
        $mensaje = $pedido->a_credito && ! $pedido->estaPagado()
            ? "Abono registrado. Al pedido {$pedido->codigo} le quedan USD " . number_format((float) $pedido->saldoUsd(), 2, ',', '.') . '.'
            : "Pago registrado. El pedido {$pedido->codigo} quedó pagado.";

        return $this->conAvisoAlCliente(back()->with($aviso ? 'error' : 'exito', $aviso ?? $mensaje), $pedido);
    }

    /**
     * Tras verificar, nada sale solo: el personal revisa el comprobante y decide cuándo enviarlo
     * (correo con el PDF adjunto o WhatsApp con el enlace del pedido).
     */
    private function conAvisoAlCliente(RedirectResponse $respuesta, Pedido $pedido): RedirectResponse
    {
        // Abono a un crédito que aún tiene saldo: se le avisa cuánto falta (el comprobante sale al saldar)
        if ($pedido->a_credito && ! $pedido->estaPagado()) {
            return $respuesta->with('aviso_cliente', [
                'texto' => "Avisa al cliente su nuevo saldo del pedido {$pedido->codigo}: USD " . number_format((float) $pedido->saldoUsd(), 2, ',', '.'),
                'correo' => false,
                'whatsapp' => $this->avisos->whatsapp($pedido, AvisoClienteService::ABONO),
            ]);
        }

        if (! $pedido->tieneComprobante()) {
            return $respuesta;
        }

        return $respuesta->with('aviso_cliente', [
            'texto' => "Revisa el comprobante del pedido {$pedido->codigo} y envíaselo al cliente",
            'comprobante' => route('gestion.pedidos.comprobante', $pedido),
            'enviar' => $this->avisos->correo($pedido) ? route('gestion.pedidos.enviar-comprobante', $pedido) : null,
            'whatsapp' => $this->avisos->whatsapp($pedido, AvisoClienteService::PAGO_VERIFICADO),
        ]);
    }

    /** Vista previa del comprobante antes de verificar el pago (solo para el personal). */
    public function vistaPrevia(Pago $pago, ComprobanteService $comprobantes): Response
    {
        abort_unless($pago->estado === Pago::POR_VERIFICAR, 404, 'Este pago ya no está por verificar.');

        return $comprobantes->pdf($pago->pedido, $pago)->stream("Vista previa {$pago->pedido->codigo}.pdf");
    }

    /** El personal ya revisó el comprobante: se lo envía al cliente por correo. */
    public function enviarComprobante(Pedido $pedido): RedirectResponse
    {
        abort_unless($pedido->tieneComprobante(), 404, 'Este pedido todavía no tiene el pago verificado.');
        $envio = $this->avisos->enviar($pedido, AvisoClienteService::PAGO_VERIFICADO);

        return back()->with($envio['correo'] ? 'exito' : 'error', $envio['correo']
            ? "Comprobante enviado por correo a {$this->avisos->correo($pedido)}."
            : 'No se pudo enviar el correo. Revisa la configuración del correo o avísale por WhatsApp.');
    }

    public function comprobante(Pago $pago): BinaryFileResponse
    {
        $ruta = $this->pagos->rutaComprobante($pago);
        abort_unless($ruta, 404, 'Este pago no tiene comprobante.');

        return response()->file($ruta);
    }
}
