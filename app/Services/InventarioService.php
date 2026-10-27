<?php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Movimientos manuales de inventario: entrada de mercancía, salida
 * (merma, uso interno) y ajuste por conteo físico.
 */
class InventarioService
{
    /**
     * @param  int  $cantidad  En entrada/salida: unidades a mover. En ajuste: stock contado físicamente.
     */
    public function registrar(Producto $producto, string $tipo, int $cantidad, string $motivo, Usuario $usuario): MovimientoInventario
    {
        return DB::transaction(function () use ($producto, $tipo, $cantidad, $motivo, $usuario) {
            $producto = Producto::lockForUpdate()->findOrFail($producto->id);
            $stockAnterior = $producto->stock;

            [$stockNuevo, $cantidadMovida] = match ($tipo) {
                MovimientoInventario::ENTRADA => [$stockAnterior + $cantidad, $cantidad],
                MovimientoInventario::SALIDA => [$stockAnterior - $cantidad, $cantidad],
                MovimientoInventario::AJUSTE => [$cantidad, $cantidad - $stockAnterior],
            };

            if ($stockNuevo < 0) {
                throw new StockInsuficienteException(
                    "No se pueden retirar {$cantidad} unidades de {$producto->codigo_fabrica}: solo hay {$stockAnterior}."
                );
            }

            $producto->update(['stock' => $stockNuevo]);

            return MovimientoInventario::create([
                'id_producto' => $producto->id,
                'tipo' => $tipo,
                'cantidad' => $cantidadMovida,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
                'motivo' => $motivo,
                'fecha' => now(),
                'id_usuario' => $usuario->id,
            ]);
        });
    }
}
