<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestión del personal y sus perfiles (solo el directivo). Aquí se crean únicamente los tres
 * perfiles del panel; los clientes se registran solos y se atienden en Gestión → Clientes.
 * Eliminar es un borrado lógico (soft delete): el usuario conserva su
 * historial de pedidos y movimientos de inventario.
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $usuarios = Usuario::whereIn('rol', Usuario::PERFILES_PANEL)
            ->when($request->query('rol'), fn ($q, $rol) => $q->where('rol', $rol))
            ->when($request->query('q'), fn ($q, $texto) => $q->where(fn ($s) => $s
                ->where('nombre', 'like', "%{$texto}%")->orWhere('correo', 'like', "%{$texto}%")))
            ->orderBy('rol')->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('admin.usuarios.form', ['usuario' => new Usuario(['rol' => 'vendedor', 'estado' => 'activo'])]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $usuario = Usuario::create($request->safe()->only(['nombre', 'correo', 'rol', 'estado', 'contrasena']));

        return redirect()->route('admin.usuarios.index')->with('exito', "Usuario {$usuario->nombre} creado.");
    }

    public function edit(Usuario $usuario): View
    {
        $this->soloPersonal($usuario);

        return view('admin.usuarios.form', compact('usuario'));
    }

    public function update(UsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $this->soloPersonal($usuario);
        $datos = $request->safe()->only(['nombre', 'correo', 'rol', 'estado']);

        if ($request->filled('contrasena')) {
            $datos['contrasena'] = $request->input('contrasena');   // El cast "hashed" aplica bcrypt
        }

        $usuario->update($datos);

        return redirect()->route('admin.usuarios.index')->with('exito', "Usuario {$usuario->nombre} actualizado.");
    }

    public function destroy(Request $request, Usuario $usuario): RedirectResponse
    {
        $this->soloPersonal($usuario);
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $usuario->update(['estado' => 'inactivo']);
        $usuario->delete();

        return redirect()->route('admin.usuarios.index')->with('exito', "Usuario {$usuario->nombre} eliminado.");
    }

    /** Las cuentas de clientes no se editan desde aquí. */
    private function soloPersonal(Usuario $usuario): void
    {
        abort_unless($usuario->esPersonal(), 404);
    }
}
