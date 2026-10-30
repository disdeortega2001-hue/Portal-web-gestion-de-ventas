<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Directorio de clientes para el personal: buscar por cédula, nombre o teléfono, ver el historial
 * de cotizaciones y pedidos de cada uno, contactarlo y corregir sus datos.
 */
class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q'));

        $clientes = Cliente::query()
            ->buscar($busqueda)
            ->withCount(['cotizaciones', 'pedidos as compras_count' => fn ($q) => $q->where('estado', Pedido::CONFIRMADO)])
            ->withSum(['pedidos as total_comprado' => fn ($q) => $q->where('estado', Pedido::CONFIRMADO)], 'total')
            ->withMax('cotizaciones as ultima_cotizacion', 'fecha')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('gestion.clientes.index', compact('clientes', 'busqueda'));
    }

    /** Búsqueda en vivo para el pedido de mostrador: devuelve hasta 8 coincidencias. */
    public function buscar(Request $request): JsonResponse
    {
        $busqueda = trim((string) $request->query('q'));
        if (mb_strlen($busqueda) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Cliente::query()->buscar($busqueda)->orderBy('nombre')->limit(8)
                ->get(['id', 'identificacion', 'nombre', 'telefono', 'credito_habilitado'])
        );
    }

    public function create(): View
    {
        return view('gestion.clientes.form', ['cliente' => new Cliente()]);
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        $cliente = Cliente::create($request->safe()->only(['identificacion', 'nombre', 'telefono', 'correo', 'direccion']));

        // Desde el pedido de mostrador se vuelve allí con el cliente ya elegido
        if ($request->input('volver') === 'pedido') {
            return redirect()->route('gestion.pedidos.create', ['cliente' => $cliente->id])
                ->with('exito', "Cliente {$cliente->nombre} registrado. Ya puedes armar su pedido.");
        }

        return redirect()->route('gestion.clientes.show', $cliente)->with('exito', 'Cliente registrado.');
    }

    public function show(Cliente $cliente): View
    {
        $cliente->load('usuario:id,correo,estado');
        $cotizaciones = $cliente->cotizaciones()->withCount('detalles')->latest('fecha')->limit(20)->get();
        $pedidos = $cliente->pedidos()->withCount('detalles')->latest('fecha')->limit(20)->get();

        $resumen = [
            'compras' => $pedidos->where('estado', Pedido::CONFIRMADO)->count(),
            'total' => (float) $cliente->pedidos()->where('estado', Pedido::CONFIRMADO)->sum('total'),
            'cotizaciones' => $cliente->cotizaciones()->count(),
            'pendientes' => $cliente->cotizaciones()->where('estado', 'pendiente')->count(),
        ];

        $credito = CreditoController::resumenCliente($cliente);

        return view('gestion.clientes.show', compact('cliente', 'cotizaciones', 'pedidos', 'resumen', 'credito'));
    }

    public function edit(Cliente $cliente): View
    {
        return view('gestion.clientes.form', compact('cliente'));
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->safe()->only(['identificacion', 'nombre', 'telefono', 'correo', 'direccion']));

        return redirect()->route('gestion.clientes.show', $cliente)->with('exito', 'Datos del cliente actualizados.');
    }

    /**
     * Contraseña temporal para un cliente que olvidó la suya y no registró correo. El personal
     * verifica antes por teléfono que es la misma persona; la clave se muestra una sola vez para
     * dictársela y el cliente puede cambiarla luego por WhatsApp con el personal.
     */
    public function contrasenaTemporal(Cliente $cliente): RedirectResponse
    {
        abort_unless($cliente->usuario, 404);

        $clave = Str::upper(Str::random(3)) . random_int(100, 999) . Str::lower(Str::random(3));
        $cliente->usuario->update(['contrasena' => $clave]);

        return back()->with('exito', "Contraseña temporal de {$cliente->nombre}: {$clave} · Dísela por teléfono o WhatsApp; entra con su cédula ({$cliente->identificacion}).");
    }
}
