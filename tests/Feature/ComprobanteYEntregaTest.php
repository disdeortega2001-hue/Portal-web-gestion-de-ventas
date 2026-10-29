<?php

namespace Tests\Feature;

use App\Http\Controllers\CompraController;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Notifications\AvisoPedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Comprobante de pago (al verificar el pago), entrega del pedido (al titular o a otra persona
 * con su cédula) y los avisos al cliente por correo y WhatsApp.
 */
class ComprobanteYEntregaTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
        Notification::fake();
    }

    /** Cliente con cuenta que compra con Pago Móvil y reporta el pago. */
    private function pedidoReportado(): array
    {
        $usuario = $this->crearUsuario('cliente', ['correo' => 'productor@prueba.test']);
        $this->crearCliente($usuario);
        $producto = $this->crearProducto(['stock' => 10, 'precio' => '100.00']);
        $this->actingAs($usuario)->post(route('carrito.agregar', $producto), ['cantidad' => 1]);
        $this->actingAs($usuario)->post(route('compra.store'), ['metodo_pago' => 'pago_movil', 'entrega' => 'retiro']);
        $pedido = Pedido::latest('id')->firstOrFail();
        $this->actingAs($usuario)->post(route('cuenta.pedidos.pago', $pedido), [
            'referencia' => '00451234', 'fecha_pago' => now()->toDateString(), 'monto' => (string) $pedido->total_bs,
        ]);

        return [$usuario, $pedido->fresh()];
    }

    private function verificar(Pedido $pedido): \Illuminate\Testing\TestResponse
    {
        $pago = $pedido->pagos()->where('estado', Pago::POR_VERIFICAR)->firstOrFail();

        return $this->actingAs($this->crearUsuario('administrativo'))->post(route('gestion.pagos.verificar', $pago));
    }

    public function test_al_verificar_el_pago_el_cliente_recibe_aviso_y_descarga_su_comprobante(): void
    {
        [$usuario, $pedido] = $this->pedidoReportado();

        // Sin verificar todavía no hay comprobante
        $this->actingAs($usuario)->get(route('cuenta.pedidos.comprobante', $pedido))->assertNotFound();

        $this->verificar($pedido)->assertSessionHas('exito')->assertSessionHas('aviso_cliente', fn ($a) => $a['correo'] && str_starts_with($a['whatsapp'], 'https://wa.me/58414'));

        Notification::assertSentTo(new AnonymousNotifiable(), AvisoPedido::class, function (AvisoPedido $aviso, $canales, $notificable) use ($pedido) {
            return $notificable->routes['mail'] === 'productor@prueba.test'
                && str_contains($aviso->asunto, $pedido->codigo) && $aviso->pdf !== null;
        });

        $this->actingAs($usuario)->get(route('cuenta.pedidos.show', $pedido))
            ->assertSee('Pago <em>verificado</em>', false)->assertSee('Descargar comprobante de pago');
        $respuesta = $this->actingAs($usuario)->get(route('cuenta.pedidos.comprobante', $pedido))->assertOk();
        $this->assertSame('application/pdf', $respuesta->headers->get('content-type'));

        // Otro cliente no puede bajar el comprobante ajeno
        $ajeno = $this->crearUsuario('cliente');
        $this->crearCliente($ajeno);
        $this->actingAs($ajeno)->get(route('cuenta.pedidos.comprobante', $pedido))->assertForbidden();
    }

    public function test_sin_cuenta_el_comprobante_se_baja_con_el_enlace_firmado(): void
    {
        $producto = $this->crearProducto(['stock' => 10, 'precio' => '100.00']);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);
        $this->post(route('compra.store'), [
            'tipo_identificacion' => 'V', 'numero_identificacion' => '20111222', 'nombre' => 'Pedro Pérez',
            'telefono' => '0414-1112233', 'direccion' => 'Finca La Ceiba, Zaraza', 'metodo_pago' => 'efectivo', 'entrega' => 'retiro',
        ]);
        $pedido = Pedido::latest('id')->firstOrFail();
        $this->actingAs($this->crearUsuario('administrativo'))
            ->post(route('gestion.pedidos.pagos.store', $pedido), ['metodo' => 'efectivo', 'monto' => (string) $pedido->total])
            ->assertSessionHas('aviso_cliente', fn ($a) => ! $a['correo'] && $a['whatsapp'] !== null);
        Notification::assertNothingSent();   // Sin correo registrado: solo el WhatsApp

        auth()->logout();
        $this->get(route('pedidos.seguimiento.comprobante', ['pedido' => $pedido]))->assertForbidden();
        $this->get(CompraController::enlaceSeguimiento($pedido, 'pedidos.seguimiento.comprobante'))->assertOk();
    }

    public function test_el_vendedor_entrega_el_pedido_a_otra_persona_y_se_avisa_al_cliente(): void
    {
        [$usuario, $pedido] = $this->pedidoReportado();
        $vendedor = $this->crearUsuario('vendedor');

        // Antes de verificar el pago no se entrega
        $this->actingAs($vendedor)->post(route('gestion.pedidos.entregar', $pedido), ['recibe' => 'titular'])->assertSessionHas('error');
        $this->verificar($pedido);
        Notification::fake();

        $this->actingAs($vendedor)->post(route('gestion.pedidos.entregar', $pedido), ['recibe' => 'otra'])
            ->assertSessionHasErrors(['recibido_por', 'recibido_por_identificacion']);
        $this->actingAs($vendedor)->post(route('gestion.pedidos.entregar', $pedido), [
            'recibe' => 'otra', 'recibido_por' => 'José Rodríguez', 'recibido_por_identificacion' => 'v-15222333',
        ])->assertSessionHas('exito')->assertSessionHas('aviso_cliente', fn ($a) => str_contains(urldecode($a['whatsapp']), 'José Rodríguez'));

        $pedido->refresh();
        $this->assertTrue($pedido->estaEntregado());
        $this->assertSame('V-15222333', $pedido->recibido_por_identificacion);
        $this->assertSame($vendedor->id, $pedido->id_usuario_entrega);
        Notification::assertSentTimes(AvisoPedido::class, 1);

        $this->actingAs($usuario)->get(route('cuenta.pedidos.show', $pedido))
            ->assertSee('José Rodríguez')->assertSee('Si no reconoces esta entrega');
        $this->actingAs($usuario)->get(route('cuenta.pedidos'))->assertSee('Retirado el');

        // No se entrega dos veces
        $this->actingAs($vendedor)->post(route('gestion.pedidos.entregar', $pedido), ['recibe' => 'titular'])->assertSessionHas('error');
    }

    public function test_solo_el_directivo_modifica_la_configuracion_del_portal(): void
    {
        $datos = ['tasa_cambio' => '40', 'porcentaje_iva' => '16', 'porcentaje_igtf' => '3', 'umbral_stock_bajo' => '5'];

        foreach (['vendedor', 'administrativo'] as $rol) {
            $this->actingAs($this->crearUsuario($rol))->put(route('admin.configuracion.update'), $datos)->assertForbidden();
        }
        $this->actingAs($this->crearUsuario('administrativo'))->get(route('gestion.tablero'))->assertDontSee('Tasa del día');
        $this->actingAs($this->crearUsuario('directivo'))->get(route('admin.configuracion.edit'))->assertOk();
    }

    public function test_solo_administrativo_y_directivo_modifican_el_inventario(): void
    {
        $producto = $this->crearProducto(['stock' => 10]);
        $movimiento = ['id_producto' => $producto->id, 'tipo' => 'entrada', 'cantidad' => 5, 'motivo' => 'Compra al proveedor'];

        $this->actingAs($this->crearUsuario('vendedor'))->post(route('gestion.inventario.store'), $movimiento)->assertForbidden();
        $this->actingAs($this->crearUsuario('vendedor'))->get(route('admin.productos.create'))->assertForbidden();
        foreach (['administrativo', 'directivo'] as $rol) {
            $this->actingAs($this->crearUsuario($rol))->post(route('gestion.inventario.store'), $movimiento)->assertSessionHasNoErrors();
        }

        $this->assertSame(20, $producto->fresh()->stock);
    }
}
