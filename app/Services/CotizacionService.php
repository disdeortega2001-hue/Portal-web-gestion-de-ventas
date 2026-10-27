<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Cotizacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Crea cotizaciones a partir de las líneas del carrito.
 */
class CotizacionService
{
    public function __construct(private readonly CalculadoraComercial $calculadora)
    {
    }

    /**
     * @param  Collection<int, array{producto: \App\Models\Producto, precio: string, cantidad: int}>  $lineas
     */
    public function crear(Cliente $cliente, Collection $lineas, ?string $observaciones = null): Cotizacion
    {
        return DB::transaction(function () use ($cliente, $lineas, $observaciones) {
            $calculo = $this->calculadora->calcular($lineas);

            $cotizacion = Cotizacion::create([
                'id_cliente' => $cliente->id,
                'fecha' => now(),
                'subtotal' => $calculo['subtotal'],
                'descuento' => $calculo['descuento'],
                'porcentaje_iva' => $calculo['porcentaje_iva'],
                'impuesto' => $calculo['impuesto'],
                'total' => $calculo['total'],
                'tasa_cambio' => $calculo['tasa_cambio'],
                'total_bs' => $calculo['total_bs'],
                'estado' => Cotizacion::PENDIENTE,
                'observaciones' => $observaciones,
            ]);

            foreach ($calculo['lineas'] as $linea) {
                $cotizacion->detalles()->create([
                    'id_producto' => $linea['producto']->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'subtotal' => $linea['subtotal'],
                ]);
            }

            $cotizacion->update(['codigo' => 'COT-' . str_pad((string) $cotizacion->id, 6, '0', STR_PAD_LEFT)]);

            return $cotizacion;
        });
    }
}
