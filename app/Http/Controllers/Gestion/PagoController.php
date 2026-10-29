<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Pedido;
use App\Services\AvisoClienteService;
use App\Services\PagoService;
use App\Support\MetodosPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('gestion.pagos.index', compact('pagos', 'estado'));
    }

    public function verificar(Request $request, Pago $pago): RedirectResponse
    {
        try {
            $aviso = $this->pagos->verificar($pago, $request->user());
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pedido = $pago->pedido->fresh();

        return $this->conAvisoAlCliente(back()->with($aviso ? 'error' : 'exito', $aviso
            ?? "Pago verificado. El pedido {$pedido->codigo} quedó pagado y la venta confirmada."), $pedido);
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

        return $this->conAvisoAlCliente(back()->with($aviso ? 'error' : 'exito', $aviso ?? "Pago registrado. El pedido {$pedido->codigo} quedó pagado."), $pedido->fresh());
    }

    /** Avisa al cliente que su pago quedó verificado (correo con el comprobante y WhatsApp listo para enviar). */
    private function conAvisoAlCliente(RedirectResponse $respuesta, Pedido $pedido): RedirectResponse
    {
        $envio = $this->avisos->enviar($pedido, AvisoClienteService::PAGO_VERIFICADO);

        return $respuesta->with('aviso_cliente', $envio + ['texto' => 'Avisa al cliente que su pago fue verificado']);
    }

    public function comprobante(Pago $pago): BinaryFileResponse
    {
        $ruta = $this->pagos->rutaComprobante($pago);
        abort_unless($ruta, 404, 'Este pago no tiene comprobante.');

        return response()->file($ruta);
    }
}
