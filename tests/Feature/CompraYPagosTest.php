<?php

namespace Tests\Feature;

use App\Http\Controllers\CompraController;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Compra en el portal: con cuenta o sin ella (sin cuenta, el pedido se sigue con un enlace firmado).
 * Pagos: el cliente reporta, el personal administrativo verifica (o cobra en la tienda).
 */
class CompraYPagosTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
    }

    /** Cliente con cuenta y ficha, y un producto en su lista. */
    private function clienteConLista(int $stock = 10, int $cantidad = 2): array
    {
        $usuario = $this->crearUsuario('cliente');
        $cliente = $this->crearCliente($usuario);
        $producto = $this->crearProducto(['stock' => $stock, 'precio' => '100.00']);
        $this->actingAs($usuario)->post(route('carrito.agregar', $producto), ['cantidad' => $cantidad]);

        return [$usuario, $cliente, $producto];
    }

    private function comprar(Usuario $usuario, string $metodo = 'pago_movil'): Pedido
    {
        $this->actingAs($usuario)->post(route('compra.store'), ['metodo_pago' => $metodo, 'entrega' => 'retiro'])
            ->assertRedirect();

        return Pedido::latest('id')->firstOrFail();
    }

    /** Datos básicos de quien compra sin cuenta (sin correo ni contraseña). */
    private const DATOS_SIN_CUENTA = [
        'tipo_identificacion' => 'V', 'numero_identificacion' => '20111222',
        'nombre' => 'Pedro Pérez', 'telefono' => '0414-1112233', 'direccion' => 'Finca La Ceiba, Zaraza',
    ];

    private function comprarSinCuenta(string $metodo = 'pago_movil', array $datos = []): Pedido
    {
        $producto = $this->crearProducto(['stock' => 10, 'precio' => '100.00']);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);
        $this->post(route('compra.store'), $datos + self::DATOS_SIN_CUENTA + ['metodo_pago' => $metodo, 'entrega' => 'retiro'])
            ->assertRedirectContains('/pedido/');

        return Pedido::latest('id')->firstOrFail();
    }

    public function test_el_visitante_compra_sin_crear_cuenta(): void
    {
        $producto = $this->crearProducto();
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);
        $this->get(route('carrito.index'))->assertOk()->assertSee('Comprar ahora')->assertSee('No necesitas crear cuenta.');
        $this->get(route('compra.create'))->assertOk()->assertSee('No necesitas crear cuenta')->assertDontSee('id="correo"', false);

        $pedido = $this->comprarSinCuenta('zelle');

        $this->assertSame('V-20111222', $pedido->cliente->identificacion);
        $this->assertSame(0, Usuario::where('rol', 'cliente')->count());
        $this->assertGuest();
        $this->get(CompraController::enlaceSeguimiento($pedido))->assertOk()
            ->assertSee($pedido->codigo)->assertSee('Guarda este enlace');
    }

    public function test_el_pedido_sin_cuenta_solo_se_abre_con_su_enlace_firmado(): void
    {
        $pedido = $this->comprarSinCuenta();
        $pago = ['referencia' => '00451234', 'fecha_pago' => now()->toDateString(), 'monto' => (string) $pedido->total_bs];

        $this->get(route('pedidos.seguimiento', $pedido))->assertForbidden();
        $this->post(route('pedidos.seguimiento.pago', $pedido), $pago)->assertForbidden();
        $this->actingAs($this->crearUsuario('cliente'))->get(route('cuenta.pedidos.show', $pedido))->assertForbidden();
        auth()->logout();

        $this->post(CompraController::enlaceSeguimiento($pedido, 'pedidos.seguimiento.pago'), $pago)->assertSessionHas('exito');
        $this->assertSame(Pedido::PAGO_POR_VERIFICAR, $pedido->fresh()->estado_pago);
    }

    public function test_sin_cuenta_se_puede_cancelar_el_pedido_pendiente(): void
    {
        $pedido = $this->comprarSinCuenta('efectivo');

        $this->post(route('pedidos.seguimiento.devolucion', $pedido), ['motivo' => 'Ya no lo necesito'])->assertForbidden();
        $this->post(CompraController::enlaceSeguimiento($pedido, 'pedidos.seguimiento.devolucion'), ['motivo' => 'Ya no lo necesito'])
            ->assertSessionHas('exito');
        $this->assertSame(Pedido::ANULADO, $pedido->fresh()->estado);
    }

    public function test_comprar_sin_cuenta_con_una_cedula_existente_no_cambia_su_ficha(): void
    {
        $cliente = tap($this->crearCliente())->update(['identificacion' => 'V-20111222', 'telefono' => '0414-0000000']);

        $pedido = $this->comprarSinCuenta('pago_movil', ['telefono' => '0424-9999999']);

        $this->assertSame($cliente->id, $pedido->id_cliente);
        $this->assertSame('0414-0000000', $cliente->fresh()->telefono);
        $this->assertStringContainsString('0424-9999999', $pedido->observaciones);
    }

    public function test_se_ofrecen_todos_los_metodos_de_pago(): void
    {
        [$usuario] = $this->clienteConLista();

        $this->actingAs($usuario)->get(route('compra.create'))->assertOk()
            ->assertSeeInOrder(['Pago Móvil', 'Transferencia bancaria', 'Zelle', 'Efectivo en dólares en la tienda', 'Punto de venta en la tienda']);
    }

    public function test_la_compra_crea_un_pedido_pendiente_sin_tocar_el_stock(): void
    {
        [$usuario, $cliente, $producto] = $this->clienteConLista();

        $pedido = $this->comprar($usuario, 'zelle');

        $this->assertSame(Pedido::PORTAL, $pedido->origen);
        $this->assertSame(Pedido::PENDIENTE, $pedido->estado);
        $this->assertSame(Pedido::SIN_PAGO, $pedido->estado_pago);
        $this->assertNull($pedido->id_usuario);
        $this->assertSame($cliente->id, $pedido->id_cliente);
        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertEmpty(session('carrito', []));

        // Sin datos de cuenta cargados, el cliente ve que se los envían por WhatsApp
        $this->actingAs($usuario)->get(route('cuenta.pedidos.show', $pedido))->assertOk()
            ->assertSee('Paga con')->assertSee('Te enviamos los datos de la cuenta por WhatsApp')->assertSee('Reporta tu pago');
    }

    public function test_los_datos_de_cuenta_cargados_se_muestran_al_cliente(): void
    {
        $this->configurar(['pago_movil_banco' => 'Banco de Prueba', 'pago_movil_telefono' => '0414-0000000', 'pago_movil_documento' => 'J-50668510-8']);
        [$usuario] = $this->clienteConLista();

        $pedido = $this->comprar($usuario);

        $this->actingAs($usuario)->get(route('cuenta.pedidos.show', $pedido))
            ->assertSee('Banco de Prueba')->assertSee('0414-0000000')->assertDontSee('por WhatsApp al teléfono');
    }

    public function test_un_metodo_desactivado_no_se_ofrece_ni_se_acepta(): void
    {
        $this->configurar(['zelle_activo' => '']);
        [$usuario] = $this->clienteConLista();

        $this->actingAs($usuario)->get(route('compra.create'))->assertDontSee('Zelle');
        $this->actingAs($usuario)->post(route('compra.store'), ['metodo_pago' => 'zelle', 'entrega' => 'retiro'])
            ->assertSessionHasErrors('metodo_pago');
    }

    public function test_el_cliente_reporta_su_pago_y_otro_cliente_no_puede(): void
    {
        Storage::fake('local');
        [$usuario] = $this->clienteConLista();
        $pedido = $this->comprar($usuario);

        $this->actingAs($usuario)->post(route('cuenta.pedidos.pago', $pedido), [
            'referencia' => '00451234', 'fecha_pago' => now()->toDateString(), 'monto' => (string) $pedido->total_bs,
            'comprobante' => UploadedFile::fake()->image('capture.jpg'),
        ])->assertSessionHas('exito');

        $pago = Pago::firstOrFail();
        $this->assertSame(Pago::POR_VERIFICAR, $pago->estado);
        $this->assertSame('VES', $pago->moneda);
        Storage::disk('local')->assertExists($pago->comprobante);
        $this->assertSame(Pedido::PAGO_POR_VERIFICAR, $pedido->fresh()->estado_pago);

        $ajeno = $this->crearUsuario('cliente');
        $this->crearCliente($ajeno);
        $this->actingAs($ajeno)->get(route('cuenta.pedidos.show', $pedido))->assertForbidden();
        $this->actingAs($ajeno)->post(route('cuenta.pedidos.pago', $pedido), ['referencia' => '9999', 'fecha_pago' => now()->toDateString(), 'monto' => 1])
            ->assertForbidden();
    }

    public function test_al_verificar_el_pago_la_venta_se_confirma_y_descuenta_stock(): void
    {
        [$usuario, , $producto] = $this->clienteConLista();
        $pedido = $this->comprar($usuario);
        $this->actingAs($usuario)->post(route('cuenta.pedidos.pago', $pedido), ['referencia' => '00451234', 'fecha_pago' => now()->toDateString(), 'monto' => (string) $pedido->total_bs]);
        $pago = Pago::firstOrFail();

        // El vendedor no verifica pagos
        $this->actingAs($this->crearUsuario('vendedor'))->get(route('gestion.pagos.index'))->assertForbidden();

        $administrativo = $this->crearUsuario('administrativo');
        $this->actingAs($administrativo)->get(route('gestion.pagos.index'))->assertOk()->assertSee('00451234')->assertSee($pedido->codigo);
        $this->actingAs($administrativo)->post(route('gestion.pagos.verificar', $pago))->assertSessionHas('exito');

        $pedido->refresh();
        $this->assertSame(Pedido::PAGADO, $pedido->estado_pago);
        $this->assertSame(Pedido::CONFIRMADO, $pedido->estado);
        $this->assertSame($administrativo->id, $pedido->id_usuario);
        $this->assertSame(8, $producto->fresh()->stock);
        $this->assertSame(Pago::VERIFICADO, $pago->fresh()->estado);
    }

    public function test_un_pago_rechazado_se_puede_reportar_de_nuevo(): void
    {
        [$usuario] = $this->clienteConLista();
        $pedido = $this->comprar($usuario, 'transferencia');
        $this->actingAs($usuario)->post(route('cuenta.pedidos.pago', $pedido), ['referencia' => '1111', 'fecha_pago' => now()->toDateString(), 'monto' => '5']);

        $this->actingAs($this->crearUsuario('directivo'))
            ->post(route('gestion.pagos.rechazar', Pago::firstOrFail()), ['motivo' => 'La referencia no aparece en el banco'])
            ->assertSessionHas('exito');

        $this->assertSame(Pedido::SIN_PAGO, $pedido->fresh()->estado_pago);
        $this->actingAs($usuario)->get(route('cuenta.pedidos.show', $pedido))
            ->assertSee('La referencia no aparece en el banco')->assertSee('Reporta tu pago');
    }

    public function test_el_cobro_en_la_tienda_deja_el_pedido_pagado_y_confirmado(): void
    {
        [$usuario, , $producto] = $this->clienteConLista();
        $pedido = $this->comprar($usuario, 'efectivo');

        // En efectivo no hay nada que reportar desde el portal
        $this->actingAs($usuario)->get(route('cuenta.pedidos.show', $pedido))->assertSee('Pagas al retirar')->assertDontSee('Reporta tu pago');

        $this->actingAs($this->crearUsuario('administrativo'))
            ->post(route('gestion.pedidos.pagos.store', $pedido), ['metodo' => 'efectivo', 'monto' => (string) $pedido->total])
            ->assertSessionHas('exito');

        $pedido->refresh();
        $this->assertSame(Pedido::PAGADO, $pedido->estado_pago);
        $this->assertSame(Pedido::CONFIRMADO, $pedido->estado);
        $this->assertSame(8, $producto->fresh()->stock);
    }

    public function test_el_personal_no_compra_desde_el_portal(): void
    {
        $this->actingAs($this->crearUsuario('vendedor'))->get(route('compra.create'))->assertForbidden();
    }

    public function test_el_cliente_cancela_un_pedido_pendiente_sin_pagar(): void
    {
        [$usuario] = $this->clienteConLista();
        $pedido = $this->comprar($usuario, 'zelle');

        $this->actingAs($usuario)->post(route('cuenta.pedidos.devolucion', $pedido), ['motivo' => 'Me equivoqué de repuesto'])->assertSessionHas('exito');

        $pedido->refresh();
        $this->assertSame(Pedido::ANULADO, $pedido->estado);
        $this->assertStringContainsString('Me equivoqué de repuesto', $pedido->motivo_anulacion);
    }

    public function test_la_devolucion_de_un_pedido_pagado_la_atiende_el_personal_y_repone_existencia(): void
    {
        [$usuario, , $producto] = $this->clienteConLista();
        $pedido = $this->comprar($usuario, 'efectivo');
        $administrativo = $this->crearUsuario('administrativo');
        $this->actingAs($administrativo)->post(route('gestion.pedidos.pagos.store', $pedido), ['metodo' => 'efectivo', 'monto' => (string) $pedido->fresh()->total]);
        $this->assertSame(8, $producto->fresh()->stock);

        // Ya pagado y confirmado: el cliente solo puede solicitar la devolución
        $this->actingAs($usuario)->post(route('cuenta.pedidos.devolucion', $pedido), ['motivo' => 'El repuesto no sirve para mi rotativa']);
        $pedido->refresh();
        $this->assertSame(Pedido::CONFIRMADO, $pedido->estado);
        $this->assertTrue($pedido->devolucionPendiente());
        $this->actingAs($administrativo)->get(route('gestion.pedidos.index', ['estado' => 'devolucion']))->assertSee($pedido->codigo);

        // El personal anula sin motivo: no se permite
        $this->actingAs($administrativo)->post(route('gestion.pedidos.anular', $pedido))->assertSessionHasErrors('motivo');

        // Con motivo: se anula, la existencia vuelve y el pago queda como reembolsado
        $this->actingAs($administrativo)->post(route('gestion.pedidos.anular', $pedido), ['motivo' => 'Devolución aceptada'])->assertSessionHas('exito');
        $pedido->refresh();
        $this->assertSame(Pedido::ANULADO, $pedido->estado);
        $this->assertSame(Pedido::REEMBOLSADO, $pedido->estado_pago);
        $this->assertSame(10, $producto->fresh()->stock);
        $this->actingAs($usuario)->get(route('cuenta.pedidos.show', $pedido))->assertSee('Pedido anulado.')->assertSee('Te devolvemos el dinero');
    }

    public function test_nadie_pide_la_devolucion_de_un_pedido_ajeno(): void
    {
        [$usuario] = $this->clienteConLista();
        $pedido = $this->comprar($usuario);
        $ajeno = $this->crearUsuario('cliente');
        $this->crearCliente($ajeno);

        $this->actingAs($ajeno)->post(route('cuenta.pedidos.devolucion', $pedido), ['motivo' => 'Quiero cancelarlo'])->assertForbidden();
        $this->assertSame(Pedido::PENDIENTE, $pedido->fresh()->estado);
    }
}
