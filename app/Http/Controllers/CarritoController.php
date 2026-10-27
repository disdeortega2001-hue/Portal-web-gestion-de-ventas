<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Services\CarritoService;
use App\Support\ClienteRecordado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Mi cotización": lista de productos que el cliente (registrado o invitado) quiere cotizar.
 * Controlador delgado: la lógica de stock y cálculos vive en CarritoService / CalculadoraComercial.
 * (Internamente se llama "carrito"; al usuario siempre se le habla de "cotización".)
 */
class CarritoController extends Controller
{
    public function __construct(private readonly CarritoService $carrito)
    {
    }

    public function index(): View
    {
        $cliente = auth()->user()?->cliente;

        return view('carrito.index', [
            'lineas' => $this->carrito->lineas(),
            'resumen' => $this->carrito->resumen(),
            'cliente' => $cliente,
            // Invitado que pidió "Recordar mis datos": el formulario llega ya lleno
            'recordado' => $cliente ? [] : ClienteRecordado::leer(),
        ]);
    }

    /**
     * Agrega desde el catálogo. Con JavaScript responde JSON (sin recargar la página);
     * sin JavaScript vuelve a la misma tarjeta del catálogo (#producto-ID) para no perder la posición.
     */
    public function agregar(Request $request, Producto $producto): JsonResponse|RedirectResponse
    {
        $datos = $request->validate(['cantidad' => ['required', 'integer', 'min:1', 'max:10000']], [
            'cantidad.required' => 'Indica la cantidad.',
            'cantidad.min' => 'La cantidad mínima es 1.',
        ]);

        abort_unless($producto->activo, 404);
        $this->carrito->agregar($producto, (int) $datos['cantidad']);

        // Corto (una línea en el celular) y con lo que el cliente necesita saber: cuántas lleva
        $llevas = $this->carrito->cantidadDe($producto->id);
        $mensaje = "Agregado: {$producto->codigo_fabrica}. Llevas {$llevas}.";

        if ($request->expectsJson()) {
            return response()->json([
                'mensaje' => $mensaje,
                'codigo' => $producto->codigo_fabrica,   // El aviso lo muestra sin partirlo en los guiones
                'items' => $this->carrito->cantidadItems(),
                'llevas' => $llevas,
                'enlace' => route('carrito.index') . '#linea-' . $producto->id,   // Directo a su selector − / +
            ]);
        }

        return back()->withFragment('producto-' . $producto->id)->with('exito', $mensaje);
    }

    /**
     * Cambia la cantidad. Acepta la cantidad escrita y, opcionalmente, un ajuste de los
     * botones − / + (ajuste = -1 o 1). Si queda en 0, el producto sale de la cotización.
     */
    public function actualizar(Request $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:0', 'max:10000'],
            'ajuste' => ['nullable', 'integer', 'in:-1,1'],
        ], [
            'cantidad.required' => 'Indica la cantidad.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
        ]);

        $cantidad = (int) $datos['cantidad'] + (int) ($datos['ajuste'] ?? 0);

        if ($cantidad < 1) {
            $this->carrito->quitar($producto->id);

            return redirect()->route('carrito.index')->with('exito', "Quitaste {$producto->nombre} de tu cotización.");
        }

        $this->carrito->actualizar($producto, $cantidad);

        return redirect()->route('carrito.index')->withFragment('linea-' . $producto->id);
    }

    public function quitar(Producto $producto): RedirectResponse
    {
        $this->carrito->quitar($producto->id);

        return redirect()->route('carrito.index')->with('exito', "Quitaste {$producto->nombre} de tu cotización.");
    }

    public function vaciar(): RedirectResponse
    {
        $this->carrito->vaciar();

        return redirect()->route('carrito.index')->with('exito', 'Tu cotización quedó vacía.');
    }
}
