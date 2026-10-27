<?php

namespace App\Http\Controllers\Gestion;

use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\MovimientoInventarioRequest;
use App\Models\Configuracion;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Inventario: existencias actuales, registro de entradas/salidas/ajustes
 * y bitácora de movimientos con usuario responsable.
 */
class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventario)
    {
    }

    public function index(Request $request): View
    {
        $soloBajo = $request->boolean('bajo_stock');

        $productos = Producto::with('categoria:id,nombre')
            ->buscar($request->query('q'))
            ->when($soloBajo, fn ($q) => $q->where('stock', '<=', Configuracion::umbralStockBajo()))
            ->orderBy('stock')
            ->paginate(20, ['*'], 'productos')
            ->withQueryString();

        $movimientos = MovimientoInventario::with(['producto:id,codigo_fabrica,nombre', 'usuario:id,nombre'])
            ->latest('fecha')
            ->paginate(15, ['*'], 'movimientos')
            ->withQueryString();

        return view('gestion.inventario.index', [
            'productos' => $productos,
            'movimientos' => $movimientos,
            'listaProductos' => Producto::orderBy('nombre')->get(['id', 'codigo_fabrica', 'nombre', 'stock']),
            'umbral' => Configuracion::umbralStockBajo(),
        ]);
    }

    public function store(MovimientoInventarioRequest $request): RedirectResponse
    {
        $producto = Producto::findOrFail($request->integer('id_producto'));

        try {
            $movimiento = $this->inventario->registrar(
                $producto,
                $request->input('tipo'),
                $request->integer('cantidad'),
                $request->input('motivo'),
                $request->user()
            );
        } catch (StockInsuficienteException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('gestion.inventario.index')->with(
            'exito',
            "Movimiento registrado: {$producto->codigo_fabrica} pasó de {$movimiento->stock_anterior} a {$movimiento->stock_nuevo} unidades."
        );
    }
}
