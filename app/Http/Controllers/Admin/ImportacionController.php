<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImportadorCatalogo;
use App\Services\RespaldoService;
use App\Support\PlantillaCatalogo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Carga del catálogo desde Excel en el panel (lo mismo que el comando catalogo:importar, sin consola).
 * Paso 1: subir el Excel y las fotos. Paso 2: revisar la vista previa; con un solo error no se carga nada.
 * Paso 3: confirmar. Los archivos esperan en una carpeta temporal identificada por un token.
 */
class ImportacionController extends Controller
{
    public function __construct(private readonly ImportadorCatalogo $importador)
    {
    }

    public function create(): View
    {
        return view('admin.importacion.create');
    }

    public function plantilla(): BinaryFileResponse
    {
        $archivo = storage_path('app/importaciones/plantilla-productos.xlsx');
        File::ensureDirectoryExists(dirname($archivo));
        PlantillaCatalogo::generar($archivo);

        return response()->download($archivo, 'plantilla-productos.xlsx')->deleteFileAfterSend();
    }

    public function store(Request $request): View|RedirectResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'fotos' => ['nullable', 'array', 'max:300'],
            'fotos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'archivo.required' => 'Elige el archivo de Excel con los productos.',
            'archivo.mimes' => 'El archivo debe ser un Excel (.xlsx o .xls) o un CSV.',
            'fotos.*.mimes' => 'Las fotos deben ser JPG, PNG o WEBP.',
            'fotos.*.max' => 'Cada foto puede pesar hasta 4 MB.',
        ]);

        $token = (string) Str::uuid();
        $carpeta = $this->carpeta($token);
        File::ensureDirectoryExists("{$carpeta}/fotos");

        $extension = strtolower($request->file('archivo')->getClientOriginalExtension());
        $request->file('archivo')->move($carpeta, "productos.{$extension}");
        foreach ($request->file('fotos', []) as $foto) {
            // Solo el nombre del archivo, sin rutas: así se asocia por código o por la columna «Foto»
            $foto->move("{$carpeta}/fotos", basename($foto->getClientOriginalName()));
        }

        $lectura = $this->importador->leer("{$carpeta}/productos.{$extension}", "{$carpeta}/fotos");
        if ($lectura['errores']) {
            File::deleteDirectory($carpeta);
        }

        return view('admin.importacion.revisar', [
            'token' => $token,
            'filas' => $lectura['filas'],
            'errores' => $lectura['errores'],
            'actual' => $this->importador->contarCatalogoActual(),
        ]);
    }

    public function confirmar(Request $request, RespaldoService $respaldos): RedirectResponse
    {
        $datos = $request->validate([
            'token' => ['required', 'uuid'],
            'reemplazar' => ['nullable', 'boolean'],
        ]);
        $carpeta = $this->carpeta($datos['token']);
        $archivo = collect(File::glob("{$carpeta}/productos.*"))->first();
        if (! $archivo) {
            return redirect()->route('admin.importacion.create')->with('error', 'La carga venció. Sube el archivo de nuevo.');
        }

        $lectura = $this->importador->leer($archivo, "{$carpeta}/fotos");
        if ($lectura['errores']) {
            File::deleteDirectory($carpeta);

            return redirect()->route('admin.importacion.create')->with('error', 'El archivo tiene errores. Súbelo de nuevo para revisarlos.');
        }

        $reemplazar = (bool) ($datos['reemplazar'] ?? false);
        if ($reemplazar) {
            // Reemplazar borra pedidos y cotizaciones: antes se guarda el estado actual para poder volver atrás
            $respaldos->crear();
        }

        $r = $this->importador->importar($lectura['filas'], $request->user(), $reemplazar);
        File::deleteDirectory($carpeta);

        return redirect()->route('admin.productos.index')->with('exito',
            "Catálogo cargado: {$r['creados']} productos nuevos, {$r['actualizados']} actualizados y {$r['con_foto']} con foto."
            . ($r['eliminados'] ? " Se reemplazaron {$r['eliminados']} productos anteriores." : ''));
    }

    private function carpeta(string $token): string
    {
        return storage_path('app/importaciones/' . $token);
    }
}
