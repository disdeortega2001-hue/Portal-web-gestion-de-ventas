<?php

namespace Tests\Feature;

use App\Services\CalculadoraComercial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Exactitud del motor de cálculo (problema 2 del diagnóstico: 16,67 % de cálculos erróneos).
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

    public function test_calcula_subtotal_iva_total_y_bolivares(): void
    {
        // 2 × 45,00 + 3 × 12,80 = 128,40 · IVA 16 % = 20,544 → 20,54 · total 148,94 · × 36,50 = 5.436,31 Bs.
        $r = $this->calculadora->calcular([$this->linea('45.00', 2), $this->linea('12.80', 3)]);

        $this->assertSame('128.40', $r['subtotal']);
        $this->assertSame('0.00', $r['descuento']);
        $this->assertSame('20.54', $r['impuesto']);
        $this->assertSame('148.94', $r['total']);
        $this->assertSame('5436.31', $r['total_bs']);
        $this->assertSame('90.00', $r['lineas'][0]['subtotal']);
        $this->assertSame('38.40', $r['lineas'][1]['subtotal']);
    }

    public function test_no_tiene_errores_de_punto_flotante(): void
    {
        // En float, 0.1 × 3 + 0.2 = 0.5000000000000001; en centavos da exactamente 0,50
        $r = $this->calculadora->calcular([$this->linea('0.10', 3), $this->linea('0.20', 1)]);
        $this->assertSame('0.50', $r['subtotal']);

        // 3 × 19,99 = 59,97 · IVA 9,5952 → 9,60 · total 69,57
        $r = $this->calculadora->calcular([$this->linea('19.99', 3)]);
        $this->assertSame('59.97', $r['subtotal']);
        $this->assertSame('9.60', $r['impuesto']);
        $this->assertSame('69.57', $r['total']);
    }

    public function test_aplica_descuento_al_alcanzar_el_monto_minimo_y_el_iva_va_sobre_la_base_descontada(): void
    {
        // 2 × 250 = 500 (justo el mínimo) · −5 % = −25 · base 475 · IVA 76 · total 551 · Bs. 20.111,50
        $r = $this->calculadora->calcular([$this->linea('250.00', 2)]);

        $this->assertSame(5.0, $r['porcentaje_descuento']);
        $this->assertSame('25.00', $r['descuento']);
        $this->assertSame('76.00', $r['impuesto']);
        $this->assertSame('551.00', $r['total']);
        $this->assertSame('20111.50', $r['total_bs']);
    }

    public function test_no_aplica_descuento_por_debajo_del_minimo(): void
    {
        // 499,99 < 500 · IVA 79,9984 → 80,00 · total 579,99
        $r = $this->calculadora->calcular([$this->linea('499.99', 1)]);

        $this->assertSame('0.00', $r['descuento']);
        $this->assertSame('80.00', $r['impuesto']);
        $this->assertSame('579.99', $r['total']);
    }

    public function test_usa_los_parametros_vigentes_de_configuracion(): void
    {
        $this->configurar(['porcentaje_iva' => '0', 'tasa_cambio' => '40', 'descuento_porcentaje' => '0']);

        $r = $this->calculadora->calcular([$this->linea('1000.00', 1)]);

        $this->assertSame('0.00', $r['descuento']);
        $this->assertSame('0.00', $r['impuesto']);
        $this->assertSame('1000.00', $r['total']);
        $this->assertSame('40000.00', $r['total_bs']);
    }

    public function test_carrito_vacio_da_ceros(): void
    {
        $r = $this->calculadora->calcular([]);

        $this->assertSame('0.00', $r['total']);
        $this->assertSame('0.00', $r['total_bs']);
    }
}
