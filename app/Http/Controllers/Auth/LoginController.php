<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Inicio y cierre de sesión. Hace lo mismo que el de Laravel Breeze, pero
 * con los campos en español y limitando los intentos: 5 intentos fallidos por usuario + IP bloquean
 * el acceso durante 60 segundos.
 *
 * El cliente entra con su cédula o RIF (como la escriba: «V-12345678», «v12345678» o «12345678»);
 * el personal, con su correo. La respuesta de error es la misma en ambos casos, para no revelar
 * qué cédulas o correos tienen cuenta.
 */
class LoginController extends Controller
{
    private const MAX_INTENTOS = 5;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $acceso = trim((string) $request->input('usuario'));
        $claveLimite = Str::lower($acceso) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($claveLimite, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($claveLimite);
            throw ValidationException::withMessages([
                'usuario' => "Demasiados intentos. Intenta de nuevo en {$segundos} segundos.",
            ]);
        }

        $usuario = $this->buscarUsuario($acceso);

        if (! $usuario || $usuario->estado !== 'activo' || ! Hash::check((string) $request->input('contrasena'), $usuario->contrasena)) {
            RateLimiter::hit($claveLimite, 60);
            throw ValidationException::withMessages([
                'usuario' => 'Los datos de acceso o la contraseña no son correctos.',
            ]);
        }

        Auth::login($usuario, $request->boolean('recordar'));
        RateLimiter::clear($claveLimite);
        $request->session()->regenerate();   // Evita fijación de sesión

        return redirect()->intended($this->destinoSegunRol($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalogo.index');
    }

    /** Con «@» es un correo (personal o cliente que lo registró); si no, una cédula o RIF de cliente. */
    private function buscarUsuario(string $acceso): ?Usuario
    {
        if (str_contains($acceso, '@')) {
            return Usuario::where('correo', Str::lower($acceso))->first();
        }

        // «v-12.345.678» → «V12345678»; sin letra se asume cédula venezolana (V)
        $normalizada = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $acceso));
        if ($normalizada === '') {
            return null;
        }
        if (ctype_digit($normalizada)) {
            $normalizada = 'V' . $normalizada;
        }

        return Cliente::whereRaw("REPLACE(identificacion, '-', '') = ?", [$normalizada])
            ->whereNotNull('id_usuario')
            ->first()?->usuario;
    }

    private function destinoSegunRol(Usuario $usuario): string
    {
        return $usuario->esPersonal()
            ? route('gestion.tablero')
            : route('cuenta.cotizaciones');
    }
}
