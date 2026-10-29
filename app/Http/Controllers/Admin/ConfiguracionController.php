<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfiguracionRequest;
use App\Models\Configuracion;
use App\Services\TasaBcvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /** Tasa del día: el administrativo la actualiza a diario; el resto de la configuración es del directivo. */
    public function tasa(): View
    {
        $configuraciones = Configuracion::whereIn('clave', ['tasa_cambio', 'tasa_fuente', 'tasa_fecha_valor'])->get()->keyBy('clave');

        return view('admin.tasa', compact('configuraciones'));
    }

    public function actualizarTasa(Request $request): RedirectResponse
    {
        $datos = $request->validate(['tasa_cambio' => ['required', 'numeric', 'gt:0', 'max:99999999', 'decimal:0,4']], [
            'tasa_cambio.required' => 'Indica la tasa de cambio.',
            'tasa_cambio.gt' => 'La tasa debe ser mayor que cero.',
            'tasa_cambio.decimal' => 'La tasa admite hasta 4 decimales.',
        ]);

        $configuracion = Configuracion::firstOrNew(['clave' => 'tasa_cambio']);
        $configuracion->valor = (string) $datos['tasa_cambio'];
        if ($configuracion->isDirty('valor')) {
            Configuracion::updateOrCreate(['clave' => 'tasa_fuente'], ['valor' => 'manual']);
        }
        $configuracion->save();

        return back()->with('exito', 'Tasa guardada: Bs. ' . number_format((float) $datos['tasa_cambio'], 4, ',', '.') . ' por dólar. Ya se aplica en el catálogo y en la compra.');
    }

    /** «Traer la tasa del BCV»: la consulta en ese momento; si el BCV no responde, la tasa anterior sigue vigente. */
    public function tasaBcv(TasaBcvService $bcv): RedirectResponse
    {
        try {
            $datos = $bcv->actualizar();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('exito', 'Tasa actualizada desde el BCV: Bs. '
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
