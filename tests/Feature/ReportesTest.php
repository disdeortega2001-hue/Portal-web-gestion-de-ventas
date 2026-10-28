<?php

namespace Tests\Feature;

use App\Exports\InventarioExport;
use App\Exports\VentasExport;
use App\Models\Producto;
use App\Models\Reporte;
use App\Models\Usuario;
use App\Services\InventarioService;
use App\Services\PedidoService;
use App\Services\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Totales de los reportes: solo cuentan ventas CONFIRMADAS dentro del rango,
 * los filtros funcionan y lo exportado coincide con lo consultado.
 *
 * Escenario (IVA 16 %, sin descuento porque ningún pedido llega a USD 500):
 *   Pedido 1 · Vendedor A · 2 × cuchilla 45,00 = 90,00 + IVA 14,40 = 104,40   ✔ cuenta
 *   Pedido 2 · Vendedor B · 1 × kit 410,00     = 410,00 + IVA 65,60 = 475,60 ✔ cuenta
 *   Pedido 3 · pendiente (5 cuchillas)                                       ✘
 *   Pedido 4 · confirmado y luego anulado (1 cuchilla)                       ✘
 *   Pedido 5 · confirmado el mes pasado (1 cuchilla)                         ✘ fuera de rango
 *   Total del período: 2 pedidos · 3 unidades · subtotal 500,00 · IVA 80,00 · total 580,00
 */
class ReportesTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    private ReporteService $reportes;
    private Usuario $vendedorA;
    private Usuario $vendedorB;
    private Producto $cuchilla;
    private Producto $kit;
    private array $filtros;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
        $this->reportes = app(ReporteService::class);

        $this->vendedorA = $this->crearUsuario('vendedor', ['nombre' => 'Vendedor A']);
        $this->vendedorB = $this->crearUsuario('vendedor', ['nombre' => 'Vendedor B']);
        $this->cuchilla = $this->crearProducto(['codigo_fabrica' => 'CUC-001', 'precio' => '45.00', 'stock' => 30]);
        $this->kit = $this->crearProducto(['codigo_fabrica' => 'KIT-001', 'precio' => '410.00', 'stock' => 3]);
        $cliente = $this->crearCliente();

        // Pedido 5: el mes pasado (fuera de rango)
        $this->travelTo(now()->subMonthNoOverflow()->startOfMonth()->addDays(10));
        $this->venta($this->vendedorA, $cliente, [[$this->cuchilla, 1]]);
        $this->travelBack();

        $this->venta($this->vendedorA, $cliente, [[$this->cuchilla, 2]]);                     // 1
        $this->venta($this->vendedorB, $cliente, [[$this->kit, 1]]);                          // 2
        $this->venta($this->vendedorA, $cliente, [[$this->cuchilla, 5]], confirmar: false);   // 3
        app(PedidoService::class)->anular($this->venta($this->vendedorB, $cliente, [[$this->cuchilla, 1]]), $this->vendedorB); // 4

        $this->filtros = ['tipo' => 'ventas', 'desde' => now()->startOfMonth()->toDateString(), 'hasta' => now()->toDateString()];
    }

    public function test_resumen_de_ventas_cuenta_solo_lo_confirmado_en_el_rango(): void
    {
        $r = $this->reportes->resumenVentas($this->filtros);

        $this->assertSame(2, $r['pedidos']);
        $this->assertSame(3, $r['unidades']);
        $this->assertEqualsWithDelta(500.00, $r['monto_lineas'], 0.001);
        $this->assertEqualsWithDelta(500.00, (float) $r['facturado']['subtotal'], 0.001);
        $this->assertEqualsWithDelta(80.00, (float) $r['facturado']['impuesto'], 0.001);
        $this->assertEqualsWithDelta(580.00, (float) $r['facturado']['total'], 0.001);
        $this->assertEqualsWithDelta(580.00 * 36.50, (float) $r['facturado']['total_bs'], 0.001);
        $this->assertFalse($r['filtrado_por_producto']);
    }

    public function test_el_detalle_tiene_una_fila_por_producto_vendido(): void
    {
        $filas = $this->reportes->consultaVentas($this->filtros)->get();

        $this->assertCount(2, $filas);
        $this->assertEqualsCanonicalizing(['CUC-001', 'KIT-001'], $filas->pluck('codigo_fabrica')->all());
        // La suma del detalle cuadra con el resumen
        $this->assertEqualsWithDelta(500.00, $filas->sum(fn ($f) => (float) $f->subtotal), 0.001);
    }

    public function test_ventas_por_vendedor(): void
    {
        $porVendedor = $this->reportes->resumenVentas($this->filtros)['por_vendedor']->keyBy('nombre');

        $this->assertEqualsWithDelta(475.60, (float) $porVendedor['Vendedor B']->total_vendido, 0.001);   // El mayor va primero
        $this->assertEqualsWithDelta(104.40, (float) $porVendedor['Vendedor A']->total_vendido, 0.001);
        $this->assertSame('Vendedor B', $this->reportes->resumenVentas($this->filtros)['por_vendedor']->first()->nombre);
    }

    public function test_filtro_por_vendedor(): void
    {
        $r = $this->reportes->resumenVentas($this->filtros + ['id_vendedor' => $this->vendedorA->id]);

        $this->assertSame(1, $r['pedidos']);
        $this->assertSame(2, $r['unidades']);
        $this->assertEqualsWithDelta(104.40, (float) $r['facturado']['total'], 0.001);
    }

    public function test_filtro_por_producto(): void
    {
        $r = $this->reportes->resumenVentas($this->filtros + ['id_producto' => $this->kit->id]);

        $this->assertSame(1, $r['unidades']);
        $this->assertEqualsWithDelta(410.00, $r['monto_lineas'], 0.001);
        $this->assertTrue($r['filtrado_por_producto']);
    }

    public function test_un_rango_sin_ventas_da_ceros(): void
    {
        $r = $this->reportes->resumenVentas(['tipo' => 'ventas', 'desde' => '2020-01-01', 'hasta' => '2020-01-31']);

        $this->assertSame(0, $r['pedidos']);
        $this->assertEqualsWithDelta(0.0, (float) $r['facturado']['total'], 0.001);
    }

    public function test_resumen_de_inventario(): void
    {
        // Stock tras las ventas: cuchilla 30 − 1 − 2 − 1 + 1 (anulación) = 27 · kit 3 − 1 = 2 (bajo stock)
        $agotado = $this->crearProducto(['stock' => 0, 'precio' => '99.00']);
        app(InventarioService::class)->registrar($this->cuchilla, 'entrada', 10, 'Compra a proveedor', $this->vendedorA);   // → 37

        $r = $this->reportes->resumenInventario(
            ['tipo' => 'inventario', 'desde' => now()->startOfMonth()->toDateString(), 'hasta' => now()->toDateString()],
            5
        );

        $this->assertSame(3, $r['productos']);
        $this->assertSame(39, $r['unidades']);                                    // 37 + 2 + 0
        $this->assertEqualsWithDelta(37 * 45 + 2 * 410, $r['valor_venta'], 0.001); // Nunca a costo
        $this->assertSame(1, $r['agotados']);
        $this->assertSame(1, $r['bajo']);
        $this->assertSame(10 + 1, $r['entradas']);                                // compra + reposición por anulación
        $this->assertSame(2 + 1 + 1, $r['salidas']);                               // ventas del mes (la del mes pasado no cuenta)
        $this->assertNotNull($agotado);
    }

    public function test_exportar_a_excel_entrega_las_mismas_filas_y_queda_auditado(): void
    {
        Excel::fake();
        $admin = $this->crearUsuario('directivo');

        $this->actingAs($admin)
            ->get(route('gestion.reportes.exportar', $this->filtros + ['formato' => 'excel']))
            ->assertOk();

        Excel::assertDownloaded(
            "reporte-ventas-{$this->filtros['desde']}-al-{$this->filtros['hasta']}.xlsx",
            fn (VentasExport $export) => $export->collection()->count() === 2
        );

        $auditoria = Reporte::sole();
        $this->assertSame('ventas', $auditoria->tipo);
        $this->assertSame('excel', $auditoria->formato);
        $this->assertSame(2, $auditoria->registros);
        $this->assertSame($admin->id, $auditoria->id_usuario);
    }

    public function test_exportar_inventario_a_excel_genera_dos_hojas(): void
    {
        Excel::fake();

        $this->actingAs($this->crearUsuario('directivo'))
            ->get(route('gestion.reportes.exportar', ['tipo' => 'inventario', 'formato' => 'excel'] + $this->filtros))
            ->assertOk();

        Excel::assertDownloaded(
            "reporte-inventario-{$this->filtros['desde']}-al-{$this->filtros['hasta']}.xlsx",
            fn (InventarioExport $export) => count($export->sheets()) === 2
        );
    }

    public function test_exportar_a_pdf(): void
    {
        $respuesta = $this->actingAs($this->crearUsuario('directivo'))
            ->get(route('gestion.reportes.exportar', $this->filtros + ['formato' => 'pdf']));

        $respuesta->assertOk();
        $this->assertSame('application/pdf', $respuesta->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        $this->assertSame(1, Reporte::where('formato', 'pdf')->count());
    }

    public function test_rango_mayor_a_un_anio_es_rechazado(): void
    {
        $this->actingAs($this->crearUsuario('directivo'))
            ->get(route('gestion.reportes.exportar', ['tipo' => 'ventas', 'desde' => '2023-01-01', 'hasta' => '2024-06-30', 'formato' => 'pdf']))
            ->assertSessionHasErrors('hasta');

        $this->assertSame(0, Reporte::count());
    }

    public function test_el_vendedor_no_puede_exportar_reportes(): void
    {
        $this->actingAs($this->vendedorA)
            ->get(route('gestion.reportes.exportar', $this->filtros + ['formato' => 'excel']))
            ->assertForbidden();
    }
}
