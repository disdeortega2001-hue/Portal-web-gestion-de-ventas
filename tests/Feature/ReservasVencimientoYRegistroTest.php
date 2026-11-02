<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\Pedido;
use App\Models\RegistroCambio;
use App\Support\Reservas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Existencias reservadas por pedidos pendientes, vencimiento del crédito con su bloqueo,
 * estado de cuenta, cuentas por cobrar y registro de cambios.
 */
class ReservasVencimientoYRegistroTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
    }

    private function comprador(?array $credito = null): array
    {
        $usuario = $this->crearUsuario('cliente');
        $cliente = $this->crearCliente($usuario);
        if ($credito) {
            $cliente->forceFill($credito)->save();
        }

        return [$usuario, $cliente];
    }

    private function comprar($usuario, $producto, int $cantidad, string $metodo = 'zelle')
    {
        $this->actingAs($usuario)->post(route('carrito.agregar', $producto), ['cantidad' => $cantidad]);

        return $this->post(route('compra.store'), ['metodo_pago' => $metodo, 'entrega' => 'retiro']);
    }

    private function creditoConfirmado(array $credito = ['credito_habilitado' => true])
    {
        [$usuario, $cliente] = $this->comprador($credito);
        $producto = $this->crearProducto(['stock' => 10, 'precio' => '100.00']);
        $this->comprar($usuario, $producto, 1, 'credito_usd')->assertSessionHasNoErrors();
        $pedido = Pedido::latest('id')->firstOrFail();
        $this->actingAs($this->crearUsuario('administrativo'))->post(route('gestion.pedidos.confirmar', $pedido))->assertSessionHas('exito');

        return [$usuario, $cliente, $pedido->fresh(), $producto];
    }

    /* ---------- Reservas ---------- */

    public function test_un_pedido_pendiente_aparta_la_ultima_unidad(): void
    {
        $producto = $this->crearProducto(['stock' => 1, 'precio' => '50.00']);
        [$primero] = $this->comprador();
        [$segundo] = $this->comprador();

        // El segundo ya la tenía en su lista cuando el primero la compró
        $this->actingAs($segundo)->post(route('carrito.agregar', $producto), ['cantidad' => 1]);
        $carritoDelSegundo = session()->all();
        $this->flushSession();
        $this->comprar($primero, $producto, 1)->assertSessionHasNoErrors();

        $this->assertSame(1, $producto->fresh()->stock);   // Aún no se descuenta
        $this->assertSame(0, $producto->fresh()->disponible());

        $this->flushSession();
        $this->actingAs($segundo)->withSession($carritoDelSegundo)
            ->post(route('compra.store'), ['metodo_pago' => 'zelle', 'entrega' => 'retiro'])->assertSessionHasErrors('carrito');
        $this->assertSame(1, Pedido::count());

        // Quien llega después ya no puede ni agregarla
        $this->flushSession();
        $this->actingAs($segundo)->post(route('carrito.agregar', $producto), ['cantidad' => 1]);
        $this->assertSame(1, Pedido::count());

        $this->actingAs($this->crearUsuario('vendedor'))->get(route('gestion.inventario.index'))->assertSee('1 reservada');
        auth()->logout();
        $this->get(route('catalogo.show', $producto))->assertSee('Agotado');
    }

    public function test_anular_libera_la_reserva(): void
    {
        $producto = $this->crearProducto(['stock' => 1]);
        [$usuario] = $this->comprador();
        $this->comprar($usuario, $producto, 1);
        $pedido = Pedido::firstOrFail();

        $this->actingAs($this->crearUsuario('vendedor'))->post(route('gestion.pedidos.anular', $pedido), ['motivo' => 'Cliente desistió'])->assertSessionHas('exito');

        $this->assertSame(1, $producto->fresh()->disponible());
    }

    public function test_la_reserva_de_un_pedido_sin_pago_se_libera_a_las_72_horas(): void
    {
        $producto = $this->crearProducto(['stock' => 1]);
        [$usuario] = $this->comprador();
        $this->comprar($usuario, $producto, 1);
        $this->assertSame(0, $producto->fresh()->disponible());

        $this->travel(Reservas::HORAS + 1)->hours();
        app(Reservas::class)->olvidar();

        $this->assertSame(1, $producto->fresh()->disponible());
    }

    public function test_con_pago_reportado_la_reserva_sigue_despues_de_72_horas(): void
    {
        $producto = $this->crearProducto(['stock' => 1]);
        [$usuario] = $this->comprador();
        $this->comprar($usuario, $producto, 1);
        Pedido::firstOrFail()->update(['estado_pago' => Pedido::PAGO_POR_VERIFICAR]);

        $this->travel(Reservas::HORAS + 1)->hours();
        app(Reservas::class)->olvidar();

        $this->assertSame(0, $producto->fresh()->disponible());
    }

    /* ---------- Vencimiento del crédito ---------- */

    public function test_el_credito_vence_segun_el_plazo_del_cliente(): void
    {
        [, , $pedido] = $this->creditoConfirmado(['credito_habilitado' => true, 'plazo_credito_dias' => 15]);

        $this->assertSame(now()->addDays(15)->toDateString(), $pedido->credito_vence_en->toDateString());
        $this->assertFalse($pedido->creditoVencido());

        $this->travel(16)->days();
        $this->assertTrue($pedido->fresh()->creditoVencido());
        $this->assertSame(1, $pedido->fresh()->diasDeAtraso());
    }

    public function test_con_un_credito_vencido_no_puede_volver_a_comprar_a_credito(): void
    {
        [$usuario, $cliente, $pedido, $producto] = $this->creditoConfirmado();
        $this->travel(31)->days();

        $this->comprar($usuario, $producto, 1, 'credito_usd')->assertSessionHasErrors('metodo_pago');
        $this->assertSame(1, Pedido::count());
        $this->get(route('compra.create'))->assertSee('Tienes un crédito vencido');

        // De contado sí, y al saldar se desbloquea
        $this->actingAs($this->crearUsuario('administrativo'))->get(route('gestion.creditos.index', ['estado' => 'vencidos']))->assertSee($pedido->codigo)->assertSee('Vencido hace');
        $this->post(route('gestion.pedidos.pagos.store', $pedido), ['metodo' => 'efectivo', 'monto' => (string) $pedido->total])->assertSessionHas('exito');
        $this->assertFalse(\App\Support\Credito::tieneVencido($cliente));
    }

    public function test_el_mostrador_tampoco_vende_a_credito_con_uno_vencido(): void
    {
        [, $cliente, , $producto] = $this->creditoConfirmado();
        $this->travel(31)->days();

        $this->actingAs($this->crearUsuario('vendedor'))->post(route('gestion.pedidos.store'), [
            'id_cliente' => $cliente->id, 'moneda_pago' => 'USD', 'a_credito' => '1',
            'items' => [['id_producto' => $producto->id, 'cantidad' => 1]],
        ])->assertSessionHas('error');
        $this->assertSame(1, Pedido::count());
    }

    public function test_las_fichas_del_pedido_muestran_el_vencimiento(): void
    {
        [$usuario, , $pedido] = $this->creditoConfirmado();
        $vence = $pedido->credito_vence_en->format('d/m/Y');

        $this->actingAs($this->crearUsuario('vendedor'))->get(route('gestion.pedidos.show', $pedido))->assertOk()->assertSee('Vence')->assertSee($vence);
        $this->actingAs($usuario)->get(route('cuenta.pedidos.show', $pedido))->assertOk()->assertSee($vence)->assertSee('Estado de cuenta');

        $this->travel(31)->days();
        $this->actingAs($this->crearUsuario('vendedor'))->get(route('gestion.pedidos.show', $pedido))->assertOk()->assertSee('días de atraso');
    }

    /** Compila todas las vistas: un error de sintaxis rompe la pantalla aunque ninguna otra prueba la abra. */
    public function test_todas_las_vistas_compilan_sin_errores(): void
    {
        $vistas = collect(\Illuminate\Support\Facades\File::allFiles(resource_path('views')))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.blade.php'));

        $this->assertGreaterThan(50, $vistas->count());
        foreach ($vistas as $vista) {
            token_get_all(app('blade.compiler')->compileString($vista->getContents()), TOKEN_PARSE);   // Lanza ParseError si algo falla
        }
    }

    /* ---------- Documentos ---------- */

    public function test_estado_de_cuenta_y_cuentas_por_cobrar_en_pdf(): void
    {
        [$usuario, $cliente] = $this->creditoConfirmado();
        $administrativo = $this->crearUsuario('administrativo');

        $estado = $this->actingAs($administrativo)->get(route('gestion.clientes.estado-cuenta', $cliente));
        $estado->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('gestion.creditos.cuentas'))->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->actingAs($usuario)->get(route('cuenta.estado-cuenta'))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->actingAs($this->crearUsuario('vendedor'))->get(route('gestion.creditos.cuentas'))->assertForbidden();

        $filas = \App\Support\Credito::cuentasPorCobrar();
        $this->assertSame(103.0, $filas->first()['al_dia']);   // 100 + IGTF 3 %
        $this->travel(40)->days();
        $this->assertSame(103.0, \App\Support\Credito::cuentasPorCobrar()->first()['d30']);   // 10 días de atraso
    }

    /* ---------- Registro de cambios ---------- */

    public function test_queda_registrado_quien_cambia_precio_configuracion_y_credito(): void
    {
        $directivo = $this->crearUsuario('directivo', ['nombre' => 'Daysi Prueba']);
        $producto = $this->crearProducto(['precio' => '10.00']);
        $cliente = $this->crearCliente();
        $this->actingAs($directivo);

        $producto->update(['precio' => '12.50']);
        Configuracion::updateOrCreate(['clave' => 'porcentaje_iva'], ['valor' => '15']);
        $this->put(route('gestion.clientes.credito', $cliente), ['credito_habilitado' => '1', 'limite_credito' => '500', 'plazo_credito_dias' => '45'])->assertSessionHas('exito');

        $this->assertDatabaseHas('registros_cambios', ['entidad' => 'Producto', 'campo' => 'Precio (USD)', 'antes' => '10.00', 'despues' => '12.50', 'id_usuario' => $directivo->id]);
        $this->assertDatabaseHas('registros_cambios', ['entidad' => 'Configuración', 'referencia' => 'Porcentaje iva', 'antes' => '16', 'despues' => '15']);
        $this->assertDatabaseHas('registros_cambios', ['entidad' => 'Cliente', 'campo' => 'Crédito autorizado', 'antes' => 'No', 'despues' => 'Sí']);
        $this->assertDatabaseHas('registros_cambios', ['entidad' => 'Cliente', 'campo' => 'Plazo de crédito (días)', 'despues' => '45']);

        $this->get(route('admin.registro.index'))->assertOk()->assertSee('Daysi Prueba')->assertSee('12.50');
        $this->actingAs($this->crearUsuario('administrativo'))->get(route('admin.registro.index'))->assertForbidden();
    }

    public function test_lo_que_cambia_una_tarea_automatica_no_llena_el_registro(): void
    {
        Configuracion::updateOrCreate(['clave' => 'tasa_cambio'], ['valor' => '40']);

        $this->assertSame(0, RegistroCambio::count());
    }
}
