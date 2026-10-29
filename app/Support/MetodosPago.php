<?php

namespace App\Support;

use App\Models\Configuracion;

/**
 * Métodos de pago que acepta Rialto 7 y los datos que el cliente necesita para pagar.
 *
 * Los datos (banco, cuenta, teléfono, correo) los carga el personal administrativo en
 * Administración → Configuración del portal. Todos los métodos se ofrecen mientras estén activos;
 * si aún no tienen sus datos cargados, el cliente ve que la empresa se los envía por WhatsApp.
 *
 * - En línea (Pago Móvil, transferencia, Zelle): el cliente paga y reporta la referencia y el comprobante.
 * - En la tienda (efectivo, punto de venta): el cliente paga al retirar y el personal registra el pago.
 */
class MetodosPago
{
    public const METODOS = [
        'pago_movil' => [
            'nombre' => 'Pago Móvil',
            'moneda' => 'VES',
            'en_linea' => true,
            'descripcion' => 'En bolívares, a la tasa del día.',
            'campos' => ['pago_movil_banco' => 'Banco', 'pago_movil_telefono' => 'Teléfono', 'pago_movil_documento' => 'Cédula o RIF'],
        ],
        'transferencia' => [
            'nombre' => 'Transferencia bancaria',
            'moneda' => 'VES',
            'en_linea' => true,
            'descripcion' => 'En bolívares, a la tasa del día.',
            'campos' => ['transferencia_banco' => 'Banco', 'transferencia_cuenta' => 'Número de cuenta', 'transferencia_titular' => 'Titular', 'transferencia_documento' => 'RIF'],
        ],
        'zelle' => [
            'nombre' => 'Zelle',
            'moneda' => 'USD',
            'en_linea' => true,
            'descripcion' => 'En dólares.',
            'campos' => ['zelle_correo' => 'Correo', 'zelle_titular' => 'Titular'],
        ],
        'efectivo' => [
            'nombre' => 'Efectivo en dólares en la tienda',
            'moneda' => 'USD',
            'en_linea' => false,
            'descripcion' => 'En dólares, al retirar en el Edificio Campagna.',
            'campos' => [],
        ],
        'punto_venta' => [
            'nombre' => 'Punto de venta en la tienda',
            'moneda' => 'VES',
            'en_linea' => false,
            'descripcion' => 'Tarjeta de débito o crédito al retirar.',
            'campos' => [],
        ],
    ];

    /** Claves de configuración de todos los métodos (para el formulario de administración). */
    public static function clavesConfiguracion(): array
    {
        $claves = [];
        foreach (self::METODOS as $metodo => $definicion) {
            $claves[] = "{$metodo}_activo";
            array_push($claves, ...array_keys($definicion['campos']));
        }

        return $claves;
    }

    public static function existe(?string $metodo): bool
    {
        return isset(self::METODOS[$metodo]);
    }

    public static function nombre(?string $metodo): string
    {
        return self::METODOS[$metodo]['nombre'] ?? 'Sin definir';
    }

    public static function moneda(string $metodo): string
    {
        return self::METODOS[$metodo]['moneda'];
    }

    public static function enLinea(?string $metodo): bool
    {
        return self::METODOS[$metodo]['en_linea'] ?? false;
    }

    /** Datos para pagar: [etiqueta => valor]. */
    public static function datos(string $metodo): array
    {
        $datos = [];
        foreach (self::METODOS[$metodo]['campos'] as $clave => $etiqueta) {
            $datos[$etiqueta] = trim((string) Configuracion::valor($clave, ''));
        }

        return $datos;
    }

    /** Métodos que el cliente puede elegir hoy (activos por defecto), con los datos que ya estén cargados. */
    public static function disponibles(): array
    {
        $disponibles = [];
        foreach (self::METODOS as $metodo => $definicion) {
            if (Configuracion::valor("{$metodo}_activo", '1') !== '1') {
                continue;
            }
            $disponibles[$metodo] = $definicion + ['datos' => array_filter(self::datos($metodo), fn ($v) => $v !== '')];
        }

        return $disponibles;
    }

    /** Monto a pagar en la moneda del método: USD tal cual o Bs. a la tasa guardada en el pedido. */
    public static function montoEnMoneda(string $moneda, string|float $totalUsd, string|float $totalBs): string
    {
        return $moneda === 'USD' ? (string) $totalUsd : (string) $totalBs;
    }
}
