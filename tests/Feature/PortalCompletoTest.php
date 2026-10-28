<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Producto;
use App\Services\CotizacionService;
use App\Support\PlantillaCatalogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Lo que un portal público necesita además de vender (inicio, buscadores, páginas legales y de error)
 * y las funciones nuevas del personal: clientes, venta de mostrador, carga desde Excel y búsquedas.
 */
class PortalCompletoTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar(['telefono_whatsapp' => '04129477195']);
    }

    /* ---------- Sitio público ---------- */

    public function test_la_portada_muestra_la_tasa_las_marcas_y_los_mas_pedidos(): void
    {
        $this->crearProducto(['codigo_fabrica' => 'CC55', 'nombre' => 'Cruceta 30 x 106', 'stock' => 8]);

        $this->get('/')->assertOk()
            ->assertSee('Tasa del día')
            ->assertSee('Bs. 36,50')
            ->assertSee('img/portada/sede-fachada.jpg', false)
            ->assertSeeInOrder(['Marchesan Tatu', 'Colombo Aemco', 'Vicon', 'Lumagi'])
            ->assertSee('Los más pedidos')
            ->assertSee('CC55')
            ->assertSee(route('catalogo.index'), false);
    }

    public function test_el_mapa_del_sitio_y_robots_guian_a_los_buscadores(): void
    {
        $producto = $this->crearProducto();
        $oculto = $this->crearProducto(['activo' => false]);

        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('catalogo.show', $producto), false)
            ->assertDontSee(route('catalogo.show', $oculto), false)
            ->assertSee(route('contacto'), false);

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /gestion')
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: ' . route('sitemap'));
    }

    public function test_cada_producto_trae_vista_previa_y_datos_para_google(): void
    {
        $producto = $this->crearProducto(['codigo_fabrica' => 'LUB600', 'nombre' => 'Engrasadora 12 kg', 'marca' => 'Lumagi', 'stock' => 3]);

        $this->get(route('catalogo.show', $producto))->assertOk()
            ->assertSee('<meta property="og:title" content="Engrasadora 12 kg · Inversiones Rialto 7">', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"sku":"LUB600"', false)
            ->assertSee('https://schema.org/InStock', false)
            ->assertSee('<link rel="canonical"', false);

        // Las páginas privadas no se indexan
        $this->get(route('carrito.index'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_contacto_y_paginas_legales(): void
    {
        $this->get(route('contacto'))->assertOk()->assertSee('Escríbenos tu consulta')->assertSee('0412-9477195');
        $this->get(route('legal.privacidad'))->assertOk()->assertSee('artículo 28 de la Constitución');
        $this->get(route('legal.terminos'))->assertOk()->assertSee('IVA, que hoy es de 16 %')->assertSee('Ley sobre Mensajes de Datos');
    }

    public function test_el_formulario_de_contacto_abre_whatsapp_con_el_mensaje_listo(): void
    {
        $this->get(route('contacto.whatsapp', ['nombre' => 'Ana', 'codigo' => 'CC55', 'mensaje' => 'Necesito 4']))
            ->assertRedirect('https://wa.me/584129477195?text=' . rawurlencode("Hola, soy Ana. Repuesto: CC55.\nNecesito 4"));

        $this->get(route('contacto.whatsapp', ['nombre' => 'Ana']))->assertSessionHasErrors('mensaje');
    }

    public function test_la_pagina_no_encontrada_orienta_al_catalogo(): void
    {
        $this->get('/esta-pagina-no-existe')->assertNotFound()->assertSee('Esta página no existe')->assertSee('Ir al catálogo');
    }

    public function test_el_catalogo_ordena_por_precio_y_sugiere_mientras_se_escribe(): void
    {
        $this->crearProducto(['codigo_fabrica' => 'CARO-1', 'precio' => '900.00']);
        $this->crearProducto(['codigo_fabrica' => 'BARATO-1', 'precio' => '5.00']);

        $this->get(route('catalogo.index', ['orden' => 'precio_menor']))->assertOk()->assertSeeInOrder(['BARATO-1', 'CARO-1']);
        $this->get(route('catalogo.index', ['orden' => 'precio_mayor']))->assertOk()->assertSeeInOrder(['CARO-1', 'BARATO-1']);

        $this->getJson(route('catalogo.sugerencias', ['q' => 'barat']))->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.codigo', 'BARATO-1')->assertJsonPath('0.precio', 'USD 5,00');
        $this->getJson(route('catalogo.sugerencias', ['q' => 'b']))->assertOk()->assertJsonCount(0);
    }

    public function test_el_cliente_repite_una_cotizacion_con_los_precios_de_hoy(): void
    {
        $usuario = $this->crearUsuario('cliente');
        $cliente = $this->crearCliente($usuario);
        $disco = $this->crearProducto(['codigo_fabrica' => 'DISCO-1', 'stock' => 3]);
        $agotado = $this->crearProducto(['codigo_fabrica' => 'AGOT-1', 'stock' => 5]);
        $cotizacion = app(CotizacionService::class)->crear($cliente, collect([
            ['producto' => $disco, 'precio' => '10.00', 'cantidad' => 5],
            ['producto' => $agotado, 'precio' => '10.00', 'cantidad' => 1],
        ]));
        $agotado->update(['stock' => 0]);

        $this->actingAs($usuario)->post(route('cuenta.cotizaciones.repetir', $cotizacion))
            ->assertRedirect(route('carrito.index'))
            ->assertSessionHas('exito', fn ($m) => str_contains($m, 'Agregamos 1 producto') && str_contains($m, 'AGOT-1'));
        $this->get(route('carrito.index'))->assertSee('DISCO-1');   // Ajustado a las 3 que hay

        // Una cotización de otro cliente no se puede repetir
        $ajena = app(CotizacionService::class)->crear($this->crearCliente(), collect([['producto' => $disco, 'precio' => '10.00', 'cantidad' => 1]]));
        $this->post(route('cuenta.cotizaciones.repetir', $ajena))->assertForbidden();
    }

    /* ---------- Personal ---------- */

    public function test_el_directorio_de_clientes_busca_y_muestra_el_historial(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $cliente = $this->crearCliente();
        $cliente->update(['nombre' => 'José Gregorio Rondón', 'identificacion' => 'V-12345678', 'telefono' => '0426-3412345']);
        $otro = $this->crearCliente();
        $this->venta($vendedor, $cliente, [[$this->crearProducto(['precio' => '100.00']), 1]]);

        $this->actingAs($vendedor);
        $this->get(route('gestion.clientes.index', ['q' => '12345678']))->assertOk()->assertSee('José Gregorio Rondón')->assertDontSee($otro->identificacion);
        $this->get(route('gestion.clientes.index', ['q' => '04263412345']))->assertSee('José Gregorio Rondón');
        $this->get(route('gestion.clientes.show', $cliente))->assertOk()
            ->assertSee('USD 116,00')                          // 100 + IVA, venta confirmada
            ->assertSee('https://wa.me/584263412345', false);
        $this->getJson(route('gestion.clientes.buscar', ['q' => 'Rondón']))->assertOk()->assertJsonPath('0.identificacion', 'V-12345678');

        $this->put(route('gestion.clientes.update', $cliente), [
            'tipo_identificacion' => 'V', 'numero_identificacion' => '12345678', 'nombre' => 'José G. Rondón',
            'telefono' => '0414-5550000', 'direccion' => 'Hato La Esperanza, Infante',
        ])->assertRedirect(route('gestion.clientes.show', $cliente));
        $this->assertSame('0414-5550000', $cliente->fresh()->telefono);

        // Un cliente del portal no entra al directorio
        $this->actingAs($this->crearUsuario('cliente'))->get(route('gestion.clientes.index'))->assertForbidden();
    }

    public function test_registrar_un_cliente_desde_la_venta_vuelve_a_la_venta_con_el_cliente_elegido(): void
    {
        $this->actingAs($this->crearUsuario('vendedor'));

        $respuesta = $this->post(route('gestion.clientes.store'), [
            'tipo_identificacion' => 'J', 'numero_identificacion' => '401234567', 'nombre' => 'Agropecuaria Los Samanes',
            'telefono' => '0235-3411111', 'direccion' => 'Vía Tucupido, km 5', 'volver' => 'pedido',
        ]);
        $nuevo = Cliente::where('identificacion', 'J-401234567')->firstOrFail();
        $respuesta->assertRedirect(route('gestion.pedidos.create', ['cliente' => $nuevo->id]));

        $this->get(route('gestion.pedidos.create', ['cliente' => $nuevo->id]))->assertOk()->assertSee('Agropecuaria Los Samanes');
    }

    public function test_cotizaciones_y_pedidos_se_buscan_por_cliente_o_codigo(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $cliente = $this->crearCliente();
        $cliente->update(['nombre' => 'Hato La Esperanza']);
        $producto = $this->crearProducto();
        $buscada = app(CotizacionService::class)->crear($cliente, collect([['producto' => $producto, 'precio' => '10.00', 'cantidad' => 1]]));
        $otra = app(CotizacionService::class)->crear($this->crearCliente(), collect([['producto' => $producto, 'precio' => '10.00', 'cantidad' => 1]]));
        $pedido = $this->venta($vendedor, $cliente, [[$producto, 1]], false);

        $this->actingAs($vendedor);
        $this->get(route('gestion.cotizaciones.index', ['q' => 'Esperanza']))->assertOk()->assertSee($buscada->codigo)->assertDontSee($otra->codigo);
        $this->get(route('gestion.cotizaciones.index', ['q' => $otra->codigo]))->assertSee($otra->codigo)->assertDontSee($buscada->codigo);
        $this->get(route('gestion.pedidos.index', ['q' => 'Esperanza']))->assertOk()->assertSee($pedido->codigo);

        // El menú avisa lo que espera atención: 2 cotizaciones y 1 pedido pendientes
        $this->get(route('gestion.tablero'))->assertSee('aria-label="2 por atender"', false)->assertSee('aria-label="1 por atender"', false);
    }

    public function test_el_administrador_carga_productos_desde_excel_con_revision_previa(): void
    {
        Storage::fake('public');
        $admin = $this->crearUsuario('administrador');
        $carpeta = storage_path('framework/testing/excel-' . uniqid());
        File::ensureDirectoryExists($carpeta);
        $archivo = "{$carpeta}/productos.xlsx";
        PlantillaCatalogo::generar($archivo);
        $libro = IOFactory::load($archivo);
        $libro->getSheetByName('Productos')->fromArray([
            ['CC56', 'Yugo estriado 330', 'Transmisión y cardanes', 'Colombo Aemco', '80', 50, '', '', '', ''],
        ], null, 'A2', true);
        IOFactory::createWriter($libro, 'Xlsx')->save($archivo);

        $this->actingAs($admin);
        $this->get(route('admin.importacion.create'))->assertOk()->assertSee('Descargar plantilla');
        $this->get(route('admin.importacion.plantilla'))->assertOk()->assertDownload('plantilla-productos.xlsx');

        $revision = $this->post(route('admin.importacion.store'), [
            'archivo' => new UploadedFile($archivo, 'productos.xlsx', null, null, true),
        ])->assertOk()->assertSee('1 producto listo')->assertSee('CC56');
        $this->assertSame(0, Producto::count());   // La revisión no guarda nada

        $token = $revision->viewData('token');
        $this->post(route('admin.importacion.confirmar'), ['token' => $token])->assertRedirect(route('admin.productos.index'));
        $this->assertSame(50, Producto::where('codigo_fabrica', 'CC56')->value('stock'));

        // Un archivo con errores no avanza
        $libro->getSheetByName('Productos')->fromArray([['CC57', 'Sin precio', 'Cardanes', 'Aemco', '', 5, '', '', '', '']], null, 'A2', true);
        IOFactory::createWriter($libro, 'Xlsx')->save($archivo);
        $this->post(route('admin.importacion.store'), ['archivo' => new UploadedFile($archivo, 'productos.xlsx', null, null, true)])
            ->assertOk()->assertSee('no se cargó nada')->assertSee('Fila 2');

        File::deleteDirectory($carpeta);

        // El vendedor no puede cargar el catálogo
        $this->actingAs($this->crearUsuario('vendedor'))->get(route('admin.importacion.create'))->assertForbidden();
    }
}
