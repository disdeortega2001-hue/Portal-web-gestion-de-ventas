<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\View\View;

/**
 * «Nuestra historia»: resumen de la empresa con cifras, línea de vida, pilares y las marcas
 * de representación exclusiva. Las cifras de productos salen del catálogo, así se mantienen al día solas.
 */
class NosotrosController extends Controller
{
    public const FUNDACION = 1963;

    /** Marcas exclusivas: nombre tal como está en el campo «marca» del producto => logo */
    public const MARCAS = [
        'Marchesan Tatu' => 'img/marcas/marchesan-tatu.jpg',
        'Colombo Aemco' => 'img/marcas/colombo-aemco.png',
        'Vicon' => 'img/marcas/vicon.jpg',
        'Lumagi' => 'img/marcas/lumagi.jpg',
    ];

    public function __invoke(): View
    {
        $productos = Producto::activos()->with('categoria:id,nombre')->get(['id', 'marca', 'id_categoria']);

        $marcas = collect(self::MARCAS)->map(function ($logo, $nombre) use ($productos) {
            $suyos = $productos->where('marca', $nombre);

            return [
                'nombre' => $nombre,
                'logo' => $logo,
                'productos' => $suyos->count(),
                'categorias' => $suyos->pluck('categoria.nombre')->filter()->unique()->sort()->values()->all(),
            ];
        })->values();

        return view('nosotros', [
            'anios' => now()->year - self::FUNDACION,
            'totalProductos' => $productos->count(),
            'marcas' => $marcas,
        ]);
    }
}
