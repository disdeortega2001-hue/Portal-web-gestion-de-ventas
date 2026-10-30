<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Services\PedidoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use LogicException;

/**
 * Bandeja de cotizaciones del vendedor: revisar, convertir en pedido o rechazar.
 */
class CotizacionController extends Controller
{
    public function __construct(private readonly PedidoService $pedidos)
    {
    }

    public function index(Request $request): View
    {
        $estado = $request->query('estado', Cotizacion::PENDIENTE);
        $busqueda = trim((string) $request->query('q'));

        $cotizaciones = Cotizacion::with('cliente:id,identificacion,nombre,telefono')
            ->withCount('detalles')
            ->when($estado !== 'todas', fn ($q) => $q->where('estado', $estado))
            // Por código (COT-000012 o solo 12) o por el cliente (nombre, cédula, teléfono)
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('codigo', 'like', '%' . strtoupper($busqueda) . '%')
                ->orWhereHas('cliente', fn ($c) => $c->buscar($busqueda))))
            ->latest('fecha')
            ->paginate(15)
            ->withQueryString();

        $conteos = Cotizacion::selectRaw('estado, count(*) as n')->groupBy('estado')->pluck('n', 'estado')->all();
        $conteos['todas'] = array_sum($conteos);

        return view('gestion.cotizaciones.index', compact('cotizaciones', 'estado', 'busqueda', 'conteos'));
    }

    public function show(Cotizacion $cotizacion): View
    {
        $cotizacion->load(['cliente', 'detalles.producto', 'pedido']);

        return view('gestion.cotizaciones.show', compact('cotizacion'));
    }

    public function convertir(Request $request, Cotizacion $cotizacion): RedirectResponse
    {
        try {
            $pedido = $this->pedidos->crearDesdeCotizacion($cotizacion, $request->user());
        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gestion.pedidos.show', $pedido)
            ->with('exito', "Se creó el pedido {$pedido->codigo}. Revisa y confirma la venta.");
    }

    public function rechazar(Request $request, Cotizacion $cotizacion): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']], [
            'motivo.required' => 'Indica el motivo del rechazo.',
        ]);

        if (! $cotizacion->estaPendiente()) {
            return back()->with('error', 'Solo se pueden rechazar cotizaciones pendientes.');
        }

        $cotizacion->update([
            'estado' => Cotizacion::RECHAZADA,
            'id_usuario' => $request->user()->id,
            'observaciones' => trim($cotizacion->observaciones . "\nRechazada: " . strip_tags($datos['motivo'])),
        ]);

        return redirect()->route('gestion.cotizaciones.index')->with('exito', "Cotización {$cotizacion->codigo} rechazada.");
    }
}
