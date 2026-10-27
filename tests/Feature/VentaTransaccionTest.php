<?php

namespace Tests\Feature;

use App\Exceptions\StockInsuficienteException;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Services\InventarioService;
use App\Services\PedidoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Transacción ACID de la venta: nunca se vende más del stock disponible y,
 * si algo falla, no queda ningún cambio a medias (rollback).
 */
class VentaTransaccionTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    private PedidoService $pedidos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
        $this->pedidos = app(PedidoService::class);
    }

    public function test_confirmar_venta_descuenta_stock_y_registra_movimientos(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $filtro = $this->crearProducto(['stock' => 10]);
        $correa = $this->crearProducto(['stock' => 4]);

        $pedido = $this->venta($vendedor, $this->crearCliente(), [[$filtro, 3], [$correa, 4]]);

        $this->assertSame(Pedido::CONFIRMADO, $pedido->fresh()->estado);
        $this->assertNotNull($pedido->fresh()->fecha_confirmacion);
        $this->assertSame(7, $filtro->fresh()->stock);
        $this->assertSame(0, $correa->fresh()->stock);   // Se puede vender exactamente lo disponible

        $movimiento = MovimientoInventario::where('id_producto', $filtro->id)->sole();
        $this->assertSame('salida', $movimiento->tipo);
        $this->assertSame(3, $movimiento->cantidad);
        $this->assertSame(10, $movimiento->stock_anterior);
        $this->assertSame(7, $movimiento->stock_nuevo);
        $this->assertSame($pedido->id, $movimiento->id_pedido);
        $this->assertSame($vendedor->id, $movimiento->id_usuario);
    }

    public function test_stock_insuficiente_en_una_linea_revierte_todo_el_pedido(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $conStock = $this->crearProducto(['stock' => 10]);
        $sinStock = $this->crearProducto(['stock' => 2]);

        // El pedido se creó cuando había stock; luego alguien más se llevó unidades
        $pedido = $this->venta($vendedor, $this->crearCliente(), [[$conStock, 5], [$sinStock, 3]], confirmar: false);

        try {
            $this->pedidos->confirmarVenta($pedido, $vendedor);
            $this->fail('Se esperaba StockInsuficienteException.');
        } catch (StockInsuficienteException $e) {
            $this->assertStringContainsString($sinStock->codigo_fabrica, $e->getMessage());
            $this->assertStringContainsString('disponible 2', $e->getMessage());
        }

        // Nada cambió: ni el producto que sí tenía stock
        $this->assertSame(10, $conStock->fresh()->stock);
        $this->assertSame(2, $sinStock->fresh()->stock);
        $this->assertSame(0, MovimientoInventario::count());
        $this->assertSame(Pedido::PENDIENTE, $pedido->fresh()->estado);
        $this->assertNull($pedido->fresh()->fecha_confirmacion);
    }

    public function test_el_stock_vendido_por_otra_via_bloquea_la_confirmacion(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $producto = $this->crearProducto(['stock' => 5]);
        $pedido = $this->venta($vendedor, $this->crearCliente(), [[$producto, 3]], confirmar: false);

        // Entre la cotización y la confirmación salen 3 unidades (quedan 2)
        app(InventarioService::class)->registrar($producto, 'salida', 3, 'Merma por daño', $vendedor);

        $this->expectException(StockInsuficienteException::class);
        $this->pedidos->confirmarVenta($pedido, $vendedor);
    }

    public function test_no_se_puede_confirmar_dos_veces_el_mismo_pedido(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $producto = $this->crearProducto(['stock' => 10]);
        $pedido = $this->venta($vendedor, $this->crearCliente(), [[$producto, 4]]);

        try {
            $this->pedidos->confirmarVenta($pedido, $vendedor);
            $this->fail('Se esperaba LogicException.');
        } catch (LogicException) {
        }

        $this->assertSame(6, $producto->fresh()->stock);   // Se descontó una sola vez
        $this->assertSame(1, MovimientoInventario::count());
    }

    public function test_anular_un_pedido_confirmado_repone_el_stock(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $producto = $this->crearProducto(['stock' => 10]);
        $pedido = $this->venta($vendedor, $this->crearCliente(), [[$producto, 4]]);

        $this->pedidos->anular($pedido, $vendedor);

        $this->assertSame(Pedido::ANULADO, $pedido->fresh()->estado);
        $this->assertSame(10, $producto->fresh()->stock);
        $this->assertSame(1, MovimientoInventario::where('tipo', 'entrada')->where('id_pedido', $pedido->id)->count());
    }

    public function test_salida_manual_mayor_al_stock_no_deja_stock_negativo(): void
    {
        $producto = $this->crearProducto(['stock' => 3]);

        $this->expectException(StockInsuficienteException::class);

        try {
            app(InventarioService::class)->registrar($producto, 'salida', 4, 'Prueba de salida', $this->crearUsuario('vendedor'));
        } finally {
            $this->assertSame(3, $producto->fresh()->stock);
            $this->assertSame(0, MovimientoInventario::count());
        }
    }

    public function test_desde_la_pantalla_el_vendedor_ve_el_error_y_nada_cambia(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $producto = $this->crearProducto(['stock' => 1]);
        $pedido = $this->venta($vendedor, $this->crearCliente(), [[$producto, 1]], confirmar: false);
        $producto->update(['stock' => 0]);   // Se agotó mientras tanto

        $this->actingAs($vendedor)
            ->from(route('gestion.pedidos.show', $pedido))
            ->post(route('gestion.pedidos.confirmar', $pedido))
            ->assertRedirect(route('gestion.pedidos.show', $pedido))
            ->assertSessionHas('error', fn ($mensaje) => str_contains($mensaje, 'Stock insuficiente'));

        $this->assertSame(Pedido::PENDIENTE, $pedido->fresh()->estado);
        $this->assertSame(0, $producto->fresh()->stock);
    }
}
