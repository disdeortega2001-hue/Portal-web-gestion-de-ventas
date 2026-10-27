<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Inicio y cierre de sesión. Hace lo mismo que el de Laravel Breeze, pero
 * con los campos en español (correo/contrasena) y limitando los intentos:
 * 5 intentos fallidos por correo + IP bloquean el acceso durante 60 segundos.
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
        $claveLimite = Str::lower($request->input('correo')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($claveLimite, self::MAX_INTENTOS)) {
            $segundos = RateLimiter::availableIn($claveLimite);
            throw ValidationException::withMessages([
                'correo' => "Demasiados intentos. Intenta de nuevo en {$segundos} segundos.",
            ]);
        }

        $credenciales = [
            'correo' => $request->input('correo'),
            'password' => $request->input('contrasena'),   // Laravel compara contra getAuthPassword()
            'estado' => 'activo',
        ];

        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            RateLimiter::hit($claveLimite, 60);
            throw ValidationException::withMessages([
                'correo' => 'El correo o la contraseña no son correctos.',
            ]);
        }

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

    private function destinoSegunRol(Usuario $usuario): string
    {
        return $usuario->esPersonal()
            ? route('gestion.tablero')
            : route('cuenta.cotizaciones');
    }
}
