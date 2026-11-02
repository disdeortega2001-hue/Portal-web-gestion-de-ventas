<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistroCambio;
use Illuminate\View\View;

/** Registro de cambios (directivo): quién cambió precios, configuración y créditos, y cuándo. */
class RegistroController extends Controller
{
    public function index(): View
    {
        return view('admin.registro', [
            'cambios' => RegistroCambio::with('usuario:id,nombre')->latest('creado_en')->latest('id')->paginate(30),
        ]);
    }
}
