<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\ImagenProductoService;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Throwable;

/**
 * Catálogo de productos (administrador).
 * - El stock inicial se registra como movimiento de "entrada" (trazabilidad desde el primer día).
 * - Después, el stock solo cambia desde Inventario o por ventas, nunca editando la ficha.
 * - Las fotos se guardan redimensionadas en storage/app/public/productos (enlazado con storage:link).
 */
class ProductoController extends Controller
{
    public function __construct(
        private readonly InventarioService $inventario,
        private readonly ImagenProductoService $imagenes,
    ) {
    }

    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'activos');

        $productos = Producto::query()
            ->with('categoria:id,nombre')
            ->when($estado === 'activos', fn ($q) => $q->where('activo', true))
            ->when($estado === 'inactivos', fn ($q) => $q->where('activo', false))
            ->when($estado === 'eliminados', fn ($q) => $q->onlyTrashed())
            ->when($request->query('categoria'), fn ($q, $id) => $q->where('id_categoria', $id))
            ->buscar($request->query('q'))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('admin.productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
            'estado' => $estado,
        ]);
    }

    public function create(): View
    {
        return view('admin.productos.form', [
            'producto' => new Producto(['activo' => true, 'stock' => 0]),
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(ProductoRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except(['imagen', 'stock']);
        $stockInicial = $request->integer('stock');
        $rutaImagen = $request->hasFile('imagen') ? $this->guardarImagen($request->file('imagen')) : null;

        try {
            $producto = Producto::create($datos + ['imagen' => $rutaImagen, 'stock' => 0]);

            if ($stockInicial > 0) {
                $this->inventario->registrar($producto, MovimientoInventario::ENTRADA, $stockInicial, 'Inventario inicial al registrar el producto', $request->user());
            }
        } catch (Throwable $e) {
            // Si falla la BD, no se deja una imagen huérfana en el disco
            $this->imagenes->eliminar($rutaImagen);
            throw $e;
        }

        return redirect()->route('admin.productos.index')->with('exito', "Producto {$producto->codigo_fabrica} creado.");
    }

    public function edit(Producto $producto): View
    {
        return view('admin.productos.form', [
            'producto' => $producto,
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(ProductoRequest $request, Producto $producto): RedirectResponse
    {
        $datos = $request->safe()->except(['imagen', 'stock']);
        $imagenAnterior = $producto->imagen;

        if ($request->hasFile('imagen')) {
            $datos['imagen'] = $this->guardarImagen($request->file('imagen'));
        } elseif ($request->boolean('quitar_imagen')) {
            $datos['imagen'] = null;
        }

        $producto->update($datos);

        // La imagen anterior se borra solo después de guardar con éxito
        if (array_key_exists('imagen', $datos) && $imagenAnterior && $imagenAnterior !== $datos['imagen']) {
            $this->imagenes->eliminar($imagenAnterior);
        }

        return redirect()->route('admin.productos.index')->with('exito', "Producto {$producto->codigo_fabrica} actualizado.");
    }

    /** Borrado lógico: desaparece del catálogo, pero sigue en cotizaciones, pedidos y reportes. */
    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->delete();

        return redirect()->route('admin.productos.index')->with('exito', "Producto {$producto->codigo_fabrica} eliminado. Puedes restaurarlo desde \"Eliminados\".");
    }

    public function restaurar(int $id): RedirectResponse
    {
        $producto = Producto::onlyTrashed()->findOrFail($id);
        $producto->restore();

        return redirect()->route('admin.productos.index')->with('exito', "Producto {$producto->codigo_fabrica} restaurado.");
    }

    /** Guarda dos versiones livianas (800 y 240 px) con nombre aleatorio; ver ImagenProductoService. */
    private function guardarImagen(UploadedFile $archivo): string
    {
        return $this->imagenes->guardar($archivo);
    }
}
