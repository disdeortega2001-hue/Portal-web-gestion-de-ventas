<?php

namespace App\Http\Controllers\Gestion;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PedidoRequest;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\AvisoClienteService;
use App\Services\ComprobanteService;
use App\Services\PedidoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use LogicException;

/**
 * Gestión de pedidos: listado, pedido de mostrador, confirmación de venta
 * (transacción ACID en PedidoService) y anulación.
 */
class PedidoController extends Controller
{
    public function __construct(private readonly PedidoService $pedidos)
    {
    }

    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'todos');
        $busqueda = trim((string) $request->query('q'));

        $pedidos = Pedido::with(['cliente:id,identificacion,nombre,telefono', 'vendedor:id,nombre'])
            ->when($estado === 'devolucion', fn ($q) => $q->whereNotNull('devolucion_solicitada_en')->where('estado', '!=', Pedido::ANULADO))
            ->when(! in_array($estado, ['todos', 'devolucion'], true), fn ($q) => $q->where('estado', $estado))
            // Por código del pedido (PED-000012 o solo 12) o por el cliente (nombre, cédula, teléfono)
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('codigo', 'like', '%' . strtoupper($busqueda) . '%')
                ->orWhereHas('cliente', fn ($c) => $c->buscar($busqueda))))
            ->latest('fecha')
            ->paginate(15)
            ->withQueryString();

        return view('gestion.pedidos.index', compact('pedidos', 'estado', 'busqueda'));
    }

    /**
     * Venta de mostrador: el cliente se busca por nombre, cédula o teléfono y los productos por código;
     * la vista muestra los totales en vivo, y el servidor los vuelve a calcular al guardar.
     */
    public function create(Request $request): View
    {
        $idCliente = old('id_cliente', $request->query('cliente'));

        return view('gestion.pedidos.create', [
            'clienteElegido' => $idCliente ? Cliente::find($idCliente, ['id', 'identificacion', 'nombre', 'telefono']) : null,
            'productos' => Producto::activos()->where('stock', '>', 0)->orderBy('nombre')
                ->get(['id', 'codigo_fabrica', 'nombre', 'marca', 'precio', 'stock']),
            'parametros' => [
                'tasa' => (float) Configuracion::valor('tasa_cambio', 0),
                'iva' => Configuracion::porcentajeIva(),
                'igtf' => Configuracion::porcentajeIgtf(),
            ],
        ]);
    }

    public function store(PedidoRequest $request): RedirectResponse
    {
        $pedido = $this->pedidos->crearDirecto(
            Cliente::findOrFail($request->integer('id_cliente')),
            $request->input('items'),
            $request->user(),
            $request->input('moneda_pago', 'USD')
        );

        return redirect()->route('gestion.pedidos.show', $pedido)->with('exito', "Pedido {$pedido->codigo} creado.");
    }

    public function show(Pedido $pedido): View
    {
        $pedido->load(['cliente', 'vendedor', 'cotizacion', 'detalles.producto', 'movimientos.usuario', 'pagos.registradoPor', 'entregadoPor:id,nombre']);

        return view('gestion.pedidos.show', compact('pedido'));
    }

    public function confirmar(Request $request, Pedido $pedido): RedirectResponse
    {
        try {
            $this->pedidos->confirmarVenta($pedido, $request->user());
        } catch (StockInsuficienteException|LogicException $e) {
            // La transacción ya hizo rollback: nada cambió en la BD
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', "Venta procesada. El stock de los productos del pedido {$pedido->codigo} fue descontado.");
    }

    /**
     * Entrega del pedido pagado: lo retira el titular o una persona autorizada (con su cédula),
     * o se despacha. Queda quién lo recibió y quién lo entregó, y se avisa al cliente.
     */
    public function entregar(Request $request, Pedido $pedido, AvisoClienteService $avisos): RedirectResponse
    {
        $datos = $request->validate([
            'recibe' => ['required', 'in:titular,otra'],
            'recibido_por' => ['required_if:recibe,otra', 'nullable', 'string', 'min:3', 'max:120'],
            'recibido_por_identificacion' => ['required_if:recibe,otra', 'nullable', 'string', 'regex:/^[VEJPGvejpg]?-?\d{5,10}$/'],
        ], [
            'recibido_por.required_if' => 'Escribe el nombre de quien retira el pedido.',
            'recibido_por_identificacion.required_if' => 'Escribe la cédula de quien retira el pedido.',
            'recibido_por_identificacion.regex' => 'Escribe la cédula con solo números (ej. 12345678 o V-12345678).',
        ]);

        if (! $pedido->puedeEntregarse()) {
            return back()->with('error', "El pedido {$pedido->codigo} no se puede entregar: debe estar confirmado, pagado y sin entregar.");
        }

        $titular = $datos['recibe'] === 'titular';
        $pedido->update([
            'entregado_en' => now(),
            'recibido_por' => $titular ? $pedido->cliente->nombre : trim($datos['recibido_por']),
            'recibido_por_identificacion' => $titular ? $pedido->cliente->identificacion : strtoupper(trim($datos['recibido_por_identificacion'])),
            'id_usuario_entrega' => $request->user()->id,
        ]);
        $envio = $avisos->enviar($pedido, AvisoClienteService::ENTREGADO);

        return back()->with('exito', "Pedido {$pedido->codigo} entregado a {$pedido->recibido_por}.")
            ->with('aviso_cliente', $envio + ['texto' => $titular ? 'Avisa al cliente que su pedido fue entregado' : 'Avisa al cliente quién retiró su pedido']);
    }

    /** Comprobante de pago en PDF (el mismo que descarga el cliente). */
    public function comprobante(Pedido $pedido, ComprobanteService $comprobantes): Response
    {
        abort_unless($pedido->tieneComprobante(), 404, 'Este pedido todavía no tiene el pago verificado.');

        return $comprobantes->pdf($pedido)->stream($comprobantes->nombreArchivo($pedido));
    }

    /** Anula (o registra la devolución): repone la existencia si estaba confirmado; el motivo es obligatorio. */
    public function anular(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:500']], [
            'motivo.required' => 'Escribe el motivo de la anulación o devolución.',
            'motivo.min' => 'Explica un poco más el motivo.',
        ]);

        try {
            $this->pedidos->anular($pedido, $request->user(), $datos['motivo']);
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pedido->refresh();

        return back()->with('exito', "Pedido {$pedido->codigo} anulado." . ($pedido->estado_pago === Pedido::REEMBOLSADO ? ' Recuerda devolverle el dinero al cliente: el pago quedó como reembolsado.' : ''));
    }
}
