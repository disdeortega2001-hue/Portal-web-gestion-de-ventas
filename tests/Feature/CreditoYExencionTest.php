<?php

namespace Tests\Feature;

use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Services\CalculadoraComercial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Ventas a crédito (autorización, límite, abonos, saldo y recordatorio cada 30 días)
 * y productos exentos de IVA (los implementos Marchesan Tatu).
 */
class CreditoYExencionTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
    }

    /** Cliente con cuenta, crédito autorizado y un producto de USD 100 × 2 en su lista. */
    private function clienteACredito(?string $limite = null, int $cantidad = 2): array
    {
        $usuario = $this->crearUsuario('cliente');
        $cliente = $this->crearCliente($usuario);
        $cliente->forceFill(['credito_habilitado' => true, 'limite_credito' => $limite])->save();
        $producto = $this->crearProducto(['stock' => 10, 'precio' => '100.00']);
        $this->actingAs($usuario)->post(route('carrito.agregar', $producto), ['cantidad' => $cantidad]);

        return [$usuario, $cliente, $producto];
    }

    private function comprarACredito(Usuario $usuario, string $opcion = 'credito_usd'): Pedido
    {
        $this->actingAs($usuario)->post(route('compra.store'), ['metodo_pago' => $opcion, 'entrega' => 'retiro'])
            ->assertRedirect()->assertSessionHasNoErrors();

        return Pedido::latest('id')->firstOrFail();
    }

    /* ---------- Exentos de IVA ---------- */

    public function test_los_productos_exentos_no_pagan_iva_pero_si_igtf(): void
    {
        $calculadora = app(CalculadoraComercial::class);
        $lineas = [
            ['precio' => '100.00', 'cantidad' => 1, 'exento' => true],    // implemento Tatu
            ['precio' => '50.00', 'cantidad' => 2, 'exento' => false],
        ];

        $enBolivares = $calculadora->calcular($lineas, 'VES');
        $this->assertSame('200.00', $enBolivares['subtotal']);
        $this->assertSame('100.00', $enBolivares['monto_exento']);
        $this->assertSame('16.00', $enBolivares['impuesto']);          // 16 % solo de los USD 100 gravados
        $this->assertSame('216.00', $enBolivares['total']);

        $enDolares = $calculadora->calcular($lineas, 'USD');
        $this->assertSame('6.00', $enDolares['impuesto']);             // el IGTF grava todo el pago
    }

    public function test_la_compra_guarda_que_linea_es_exenta_y_el_monto_exento(): void
    {
        $usuario = $this->crearUsuario('cliente');
        $this->crearCliente($usuario);
        $tatu = $this->crearProducto(['marca' => 'Marchesan Tatu', 'precio' => '300.00', 'exento_iva' => true]);
        $this->actingAs($usuario)->post(route('carrito.agregar', $tatu), ['cantidad' => 1]);

        $this->post(route('compra.store'), ['metodo_pago' => 'pago_movil', 'entrega' => 'retiro'])->assertRedirect();
        $pedido = Pedido::latest('id')->firstOrFail();

        $this->assertSame('300.00', (string) $pedido->monto_exento);
        $this->assertSame('0.00', (string) $pedido->impuesto);
        $this->assertTrue($pedido->detalles->first()->exento_iva);
        $this->get(route('catalogo.show', $tatu))->assertSee('Exento de IVA');
    }

    public function test_el_administrativo_marca_un_producto_como_exento(): void
    {
        $producto = $this->crearProducto();
        $datos = $producto->only(['codigo_fabrica', 'nombre', 'marca', 'precio', 'id_categoria']) + ['activo' => '1', 'exento_iva' => '1'];

        $this->actingAs($this->crearUsuario('administrativo'))->put(route('admin.productos.update', $producto), $datos)->assertRedirect();

        $this->assertTrue($producto->fresh()->exento_iva);
    }

    /* ---------- Crédito ---------- */

    public function test_solo_el_cliente_autorizado_ve_y_usa_el_credito(): void
    {
        $usuario = $this->crearUsuario('cliente');
        $this->crearCliente($usuario);
        $this->actingAs($usuario)->post(route('carrito.agregar', $this->crearProducto()), ['cantidad' => 1]);

        $this->get(route('compra.create'))->assertOk()->assertDontSee('Crédito en dólares');
        $this->post(route('compra.store'), ['metodo_pago' => 'credito_usd', 'entrega' => 'retiro'])->assertSessionHasErrors('metodo_pago');
        $this->assertSame(0, Pedido::count());
    }

    public function test_el_cliente_compra_a_credito_y_abona_hasta_saldar(): void
    {
        [$usuario] = $this->clienteACredito();
        $this->get(route('compra.create'))->assertSee('Crédito en dólares')->assertSee('Sin límite');

        $pedido = $this->comprarACredito($usuario);
        $this->assertTrue($pedido->a_credito);
        $this->assertNull($pedido->metodo_pago);
        $this->assertSame('206.00', (string) $pedido->total);   // 200 + IGTF 3 %
        $this->assertSame('Crédito por pagar', $pedido->etiquetaPago());

        // El personal confirma la venta: puede retirarla sin haber pagado
        $admin = $this->crearUsuario('administrativo');
        $this->actingAs($admin)->post(route('gestion.pedidos.confirmar', $pedido))->assertSessionHas('exito');
        $this->assertTrue($pedido->fresh()->puedeEntregarse());

        // Abono reportado por el cliente con Pago Móvil (Bs. a la tasa de hoy: 36,50)
        $this->actingAs($usuario)->post(route('cuenta.pedidos.pago', $pedido), [
            'metodo' => 'pago_movil', 'referencia' => '12345678', 'fecha_pago' => now()->toDateString(), 'monto' => '3650.00',
        ])->assertSessionHas('exito');
        $pago = Pago::latest('id')->first();
        $this->assertSame('100.00', (string) $pago->monto_usd);

        $this->actingAs($admin)->post(route('gestion.pagos.verificar', $pago))->assertSessionHas('aviso_cliente');
        $pedido->refresh();
        $this->assertSame(Pedido::PAGO_PARCIAL, $pedido->estado_pago);
        $this->assertSame('106.00', $pedido->saldoUsd());

        // Un abono que pasa del saldo se rechaza; el resto se cobra en la tienda y salda la deuda
        $this->post(route('gestion.pedidos.pagos.store', $pedido), ['metodo' => 'efectivo', 'monto' => '200'])->assertSessionHas('error');
        $this->post(route('gestion.pedidos.pagos.store', $pedido), ['metodo' => 'efectivo', 'monto' => '106'])->assertSessionHas('exito');
        $this->assertSame(Pedido::PAGADO, $pedido->fresh()->estado_pago);
        $this->assertTrue($pedido->fresh()->tieneComprobante());
    }

    public function test_el_limite_de_credito_se_respeta(): void
    {
        [$usuario] = $this->clienteACredito(limite: '150.00');   // el pedido es de USD 206

        $this->get(route('compra.create'))->assertSee('Pasa de tu crédito disponible');
        $this->post(route('compra.store'), ['metodo_pago' => 'credito_usd', 'entrega' => 'retiro'])->assertSessionHasErrors('metodo_pago');
        $this->assertSame(0, Pedido::count());
    }

    public function test_venta_de_mostrador_a_credito_dentro_del_limite(): void
    {
        $cliente = $this->crearCliente();
        $cliente->forceFill(['credito_habilitado' => true, 'limite_credito' => '250.00'])->save();
        $producto = $this->crearProducto(['stock' => 10, 'precio' => '100.00']);
        $vendedor = $this->crearUsuario('vendedor');
        $datos = ['id_cliente' => $cliente->id, 'moneda_pago' => 'USD', 'a_credito' => '1'];

        $this->actingAs($vendedor)->post(route('gestion.pedidos.store'), $datos + ['items' => [['id_producto' => $producto->id, 'cantidad' => 2]]])->assertRedirect();
        $this->assertTrue(Pedido::firstOrFail()->a_credito);

        // Otro de USD 103 ya pasa del límite (debe 206 de 250): no se crea
        $this->post(route('gestion.pedidos.store'), $datos + ['items' => [['id_producto' => $producto->id, 'cantidad' => 1]]])->assertSessionHas('error');
        $this->assertSame(1, Pedido::count());
    }

    public function test_el_recordatorio_sale_a_los_30_dias_y_luego_cada_30(): void
    {
        Notification::fake();
        [$usuario] = $this->clienteACredito();
        $pedido = $this->comprarACredito($usuario);
        $this->actingAs($this->crearUsuario('administrativo'))->post(route('gestion.pedidos.confirmar', $pedido));

        $this->travel(29)->days();
        $this->artisan('credito:recordatorios')->assertSuccessful();
        $this->assertSame(0, $pedido->fresh()->recordatorios_enviados);

        $this->travel(2)->days();
        $this->artisan('credito:recordatorios')->assertSuccessful();
        $this->assertSame(1, $pedido->fresh()->recordatorios_enviados);
        Notification::assertSentOnDemandTimes(\App\Notifications\AvisoPedido::class, 1);

        // Al día siguiente no se repite; a los 30 días del aviso, sí
        $this->travel(1)->days();
        $this->artisan('credito:recordatorios');
        $this->assertSame(1, $pedido->fresh()->recordatorios_enviados);
        $this->travel(30)->days();
        $this->artisan('credito:recordatorios');
        $this->assertSame(2, $pedido->fresh()->recordatorios_enviados);
    }

    public function test_sin_correo_el_personal_recuerda_por_whatsapp_desde_creditos(): void
    {
        [$usuario, $cliente] = $this->clienteACredito();
        $cliente->forceFill(['correo' => null])->save();
        $usuario->forceFill(['correo' => null])->save();
        $pedido = $this->comprarACredito($usuario);
        $admin = $this->crearUsuario('administrativo');
        $this->actingAs($admin)->post(route('gestion.pedidos.confirmar', $pedido));

        $this->travel(31)->days();
        $this->artisan('credito:recordatorios');
        $this->assertSame(0, $pedido->fresh()->recordatorios_enviados);

        $this->actingAs($admin)->get(route('gestion.creditos.index', ['estado' => 'por_recordar']))
            ->assertOk()->assertSee($pedido->codigo)->assertSee('Recordar por WhatsApp');
        $this->post(route('gestion.creditos.recordar', $pedido))->assertRedirectContains('wa.me');
        $this->assertSame(1, $pedido->fresh()->recordatorios_enviados);
    }

    public function test_solo_administrativo_y_directivo_autorizan_credito(): void
    {
        $cliente = $this->crearCliente();
        $datos = ['credito_habilitado' => '1', 'limite_credito' => '500'];

        $this->actingAs($this->crearUsuario('vendedor'))->put(route('gestion.clientes.credito', $cliente), $datos)->assertForbidden();
        $this->actingAs($this->crearUsuario('vendedor'))->get(route('gestion.creditos.index'))->assertForbidden();
        $this->assertFalse($cliente->fresh()->credito_habilitado);

        $this->actingAs($this->crearUsuario('administrativo'))->put(route('gestion.clientes.credito', $cliente), $datos)->assertSessionHas('exito');
        $this->assertTrue($cliente->fresh()->credito_habilitado);
        $this->assertSame('500.00', (string) $cliente->fresh()->limite_credito);
    }
}
