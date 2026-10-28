<?php

namespace App\Http\Controllers\Gestion;

use App\Exports\InventarioExport;
use App\Exports\VentasExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReporteRequest;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reportes gerenciales de ventas e inventario: en pantalla, PDF o Excel.
 * Cada reporte generado queda registrado en la tabla "reportes".
 */
class ReporteController extends Controller
{
    public function __construct(private readonly ReporteService $reportes)
    {
    }

    /** Formulario de filtros. Si viene ?tipo=..., también muestra el resultado en pantalla. */
    public function index(Request $request): View
    {
        $datos = [
            'productos' => Producto::orderBy('nombre')->get(['id', 'codigo_fabrica', 'nombre']),
            'vendedores' => Usuario::whereIn('rol', Usuario::PERFILES_PANEL)->orderBy('nombre')->get(['id', 'nombre']),
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
            'resultado' => null,
        ];

        if ($request->filled('tipo')) {
            $filtros = app(ReporteRequest::class)->filtros();   // Valida igual que las exportaciones
            $datos['resultado'] = $this->resultadoPantalla($request, $filtros);
        }

        return view('gestion.reportes.index', $datos);
    }

    public function exportar(ReporteRequest $request): Response
    {
        $filtros = $request->filtros();
        $formato = $request->input('formato') === 'excel' ? 'excel' : 'pdf';
        $nombre = "reporte-{$filtros['tipo']}-{$filtros['desde']}-al-{$filtros['hasta']}";

        if ($filtros['tipo'] === 'ventas') {
            $filas = $this->reportes->consultaVentas($filtros)->get();
            $this->reportes->registrar($request->user(), $filtros, $formato, $filas->count());

            if ($formato === 'excel') {
                return Excel::download(new VentasExport($filas), "{$nombre}.xlsx");
            }

            return Pdf::loadView('gestion.reportes.pdf.ventas', [
                'filas' => $filas,
                'resumen' => $this->reportes->resumenVentas($filtros),
                'filtros' => $this->describirFiltros($filtros),
            ])->setPaper('letter', 'landscape')->download("{$nombre}.pdf");
        }

        $existencias = $this->reportes->consultaExistencias($filtros)->get();
        $movimientos = $this->reportes->consultaMovimientos($filtros)->get();
        $this->reportes->registrar($request->user(), $filtros, $formato, $existencias->count() + $movimientos->count());

        if ($formato === 'excel') {
            return Excel::download(new InventarioExport($existencias, $movimientos), "{$nombre}.xlsx");
        }

        return Pdf::loadView('gestion.reportes.pdf.inventario', [
            'existencias' => $existencias,
            'movimientos' => $movimientos,
            'resumen' => $this->reportes->resumenInventario($filtros, Configuracion::umbralStockBajo()),
            'umbral' => Configuracion::umbralStockBajo(),
            'filtros' => $this->describirFiltros($filtros),
        ])->setPaper('letter', 'landscape')->download("{$nombre}.pdf");
    }

    private function resultadoPantalla(Request $request, array $filtros): array
    {
        // Se audita solo la primera página, no cada clic de paginación
        $esConsultaNueva = ! $request->hasAny(['page', 'existencias', 'movimientos']);

        if ($filtros['tipo'] === 'ventas') {
            $filas = $this->reportes->consultaVentas($filtros)->paginate(50)->withQueryString();
            if ($esConsultaNueva) {
                $this->reportes->registrar($request->user(), $filtros, 'pantalla', $filas->total());
            }

            return ['tipo' => 'ventas', 'filas' => $filas, 'resumen' => $this->reportes->resumenVentas($filtros)];
        }

        $umbral = Configuracion::umbralStockBajo();
        $existencias = $this->reportes->consultaExistencias($filtros)->paginate(50, ['*'], 'existencias')->withQueryString();
        $movimientos = $this->reportes->consultaMovimientos($filtros)->paginate(50, ['*'], 'movimientos')->withQueryString();
        if ($esConsultaNueva) {
            $this->reportes->registrar($request->user(), $filtros, 'pantalla', $existencias->total() + $movimientos->total());
        }

        return [
            'tipo' => 'inventario',
            'existencias' => $existencias,
            'movimientos' => $movimientos,
            'resumen' => $this->reportes->resumenInventario($filtros, $umbral),
            'umbral' => $umbral,
        ];
    }

    /** Texto legible de los filtros para el encabezado del PDF. */
    private function describirFiltros(array $f): array
    {
        return array_filter([
            'Período' => date('d/m/Y', strtotime($f['desde'])) . ' al ' . date('d/m/Y', strtotime($f['hasta'])),
            'Producto' => ! empty($f['id_producto']) ? Producto::find($f['id_producto'])?->codigo_fabrica : null,
            'Vendedor / usuario' => ! empty($f['id_vendedor']) ? Usuario::find($f['id_vendedor'])?->nombre : null,
            'Categoría' => ! empty($f['id_categoria']) ? Categoria::find($f['id_categoria'])?->nombre : null,
        ]);
    }
}
