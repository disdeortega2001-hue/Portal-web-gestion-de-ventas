<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\Cotizacion;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Impuesto según la moneda de pago (IGTF en dólares, IVA en bolívares) y tasa oficial del BCV.
 */
class ImpuestosYTasaBcvTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    /** Fragmento real de la página del BCV con el recuadro del dólar y la fecha valor. */
    private const HTML_BCV = <<<'HTML'
        <div id="euro">…</div>
        <div id="dolar" class="col-sm-12 col-xs-12 ">
          <div class="field-content"><div class="row recuadrotsmc">
            <div class="col-sm-6 col-xs-6"><span> USD</span></div>
            <div class="col-sm-6 col-xs-6 centrado textp"> <strong class="strong-tb">888,11830000</strong>  </div>
          </div></div>
        </div>
        <div class="pull-right dinpro center">
        Fecha Valor: <span class="date-display-single" property="dc:date" datatype="xsd:dateTime" content="2026-10-28T00:00:00-04:00">Miércoles, 28 Octubre  2026</span>
        </div>
        HTML;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
    }

    public function test_el_comando_trae_la_tasa_del_bcv_con_su_fecha_valor(): void
    {
        Http::fake(['www.bcv.org.ve/*' => Http::response(self::HTML_BCV)]);

        $this->artisan('tasa:bcv')->assertSuccessful();

        $this->assertSame(888.1183, Configuracion::tasaCambio());
        $this->assertSame('BCV', Configuracion::valor('tasa_fuente'));
        $this->assertSame('2026-10-28', Configuracion::valor('tasa_fecha_valor'));
    }

    public function test_si_el_bcv_falla_la_tasa_anterior_sigue_vigente(): void
    {
        Http::fake(['www.bcv.org.ve/*' => Http::response('mantenimiento', 503)]);
        $this->artisan('tasa:bcv')->assertFailed();

        Http::fake(['www.bcv.org.ve/*' => Http::response('<html>sin tasa</html>')]);
        $this->artisan('tasa:bcv')->assertFailed();

        $this->assertSame(36.5, Configuracion::tasaCambio());
    }

    public function test_el_boton_de_la_configuracion_actualiza_la_tasa(): void
    {
        Http::fake(['www.bcv.org.ve/*' => Http::response(self::HTML_BCV)]);

        $this->actingAs($this->crearUsuario('administrativo'))
            ->post(route('admin.configuracion.tasa-bcv'))
            ->assertRedirect(route('admin.configuracion.edit'))
            ->assertSessionHas('exito');

        $this->assertSame(888.1183, Configuracion::tasaCambio());
        $this->actingAs($this->crearUsuario('vendedor'))->post(route('admin.configuracion.tasa-bcv'))->assertForbidden();
    }

    public function test_la_compra_en_dolares_lleva_igtf_y_en_bolivares_iva(): void
    {
        $usuario = $this->crearUsuario('cliente');
        $this->crearCliente($usuario);
        $producto = $this->crearProducto(['precio' => '100.00', 'stock' => 10]);

        // Zelle (dólares): 100 + IGTF 3 % = 103
        $this->actingAs($usuario)->post(route('carrito.agregar', $producto), ['cantidad' => 1]);
        $this->actingAs($usuario)->post(route('compra.store'), ['metodo_pago' => 'zelle', 'entrega' => 'retiro']);
        $enDolares = Pedido::latest('id')->first();
        $this->assertSame(['USD', 'IGTF', '3.00', '103.00'], [$enDolares->moneda_pago, $enDolares->tipo_impuesto, (string) $enDolares->porcentaje_impuesto, (string) $enDolares->total]);

        // Pago Móvil (bolívares): 100 + IVA 16 % = 116 → Bs. 4.234,00 a 36,50
        $this->actingAs($usuario)->post(route('carrito.agregar', $producto), ['cantidad' => 1]);
        $this->actingAs($usuario)->post(route('compra.store'), ['metodo_pago' => 'pago_movil', 'entrega' => 'retiro']);
        $enBolivares = Pedido::latest('id')->first();
        $this->assertSame(['VES', 'IVA', '116.00', '4234.00'], [$enBolivares->moneda_pago, $enBolivares->tipo_impuesto, (string) $enBolivares->total, (string) $enBolivares->total_bs]);
    }

    public function test_la_cotizacion_usa_la_moneda_elegida_en_la_lista(): void
    {
        $producto = $this->crearProducto(['precio' => '100.00', 'stock' => 10]);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);

        $this->get(route('carrito.index'))->assertSee('IGTF (3%)');
        $this->post(route('carrito.moneda'), ['moneda' => 'VES'])->assertRedirect(route('carrito.index'));
        $this->get(route('carrito.index'))->assertSee('IVA (16%)');

        $this->post(route('cotizaciones.store'), [
            'tipo_identificacion' => 'V', 'numero_identificacion' => '20111222', 'nombre' => 'Pedro Pérez',
            'telefono' => '0414-1112233', 'direccion' => 'Finca La Ceiba, Zaraza',
        ]);
        $cotizacion = Cotizacion::firstOrFail();
        $this->assertSame(['VES', 'IVA', '116.00'], [$cotizacion->moneda_pago, $cotizacion->tipo_impuesto, (string) $cotizacion->total]);
    }

    public function test_la_venta_de_mostrador_cobra_segun_la_moneda(): void
    {
        $producto = $this->crearProducto(['precio' => '50.00', 'stock' => 10]);
        $cliente = $this->crearCliente();
        $vendedor = $this->crearUsuario('vendedor');

        $this->actingAs($vendedor)->post(route('gestion.pedidos.store'), [
            'id_cliente' => $cliente->id, 'moneda_pago' => 'USD', 'items' => [['id_producto' => $producto->id, 'cantidad' => 2]],
        ])->assertRedirect();

        $pedido = Pedido::firstOrFail();
        $this->assertSame(['IGTF', '3.00', '103.00'], [$pedido->tipo_impuesto, (string) $pedido->impuesto, (string) $pedido->total]);
    }
}
