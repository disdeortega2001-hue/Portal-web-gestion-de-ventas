<?php

namespace App\Services;

use App\Models\Configuracion;

/**
 * Motor de cálculo comercial. Es el ÚNICO lugar donde se calculan subtotal,
 * impuesto, total y conversión a Bs.
 *
 * El impuesto depende de la moneda en que paga el cliente:
 * - en dólares (USD): IGTF, 3 % por defecto, sin IVA;
 * - en bolívares (VES): IVA, 16 % por defecto.
 *
 * Trabaja en centavos (enteros) para evitar errores de redondeo de punto
 * flotante: 0.1 + 0.2 no da 0.3 en float, pero 10 + 20 sí da 30 en enteros.
 */
class CalculadoraComercial
{
    public const USD = 'USD';
    public const VES = 'VES';

    /**
     * @param  iterable<array{precio: string|float, cantidad: int}>  $lineas
     * @param  string  $moneda  moneda de pago: USD (IGTF) o VES (IVA)
     * @return array{lineas: array, subtotal: string, descuento: string, moneda_pago: string, tipo_impuesto: string,
     *               porcentaje_impuesto: float, impuesto: string, total: string, tasa_cambio: float, total_bs: string}
     */
    public function calcular(iterable $lineas, string $moneda = self::USD): array
    {
        [$tipo, $porcentaje] = self::impuesto($moneda);
        $tasa = Configuracion::tasaCambio();

        $lineasCalculadas = [];
        $subtotal = 0;

        foreach ($lineas as $clave => $linea) {
            $precio = $this->aCentavos($linea['precio']);
            $cantidad = (int) $linea['cantidad'];
            $importe = $precio * $cantidad;
            $subtotal += $importe;

            $lineasCalculadas[$clave] = $linea + [
                'precio_unitario' => $this->aDecimal($precio),
                'subtotal' => $this->aDecimal($importe),
            ];
        }

        $impuesto = (int) round($subtotal * $porcentaje / 100);
        $total = $subtotal + $impuesto;
        $totalBs = (int) round($total * $tasa);

        return [
            'lineas' => $lineasCalculadas,
            'subtotal' => $this->aDecimal($subtotal),
            'descuento' => '0.00',
            'moneda_pago' => $moneda === self::VES ? self::VES : self::USD,
            'tipo_impuesto' => $tipo,
            'porcentaje_impuesto' => $porcentaje,
            'impuesto' => $this->aDecimal($impuesto),
            'total' => $this->aDecimal($total),
            'tasa_cambio' => $tasa,
            'total_bs' => $this->aDecimal($totalBs),
        ];
    }

    /** @return array{0: string, 1: float} [tipo de impuesto, porcentaje] según la moneda de pago */
    public static function impuesto(string $moneda): array
    {
        return $moneda === self::VES
            ? ['IVA', Configuracion::porcentajeIva()]
            : ['IGTF', Configuracion::porcentajeIgtf()];
    }

    private function aCentavos(string|float|int $monto): int
    {
        return (int) round(((float) $monto) * 100);
    }

    private function aDecimal(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
