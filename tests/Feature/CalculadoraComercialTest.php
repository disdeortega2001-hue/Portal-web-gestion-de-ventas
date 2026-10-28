<?php

namespace Tests\Feature;

use App\Services\CalculadoraComercial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Exactitud del motor de cálculo (problema 2 del diagnóstico: 16,67 % de cálculos erróneos).
 * Impuesto según la moneda de pago: IGTF 3 % en dólares, IVA 16 % en bolívares. Sin descuentos.
 * Cada resultado esperado está calculado a mano en el comentario de la prueba.
 */
class CalculadoraComercialTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    private CalculadoraComercial $calculadora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
        $this->calculadora = app(CalculadoraComercial::class);
    }

    private function linea(string $precio, int $cantidad): array
    {
        return ['precio' => $precio, 'cantidad' => $cantidad];
    }

    public function test_pago_en_bolivares_lleva_iva(): void
    {
        // 2 × 45,00 + 3 × 12,80 = 128,40 · IVA 16 % = 20,544 → 20,54 · total 148,94 · × 36,50 = 5.436,31 Bs.
        $r = $this->calculadora->calcular([$this->linea('45.00', 2), $this->linea('12.80', 3)], 'VES');

        $this->assertSame('128.40', $r['subtotal']);
        $this->assertSame('IVA', $r['tipo_impuesto']);
        $this->assertSame(16.0, $r['porcentaje_impuesto']);
        $this->assertSame('20.54', $r['impuesto']);
        $this->assertSame('148.94', $r['total']);
        $this->assertSame('5436.31', $r['total_bs']);
        $this->assertSame('VES', $r['moneda_pago']);
    }

    public function test_pago_en_dolares_lleva_igtf_y_no_iva(): void
    {
        // 128,40 · IGTF 3 % = 3,852 → 3,85 · total 132,25 · × 36,50 = 4.827,13 Bs. (referencial)
        $r = $this->calculadora->calcular([$this->linea('45.00', 2), $this->linea('12.80', 3)], 'USD');

        $this->assertSame('IGTF', $r['tipo_impuesto']);
        $this->assertSame(3.0, $r['porcentaje_impuesto']);
        $this->assertSame('3.85', $r['impuesto']);
        $this->assertSame('132.25', $r['total']);
        $this->assertSame('4827.13', $r['total_bs']);
    }

    public function test_por_defecto_se_calcula_en_dolares(): void
    {
        $this->assertSame('IGTF', $this->calculadora->calcular([$this->linea('10.00', 1)])['tipo_impuesto']);
    }

    public function test_no_hay_descuento_aunque_el_monto_sea_grande(): void
    {
        // 10 × 1.000,00 = 10.000,00 · IVA 1.600,00 · total 11.600,00 (sin descuento por volumen)
        $r = $this->calculadora->calcular([$this->linea('1000.00', 10)], 'VES');

        $this->assertSame('0.00', $r['descuento']);
        $this->assertSame('1600.00', $r['impuesto']);
        $this->assertSame('11600.00', $r['total']);
    }

    public function test_evita_errores_de_punto_flotante(): void
    {
        // 3 × 0,10 + 0,20 = 0,50 exacto (en float daría 0,50000000000000011)
        $r = $this->calculadora->calcular([$this->linea('0.10', 3), $this->linea('0.20', 1)], 'VES');
        $this->assertSame('0.50', $r['subtotal']);

        // 3 × 19,99 = 59,97 · IVA 9,5952 → 9,60 · total 69,57
        $r = $this->calculadora->calcular([$this->linea('19.99', 3)], 'VES');
        $this->assertSame('59.97', $r['subtotal']);
        $this->assertSame('9.60', $r['impuesto']);
        $this->assertSame('69.57', $r['total']);
    }

    public function test_usa_los_porcentajes_y_la_tasa_configurados(): void
    {
        $this->configurar(['porcentaje_iva' => '0', 'porcentaje_igtf' => '5', 'tasa_cambio' => '40']);

        $iva = $this->calculadora->calcular([$this->linea('1000.00', 1)], 'VES');
        $this->assertSame('1000.00', $iva['total']);
        $this->assertSame('40000.00', $iva['total_bs']);

        $igtf = $this->calculadora->calcular([$this->linea('1000.00', 1)], 'USD');
        $this->assertSame('50.00', $igtf['impuesto']);
        $this->assertSame('1050.00', $igtf['total']);
    }

    public function test_carrito_vacio_da_ceros(): void
    {
        $r = $this->calculadora->calcular([]);

        $this->assertSame('0.00', $r['subtotal']);
        $this->assertSame('0.00', $r['total']);
        $this->assertSame('0.00', $r['total_bs']);
    }
}
