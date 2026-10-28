<?php

namespace Tests\Feature;

use App\Exports\VentasExport;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Services\CotizacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as FormatoExcel;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Huecos encontrados en la revisión de seguridad y sus correcciones.
 */
class SeguridadTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
    }

    private function cotizarComoInvitado(string $nombre, string $telefono): void
    {
        $this->post(route('carrito.agregar', $this->crearProducto()), ['cantidad' => 1]);
        $this->post(route('cotizaciones.store'), [
            'tipo_identificacion' => 'V', 'numero_identificacion' => '12345678',
            'nombre' => $nombre, 'telefono' => $telefono, 'direccion' => 'Sector cualquiera, Zaraza',
        ])->assertSessionHasNoErrors();
    }

    /* ---------- La cédula no prueba identidad ---------- */

    public function test_escribir_una_cedula_ajena_no_cambia_el_telefono_del_cliente(): void
    {
        $cliente = Cliente::create([
            'identificacion' => 'V-12345678', 'nombre' => 'José Rondón',
            'telefono' => '0426-3412345', 'direccion' => 'Hato La Esperanza, Infante',
        ]);

        $this->cotizarComoInvitado('Otra persona', '0414-9999999');

        // La ficha del cliente real no cambia: el vendedor sigue llamando a su número
        $this->assertSame('0426-3412345', $cliente->fresh()->telefono);
        $this->assertSame('José Rondón', $cliente->fresh()->nombre);

        // El dato distinto queda anotado en la cotización para que el vendedor lo verifique
        $this->assertStringContainsString('verificar antes de llamar', Cotizacion::latest('id')->first()->observaciones);
        $this->assertStringContainsString('0414-9999999', Cotizacion::latest('id')->first()->observaciones);
    }

    public function test_el_mismo_cliente_que_vuelve_no_genera_notas(): void
    {
        Cliente::create([
            'identificacion' => 'V-12345678', 'nombre' => 'José Rondón',
            'telefono' => '0426-3412345', 'direccion' => 'Sector cualquiera, Zaraza',
        ]);

        $this->cotizarComoInvitado('José Rondón', '04263412345');   // Mismo número, sin guion

        $this->assertNull(Cotizacion::latest('id')->first()->observaciones);
    }

    /** Ficha de invitado con una cotización previa (historial que hay que proteger). */
    private function clienteConHistorial(): array
    {
        $producto = $this->crearProducto();
        $cliente = Cliente::create([
            'identificacion' => 'V-12345678', 'nombre' => 'José Rondón',
            'telefono' => '0426-3412345', 'direccion' => 'Hato La Esperanza, Infante',
        ]);
        $cotizacion = app(CotizacionService::class)->crear($cliente, collect([
            $producto->id => ['producto' => $producto, 'precio' => '10.00', 'cantidad' => 1],
        ]));

        return [$cliente, $cotizacion];
    }

    private function registrar(string $correo, string $nombre = 'Otra persona')
    {
        return $this->post(route('registro'), [
            'tipo_identificacion' => 'V', 'numero_identificacion' => '12345678',
            'nombre' => $nombre, 'telefono' => '0414-9999999', 'direccion' => 'Otra dirección, Tucupido',
            'correo' => $correo, 'contrasena' => 'Clave1234', 'contrasena_confirmation' => 'Clave1234',
        ]);
    }

    public function test_registrar_una_cedula_con_historial_queda_en_verificacion(): void
    {
        [$cliente, $anterior] = $this->clienteConHistorial();

        $this->registrar('otra@prueba.test')->assertSessionHasNoErrors();
        $usuario = \App\Models\Usuario::where('correo', 'otra@prueba.test')->sole();

        // La cuenta NO queda asociada: queda como solicitud y no ve el historial
        $this->assertNull($cliente->fresh()->id_usuario);
        $this->assertSame($usuario->id, $cliente->fresh()->id_usuario_solicitante);
        $this->get(route('cuenta.cotizaciones'))->assertOk()
            ->assertSee('Estamos verificando tu cuenta.')
            ->assertDontSee($anterior->codigo);
        $this->get(route('cotizaciones.show', $anterior))->assertForbidden();

        // Y no reemplazó el teléfono del cliente real
        $this->assertSame('0426-3412345', $cliente->fresh()->telefono);
    }

    public function test_nadie_mas_puede_registrar_la_cedula_mientras_se_verifica(): void
    {
        $this->clienteConHistorial();
        $this->registrar('primera@prueba.test')->assertSessionHasNoErrors();
        auth()->logout();

        $this->registrar('segunda@prueba.test')->assertSessionHasErrors('identificacion');
    }

    public function test_el_vendedor_verifica_y_entonces_el_cliente_ve_su_historial(): void
    {
        [$cliente, $anterior] = $this->clienteConHistorial();
        $this->registrar('jose@prueba.test', 'José Rondón');
        $usuario = \App\Models\Usuario::where('correo', 'jose@prueba.test')->sole();

        $vendedor = $this->crearUsuario('vendedor');
        $this->actingAs($vendedor)->get(route('gestion.verificaciones.index'))
            ->assertSee('0426-3412345')   // Se llama al teléfono de la FICHA
            ->assertSee('José Rondón');
        $this->actingAs($vendedor)->post(route('gestion.verificaciones.aprobar', $cliente))->assertSessionHas('exito');

        $this->assertSame($usuario->id, $cliente->fresh()->id_usuario);
        $this->assertNull($cliente->fresh()->id_usuario_solicitante);
        $this->actingAs($usuario->fresh())->get(route('cuenta.cotizaciones'))->assertSee($anterior->codigo);
        $this->actingAs($usuario->fresh())->get(route('cotizaciones.show', $anterior))->assertOk();
    }

    public function test_si_no_es_la_misma_persona_el_vendedor_rechaza(): void
    {
        [$cliente] = $this->clienteConHistorial();
        $this->registrar('otra@prueba.test');

        $this->actingAs($this->crearUsuario('vendedor'))
            ->post(route('gestion.verificaciones.rechazar', $cliente))->assertSessionHas('exito');

        $this->assertNull($cliente->fresh()->id_usuario);
        $this->assertNull($cliente->fresh()->id_usuario_solicitante);
    }

    public function test_una_ficha_sin_historial_se_asocia_al_registrarse(): void
    {
        $cliente = Cliente::create([
            'identificacion' => 'V-12345678', 'nombre' => 'José Rondón',
            'telefono' => '0426-3412345', 'direccion' => 'Hato La Esperanza, Infante',
        ]);

        $this->registrar('jose@prueba.test', 'José Rondón')->assertSessionHasNoErrors();

        $this->assertNotNull($cliente->fresh()->id_usuario);   // Sin historial no hay nada que proteger
        $this->assertNull($cliente->fresh()->id_usuario_solicitante);
    }

    public function test_un_usuario_conectado_que_abre_ingresar_va_a_su_inicio_y_no_a_un_404(): void
    {
        $this->actingAs($this->crearUsuario('vendedor'))->get(route('login'))->assertRedirect(route('gestion.tablero'));
        $this->actingAs($this->crearUsuario('cliente'))->get(route('registro'))->assertRedirect(route('catalogo.index'));
    }

    public function test_el_cliente_no_puede_ver_la_pantalla_de_verificacion(): void
    {
        $this->actingAs($this->crearUsuario('cliente'))->get(route('gestion.verificaciones.index'))->assertForbidden();
    }

    /* ---------- Validación con soft deletes ---------- */

    public function test_no_se_puede_vender_un_producto_eliminado(): void
    {
        $producto = $this->crearProducto();
        $producto->delete();

        $this->actingAs($this->crearUsuario('vendedor'))
            ->post(route('gestion.pedidos.store'), [
                'id_cliente' => $this->crearCliente()->id,
                'items' => [['id_producto' => $producto->id, 'cantidad' => 1]],
            ])
            ->assertSessionHasErrors('items.0.id_producto');   // Antes: error 500
    }

    public function test_no_se_registran_movimientos_de_un_producto_eliminado(): void
    {
        $producto = $this->crearProducto();
        $producto->delete();

        $this->actingAs($this->crearUsuario('administrativo'))
            ->post(route('gestion.inventario.store'), [
                'id_producto' => $producto->id, 'tipo' => 'entrada', 'cantidad' => 5, 'motivo' => 'Compra a proveedor',
            ])
            ->assertSessionHasErrors('id_producto');
    }

    public function test_un_producto_oculto_no_expone_su_stock(): void
    {
        $producto = $this->crearProducto(['activo' => false]);

        $this->getJson(route('catalogo.stock', $producto))->assertNotFound();
    }

    /* ---------- Inyección de fórmulas en Excel ---------- */

    public function test_un_nombre_con_formula_se_exporta_como_texto(): void
    {
        $formula = '=HYPERLINK("http://sitio-malicioso.test","Ver detalles")';
        $fila = (object) [
            'fecha_confirmacion' => now()->toDateTimeString(), 'pedido' => 'PED-000001',
            'identificacion' => 'V-12345678', 'cliente' => $formula, 'vendedor' => 'Vendedor',
            'codigo_fabrica' => 'CUC-001', 'producto' => 'Cuchilla', 'cantidad' => 2,
            'precio_unitario' => '45.00', 'subtotal' => '90.00',
        ];

        $ruta = tempnam(sys_get_temp_dir(), 'r7') . '.xlsx';
        file_put_contents($ruta, Excel::raw(new VentasExport(collect([$fila])), FormatoExcel::XLSX));
        // maatwebsite/excel deja el binder de la exportación como binder global de PhpSpreadsheet;
        // sin restablecerlo, la lectura del archivo volvería a convertir los números en texto.
        Cell::setValueBinder(new DefaultValueBinder());
        $hoja = IOFactory::load($ruta)->getActiveSheet();
        @unlink($ruta);

        $this->assertSame(DataType::TYPE_STRING, $hoja->getCell('D2')->getDataType());   // Columna "Cliente"
        $this->assertSame($formula, $hoja->getCell('D2')->getValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $hoja->getCell('J2')->getDataType());  // Los montos siguen siendo números
    }

    public function test_las_paginas_llevan_politica_de_contenido_y_no_anuncian_php(): void
    {
        $respuesta = $this->get(route('inicio'))->assertOk();

        $csp = $respuesta->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]+'/", $csp);
        $this->assertFalse($respuesta->headers->has('X-Powered-By'));

        // El script en línea de la página lleva el nonce de la política
        preg_match("/'nonce-([A-Za-z0-9]+)'/", $csp, $m);
        $respuesta->assertSee('nonce="' . $m[1] . '"', false);
    }
}
