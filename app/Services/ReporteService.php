<?php

namespace App\Services;

use App\Models\DetallePedido;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Reporte;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Construye las consultas de los reportes. Devuelve consultas (Builder) y no
 * colecciones, para que la pantalla pagine y las exportaciones lean todo,
 * usando la MISMA consulta: lo que se ve en pantalla es lo que se exporta.
 */
class ReporteService
{
    /* ===================== VENTAS ===================== */

    /** Una fila por producto vendido en pedidos confirmados dentro del rango. */
    public function consultaVentas(array $f): Builder
    {
        return $this->baseVentas($f)
            ->join('productos', 'productos.id', '=', 'detalle_pedidos.id_producto')
            ->join('clientes', 'clientes.id', '=', 'pedidos.id_cliente')
            ->join('usuarios', 'usuarios.id', '=', 'pedidos.id_usuario')
            ->select([
                'pedidos.fecha_confirmacion', 'pedidos.codigo AS pedido',
                'clientes.identificacion', 'clientes.nombre AS cliente',
                'usuarios.nombre AS vendedor',
                'productos.codigo_fabrica', 'productos.nombre AS producto',
                'detalle_pedidos.cantidad', 'detalle_pedidos.precio_unitario', 'detalle_pedidos.subtotal',
            ])
            ->orderBy('pedidos.fecha_confirmacion')
            ->orderBy('pedidos.id');
    }

    public function resumenVentas(array $f): array
    {
        $lineas = $this->baseVentas($f)
            ->selectRaw('COUNT(DISTINCT pedidos.id) AS pedidos, COALESCE(SUM(detalle_pedidos.cantidad), 0) AS unidades, COALESCE(SUM(detalle_pedidos.subtotal), 0) AS monto')
            ->first();

        // Totales facturados de los pedidos del rango, con el impuesto separado en IVA (pagos en Bs.) e IGTF (pagos en $)
        $pedidos = $this->pedidosConfirmados($f)
            ->selectRaw('COALESCE(SUM(subtotal),0) AS subtotal, COALESCE(SUM(descuento),0) AS descuento, COALESCE(SUM(impuesto),0) AS impuesto, COALESCE(SUM(CASE WHEN tipo_impuesto = \'IVA\' THEN impuesto ELSE 0 END),0) AS iva, COALESCE(SUM(CASE WHEN tipo_impuesto = \'IGTF\' THEN impuesto ELSE 0 END),0) AS igtf, COALESCE(SUM(total),0) AS total, COALESCE(SUM(total_bs),0) AS total_bs')
            ->first();

        $porVendedor = $this->pedidosConfirmados($f)
            ->join('usuarios', 'usuarios.id', '=', 'pedidos.id_usuario')
            ->groupBy('usuarios.id', 'usuarios.nombre')
            ->selectRaw('usuarios.nombre, COUNT(*) AS pedidos, SUM(pedidos.total) AS total_vendido')
            ->orderByDesc('total_vendido')
            ->get();

        // Los más vendidos del período, por unidades (los productos de mayor rotación)
        $masVendidos = $this->baseVentas($f)
            ->join('productos', 'productos.id', '=', 'detalle_pedidos.id_producto')
            ->groupBy('productos.id', 'productos.codigo_fabrica', 'productos.nombre')
            ->selectRaw('productos.codigo_fabrica, productos.nombre, SUM(detalle_pedidos.cantidad) AS unidades, SUM(detalle_pedidos.subtotal) AS monto')
            ->orderByDesc('unidades')
            ->limit(5)
            ->get();

        return [
            'mas_vendidos' => $masVendidos,
            'pedidos' => (int) $lineas->pedidos,
            'unidades' => (int) $lineas->unidades,
            'monto_lineas' => (float) $lineas->monto,
            'facturado' => $pedidos->only(['subtotal', 'impuesto', 'iva', 'igtf', 'total', 'total_bs']),
            'por_vendedor' => $porVendedor,
            // Con filtro de producto, los totales facturados incluyen otros productos de esos pedidos
            'filtrado_por_producto' => ! empty($f['id_producto']),
        ];
    }

    private function baseVentas(array $f): Builder
    {
        return DetallePedido::query()
            ->join('pedidos', 'pedidos.id', '=', 'detalle_pedidos.id_pedido')
            ->where('pedidos.estado', Pedido::CONFIRMADO)
            ->whereNull('pedidos.deleted_at')
            ->whereBetween('pedidos.fecha_confirmacion', $this->rango($f))
            ->when($f['id_producto'] ?? null, fn ($q, $id) => $q->where('detalle_pedidos.id_producto', $id))
            ->when($f['id_vendedor'] ?? null, fn ($q, $id) => $q->where('pedidos.id_usuario', $id));
    }

