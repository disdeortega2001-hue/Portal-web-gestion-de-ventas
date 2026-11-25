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
 * Inicio y cierre de sesión.
 *
 * El cliente entra con su cédula o RIF y el personal con su correo.
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

        if (! $usuario
            || $usuario->estado !== 'activo'
            || ! Hash::check((string) $request->input('contrasena'), $usuario->contrasena)
        ) {
            RateLimiter::hit($claveLimite, 60);
            throw ValidationException::withMessages([
                'usuario' => 'Los datos de acceso o la contraseña no son correctos.',
            ]);
        }

        Auth::login($usuario, $request->boolean('recordar'));
        RateLimiter::clear($claveLimite);
        $request->session()->regenerate();

        $pendiente = ltrim((string) parse_url((string) $request->session()->get('url.intended'), PHP_URL_PATH), '/');
        if (! $usuario->esPersonal() && Str::startsWith($pendiente, ['gestion/', 'admin/'])) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended($this->destinoSegunRol($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('catalogo.index');
    }

    /** Con «@» es un correo; si no, una cédula o RIF de cliente. */
    private function buscarUsuario(string $acceso): ?Usuario
    {
        if (str_contains($acceso, '@')) {
            $correo = mb_strtolower(trim($acceso));

            return Usuario::query()
                ->whereRaw('LOWER(TRIM(correo)) = ?', [$correo])
                ->first();
        }

        $normalizada = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $acceso));
        if ($normalizada === '') {
            return null;
        }
        if (ctype_digit($normalizada)) {
            $normalizada = 'V' . $normalizada;
        }

        return Cliente::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(UPPER(TRIM(identificacion)), '-', ''), '.', ''), ' ', ''), '_', '') = ?", [$normalizada])
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
