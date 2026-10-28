<?php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Ciclo de vida del pedido: crear (desde cotización, en mostrador o comprado en el portal),
 * confirmar la venta (descuenta stock) y anular (repone stock).
 *
 * Toda operación que toca stock corre dentro de DB::transaction():
 * si cualquier paso falla se hace ROLLBACK de pedidos, detalle_pedidos,
 * movimientos_inventario y productos a la vez (atomicidad ACID).
 */
class PedidoService
{
    /** Reintentos si MySQL detecta un interbloqueo (deadlock) entre dos ventas simultáneas. */
    private const REINTENTOS = 3;

    public function __construct(private readonly CalculadoraComercial $calculadora)
    {
    }

    /** Convierte una cotización pendiente en pedido, respetando sus precios cotizados. */
    public function crearDesdeCotizacion(Cotizacion $cotizacion, Usuario $vendedor): Pedido
    {
        return DB::transaction(function () use ($cotizacion, $vendedor) {
            $cotizacion = Cotizacion::with('detalles')->lockForUpdate()->findOrFail($cotizacion->id);

            if (! $cotizacion->estaPendiente()) {
                throw new LogicException("La cotización {$cotizacion->codigo} ya fue {$cotizacion->estado}.");
            }

            $pedido = $this->registrarPedido(
                $cotizacion->id_cliente,
                $vendedor,
                $cotizacion->detalles->map(fn ($d) => [
                    'id_producto' => $d->id_producto,
                    'precio' => (string) $d->precio_unitario,
                    'cantidad' => $d->cantidad,
                ]),
                $cotizacion->id,
                ['origen' => Pedido::DESDE_COTIZACION]
            );

            $cotizacion->update(['estado' => Cotizacion::CONVERTIDA, 'id_usuario' => $vendedor->id]);

            return $pedido;
        }, self::REINTENTOS);
    }

    /**
     * Pedido de mostrador creado directamente por el vendedor.
     *
     * @param  array<int, array{id_producto: int, cantidad: int}>  $items
     */
    public function crearDirecto(Cliente $cliente, array $items, Usuario $vendedor): Pedido
    {
        return DB::transaction(function () use ($cliente, $items, $vendedor) {
            $productos = Producto::whereIn('id', array_column($items, 'id_producto'))->get()->keyBy('id');

            $lineas = collect($items)->map(fn ($item) => [
                'id_producto' => (int) $item['id_producto'],
                'precio' => (string) $productos[$item['id_producto']]->precio,
                'cantidad' => (int) $item['cantidad'],
            ]);

            return $this->registrarPedido($cliente->id, $vendedor, $lineas);
        }, self::REINTENTOS);
    }

    /**
     * Compra del cliente en el portal: queda pendiente, sin vendedor asignado y sin tocar el stock,
     * hasta que el personal verifica el pago y confirma la venta.
     *
     * @param  iterable<array{producto: Producto, precio: string, cantidad: int}>  $lineas  las del carrito
     */
    public function crearEnLinea(Cliente $cliente, iterable $lineas, string $metodoPago, string $entrega, ?string $observaciones): Pedido
    {
        return DB::transaction(function () use ($cliente, $lineas, $metodoPago, $entrega, $observaciones) {
            $items = collect($lineas)->map(fn ($l) => [
                'id_producto' => $l['producto']->id,
                'precio' => (string) $l['producto']->precio,
                'cantidad' => (int) $l['cantidad'],
            ])->values();

            return $this->registrarPedido($cliente->id, null, $items, null, [
                'origen' => Pedido::PORTAL,
                'metodo_pago' => $metodoPago,
                'entrega' => $entrega,
                'observaciones' => $observaciones,
            ]);
        }, self::REINTENTOS);
    }

