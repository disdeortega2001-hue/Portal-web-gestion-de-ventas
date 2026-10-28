<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Mejoras de usabilidad para el celular (crítica de diseño): agregar sin recargar,
 * cantidades con − / +, contacto por WhatsApp configurable, cédula en dos partes,
 * fotos redimensionadas y tablero legible sin JavaScript.
 */
class UsabilidadMovilTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
    }

    /* ---------- Agregar a la cotización ---------- */

    public function test_agregar_con_javascript_responde_json_sin_recargar(): void
    {
        $producto = $this->crearProducto(['codigo_fabrica' => 'COR-B75', 'stock' => 10]);

        $this->postJson(route('carrito.agregar', $producto), ['cantidad' => 2])
            ->assertOk()
            ->assertJson([
                'items' => 2,
                'llevas' => 2,
                'mensaje' => 'Agregado: COR-B75. Llevas 2.',
                'enlace' => route('carrito.index') . '#linea-' . $producto->id,   // Directo a su − / +
            ]);

        $this->postJson(route('carrito.agregar', $producto), ['cantidad' => 1])
            ->assertJson(['items' => 3, 'llevas' => 3, 'mensaje' => 'Agregado: COR-B75. Llevas 3.']);
    }

    public function test_agregar_mas_del_stock_con_javascript_devuelve_el_error_en_json(): void
    {
        $producto = $this->crearProducto(['stock' => 2]);

        $this->postJson(route('carrito.agregar', $producto), ['cantidad' => 3])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cantidad');
    }

    public function test_agregar_sin_javascript_vuelve_a_la_misma_tarjeta(): void
    {
        $producto = $this->crearProducto();

        $this->from(route('catalogo.index', ['page' => 2]))
            ->post(route('carrito.agregar', $producto), ['cantidad' => 1])
            ->assertRedirect(route('catalogo.index', ['page' => 2]) . '#producto-' . $producto->id);
    }

    /* ---------- Cantidades con − / + ---------- */

    public function test_los_botones_mas_y_menos_ajustan_la_cantidad(): void
    {
        $producto = $this->crearProducto(['stock' => 10]);
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 3]);

        $this->patch(route('carrito.actualizar', $producto), ['cantidad' => 3, 'ajuste' => 1]);
        $this->assertSame([$producto->id => 4], session('carrito'));

        $this->patch(route('carrito.actualizar', $producto), ['cantidad' => 4, 'ajuste' => -1]);
        $this->assertSame([$producto->id => 3], session('carrito'));
    }

    public function test_bajar_de_uno_quita_el_producto(): void
    {
        $producto = $this->crearProducto();
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);

        $this->patch(route('carrito.actualizar', $producto), ['cantidad' => 1, 'ajuste' => -1])
            ->assertSessionHas('exito');

        $this->assertSame([], session('carrito'));
    }

    /* ---------- Contacto por WhatsApp ---------- */

    public function test_sin_numero_configurado_no_hay_botones_de_whatsapp(): void
    {
        $this->crearProducto(['stock' => 0]);

        $this->get(route('catalogo.index'))->assertOk()->assertDontSee('wa.me', false);
    }

    public function test_con_numero_aparece_whatsapp_y_la_consulta_por_agotados(): void
    {
        $this->configurar(['telefono_whatsapp' => '04141234567']);
        $agotado = $this->crearProducto(['codigo_fabrica' => 'EMB-MF-001', 'stock' => 0]);

        $this->get(route('catalogo.index'))
            ->assertSee('https://wa.me/584141234567', false)
            ->assertSee('Preguntar');

        $this->get(route('catalogo.show', $agotado))
            ->assertSee('Preguntar cuándo llega')
            ->assertSee(rawurlencode('EMB-MF-001'), false);
    }

    public function test_el_administrador_guarda_el_numero_en_cualquier_formato(): void
    {
        $this->actingAs($this->crearUsuario('directivo'))
            ->put(route('admin.configuracion.update'), [
                'tasa_cambio' => '36,50', 'porcentaje_iva' => '16', 'umbral_stock_bajo' => '5',
                'descuento_porcentaje' => '5', 'descuento_monto_minimo' => '500',
                'telefono_whatsapp' => '+58 414-123 4567',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('04141234567', Configuracion::valor('telefono_whatsapp'));
    }

    /* ---------- Textos para el cliente ---------- */

    public function test_el_cliente_lee_quedan_y_el_personal_lee_bajo_stock(): void
    {
        $producto = $this->crearProducto(['stock' => 3]);

        $this->get(route('catalogo.show', $producto))->assertSee('Quedan 3')->assertDontSee('Bajo stock');
        $this->actingAs($this->crearUsuario('vendedor'))
            ->get(route('gestion.inventario.index'))->assertSee('Bajo stock: 3');
    }

    /* ---------- Cédula en dos partes ---------- */

    public function test_la_cedula_en_dos_partes_se_guarda_en_el_formato_de_siempre(): void
    {
        $producto = $this->crearProducto();
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);

        $this->post(route('cotizaciones.store'), [
            'tipo_identificacion' => 'J',
            'numero_identificacion' => '3012345677',
            'nombre' => 'Agropecuaria La Ceiba',
            'telefono' => '0414-7654321',
            'direccion' => 'Sector El Guafal, Zaraza',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clientes', ['identificacion' => 'J-3012345677']);
    }

    /* ---------- Fotos livianas ---------- */

    public function test_la_foto_subida_se_guarda_en_dos_tamanos_livianos(): void
    {
        Storage::fake('public');
        $categoria = $this->crearProducto()->id_categoria;

        $this->actingAs($this->crearUsuario('directivo'))
            ->post(route('admin.productos.store'), [
                'codigo_fabrica' => 'BOM-TEST-1', 'nombre' => 'Bomba de prueba', 'marca' => 'Pedrollo',
                'precio' => '285.00', 'id_categoria' => $categoria, 'stock' => 3, 'activo' => '1',
                'imagen' => UploadedFile::fake()->image('foto-celular.jpg', 3000, 2000),
            ])
            ->assertSessionHasNoErrors();

        $imagen = Producto::where('codigo_fabrica', 'BOM-TEST-1')->value('imagen');
        $mini = str_replace('-800.', '-240.', $imagen);

        Storage::disk('public')->assertExists([$imagen, $mini]);
        $this->assertSame(800, getimagesizefromstring(Storage::disk('public')->get($imagen))[0]);
        $this->assertSame(240, getimagesizefromstring(Storage::disk('public')->get($mini))[0]);
    }

    public function test_sin_foto_se_usa_la_silueta_de_su_categoria(): void
    {
        $producto = $this->crearProducto()->load('categoria');
        $producto->categoria->update(['nombre' => 'Filtros']);

        $this->assertStringEndsWith('img/categorias/filtro.svg', $producto->fresh('categoria')->urlImagen());
    }

    /* ---------- Tablero ---------- */

    public function test_el_tablero_muestra_las_cifras_sin_depender_de_javascript(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $this->venta($vendedor, $this->crearCliente(), [[$this->crearProducto(['precio' => '45.00']), 2]]);

        // 2 × 45 = 90 + IVA 14,40 = 104,40, impreso en el HTML (no en un <span> vacío para JS)
        $this->actingAs($vendedor)->get(route('gestion.tablero'))
            ->assertOk()
            ->assertSee('USD 104,40');
    }

    public function test_sin_whatsapp_el_tablero_avisa_al_administrador_como_activarlo(): void
    {
        $this->actingAs($this->crearUsuario('directivo'))->get(route('gestion.tablero'))
            ->assertSee('Los clientes todavía no pueden escribirte.')
            ->assertSee('Configurar WhatsApp');

        $this->actingAs($this->crearUsuario('vendedor'))->get(route('gestion.tablero'))
            ->assertSee('Pídele al personal administrativo que lo configure.')
            ->assertDontSee('Configurar WhatsApp');

        $this->configurar(['telefono_whatsapp' => '04141234567']);
        $this->actingAs($this->crearUsuario('directivo'))->get(route('gestion.tablero'))
            ->assertDontSee('Los clientes todavía no pueden escribirte.');
    }

    /* ---------- Textos del catálogo ---------- */

    public function test_el_panel_se_llama_ajustar_para_no_confundirse_con_la_categoria_filtros(): void
    {
        $this->crearProducto()->categoria->update(['nombre' => 'Filtros']);

        $this->get(route('catalogo.index'))
            ->assertSee('Ajustar')
            ->assertSee('Ajustar búsqueda')
            ->assertSee('Ver resultados');
    }

    /* ---------- Recordar al invitado ---------- */

    private function enviarCotizacionComoInvitado(array $extra = [])
    {
        $producto = $this->crearProducto();
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1]);

        return $this->post(route('cotizaciones.store'), $extra + [
            'tipo_identificacion' => 'V', 'numero_identificacion' => '12345678',
            'nombre' => 'José Gregorio Rondón', 'telefono' => '0426-3412345',
            'direccion' => 'Hato La Esperanza, Infante',
        ]);
    }

    public function test_con_recordar_marcado_los_datos_quedan_en_este_telefono(): void
    {
        $this->enviarCotizacionComoInvitado(['recordar' => '1'])
            ->assertSessionHasNoErrors()
            ->assertCookie('r7_cliente');

        // Otro día, otra cotización: el formulario llega lleno
        $datos = json_encode(['tipo_identificacion' => 'V', 'numero_identificacion' => '12345678', 'nombre' => 'José Gregorio Rondón',
            'telefono' => '0426-3412345', 'correo' => '', 'direccion' => 'Hato La Esperanza, Infante']);
        $this->post(route('carrito.agregar', $this->crearProducto()), ['cantidad' => 1]);

        $this->withCookie('r7_cliente', $datos)->get(route('carrito.index'))
            ->assertSee('value="José Gregorio Rondón"', false)
            ->assertSee('value="0426-3412345"', false)
            ->assertSee('Borrar mis datos');
    }

    public function test_sin_recordar_no_se_guarda_nada(): void
    {
        $this->enviarCotizacionComoInvitado()
            ->assertSessionHasNoErrors()
            ->assertCookieExpired('r7_cliente');
    }

    public function test_borrar_mis_datos_elimina_la_cookie(): void
    {
        $this->delete(route('cotizaciones.olvidar-datos'))
            ->assertRedirect(route('carrito.index'))
            ->assertCookieExpired('r7_cliente');
    }

    /* ---------- La cotización sin enviar sobrevive a la sesión ---------- */

    public function test_la_cotizacion_se_respalda_en_una_cookie(): void
    {
        $producto = $this->crearProducto();

        $this->post(route('carrito.agregar', $producto), ['cantidad' => 2])
            ->assertCookie('r7_cotizacion', json_encode([$producto->id => 2]));
    }

    public function test_al_volver_otro_dia_se_recupera_la_cotizacion(): void
    {
        $producto = $this->crearProducto(['nombre' => 'Disco de arado 26']);

        // Sesión nueva (la anterior venció), pero el teléfono conserva el respaldo
        $this->withCookie('r7_cotizacion', json_encode([$producto->id => 2]))
            ->get(route('carrito.index'))
            ->assertSee('Disco de arado 26')
            ->assertSee('value="2"', false);
    }

    public function test_un_respaldo_alterado_se_ignora(): void
    {
        $this->withCookie('r7_cotizacion', json_encode(['x' => 'mucho', '99999' => -5]))
            ->get(route('carrito.index'))
            ->assertSee('Tu cotización está vacía');
    }
}
