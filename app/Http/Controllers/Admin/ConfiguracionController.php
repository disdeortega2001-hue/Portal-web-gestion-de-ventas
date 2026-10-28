<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfiguracionRequest;
use App\Models\Configuracion;
use App\Services\TasaBcvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

/**
 * Parámetros de negocio: tasa USD/Bs. (automática desde el BCV o a mano), IVA (pagos en Bs.),
 * IGTF (pagos en $), umbral de stock bajo, WhatsApp y los métodos de pago que se ofrecen al comprar.
 * Al guardar, el modelo limpia su caché y el nuevo valor se aplica de inmediato
 * en catálogo, carrito y tablero. Las cotizaciones y pedidos ya emitidos NO cambian,
 * porque guardan la tasa y el impuesto del momento.
 */
class ConfiguracionController extends Controller
{
    public function edit(): View
    {
        $configuraciones = Configuracion::whereIn('clave', [...ConfiguracionRequest::claves(), 'tasa_fuente', 'tasa_fecha_valor'])->get()->keyBy('clave');

        return view('admin.configuracion', compact('configuraciones'));
    }

    /** «Traer la tasa del BCV»: la consulta en ese momento; si el BCV no responde, la tasa anterior sigue vigente. */
    public function tasaBcv(TasaBcvService $bcv): RedirectResponse
    {
        try {
            $datos = $bcv->actualizar();
        } catch (RuntimeException $e) {
            return redirect()->route('admin.configuracion.edit')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.configuracion.edit')->with('exito', 'Tasa actualizada desde el BCV: Bs. '
            . number_format($datos['tasa'], 4, ',', '.') . ($datos['fecha_valor'] ? ' (fecha valor ' . $datos['fecha_valor']->format('d/m/Y') . ').' : '.'));
    }

    public function update(ConfiguracionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            foreach (ConfiguracionRequest::claves() as $clave) {
                $configuracion = Configuracion::firstOrNew(['clave' => $clave]);
                $configuracion->valor = (string) $request->input($clave);
                // Si se escribe una tasa distinta a mano, la fuente pasa a «manual»
                if ($clave === 'tasa_cambio' && $configuracion->isDirty('valor')) {
                    Configuracion::updateOrCreate(['clave' => 'tasa_fuente'], ['valor' => 'manual']);
                }
                $configuracion->save();   // Solo actualiza updated_at si el valor cambió
            }
        });

        return redirect()->route('admin.configuracion.edit')->with('exito', 'Configuración guardada. Los nuevos valores ya se aplican en el catálogo y en la compra.');
    }
}
