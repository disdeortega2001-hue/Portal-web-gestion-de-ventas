<?php

namespace App\Support;

use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

/**
 * Existencias reservadas: lo pedido en ventas que aún no se confirman. La existencia física solo baja
 * al confirmar la venta, así que sin esto dos clientes podrían comprar la misma última unidad.
 *
 * Un pedido pendiente reserva durante 72 horas. Después la reserva se libera, salvo que tenga un pago
 * reportado o sea a crédito: esos siguen reservando hasta que el personal los confirme o los anule.
 * Una sola consulta por petición, para no repetirla en cada tarjeta del catálogo.
 */
class Reservas
{
    public const HORAS = 72;

    /** @var array<int, int>|null */
    private ?array $porProducto = null;

    public function de(int $idProducto): int
    {
        return ($this->porProducto ??= $this->consultar())[$idProducto] ?? 0;
    }

    public function olvidar(): void
    {
        $this->porProducto = null;
    }

    /** @return array<int, int> */
    private function consultar(): array
    {
        return DB::table('detalle_pedidos as d')
            ->join('pedidos as p', 'p.id', '=', 'd.id_pedido')
            ->where('p.estado', Pedido::PENDIENTE)
            ->whereNull('p.deleted_at')
            ->where(fn ($q) => $q->where('p.fecha', '>=', now()->subHours(self::HORAS))
                ->orWhere('p.estado_pago', '!=', Pedido::SIN_PAGO)
                ->orWhere('p.a_credito', true))
            ->groupBy('d.id_producto')
            ->selectRaw('d.id_producto, SUM(d.cantidad) as total')
            ->pluck('total', 'id_producto')
            ->map(fn ($n) => (int) $n)
            ->all();
    }
}
