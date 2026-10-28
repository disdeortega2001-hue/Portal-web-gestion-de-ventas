<?php

namespace App\Http\Controllers\Gestion;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PedidoRequest;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Services\PedidoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->when($estado !== 'todos', fn ($q) => $q->where('estado', $estado))
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
        $pedido->load(['cliente', 'vendedor', 'cotizacion', 'detalles.producto', 'movimientos.usuario', 'pagos.registradoPor']);

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

    public function anular(Request $request, Pedido $pedido): RedirectResponse
    {
        try {
            $this->pedidos->anular($pedido, $request->user());
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', "Pedido {$pedido->codigo} anulado.");
    }
}
