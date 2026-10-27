<?php

namespace App\Services;

use App\Models\Configuracion;

/**
 * Motor de cálculo comercial. Es el ÚNICO lugar donde se calculan subtotal,
 * descuento, IVA, total y conversión a Bs.
 *
 * Trabaja en centavos (enteros) para evitar errores de redondeo de punto
 * flotante: 0.1 + 0.2 no da 0.3 en float, pero 10 + 20 sí da 30 en enteros.
 */
class CalculadoraComercial
{
    /**
     * @param  iterable<array{precio: string|float, cantidad: int}>  $lineas
     * @return array{lineas: array, subtotal: string, porcentaje_descuento: float, descuento: string,
     *               porcentaje_iva: float, impuesto: string, total: string, tasa_cambio: float, total_bs: string}
     */
    public function calcular(iterable $lineas): array
    {
        $porcentajeIva = Configuracion::porcentajeIva();
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

        $porcentajeDescuento = $this->porcentajeDescuento($subtotal);
        $descuento = (int) round($subtotal * $porcentajeDescuento / 100);
        $base = $subtotal - $descuento;
        $impuesto = (int) round($base * $porcentajeIva / 100);
        $total = $base + $impuesto;
        $totalBs = (int) round($total * $tasa);

        return [
            'lineas' => $lineasCalculadas,
            'subtotal' => $this->aDecimal($subtotal),
            'porcentaje_descuento' => $porcentajeDescuento,
            'descuento' => $this->aDecimal($descuento),
            'porcentaje_iva' => $porcentajeIva,
            'impuesto' => $this->aDecimal($impuesto),
            'total' => $this->aDecimal($total),
            'tasa_cambio' => $tasa,
            'total_bs' => $this->aDecimal($totalBs),
        ];
    }

    /** Descuento por volumen: aplica si el subtotal alcanza el monto mínimo configurado. */
    private function porcentajeDescuento(int $subtotalCentavos): float
    {
        $porcentaje = (float) Configuracion::valor('descuento_porcentaje', 0);
        $minimo = $this->aCentavos(Configuracion::valor('descuento_monto_minimo', 0));

        return ($porcentaje > 0 && $subtotalCentavos >= $minimo) ? $porcentaje : 0.0;
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