    private function pedidosConfirmados(array $f): Builder
    {
        return Pedido::query()
            ->where('pedidos.estado', Pedido::CONFIRMADO)
            ->whereBetween('pedidos.fecha_confirmacion', $this->rango($f))
            ->when($f['id_vendedor'] ?? null, fn ($q, $id) => $q->where('pedidos.id_usuario', $id))
            ->when($f['id_producto'] ?? null, fn ($q, $id) => $q->whereHas('detalles', fn ($d) => $d->where('id_producto', $id)));
    }

    /* ===================== INVENTARIO ===================== */

    /** Existencias actuales (foto del momento en que se genera el reporte). */
    public function consultaExistencias(array $f): Builder
    {
        return Producto::query()
            ->with('categoria:id,nombre')
            ->when($f['id_categoria'] ?? null, fn ($q, $id) => $q->where('id_categoria', $id))
            ->when($f['id_producto'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->orderBy('id_categoria')
            ->orderBy('nombre');
    }

    /** Movimientos del rango, filtrables por producto y usuario responsable. */
    public function consultaMovimientos(array $f): Builder
    {
        return MovimientoInventario::query()
            ->with(['producto:id,codigo_fabrica,nombre', 'usuario:id,nombre', 'pedido:id,codigo'])
            ->whereBetween('fecha', $this->rango($f))
            ->when($f['id_producto'] ?? null, fn ($q, $id) => $q->where('id_producto', $id))
            ->when($f['id_vendedor'] ?? null, fn ($q, $id) => $q->where('id_usuario', $id))
            ->when($f['id_categoria'] ?? null, fn ($q, $id) => $q->whereHas('producto', fn ($p) => $p->where('id_categoria', $id)))
            ->orderBy('fecha');
    }

    public function resumenInventario(array $f, int $umbral): array
    {
        $existencias = $this->consultaExistencias($f)->reorder()
            ->selectRaw('COUNT(*) AS productos, COALESCE(SUM(stock),0) AS unidades, COALESCE(SUM(stock * precio),0) AS valor,
                         SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) AS agotados,
                         SUM(CASE WHEN stock BETWEEN 1 AND ? THEN 1 ELSE 0 END) AS bajo', [$umbral])
            ->setEagerLoads([])
            ->first();

        $movimientos = $this->consultaMovimientos($f)->reorder()->setEagerLoads([])
            ->selectRaw("COUNT(*) AS total,
                         COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END),0) AS entradas,
                         COALESCE(SUM(CASE WHEN tipo = 'salida' THEN cantidad ELSE 0 END),0) AS salidas,
                         COALESCE(SUM(CASE WHEN tipo = 'ajuste' THEN cantidad ELSE 0 END),0) AS ajuste_neto")
            ->first();

        return [
            'baja_rotacion' => $this->bajaRotacion($f),
            'productos' => (int) $existencias->productos,
            'unidades' => (int) $existencias->unidades,
            'valor_venta' => (float) $existencias->valor,   // Valorizado a precio de venta, nunca a costo
            'agotados' => (int) $existencias->agotados,
            'bajo' => (int) $existencias->bajo,
            'movimientos' => (int) $movimientos->total,
            'entradas' => (int) $movimientos->entradas,
            'salidas' => (int) $movimientos->salidas,
            'ajuste_neto' => (int) $movimientos->ajuste_neto,
        ];
    }

    /**
     * Baja rotación: productos visibles con existencia que no tuvieron ninguna venta confirmada en el
     * período. Es la mercancía inmovilizada que conviene promocionar o no volver a pedir.
     */
    public function bajaRotacion(array $f): \Illuminate\Support\Collection
    {
        $vendidos = $this->baseVentas(['id_producto' => null, 'id_vendedor' => null] + $f)
            ->distinct()->pluck('detalle_pedidos.id_producto');

        return Producto::activos()
            ->where('stock', '>', 0)
            ->whereNotIn('id', $vendidos)
            ->when($f['id_categoria'] ?? null, fn ($q, $id) => $q->where('id_categoria', $id))
            ->orderByDesc('stock')
            ->get(['id', 'codigo_fabrica', 'nombre', 'stock', 'precio']);
    }

    /* ===================== AUDITORÍA ===================== */

    /** Deja constancia de cada reporte generado (indicador de reportes gerenciales). */
    public function registrar(Usuario $usuario, array $f, string $formato, int $registros): void
    {
        Reporte::create([
            'id_usuario' => $usuario->id,
            'tipo' => $f['tipo'],
            'formato' => $formato,
            'filtros' => $f,
            'registros' => $registros,
            'fecha' => now(),
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} rango inclusivo de día completo */
    private function rango(array $f): array
    {
        return [Carbon::parse($f['desde'])->startOfDay(), Carbon::parse($f['hasta'])->endOfDay()];
    }
}
