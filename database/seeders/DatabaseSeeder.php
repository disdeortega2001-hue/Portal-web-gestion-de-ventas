<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\CotizacionService;
use App\Services\ImportadorCatalogo;
use App\Services\PagoService;
use App\Services\ReporteService;
use App\Services\PedidoService;
use App\Support\MetodosPago;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Datos de prueba realistas para demostrar el portal.
 * Ejecutar: php artisan migrate:fresh --seed
 *
 * Credenciales de prueba (cámbialas antes de producción):
 *   directivo@rialto7.test      / Rialto7*2024   (directivo: acceso total)
 *   administrativo@rialto7.test / Rialto7*2024   (administrativo)
 *   vendedor@rialto7.test       / Rialto7*2024   (vendedor)
 *   cliente@rialto7.test        / Rialto7*2024   (cliente: compra en el portal, sin panel)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // El catálogo y el inventario inicial se registran hace 31 días, para que el historial
        // de ventas (y el gráfico de 30 días del tablero) tenga un orden cronológico coherente.
        $hoy = now();
        Carbon::setTestNow($hoy->copy()->subDays(31)->setTime(8, 0));

        $this->configuraciones();
        [$directivo, $administrativo, $vendedor, $usuarioCliente] = $this->usuarios();
        $productos = $this->productos($administrativo);
        $clientes = $this->clientes($usuarioCliente);
        $this->historialDeVentas($clientes, $productos, [$vendedor, $administrativo], $hoy);

        Carbon::setTestNow();
        $this->cotizacionesYPedidos($clientes, $productos, $vendedor);
        $this->reportesDelMes($directivo);
    }

    private function configuraciones(): void
    {
        $valores = [
            ['tasa_cambio', '36.50', 'Tasa oficial USD → Bs. (BCV)'],
            ['porcentaje_iva', '16', 'Porcentaje de IVA aplicado a las ventas'],
            ['umbral_stock_bajo', '5', 'Unidades a partir de las cuales se alerta bajo stock'],
            ['descuento_porcentaje', '5', 'Descuento por volumen (%)'],
            ['descuento_monto_minimo', '500', 'Subtotal mínimo en USD para aplicar el descuento'],
            // Número de atención que entregó la empresa; se cambia en Administración → Tasa, IVA y contacto
            ['telefono_whatsapp', '04129477195', 'WhatsApp de atención (0414-1234567); vacío oculta los botones de contacto'],
        ];

        // Métodos de pago: todos activos y sin datos de cuentas (la empresa los envía por WhatsApp
        // hasta que se carguen en Administración → Tasa, IVA y pagos)
        foreach (MetodosPago::METODOS as $metodo => $definicion) {
            $valores[] = ["{$metodo}_activo", '1', "Ofrecer {$definicion['nombre']} al comprar"];
        }

        foreach ($valores as [$clave, $valor, $descripcion]) {
            Configuracion::updateOrCreate(['clave' => $clave], compact('valor', 'descripcion'));
        }
    }

    private function usuarios(): array
    {
        $clave = 'Rialto7*2024';

        return [
            Usuario::create(['nombre' => 'Daysi Carpio', 'correo' => 'directivo@rialto7.test', 'contrasena' => $clave, 'rol' => Usuario::ROL_DIRECTIVO]),
            Usuario::create(['nombre' => 'Luis Alberto Campagna', 'correo' => 'administrativo@rialto7.test', 'contrasena' => $clave, 'rol' => Usuario::ROL_ADMINISTRATIVO]),
            Usuario::create(['nombre' => 'Disdeira Ortega', 'correo' => 'vendedor@rialto7.test', 'contrasena' => $clave, 'rol' => 'vendedor']),
            Usuario::create(['nombre' => 'Agropecuaria El Samán', 'correo' => 'cliente@rialto7.test', 'contrasena' => $clave, 'rol' => 'cliente']),
        ];
    }

    /**
     * Catálogo real de la empresa (Marchesan Tatu, Colombo Aemco, Vicon y Lumagi), con sus fotos.
     * Se carga con el mismo importador del comando catalogo:importar, así la existencia inicial
     * queda como movimiento de inventario. Devuelve los productos indexados por código de fábrica.
     */
    private function productos(Usuario $responsable): array
    {
        $importador = app(ImportadorCatalogo::class);
        $carpeta = database_path('seeders/catalogo');
        $lectura = $importador->leer("{$carpeta}/productos.xlsx", "{$carpeta}/fotos");

        if ($lectura['errores']) {
            throw new RuntimeException("El catálogo de ejemplo tiene errores:\n" . implode("\n", $lectura['errores']));
        }

        $importador->importar($lectura['filas'], $responsable, false);

        return Producto::all()->keyBy('codigo_fabrica')->all();
    }

    private function clientes(Usuario $usuarioCliente): array
    {
        return [
            Cliente::create(['id_usuario' => $usuarioCliente->id, 'identificacion' => 'J-40123456-7', 'nombre' => 'Agropecuaria El Samán C.A.', 'telefono' => '0414-4561230', 'correo' => 'cliente@rialto7.test', 'direccion' => 'Finca El Samán, vía Tucupido, Valle de la Pascua']),
            Cliente::create(['identificacion' => 'V-12345678', 'nombre' => 'José Gregorio Rondón', 'telefono' => '0426-3412345', 'correo' => null, 'direccion' => 'Hato La Esperanza, sector Paso Real, Infante']),
            Cliente::create(['identificacion' => 'V-9876543', 'nombre' => 'María Hernández', 'telefono' => '0235-3419876', 'correo' => 'mhernandez@example.com', 'direccion' => 'Parcela 14, asentamiento Las Mercedes, Chaguaramas']),
        ];
    }

    /**
     * Ventas de mostrador confirmadas en los últimos 30 días (sin incluir hoy), con montos variados
     * y deterministas. Pasan por PedidoService, así descuentan stock y dejan sus movimientos.
     */
    private function historialDeVentas(array $clientes, array $p, array $vendedores, Carbon $hoy): void
    {
        $pedidos = app(PedidoService::class);
        $metodos = ['efectivo', 'pago_movil', 'punto_venta', 'transferencia', 'zelle'];
        // Repuestos de alta rotación: cuchillas, discos, tornillos, crucetas, yugos y engrasadoras
        $rotacion = ['0501052742R', 'CC55', '0602030250R', '0501047573R', 'CC56', 'LUB600'];

        foreach ([29, 27, 26, 24, 21, 20, 18, 15, 13, 12, 10, 8, 6, 5, 3, 1] as $i => $diasAtras) {
            Carbon::setTestNow($hoy->copy()->subDays($diasAtras)->setTime(9 + $i % 8, 15));

            $lineas = [['id_producto' => $p[$rotacion[$i % 6]]->id, 'cantidad' => 1 + $i % 3]];
            if ($i % 2 === 0) {
                $lineas[] = ['id_producto' => $p[$rotacion[($i + 3) % 6]]->id, 'cantidad' => 1];
            }

            $vendedor = $vendedores[$i % 2];
            // Cobrado en la tienda: el pago queda verificado y la venta se confirma en el mismo paso
            $this->cobrar($pedidos->crearDirecto($clientes[$i % 3], $lineas, $vendedor), $metodos[$i % 5], $vendedor);
        }
    }

    /** Usa los mismos servicios del sistema, así los datos de prueba respetan todas las reglas. */
    private function cotizacionesYPedidos(array $clientes, array $p, Usuario $vendedor): void
    {
        $cotizaciones = app(CotizacionService::class);
        $pedidos = app(PedidoService::class);
        $linea = fn (string $codigo, int $cantidad) => ['producto' => $p[$codigo], 'precio' => (string) $p[$codigo]->precio, 'cantidad' => $cantidad];

        // 1. Pendiente (bandeja del vendedor)
        $cotizaciones->crear($clientes[1], collect([
            $linea('0501052742R', 4),
            $linea('0501047573R', 8),
        ]), 'Necesito retirar el sábado.');

        // 2. Convertida en pedido y venta confirmada (descuenta stock; aplica descuento por volumen)
        $c2 = $cotizaciones->crear($clientes[0], collect([
            $linea('0602030250R', 6),
            $linea('70632', 1),
            $linea('CC55', 4),
        ]));
        $this->cobrar($pedidos->crearDesdeCotizacion($c2, $vendedor), 'transferencia', $vendedor);

        // 3. Convertida en pedido pendiente de confirmar
        $c3 = $cotizaciones->crear($clientes[2], collect([
            $linea('07665', 1),
            $linea('52429', 1),
        ]));
        $pedidos->crearDesdeCotizacion($c3, $vendedor);

        // 4. Pedido de mostrador confirmado
        $p4 = $pedidos->crearDirecto($clientes[1], [
            ['id_producto' => $p['CC56']->id, 'cantidad' => 2],
        ], $vendedor);
        $this->cobrar($p4, 'efectivo', $vendedor);

        // 5. Compra del cliente en el portal con Pago Móvil, reportada y esperando verificación
        $p5 = $pedidos->crearEnLinea($clientes[0], [$linea('0501052742R', 2), $linea('LUB600', 1)], 'pago_movil', 'retiro', 'Retiro el viernes.');
        app(PagoService::class)->reportar($p5, [
            'referencia' => '00128734', 'fecha_pago' => now()->toDateString(), 'monto' => (string) $p5->total_bs,
        ], null);

        // 6. Compra en el portal para pagar en efectivo al retirar
        $pedidos->crearEnLinea($clientes[0], [$linea('CC55', 2)], 'efectivo', 'retiro', null);
    }

    /** Los cuatro reportes que pide la gerencia (ventas e inventario, en PDF y Excel), generados este mes. */
    private function reportesDelMes(Usuario $directivo): void
    {
        $reportes = app(ReporteService::class);
        foreach (['ventas', 'inventario'] as $tipo) {
            foreach (['pdf', 'excel'] as $formato) {
                $reportes->registrar($directivo, ['tipo' => $tipo, 'desde' => now()->startOfMonth()->toDateString(), 'hasta' => now()->toDateString()], $formato, 0);
            }
        }
    }

    /** Registra el pago completo en la moneda del método (lo usa el historial de ventas). */
    private function cobrar(Pedido $pedido, string $metodo, Usuario $usuario): void
    {
        $monto = MetodosPago::montoEnMoneda(MetodosPago::moneda($metodo), $pedido->total, $pedido->total_bs);
        app(PagoService::class)->registrarEnTienda($pedido, ['metodo' => $metodo, 'monto' => $monto, 'referencia' => null], $usuario);
    }
}
