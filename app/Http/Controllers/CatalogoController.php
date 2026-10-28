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

    /** Órdenes del catálogo: valor en la URL => texto del selector */
    public const ORDENES = [
        'disponibles' => 'Disponibles primero',
        'precio_menor' => 'Precio: menor a mayor',
        'precio_mayor' => 'Precio: mayor a menor',
        'nombre' => 'Nombre (A–Z)',
    ];

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'integer'],
            'marca' => ['nullable', 'string', 'max:80'],
            'modelo' => ['nullable', 'string', 'max:80'],
            'disponibles' => ['nullable', 'in:1'],
            'orden' => ['nullable', 'in:' . implode(',', array_keys(self::ORDENES))],
        ]);

        $consulta = Producto::activos()
            ->with('categoria:id,nombre')                 // Eager loading: evita N+1
            ->buscar($filtros['q'] ?? null)
            ->filtrar($filtros);

        // Orden elegido por el cliente; por defecto, primero lo disponible
        match ($filtros['orden'] ?? 'disponibles') {
            'precio_menor' => $consulta->orderBy('precio'),
            'precio_mayor' => $consulta->orderByDesc('precio'),
            'nombre' => $consulta->orderBy('nombre'),
            default => $consulta->orderByRaw('stock = 0')->orderByDesc('stock'),
        };

        $productos = $consulta->orderBy('nombre')
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

    /** Sugerencias mientras se escribe en el buscador: hasta 6 productos visibles por código, nombre o marca. */
    public function sugerencias(Request $request): JsonResponse
    {
        $texto = trim((string) $request->query('q'));
        if (mb_strlen($texto) < 2 || mb_strlen($texto) > 100) {
            return response()->json([]);
        }

        $productos = Producto::activos()->buscar($texto)
            ->orderByRaw('codigo_fabrica LIKE ? DESC', [str_replace(['%', '_'], ['\%', '\_'], $texto) . '%'])   // Primero los que empiezan con el código escrito
            ->orderByDesc('stock')
            ->limit(6)
            ->get(['id', 'codigo_fabrica', 'nombre', 'marca', 'precio', 'stock', 'imagen', 'id_categoria']);

        return response()->json($productos->map(fn (Producto $p) => [
            'codigo' => $p->codigo_fabrica,
            'nombre' => $p->nombre,
            'marca' => $p->marca,
            'precio' => 'USD ' . number_format((float) $p->precio, 2, ',', '.'),
            'stock' => $p->stock,
            'imagen' => $p->urlImagen('mini'),
            'url' => route('catalogo.show', $p),
        ]));
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
