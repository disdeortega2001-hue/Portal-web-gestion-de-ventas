<?php

namespace Tests\Concerns;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\PedidoService;
use Illuminate\Support\Str;

/**
 * Atajos para crear datos de prueba con los mismos modelos y servicios
 * que usa el sistema (así las pruebas respetan las reglas de negocio reales).
 */
trait CreaDatos
{
    /** Parámetros de negocio por defecto: tasa 36,50 · IVA 16 % · umbral 5 · 5 % de descuento desde USD 500. */
    protected function configurar(array $valores = []): void
    {
        $base = [
            'tasa_cambio' => '36.50',
            'porcentaje_iva' => '16',
            'umbral_stock_bajo' => '5',
            'descuento_porcentaje' => '5',
            'descuento_monto_minimo' => '500',
        ];

        foreach (array_merge($base, $valores) as $clave => $valor) {
            Configuracion::updateOrCreate(['clave' => $clave], ['valor' => (string) $valor]);
        }
    }

    protected function crearUsuario(string $rol = 'vendedor', array $atributos = []): Usuario
    {
        return Usuario::create($atributos + [
            'nombre' => 'Usuario ' . ucfirst($rol),
            'correo' => Str::lower(Str::random(10)) . '@prueba.test',
            'contrasena' => 'Clave1234',
            'rol' => $rol,
            'estado' => 'activo',
        ]);
    }

    protected function crearCliente(?Usuario $usuario = null): Cliente
    {
        return Cliente::create([
            'id_usuario' => $usuario?->id,
            'identificacion' => 'V-' . random_int(10000000, 99999999),
            'nombre' => 'Productor de prueba',
            'telefono' => '0414-1234567',
            'direccion' => 'Finca de prueba, Valle de la Pascua',
        ]);
    }

    protected function crearProducto(array $atributos = []): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Repuestos de prueba']);

        return Producto::create($atributos + [
            'codigo_fabrica' => 'PRB-' . Str::upper(Str::random(8)),
            'nombre' => 'Repuesto de prueba',
            'marca' => 'Marca Prueba',
            'precio' => '10.00',
            'costo' => '6.00',
            'id_categoria' => $categoria->id,
            'stock' => 10,
        ]);
    }

    /** Crea un pedido de mostrador y, si se pide, confirma la venta (descuenta stock). */
    protected function venta(Usuario $vendedor, Cliente $cliente, array $lineas, bool $confirmar = true): Pedido
    {
        $servicio = app(PedidoService::class);
        $items = array_map(fn ($l) => ['id_producto' => $l[0]->id, 'cantidad' => $l[1]], $lineas);

        $pedido = $servicio->crearDirecto($cliente, $items, $vendedor);

        return $confirmar ? $servicio->confirmarVenta($pedido, $vendedor) : $pedido;
    }
}
