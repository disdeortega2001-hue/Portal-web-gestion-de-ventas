<?php

namespace Tests\Feature;

use App\Services\CotizacionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

/**
 * Control de acceso por rol (middleware VerificarRol) y privacidad de los datos
 * de los clientes y del costo de los productos.
 */
class ControlAccesoTest extends TestCase
{
    use RefreshDatabase, CreaDatos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurar();
    }

    /** Matriz rol × ruta: qué código HTTP debe recibir cada perfil. */
    public static function matrizDeAcceso(): array
    {
        return [
            // ruta,                             cliente, vendedor, administrador
            'tablero' => ['gestion.tablero', 403, 200, 200],
            'cotizaciones' => ['gestion.cotizaciones.index', 403, 200, 200],
            'pedidos' => ['gestion.pedidos.index', 403, 200, 200],
            'inventario' => ['gestion.inventario.index', 403, 200, 200],
            'reportes' => ['gestion.reportes.index', 403, 403, 200],
            'usuarios' => ['admin.usuarios.index', 403, 403, 200],
            'categorías' => ['admin.categorias.index', 403, 403, 200],
            'productos (admin)' => ['admin.productos.index', 403, 403, 200],
            'configuración' => ['admin.configuracion.edit', 403, 403, 200],
            'mis cotizaciones' => ['cuenta.cotizaciones', 200, 403, 403],
        ];
    }

    #[DataProvider('matrizDeAcceso')]
    public function test_cada_rol_solo_entra_a_sus_modulos(string $ruta, int $cliente, int $vendedor, int $administrador): void
    {
        foreach (['cliente' => $cliente, 'vendedor' => $vendedor, 'administrador' => $administrador] as $rol => $esperado) {
            $this->actingAs($this->crearUsuario($rol))
                ->get(route($ruta))
                ->assertStatus($esperado);
        }
    }

    public function test_un_visitante_sin_sesion_es_enviado_al_login(): void
    {
        $this->get(route('gestion.tablero'))->assertRedirect(route('login'));
        $this->get(route('admin.productos.index'))->assertRedirect(route('login'));
    }

    public function test_el_catalogo_es_publico_y_nunca_muestra_el_costo(): void
    {
        $producto = $this->crearProducto(['nombre' => 'Filtro de aceite', 'precio' => '18.90', 'costo' => '11.37']);

        $this->get(route('catalogo.index'))->assertOk()->assertSee('Filtro de aceite')->assertDontSee('11,37')->assertDontSee('11.37');
        $this->get(route('catalogo.show', $producto))->assertOk()->assertDontSee('11,37')->assertDontSee('11.37');
        $this->getJson(route('catalogo.stock', $producto))->assertOk()->assertJsonMissing(['costo' => '11.37']);
    }

    public function test_un_producto_agotado_no_se_puede_agregar_al_carrito(): void
    {
        $producto = $this->crearProducto(['stock' => 0]);

        $this->get(route('catalogo.show', $producto))->assertSee('Agotado');
        $this->post(route('carrito.agregar', $producto), ['cantidad' => 1])->assertSessionHasErrors('cantidad');
        $this->assertEmpty(session('carrito', []));
    }

    public function test_un_usuario_desactivado_es_expulsado(): void
    {
        $vendedor = $this->crearUsuario('vendedor');
        $this->actingAs($vendedor);
        $vendedor->update(['estado' => 'inactivo']);

        $this->get(route('gestion.tablero'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_un_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $this->crearUsuario('vendedor', ['correo' => 'inactivo@prueba.test', 'estado' => 'inactivo']);

        $this->post(route('login'), ['correo' => 'inactivo@prueba.test', 'contrasena' => 'Clave1234'])
            ->assertSessionHasErrors('correo');
        $this->assertGuest();
    }

    public function test_el_login_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        $this->crearUsuario('vendedor', ['correo' => 'vendedor@prueba.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['correo' => 'vendedor@prueba.test', 'contrasena' => 'equivocada1']);
        }

        // Ni siquiera la contraseña correcta entra mientras dura el bloqueo
        $this->post(route('login'), ['correo' => 'vendedor@prueba.test', 'contrasena' => 'Clave1234'])
            ->assertSessionHasErrors('correo');
        $this->assertStringStartsWith('Demasiados intentos', session('errors')->first('correo'));
        $this->assertGuest();
    }

    public function test_un_cliente_no_puede_ver_la_cotizacion_de_otro_cliente(): void
    {
        $usuarioA = $this->crearUsuario('cliente');
        $clienteA = $this->crearCliente($usuarioA);
        $clienteB = $this->crearCliente($this->crearUsuario('cliente'));
        $producto = $this->crearProducto();
        $lineas = collect([$producto->id => ['producto' => $producto, 'precio' => '10.00', 'cantidad' => 1]]);

        $propia = app(CotizacionService::class)->crear($clienteA, $lineas);
        $ajena = app(CotizacionService::class)->crear($clienteB, $lineas);

        $this->actingAs($usuarioA)->get(route('cotizaciones.show', $propia))->assertOk();
        $this->actingAs($usuarioA)->get(route('cotizaciones.show', $ajena))->assertForbidden();
        $this->actingAs($usuarioA)->get(route('cotizaciones.pdf', $ajena))->assertForbidden();
    }

    public function test_un_invitado_solo_ve_su_cotizacion_con_el_enlace_firmado(): void
    {
        $producto = $this->crearProducto();
        $cotizacion = app(CotizacionService::class)->crear(
            $this->crearCliente(),
            collect([$producto->id => ['producto' => $producto, 'precio' => '10.00', 'cantidad' => 2]])
        );

        $this->get(route('cotizaciones.show', $cotizacion))->assertForbidden();   // Adivinar el número no sirve

        $firmada = URL::temporarySignedRoute('cotizaciones.show', now()->addDay(), ['cotizacion' => $cotizacion->id]);
        $this->get($firmada)->assertOk()->assertSee($cotizacion->codigo);

        $this->travel(2)->days();
        $this->get($firmada)->assertForbidden();   // El enlace vence
    }

    public function test_el_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->crearUsuario('administrador');

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $admin), [
                'nombre' => $admin->nombre, 'correo' => $admin->correo, 'rol' => 'administrador', 'estado' => 'inactivo',
            ])
            ->assertSessionHasErrors('estado');

        $this->assertSame('activo', $admin->fresh()->estado);
    }
}
