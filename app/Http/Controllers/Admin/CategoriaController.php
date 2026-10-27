<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaRequest;
use App\Models\Categoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function index(): View
    {
        $categorias = Categoria::withCount('productos')->orderBy('nombre')->paginate(20);

        return view('admin.categorias.index', compact('categorias'));
    }

    public function create(): View
    {
        return view('admin.categorias.form', ['categoria' => new Categoria()]);
    }

    public function store(CategoriaRequest $request): RedirectResponse
    {
        $categoria = Categoria::create($request->validated());

        return redirect()->route('admin.categorias.index')->with('exito', "Categoría {$categoria->nombre} creada.");
    }

    public function edit(Categoria $categoria): View
    {
        return view('admin.categorias.form', compact('categoria'));
    }

    public function update(CategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

        return redirect()->route('admin.categorias.index')->with('exito', "Categoría {$categoria->nombre} actualizada.");
    }

    /**
     * No se elimina una categoría que todavía tiene productos (incluidos los eliminados,
     * que podrían restaurarse): primero hay que moverlos.
     */
    public function destroy(Categoria $categoria): RedirectResponse
    {
        if ($categoria->productos()->withTrashed()->exists()) {
            return back()->with('error', "La categoría {$categoria->nombre} tiene productos (activos o eliminados). Muévelos a otra categoría antes de eliminarla.");
        }

        $categoria->delete();

        return redirect()->route('admin.categorias.index')->with('exito', "Categoría {$categoria->nombre} eliminada.");
    }
}
