<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Pedido;
use App\Services\CarritoService;
use App\Support\MetodosPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Área del cliente registrado: sus cotizaciones y el estado de sus pedidos.
 * Solo consulta registros de SU cliente, nunca datos de otros clientes. Una cuenta
 * solo queda asociada a una ficha con historial después de que el personal verificó
 * por teléfono que es la misma persona (ver RegistroController y VerificacionController).
 */
class CuentaController extends Controller
{
    public function cotizaciones(Request $request): View
    {
        $cliente = $request->user()->cliente;
        $cotizaciones = $cliente
            ? $cliente->cotizaciones()->withCount('detalles')->latest('fecha')->paginate(10)
            : collect();

        return view('cuenta.cotizaciones', [
            'cotizaciones' => $cotizaciones,
            'enVerificacion' => $this->enVerificacion($request),
        ]);
    }

    public function pedidos(Request $request): View
    {
        $cliente = $request->user()->cliente;
        $pedidos = $cliente
            ? $cliente->pedidos()->with('detalles.producto:id,codigo_fabrica,nombre')->latest('fecha')->paginate(10)
            : collect();

        return view('cuenta.pedidos', [
            'pedidos' => $pedidos,
            'enVerificacion' => $this->enVerificacion($request),
        ]);
    }

    /** Detalle de un pedido propio: cómo pagarlo, reportar el pago y seguir su estado. */
    public function pedido(Request $request, Pedido $pedido): View
    {
        abort_unless($pedido->id_cliente === $request->user()->cliente?->id, 403);

        return self::vistaPedido($pedido, sinCuenta: false);
    }

    /** Con cuenta, los formularios van a «Mis pedidos»; sin cuenta, a enlaces firmados del mismo pedido. */
    public static function vistaPedido(Pedido $pedido, bool $sinCuenta): View
    {
        $pedido->load(['detalles.producto:id,codigo_fabrica,nombre', 'pagos']);

        return view('cuenta.pedido', [
            'pedido' => $pedido,
            'datosPago' => $pedido->metodo_pago ? (MetodosPago::disponibles()[$pedido->metodo_pago]['datos'] ?? []) : [],
            // A crédito abona con cualquier método a distancia
            'metodosAbono' => $pedido->a_credito ? array_filter(MetodosPago::disponibles(), fn ($m) => $m['en_linea']) : [],
            'sinCuenta' => $sinCuenta,
            'urlPedido' => $sinCuenta ? CompraController::enlaceSeguimiento($pedido) : null,
            'urlPago' => $sinCuenta ? CompraController::enlaceSeguimiento($pedido, 'pedidos.seguimiento.pago') : route('cuenta.pedidos.pago', $pedido),
            'urlDevolucion' => $sinCuenta ? CompraController::enlaceSeguimiento($pedido, 'pedidos.seguimiento.devolucion') : route('cuenta.pedidos.devolucion', $pedido),
            'urlComprobante' => $sinCuenta ? CompraController::enlaceSeguimiento($pedido, 'pedidos.seguimiento.comprobante') : route('cuenta.pedidos.comprobante', $pedido),
        ]);
    }

    /**
     * Repetir una cotización: pone en «Mi cotización» los mismos productos con los precios de hoy.
     * Lo agotado o retirado del catálogo se omite y se avisa; si hay menos existencia, se ajusta la cantidad.
     */
    public function repetir(Request $request, Cotizacion $cotizacion, CarritoService $carrito): RedirectResponse
    {
        abort_unless($cotizacion->id_cliente === $request->user()->cliente?->id, 403);

        $agregados = 0;
        $omitidos = [];
        foreach ($cotizacion->detalles()->with('producto')->get() as $detalle) {
            $producto = $detalle->producto;
            $disponible = $producto && ! $producto->trashed() && $producto->activo
                ? $producto->stock - $carrito->cantidadDe($producto->id) : 0;
            if ($disponible <= 0) {
                $omitidos[] = $producto?->codigo_fabrica ?? 'un producto retirado';
                continue;
            }
            $carrito->agregar($producto, min($detalle->cantidad, $disponible));
            $agregados++;
        }

        $mensaje = $agregados
            ? "Agregamos {$agregados} " . ($agregados === 1 ? 'producto' : 'productos') . " de {$cotizacion->codigo} con los precios de hoy."
            : "Ningún producto de {$cotizacion->codigo} tiene existencia hoy.";
        if ($omitidos) {
            $mensaje .= ' Sin existencia por ahora: ' . implode(', ', $omitidos) . '.';
        }

        return redirect()->route('carrito.index')->with($agregados ? 'exito' : 'error', $mensaje);
    }

    private function enVerificacion(Request $request): bool
    {
        return Cliente::where('id_usuario_solicitante', $request->user()->id)->exists();
    }
}
