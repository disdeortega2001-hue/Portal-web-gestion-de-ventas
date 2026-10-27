<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
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

    private function enVerificacion(Request $request): bool
    {
        return Cliente::where('id_usuario_solicitante', $request->user()->id)->exists();
    }
}
