<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Catálogo público (sin login) y ficha técnica del producto.
 */
class CatalogoController extends Controller
{
    private const POR_PAGINA = 12;

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'integer'],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'disponibles' => ['nullable', 'in:1'],
        ]);

        $productos = Producto::activos()
            ->with('categoria:id,nombre')                 // Eager loading: evita N+1
            ->buscar($filtros['q'] ?? null)
            ->filtrar($filtros)
            ->orderByDesc('stock')                         // Primero lo disponible
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        // Listas de filtros en caché 10 minutos (cambian poco)
        $categorias = Cache::remember('catalogo.categorias', 600, fn () => Categoria::orderBy('nombre')
            ->withCount(['productos' => fn ($q) => $q->activos()])
            ->get(['id', 'nombre']));
        $marcas = Cache::remember('catalogo.marcas', 600, fn () => Producto::activos()->distinct()->orderBy('marca')->pluck('marca'));

        return view('catalogo.index', compact('productos', 'categorias', 'marcas', 'filtros'));
    }

    public function show(Producto $producto): View
    {
        abort_unless($producto->activo, 404);
        $producto->load('categoria');

        $relacionados = Producto::activos()
            ->where('id_categoria', $producto->id_categoria)
            ->whereKeyNot($producto->id)
            ->where('stock', '>', 0)
            ->limit(4)
            ->get();

        return view('catalogo.show', compact('producto', 'relacionados'));
    }

    /** Stock en tiempo real para la ficha (la vista lo consulta cada 30 s). */
    public function stock(Producto $producto): JsonResponse
    {
        abort_unless($producto->activo, 404);   // Igual que la ficha: un producto oculto no expone su stock

        return response()->json([
            'stock' => $producto->stock,
            'estado' => $producto->estadoStock(),
        ]);
    }
}
