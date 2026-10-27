<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroRequest;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Registro público: siempre crea usuarios con rol "cliente".
 * Si la cédula/RIF ya cotizó como invitado y tiene historial, la cuenta queda en
 * verificación hasta que el personal confirme por teléfono que es la misma persona.
 */
class RegistroController extends Controller
{
    public function create(): View
    {
        return view('auth.registro');
    }

    public function store(RegistroRequest $request): RedirectResponse
    {
        [$usuario, $enVerificacion] = DB::transaction(function () use ($request) {
            $usuario = Usuario::create([
                'nombre' => $request->input('nombre'),
                'correo' => $request->input('correo'),
                'contrasena' => $request->input('contrasena'),   // El cast "hashed" aplica bcrypt
                'rol' => Usuario::ROL_CLIENTE,
                'estado' => 'activo',
            ]);

            // lockForUpdate: dos registros simultáneos con la misma cédula no pueden ganar ambos
            $cliente = Cliente::withTrashed()->where('identificacion', $request->input('identificacion'))->lockForUpdate()->first();

            // 1) Cédula nueva: se crea su ficha con los datos del formulario
            if (! $cliente) {
                Cliente::create(['identificacion' => $request->input('identificacion'), 'id_usuario' => $usuario->id]
                    + $request->only('nombre', 'telefono', 'correo', 'direccion'));

                return [$usuario, false];
            }

            if ($cliente->trashed()) {
                $cliente->restore();
            }

            // 2) Ficha de invitado SIN historial: no hay nada que proteger, se asocia ya
            //    (sus datos de contacto no se sobrescriben: la cédula no prueba identidad)
            if (! $cliente->tieneHistorial()) {
                $cliente->id_usuario = $usuario->id;
                $cliente->save();

                return [$usuario, false];
            }

            // 3) Ficha CON historial: queda como solicitud hasta que un vendedor llame al
            //    teléfono de la ficha y confirme que es la misma persona (Gestión → Verificar cuentas)
            $cliente->id_usuario_solicitante = $usuario->id;
            $cliente->solicitud_vinculacion_en = now();
            $cliente->save();

            return [$usuario, true];
        });

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('catalogo.index')->with('exito', $enVerificacion
            ? '¡Bienvenido! Como esta cédula ya tenía cotizaciones, un vendedor te llamará al teléfono que nos diste antes para confirmar que eres tú. Mientras tanto puedes cotizar normalmente.'
            : '¡Bienvenido! Tu cuenta fue creada.');
    }
}
