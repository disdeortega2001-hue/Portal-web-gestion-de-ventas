<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaContrasena;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Recuperación de contraseña por correo, usando el gestor de contraseñas de Laravel
 * (tokens con hash, vencimiento y límite de envíos) adaptado a usuarios(correo, contrasena).
 *
 * Privacidad: la respuesta al pedir el enlace es la misma exista o no el correo,
 * para que nadie pueda averiguar qué correos están registrados.
 */
class RecuperarContrasenaController extends Controller
{
    private const MENSAJE_ENVIADO = 'Si el correo está registrado, te enviamos un enlace para crear una nueva contraseña. Revisa también la carpeta de spam.';

    /* ---------- Paso 1: pedir el enlace ---------- */

    public function create(): View
    {
        return view('auth.recuperar');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['correo' => ['required', 'email', 'max:150']], [
            'correo.required' => 'Escribe el correo de tu cuenta.',
            'correo.email' => 'El correo no tiene un formato válido.',
        ]);

        // Solo cuentas activas reciben el enlace
        $estado = Password::broker()->sendResetLink([
            'correo' => mb_strtolower(trim($request->input('correo'))),
            'estado' => 'activo',
        ]);

        if ($estado === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages(['correo' => 'Ya te enviamos un enlace hace poco. Espera un minuto antes de pedir otro.']);
        }

        // RESET_LINK_SENT o INVALID_USER: misma respuesta
        return back()->with('exito', self::MENSAJE_ENVIADO);
    }

    /* ---------- Paso 2: crear la nueva contraseña ---------- */

    public function edit(Request $request, string $token): View
    {
        return view('auth.restablecer', ['token' => $token, 'correo' => $request->query('correo')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'correo' => ['required', 'email', 'max:150'],
            'contrasena' => ['required', 'confirmed', ReglaContrasena::min(8)->letters()->numbers()],
        ], [
            'correo.required' => 'Escribe el correo de tu cuenta.',
            'contrasena.required' => 'Crea una contraseña.',
            'contrasena.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // El gestor espera "password"/"password_confirmation"; el proveedor ignora esas claves al buscar al usuario
        $estado = Password::broker()->reset(
            [
                'correo' => mb_strtolower(trim($request->input('correo'))),
                'password' => $request->input('contrasena'),
                'password_confirmation' => $request->input('contrasena_confirmation'),
                'token' => $request->input('token'),
            ],
            function (Usuario $usuario, string $nuevaContrasena) {
                $usuario->forceFill([
                    'contrasena' => $nuevaContrasena,             // El cast "hashed" aplica bcrypt
                    'remember_token' => Str::random(60),          // Cierra sesiones "recordadas" en otros equipos
                ])->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'correo' => 'El enlace no es válido o ya venció. Pide uno nuevo desde "¿Olvidaste tu contraseña?".',
            ]);
        }

        return redirect()->route('login')->with('exito', 'Tu contraseña fue cambiada. Ya puedes ingresar con la nueva.');
    }
}
