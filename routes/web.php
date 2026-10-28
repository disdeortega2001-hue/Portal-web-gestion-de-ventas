<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\Gestion;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\NosotrosController;
use App\Http\Controllers\SitioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web del portal Rialto 7
|--------------------------------------------------------------------------
| Organizadas en bloques según quién puede acceder:
|   1. Públicas (catálogo, carrito, cotización como invitado)
|   2. Invitados (login / registro / recuperar contraseña)
|   3. Cliente autenticado (compra, mi cuenta, reporte de pagos)
|   4. Panel, los tres perfiles: vendedor, administrativo y directivo
|      (tablero, cotizaciones, pedidos, clientes, existencias)
|   5. Administrativo y directivo (inventario, pagos, reportes, catálogo, tasa e IVA)
|   6. Solo directivo (usuarios y respaldos)
*/

// 1. PÚBLICAS ---------------------------------------------------------------
Route::get('/', InicioController::class)->name('inicio');
Route::get('/catalogo', [CatalogoController::class, 'index'])->name('catalogo.index');
Route::get('/catalogo/sugerencias', [CatalogoController::class, 'sugerencias'])->middleware('throttle:90,1')->name('catalogo.sugerencias');
Route::get('/productos/{producto}', [CatalogoController::class, 'show'])->name('catalogo.show');
Route::get('/nosotros', NosotrosController::class)->name('nosotros');
Route::view('/contacto', 'contacto')->name('contacto');
Route::get('/contacto/whatsapp', [SitioController::class, 'contactoWhatsapp'])->middleware('throttle:20,1')->name('contacto.whatsapp');
Route::view('/privacidad', 'legal.privacidad')->name('legal.privacidad');
Route::view('/terminos', 'legal.terminos')->name('legal.terminos');
Route::get('/sitemap.xml', [SitioController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SitioController::class, 'robots'])->name('robots');
Route::get('/productos/{producto}/stock', [CatalogoController::class, 'stock'])
    ->middleware('throttle:60,1')->name('catalogo.stock');

Route::prefix('carrito')->name('carrito.')->controller(CarritoController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/moneda', 'moneda')->name('moneda');   // Antes de /{producto}, que la tomaría como un producto
    Route::post('/{producto}', 'agregar')->name('agregar');
    Route::patch('/{producto}', 'actualizar')->name('actualizar');
    Route::delete('/{producto}', 'quitar')->name('quitar');
    Route::delete('/', 'vaciar')->name('vaciar');
});

Route::post('/cotizaciones', [CotizacionController::class, 'store'])
    ->middleware('throttle:10,1')->name('cotizaciones.store');
Route::delete('/mis-datos', [CotizacionController::class, 'olvidarDatos'])->name('cotizaciones.olvidar-datos');
// El acceso se verifica en el controlador: enlace firmado, dueño o personal
Route::get('/cotizaciones/{cotizacion}', [CotizacionController::class, 'show'])->name('cotizaciones.show');
Route::get('/cotizaciones/{cotizacion}/pdf', [CotizacionController::class, 'pdf'])->name('cotizaciones.pdf');

// 2. INVITADOS --------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/ingresar', [LoginController::class, 'create'])->name('login');
    Route::post('/ingresar', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:5,1');

    // Recuperación de contraseña (los nombres password.* son los que espera Laravel)
    Route::get('/recuperar-contrasena', [RecuperarContrasenaController::class, 'create'])->name('password.request');
    Route::post('/recuperar-contrasena', [RecuperarContrasenaController::class, 'store'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [RecuperarContrasenaController::class, 'edit'])->name('password.reset');
    Route::post('/restablecer-contrasena', [RecuperarContrasenaController::class, 'update'])
        ->middleware('throttle:10,1')->name('password.store');
});

