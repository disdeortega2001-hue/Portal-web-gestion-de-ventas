<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Verificación de cuentas: alguien creó una cuenta con una cédula que ya tenía
 * historial como invitado. El vendedor llama al teléfono de la FICHA (no al de la
 * cuenta nueva) y, si la persona confirma, asocia la cuenta; si no, la rechaza.
 */
class VerificacionController extends Controller
{
    public function index(): View
    {
        $solicitudes = Cliente::whereNotNull('id_usuario_solicitante')
            ->with('solicitante:id,nombre,correo,created_at')
            ->withCount(['cotizaciones', 'pedidos'])
            ->orderBy('solicitud_vinculacion_en')
            ->get();

        return view('gestion.verificaciones.index', compact('solicitudes'));
    }

    public function aprobar(Cliente $cliente): RedirectResponse
    {
        $resultado = DB::transaction(function () use ($cliente) {
            $cliente = Cliente::lockForUpdate()->findOrFail($cliente->id);
            $solicitante = $cliente->solicitante;

            if (! $solicitante || $cliente->id_usuario) {
                return 'Esta solicitud ya no está pendiente.';
            }
            // Una cuenta solo puede tener una ficha (clientes.id_usuario es único)
            if (Cliente::where('id_usuario', $solicitante->id)->exists()) {
                return "La cuenta de {$solicitante->nombre} ya está asociada a otra ficha.";
            }

            $cliente->id_usuario = $solicitante->id;
            $cliente->id_usuario_solicitante = null;
            $cliente->solicitud_vinculacion_en = null;
            $cliente->save();

            return null;
        });

        return $resultado
            ? back()->with('error', $resultado)
            : back()->with('exito', "Cuenta asociada a {$cliente->identificacion}. El cliente ya ve su historial.");
    }

    public function rechazar(Cliente $cliente): RedirectResponse
    {
        $cliente->id_usuario_solicitante = null;
        $cliente->solicitud_vinculacion_en = null;
        $cliente->save();

        return back()->with('exito', "Solicitud rechazada. La ficha {$cliente->identificacion} queda como estaba y la cédula puede volver a registrarse.");
    }
}
