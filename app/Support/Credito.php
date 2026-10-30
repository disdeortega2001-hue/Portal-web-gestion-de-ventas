<?php

namespace App\Support;

use App\Models\Cliente;
use App\Models\Pedido;

/**
 * Ventas a crédito.
 *
 * - El personal administrativo (o el directivo) autoriza el crédito en la ficha del cliente y le
 *   fija un límite en dólares. Sin límite, puede comprar a crédito por cualquier monto.
 * - Al comprar, el cliente elige en qué moneda abonará: en dólares lleva IGTF y en bolívares IVA,
 *   igual que una compra de contado.
 * - La deuda se lleva en dólares; cada abono en bolívares se descuenta a la tasa del día en que se paga.
 * - Cada 30 días desde la venta (o desde el último aviso) se le recuerda al cliente lo que debe.
 */
class Credito
{
    /** Días entre un recordatorio de pago y el siguiente. */
    public const DIAS_RECORDATORIO = 30;

    public const OPCIONES = [
        'credito_usd' => [
            'nombre' => 'Crédito en dólares',
            'moneda' => 'USD',
            'descripcion' => 'Te lo llevas ahora y abonas en dólares. Te recordamos el saldo cada 30 días.',
        ],
        'credito_ves' => [
            'nombre' => 'Crédito en bolívares',
            'moneda' => 'VES',
            'descripcion' => 'Te lo llevas ahora y abonas en bolívares a la tasa del día. Te recordamos el saldo cada 30 días.',
        ],
    ];

    public static function esOpcion(?string $opcion): bool
    {
        return isset(self::OPCIONES[$opcion]);
    }

    public static function moneda(string $opcion): string
    {
        return self::OPCIONES[$opcion]['moneda'];
    }

    /** Lo que el cliente debe hoy, en USD, sumando sus compras a crédito vigentes. */
    public static function deudaUsd(Cliente $cliente): float
    {
        return round(Pedido::with('pagos')
            ->where('id_cliente', $cliente->id)
            ->where('a_credito', true)
            ->where('estado', '!=', Pedido::ANULADO)
            ->where('estado_pago', '!=', Pedido::PAGADO)
            ->get()
            ->sum(fn (Pedido $p) => (float) $p->saldoUsd()), 2);
    }

    /** Cuánto más puede comprar a crédito (null: sin límite). */
    public static function disponibleUsd(Cliente $cliente): ?float
    {
        if ($cliente->limite_credito === null) {
            return null;
        }

        return max(0, round((float) $cliente->limite_credito - self::deudaUsd($cliente), 2));
    }

    /** ¿Puede comprar a crédito este monto (en USD)? */
    public static function permite(Cliente $cliente, float $totalUsd): bool
    {
        if (! $cliente->credito_habilitado) {
            return false;
        }
        $disponible = self::disponibleUsd($cliente);

        return $disponible === null || $totalUsd <= $disponible + 0.005;
    }
}