    /**
     * PROCESA LA VENTA: valida y descuenta stock dentro de una transacción.
     *
     * 1. Bloquea el pedido (SELECT ... FOR UPDATE) para que nadie lo confirme dos veces.
     * 2. Bloquea las filas de los productos en orden de id (evita interbloqueos).
     * 3. Verifica stock de TODOS los productos antes de modificar nada.
     * 4. Descuenta stock y registra un movimiento de salida por producto.
     * 5. Marca el pedido como confirmado.
     * Si algo falla, se lanza una excepción y MySQL deshace todo.
     */
    public function confirmarVenta(Pedido $pedido, Usuario $usuario): Pedido
    {
        return DB::transaction(function () use ($pedido, $usuario) {
            $pedido = Pedido::with('detalles')->lockForUpdate()->findOrFail($pedido->id);

            if (! $pedido->estaPendiente()) {
                throw new LogicException("El pedido {$pedido->codigo} ya está {$pedido->estado}.");
            }

            // Cantidad total pedida por producto (un producto podría repetirse en el detalle)
            $requerido = $pedido->detalles->groupBy('id_producto')->map->sum('cantidad');

            $productos = Producto::whereIn('id', $requerido->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Paso 3: validar todo antes de tocar el stock
            $faltantes = [];
            foreach ($requerido as $idProducto => $cantidad) {
                $producto = $productos[$idProducto] ?? null;
                if (! $producto || $producto->stock < $cantidad) {
                    $disponible = $producto?->stock ?? 0;
                    $codigo = $producto?->codigo_fabrica ?? "#{$idProducto}";
                    $faltantes[] = "{$codigo}: pedido {$cantidad}, disponible {$disponible}";
                }
            }

            if ($faltantes) {
                throw new StockInsuficienteException('Stock insuficiente. ' . implode('; ', $faltantes) . '.');
            }

            // Paso 4: descontar y registrar trazabilidad
            foreach ($requerido as $idProducto => $cantidad) {
                $producto = $productos[$idProducto];
                $stockAnterior = $producto->stock;
                $producto->decrement('stock', $cantidad);

                MovimientoInventario::create([
                    'id_producto' => $producto->id,
                    'tipo' => MovimientoInventario::SALIDA,
                    'cantidad' => $cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockAnterior - $cantidad,
                    'motivo' => "Venta del pedido {$pedido->codigo}",
                    'id_pedido' => $pedido->id,
                    'fecha' => now(),
                    'id_usuario' => $usuario->id,
                ]);
            }

            // Paso 5 (una compra del portal queda a cargo de quien confirma la venta)
            $pedido->update([
                'estado' => Pedido::CONFIRMADO,
                'fecha_confirmacion' => now(),
                'id_usuario' => $pedido->id_usuario ?? $usuario->id,
            ]);

            return $pedido;
        }, self::REINTENTOS);
    }

    /** Anula un pedido. Si ya estaba confirmado, repone el stock con movimientos de entrada. */
    public function anular(Pedido $pedido, Usuario $usuario): Pedido
    {
        return DB::transaction(function () use ($pedido, $usuario) {
            $pedido = Pedido::with('detalles')->lockForUpdate()->findOrFail($pedido->id);

            if ($pedido->estado === Pedido::ANULADO) {
                throw new LogicException("El pedido {$pedido->codigo} ya está anulado.");
            }

            if ($pedido->estado === Pedido::CONFIRMADO) {
                $devolver = $pedido->detalles->groupBy('id_producto')->map->sum('cantidad');
                $productos = Producto::withTrashed()->whereIn('id', $devolver->keys())
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                foreach ($devolver as $idProducto => $cantidad) {
                    $producto = $productos[$idProducto];
                    $stockAnterior = $producto->stock;
                    $producto->increment('stock', $cantidad);

                    MovimientoInventario::create([
                        'id_producto' => $producto->id,
                        'tipo' => MovimientoInventario::ENTRADA,
                        'cantidad' => $cantidad,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $stockAnterior + $cantidad,
                        'motivo' => "Anulación del pedido {$pedido->codigo}",
                        'id_pedido' => $pedido->id,
                        'fecha' => now(),
                        'id_usuario' => $usuario->id,
                    ]);
                }
            }

            $pedido->update(['estado' => Pedido::ANULADO]);

            return $pedido;
        }, self::REINTENTOS);
    }

    /** Guarda cabecera y detalle con los totales del motor de cálculo. */
    private function registrarPedido(int $idCliente, ?Usuario $vendedor, $lineas, ?int $idCotizacion = null, array $extra = []): Pedido
    {
        $calculo = $this->calculadora->calcular($lineas);

        $pedido = Pedido::create($extra + [
            'id_cliente' => $idCliente,
            'id_usuario' => $vendedor?->id,
            'id_cotizacion' => $idCotizacion,
            'fecha' => now(),
            'subtotal' => $calculo['subtotal'],
            'descuento' => $calculo['descuento'],
            'porcentaje_iva' => $calculo['porcentaje_iva'],
            'impuesto' => $calculo['impuesto'],
            'total' => $calculo['total'],
            'tasa_cambio' => $calculo['tasa_cambio'],
            'total_bs' => $calculo['total_bs'],
            'estado' => Pedido::PENDIENTE,
            'origen' => Pedido::MOSTRADOR,
            'estado_pago' => Pedido::SIN_PAGO,
        ]);

        foreach ($calculo['lineas'] as $linea) {
            $pedido->detalles()->create([
                'id_producto' => $linea['id_producto'],
                'cantidad' => $linea['cantidad'],
                'precio_unitario' => $linea['precio_unitario'],
                'subtotal' => $linea['subtotal'],
            ]);
        }

        $pedido->update(['codigo' => 'PED-' . str_pad((string) $pedido->id, 6, '0', STR_PAD_LEFT)]);

        return $pedido;
    }
}
