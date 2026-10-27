<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Cotizacion;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Support\Carbon;

/**
 * Métricas del tablero administrativo. Se calculan en cada consulta (sin caché)
 * para que sean en tiempo real; todas usan columnas indexadas (estado, fecha_confirmacion, stock).
 */
class TableroService
{
    private const DIAS_SERIE = 30;

    public function metricas(): array
    {
        $hoy = now()->startOfDay();
        $inicioMes = now()->startOfMonth();
        $umbral = Configuracion::umbralStockBajo();

        return [
            'ventas_hoy' => $this->ventasDesde($hoy),
            'ventas_mes' => $this->ventasDesde($inicioMes),
            'cotizaciones' => [
                'pendientes' => Cotizacion::where('estado', Cotizacion::PENDIENTE)->count(),
                'atrasadas' => Cotizacion::where('estado', Cotizacion::PENDIENTE)->where('fecha', '<', now()->subDay())->count(),
                'conversion_mes' => $this->conversionDesde($inicioMes),
            ],
            'stock' => [
                'umbral' => $umbral,
                'bajo' => Producto::activos()->whereBetween('stock', [1, $umbral])->count(),
                'agotados' => Producto::activos()->where('stock', 0)->count(),
                'alertas' => Producto::activos()
                    ->where('stock', '<=', $umbral)
                    ->orderBy('stock')->orderBy('nombre')
                    ->limit(8)
                    ->get(['id', 'codigo_fabrica', 'nombre', 'stock'])
                    ->toArray(),
            ],
            'mas_vendidos' => $this->masVendidosDesde($inicioMes),
            'serie_ventas' => $this->serieVentas(),
            'actualizado' => now()->format('d/m/Y h:i a'),
        ];
    }

    /** @return array{pedidos: int, total: float} */
    private function ventasDesde(Carbon $desde): array
    {
        $fila = Pedido::where('estado', Pedido::CONFIRMADO)
            ->where('fecha_confirmacion', '>=', $desde)
            ->selectRaw('COUNT(*) AS pedidos, COALESCE(SUM(total), 0) AS total')
            ->first();

        return ['pedidos' => (int) $fila->pedidos, 'total' => (float) $fila->total];
    }

    /** % de cotizaciones del período que terminaron en pedido. */
    private function conversionDesde(Carbon $desde): float
    {
        $total = Cotizacion::where('fecha', '>=', $desde)->count();
        if ($total === 0) {
            return 0.0;
        }
        $convertidas = Cotizacion::where('fecha', '>=', $desde)->where('estado', Cotizacion::CONVERTIDA)->count();

        return round($convertidas * 100 / $total, 1);
    }

    private function masVendidosDesde(Carbon $desde): array
    {
        return DetallePedido::query()
            ->join('pedidos', 'pedidos.id', '=', 'detalle_pedidos.id_pedido')
            ->join('productos', 'productos.id', '=', 'detalle_pedidos.id_producto')
            ->where('pedidos.estado', Pedido::CONFIRMADO)
            ->whereNull('pedidos.deleted_at')
            ->where('pedidos.fecha_confirmacion', '>=', $desde)
            ->groupBy('productos.id', 'productos.codigo_fabrica', 'productos.nombre')
            ->selectRaw('productos.codigo_fabrica, productos.nombre, SUM(detalle_pedidos.cantidad) AS unidades, SUM(detalle_pedidos.subtotal) AS monto')
            ->orderByDesc('unidades')
            ->limit(5)
            ->get()
            ->map(fn ($f) => [
                'codigo' => $f->codigo_fabrica,
                'nombre' => $f->nombre,
                'unidades' => (int) $f->unidades,
                'monto' => (float) $f->monto,
            ])
            ->all();
    }

    /** Ventas diarias de los últimos 30 días, rellenando con 0 los días sin ventas. */
    private function serieVentas(): array
    {
        $desde = now()->subDays(self::DIAS_SERIE - 1)->startOfDay();

        $porDia = Pedido::where('estado', Pedido::CONFIRMADO)
            ->where('fecha_confirmacion', '>=', $desde)
            ->selectRaw('DATE(fecha_confirmacion) AS dia, SUM(total) AS total')
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $etiquetas = [];
        $valores = [];
        for ($i = 0; $i < self::DIAS_SERIE; $i++) {
            $dia = $desde->copy()->addDays($i);
            $etiquetas[] = $dia->format('d/m');
            $valores[] = (float) ($porDia[$dia->toDateString()] ?? 0);
        }

        return ['etiquetas' => $etiquetas, 'valores' => $valores];
    }
}
