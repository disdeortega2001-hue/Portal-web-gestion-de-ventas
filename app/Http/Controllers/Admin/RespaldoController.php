<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RespaldoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * Respaldos de la base de datos y las fotos: crear uno al momento, descargarlo para guardarlo
 * fuera del servidor y restaurarlo. Antes de restaurar se respalda el estado actual.
 */
class RespaldoController extends Controller
{
    public function __construct(private readonly RespaldoService $respaldos)
    {
    }

    public function index(): View
    {
        return view('admin.respaldos', ['respaldos' => $this->respaldos->listar()]);
    }

    public function store(): RedirectResponse
    {
        $nombre = $this->respaldos->crear();

        return redirect()->route('admin.respaldos.index')->with('exito', "Respaldo creado: {$nombre}. Descárgalo y guárdalo fuera del servidor.");
    }

    public function descargar(string $nombre): BinaryFileResponse
    {
        $ruta = $this->respaldos->archivo($nombre);
        abort_unless($ruta, 404);

        return response()->download($ruta);
    }

    public function restaurar(string $nombre): RedirectResponse
    {
        $ruta = $this->respaldos->archivo($nombre);
        abort_unless($ruta, 404);

        $previo = $this->respaldos->crear();
        try {
            $r = $this->respaldos->restaurar($ruta);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.respaldos.index')->with('error', 'No se pudo restaurar el respaldo; los datos no cambiaron.');
        }

        return redirect()->route('admin.respaldos.index')->with('exito',
            "Restaurado {$nombre}: {$r['filas']} registros, {$r['fotos']} fotos y {$r['comprobantes']} comprobantes de pago. El estado anterior quedó guardado como {$previo}.");
    }
}
