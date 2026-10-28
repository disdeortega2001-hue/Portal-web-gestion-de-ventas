<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfiguracionRequest;
use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Parámetros de negocio: tasa USD/Bs., IVA, umbral de stock bajo, descuento por volumen,
 * WhatsApp y los métodos de pago que se ofrecen al comprar.
 * Al guardar, el modelo limpia su caché y el nuevo valor se aplica de inmediato
 * en catálogo, carrito y tablero. Las cotizaciones y pedidos ya emitidos NO cambian,
 * porque guardan la tasa y el IVA del momento.
 */
class ConfiguracionController extends Controller
{
    public function edit(): View
    {
        $configuraciones = Configuracion::whereIn('clave', ConfiguracionRequest::claves())->get()->keyBy('clave');

        return view('admin.configuracion', compact('configuraciones'));
    }

    public function update(ConfiguracionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach (ConfiguracionRequest::claves() as $clave) {
                $configuracion = Configuracion::firstOrNew(['clave' => $clave]);
                $configuracion->valor = (string) $request->input($clave);
                $configuracion->save();   // Solo actualiza updated_at si el valor cambió
            }
        });

        return redirect()->route('admin.configuracion.edit')->with('exito', 'Configuración guardada. Los nuevos valores ya se aplican en el catálogo y en la compra.');
    }
}