Route::post('/salir', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Acceso del personal: direcciones fáciles de recordar que llevan al panel de gestión
Route::redirect('/panel', '/gestion/tablero')->name('panel');
Route::redirect('/gestion', '/gestion/tablero');
Route::redirect('/admin', '/gestion/tablero');

// 3. CLIENTE ----------------------------------------------------------------
Route::middleware(['auth', 'rol:cliente'])->prefix('mi-cuenta')->name('cuenta.')->group(function () {
    Route::get('/cotizaciones', [CuentaController::class, 'cotizaciones'])->name('cotizaciones');
    Route::post('/cotizaciones/{cotizacion}/repetir', [CuentaController::class, 'repetir'])->middleware('throttle:20,1')->name('cotizaciones.repetir');
    Route::get('/pedidos', [CuentaController::class, 'pedidos'])->name('pedidos');
    Route::get('/pedidos/{pedido}', [CuentaController::class, 'pedido'])->name('pedidos.show');
    Route::post('/pedidos/{pedido}/pago', [CompraController::class, 'reportarPago'])->middleware('throttle:10,1')->name('pedidos.pago');
    Route::post('/pedidos/{pedido}/devolucion', [CompraController::class, 'solicitarDevolucion'])->middleware('throttle:10,1')->name('pedidos.devolucion');
});

// Compra: el visitante sin sesión va a ingresar o crear su cuenta, y vuelve aquí
Route::middleware(['auth', 'rol:cliente'])->group(function () {
    Route::get('/comprar', [CompraController::class, 'create'])->name('compra.create');
    Route::post('/comprar', [CompraController::class, 'store'])->middleware('throttle:10,1')->name('compra.store');
});

// 4. PANEL: VENDEDOR, ADMINISTRATIVO Y DIRECTIVO --------------------------
Route::middleware(['auth', 'rol:vendedor,administrativo,directivo'])->prefix('gestion')->name('gestion.')->group(function () {
    Route::get('/tablero', [Gestion\TableroController::class, 'index'])->name('tablero');
    Route::get('/tablero/datos', [Gestion\TableroController::class, 'datos'])->name('tablero.datos');

    Route::get('/cotizaciones', [Gestion\CotizacionController::class, 'index'])->name('cotizaciones.index');
    Route::get('/cotizaciones/{cotizacion}', [Gestion\CotizacionController::class, 'show'])->name('cotizaciones.show');
    Route::post('/cotizaciones/{cotizacion}/convertir', [Gestion\CotizacionController::class, 'convertir'])->name('cotizaciones.convertir');
    Route::post('/cotizaciones/{cotizacion}/rechazar', [Gestion\CotizacionController::class, 'rechazar'])->name('cotizaciones.rechazar');

    Route::get('/pedidos', [Gestion\PedidoController::class, 'index'])->name('pedidos.index');
    Route::get('/pedidos/nuevo', [Gestion\PedidoController::class, 'create'])->name('pedidos.create');
    Route::post('/pedidos', [Gestion\PedidoController::class, 'store'])->name('pedidos.store');
    Route::get('/pedidos/{pedido}', [Gestion\PedidoController::class, 'show'])->name('pedidos.show');
    Route::post('/pedidos/{pedido}/confirmar', [Gestion\PedidoController::class, 'confirmar'])->name('pedidos.confirmar');
    Route::post('/pedidos/{pedido}/anular', [Gestion\PedidoController::class, 'anular'])->name('pedidos.anular');

    Route::get('/clientes/buscar', [Gestion\ClienteController::class, 'buscar'])->middleware('throttle:120,1')->name('clientes.buscar');
    Route::resource('clientes', Gestion\ClienteController::class)->except('destroy');

    Route::get('/verificaciones', [Gestion\VerificacionController::class, 'index'])->name('verificaciones.index');
    Route::post('/verificaciones/{cliente}/aprobar', [Gestion\VerificacionController::class, 'aprobar'])->name('verificaciones.aprobar');
    Route::post('/verificaciones/{cliente}/rechazar', [Gestion\VerificacionController::class, 'rechazar'])->name('verificaciones.rechazar');

    Route::get('/inventario', [Gestion\InventarioController::class, 'index'])->name('inventario.index');
    Route::post('/inventario/movimientos', [Gestion\InventarioController::class, 'store'])->name('inventario.store');
});

// 5. ADMINISTRATIVO Y DIRECTIVO ---------------------------------------------
Route::middleware(['auth', 'rol:administrativo,directivo'])->prefix('gestion')->name('gestion.')->group(function () {
    Route::get('/pagos', [Gestion\PagoController::class, 'index'])->name('pagos.index');
    Route::post('/pagos/{pago}/verificar', [Gestion\PagoController::class, 'verificar'])->name('pagos.verificar');
    Route::post('/pagos/{pago}/rechazar', [Gestion\PagoController::class, 'rechazar'])->name('pagos.rechazar');
    Route::get('/pagos/{pago}/comprobante', [Gestion\PagoController::class, 'comprobante'])->name('pagos.comprobante');
    Route::post('/pedidos/{pedido}/pagos', [Gestion\PagoController::class, 'registrar'])->name('pedidos.pagos.store');

    Route::get('/reportes', [Gestion\ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [Gestion\ReporteController::class, 'exportar'])
        ->middleware('throttle:20,1')->name('reportes.exportar');
});

Route::middleware(['auth', 'rol:administrativo,directivo'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('categorias', Admin\CategoriaController::class)->except('show');
    Route::get('productos/cargar-excel', [Admin\ImportacionController::class, 'create'])->name('importacion.create');
    Route::get('productos/cargar-excel/plantilla', [Admin\ImportacionController::class, 'plantilla'])->name('importacion.plantilla');
    Route::post('productos/cargar-excel', [Admin\ImportacionController::class, 'store'])->middleware('throttle:10,1')->name('importacion.store');
    Route::post('productos/cargar-excel/confirmar', [Admin\ImportacionController::class, 'confirmar'])->middleware('throttle:10,1')->name('importacion.confirmar');
    Route::resource('productos', Admin\ProductoController::class)->except('show');
    Route::post('productos/{id}/restaurar', [Admin\ProductoController::class, 'restaurar'])
        ->whereNumber('id')->name('productos.restaurar');

    Route::get('configuracion', [Admin\ConfiguracionController::class, 'edit'])->name('configuracion.edit');
    Route::put('configuracion', [Admin\ConfiguracionController::class, 'update'])->name('configuracion.update');
    Route::post('configuracion/tasa-bcv', [Admin\ConfiguracionController::class, 'tasaBcv'])->middleware('throttle:10,1')->name('configuracion.tasa-bcv');
});

// 6. SOLO DIRECTIVO ---------------------------------------------------------
Route::middleware(['auth', 'rol:directivo'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('usuarios', Admin\UsuarioController::class)->except('show');

    Route::get('respaldos', [Admin\RespaldoController::class, 'index'])->name('respaldos.index');
    Route::post('respaldos', [Admin\RespaldoController::class, 'store'])->middleware('throttle:6,1')->name('respaldos.store');
    Route::get('respaldos/{nombre}', [Admin\RespaldoController::class, 'descargar'])->name('respaldos.descargar');
    Route::post('respaldos/{nombre}/restaurar', [Admin\RespaldoController::class, 'restaurar'])->middleware('throttle:3,1')->name('respaldos.restaurar');
});
