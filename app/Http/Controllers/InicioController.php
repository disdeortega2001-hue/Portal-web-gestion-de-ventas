<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\DetallePedido;
use App\Models\Producto;
use Illuminate\View\View;

/**
 * Página de inicio: presenta la empresa, sus marcas exclusivas y las categorías,
 * y muestra los productos más pedidos con existencia, para entrar al catálogo con un toque.
 */
class InicioController extends Controller
{
    public function __invoke(): View
    {
        $productos = Producto::activos()->with('categoria:id,nombre')->get();

        // Los más vendidos en pedidos confirmados; si aún no hay ventas, los de mayor existencia
        $masVendidos = DetallePedido::query()
            ->join('pedidos', 'pedidos.id', '=', 'detalle_pedidos.id_pedido')
            ->where('pedidos.estado', 'confirmado')
            ->groupBy('detalle_pedidos.id_producto')
            ->orderByRaw('SUM(detalle_pedidos.cantidad) DESC')
            ->pluck('detalle_pedidos.id_producto');

        $destacados = $productos->where('stock', '>', 0)
            ->sortBy(fn ($p) => [($i = $masVendidos->search($p->id)) === false ? PHP_INT_MAX : $i, -$p->stock])
            ->take(4)
            ->values();

        $marcas = collect(NosotrosController::MARCAS)->map(fn ($logo, $nombre) => [
            'nombre' => $nombre,
            'logo' => $logo,
            'productos' => $productos->where('marca', $nombre)->count(),
        ])->values();

        $categorias = Categoria::withCount(['productos' => fn ($q) => $q->activos()])
            ->orderBy('nombre')
            ->get()
            ->where('productos_count', '>', 0)
            ->each(fn ($c) => $c->muestra = $productos->where('id_categoria', $c->id)->whereNotNull('imagen')->sortByDesc('precio')->first())
            ->values();

        // La foto de la portada: el equipo de mayor valor con foto y existencia
        $estrella = $productos->whereNotNull('imagen')->where('stock', '>', 0)->sortByDesc('precio')->first();

        $tasa = Configuracion::where('clave', 'tasa_cambio')->first();

        return view('inicio', [
            'destacados' => $destacados,
            'marcas' => $marcas,
            'categorias' => $categorias,
            'estrella' => $estrella,
            'anios' => now()->year - NosotrosController::FUNDACION,
            'totalProductos' => $productos->count(),
            'disponibles' => $productos->where('stock', '>', 0)->count(),
            'tasa' => (float) ($tasa?->valor ?? 0),
            'tasaActualizada' => $tasa?->updated_at,
            'iva' => (float) Configuracion::valor('porcentaje_iva', 16),
        ]);
    }
}
