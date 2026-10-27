<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\Gestion;
use App\Http\Controllers\NosotrosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas web del portal Rialto 7
|--------------------------------------------------------------------------
| Organizadas en 4 bloques según quién puede acceder:
|   1. Públicas (catálogo, carrito, cotización como invitado)
|   2. Invitados (login / registro / recuperar contraseña)
|   3. Cliente autenticado (mi cuenta)
|   4. Personal: vendedor y administrador (tablero, cotizaciones, pedidos, inventario)
|   5. Solo administrador (reportes, usuarios, categorías, productos, configuración)
*/

// 1. PÚBLICAS ---------------------------------------------------------------
Route::get('/', [CatalogoController::class, 'index'])->name('catalogo.index');
Route::get('/productos/{producto}', [CatalogoController::class, 'show'])->name('catalogo.show');
Route::get('/nosotros', NosotrosController::class)->name('nosotros');
Route::get('/productos/{producto}/stock', [CatalogoController::class, 'stock'])
    ->middleware('throttle:60,1')->name('catalogo.stock');

Route::prefix('carrito')->name('carrito.')->controller(CarritoController::class)->group(function () {
    Route::get('/', 'index')->name('index');
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

// 3. CLIENTE ----------------------------------------------------------------
Route::middleware(['auth', 'rol:cliente'])->prefix('mi-cuenta')->name('cuenta.')->group(function () {
    Route::get('/cotizaciones', [CuentaController::class, 'cotizaciones'])->name('cotizaciones');
    Route::get('/pedidos', [CuentaController::class, 'pedidos'])->name('pedidos');
});

// 4. VENDEDOR Y ADMINISTRADOR -----------------------------------------------
Route::middleware(['auth', 'rol:vendedor,administrador'])->prefix('gestion')->name('gestion.')->group(function () {
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

    Route::get('/verificaciones', [Gestion\VerificacionController::class, 'index'])->name('verificaciones.index');
    Route::post('/verificaciones/{cliente}/aprobar', [Gestion\VerificacionController::class, 'aprobar'])->name('verificaciones.aprobar');
    Route::post('/verificaciones/{cliente}/rechazar', [Gestion\VerificacionController::class, 'rechazar'])->name('verificaciones.rechazar');

    Route::get('/inventario', [Gestion\InventarioController::class, 'index'])->name('inventario.index');
    Route::post('/inventario/movimientos', [Gestion\InventarioController::class, 'store'])->name('inventario.store');
});

// 5. SOLO ADMINISTRADOR -----------------------------------------------------
Route::middleware(['auth', 'rol:administrador'])->prefix('gestion')->name('gestion.')->group(function () {
    Route::get('/reportes', [Gestion\ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar', [Gestion\ReporteController::class, 'exportar'])
        ->middleware('throttle:20,1')->name('reportes.exportar');
});

Route::middleware(['auth', 'rol:administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('usuarios', Admin\UsuarioController::class)->except('show');
    Route::resource('categorias', Admin\CategoriaController::class)->except('show');
    Route::resource('productos', Admin\ProductoController::class)->except('show');
    Route::post('productos/{id}/restaurar', [Admin\ProductoController::class, 'restaurar'])
        ->whereNumber('id')->name('productos.restaurar');

    Route::get('configuracion', [Admin\ConfiguracionController::class, 'edit'])->name('configuracion.edit');
    Route::put('configuracion', [Admin\ConfiguracionController::class, 'update'])->name('configuracion.update');

    Route::get('respaldos', [Admin\RespaldoController::class, 'index'])->name('respaldos.index');
    Route::post('respaldos', [Admin\RespaldoController::class, 'store'])->middleware('throttle:6,1')->name('respaldos.store');
    Route::get('respaldos/{nombre}', [Admin\RespaldoController::class, 'descargar'])->name('respaldos.descargar');
    Route::post('respaldos/{nombre}/restaurar', [Admin\RespaldoController::class, 'restaurar'])->middleware('throttle:3,1')->name('respaldos.restaurar');
});
