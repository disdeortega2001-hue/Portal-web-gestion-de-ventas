<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Usuario;
use App\Support\PlantillaCatalogo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Carga el catálogo real desde un Excel (o CSV) y una carpeta de fotos.
 *
 * Primero se lee y valida TODO el archivo; si una sola fila tiene un error, no se toca
 * la base de datos. La carga se hace en una transacción: o entran todos los productos o ninguno.
 * La existencia inicial entra como movimiento de inventario (misma trazabilidad que el panel).
 */
class ImportadorCatalogo
{
    private const OBLIGATORIAS = ['codigo', 'nombre', 'categoria', 'marca', 'precio', 'stock'];
    private const LARGOS = ['codigo' => 50, 'nombre' => 150, 'categoria' => 100, 'marca' => 80];
    private const EXTENSIONES_FOTO = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private readonly InventarioService $inventario,
        private readonly ImagenProductoService $imagenes,
    ) {
    }

    /**
     * @return array{filas: list<array<string, mixed>>, errores: list<string>}
     */
    public function leer(string $archivo, ?string $carpetaFotos = null): array
    {
        if (! is_file($archivo)) {
            return ['filas' => [], 'errores' => ["No existe el archivo {$archivo}."]];
        }

        $libro = IOFactory::load($archivo);
        $hoja = $libro->getSheetByName('Productos') ?? $libro->getSheet(0);
        $celdas = $hoja->toArray(null, true, false, false);
        $libro->disconnectWorksheets();

        [$filaTitulos, $columnas] = $this->ubicarTitulos($celdas);
        $faltantes = array_diff(self::OBLIGATORIAS, array_keys($columnas));
        if ($faltantes) {
            $nombres = array_map(fn ($c) => '«' . PlantillaCatalogo::COLUMNAS[$c][0] . '»', $faltantes);

            return ['filas' => [], 'errores' => ['Faltan columnas obligatorias: ' . implode(', ', $nombres) . '.']];
        }

        $fotos = $this->indexarFotos($carpetaFotos);
        $filas = [];
        $errores = [];
        $codigosVistos = [];

        foreach (array_slice($celdas, $filaTitulos + 1, null, true) as $indice => $celda) {
            $numero = $indice + 1;   // Número de fila tal como se ve en Excel
            $valores = [];
            foreach ($columnas as $clave => $posicion) {
                $valores[$clave] = $this->texto($celda[$posicion] ?? null);
            }

            if (implode('', $valores) === '') {
                continue;   // Fila vacía
            }

            $erroresFila = [];
            foreach (self::OBLIGATORIAS as $clave) {
                if ($valores[$clave] === '') {
                    $erroresFila[] = 'falta «' . PlantillaCatalogo::COLUMNAS[$clave][0] . '»';
                }
            }
            foreach (self::LARGOS as $clave => $maximo) {
                if (mb_strlen($valores[$clave]) > $maximo) {
                    $erroresFila[] = "«{$valores[$clave]}» supera {$maximo} caracteres";
                }
            }

            $codigo = mb_strtoupper($valores['codigo']);
            if ($codigo !== '' && isset($codigosVistos[$codigo])) {
                $erroresFila[] = "el código {$codigo} ya está en la fila {$codigosVistos[$codigo]}";
            }
            $codigosVistos[$codigo] = $numero;

            $precio = $this->numero($valores['precio']);
            if ($valores['precio'] !== '' && ($precio === null || $precio <= 0)) {
                $erroresFila[] = "el precio «{$valores['precio']}» no es un monto válido mayor que 0";
            }
            $costo = $valores['costo'] === '' ? null : $this->numero($valores['costo']);
            if ($valores['costo'] !== '' && ($costo === null || $costo < 0)) {
                $erroresFila[] = "el costo «{$valores['costo']}» no es un monto válido";
            }
            $stock = $this->numero($valores['stock']);
            if ($valores['stock'] !== '' && ($stock === null || $stock < 0 || floor($stock) != $stock)) {
                $erroresFila[] = "la existencia «{$valores['stock']}» debe ser un número entero, 0 o mayor";
            }

            $foto = $this->buscarFoto($valores['foto'], $codigo, $fotos);
            if ($valores['foto'] !== '' && $foto === null) {
                $erroresFila[] = "no se encontró la foto «{$valores['foto']}» en la carpeta de fotos";
            } elseif ($foto !== null && @getimagesize($foto) === false) {
                $erroresFila[] = 'la foto «' . basename($foto) . '» no es una imagen válida';
            }

            if ($erroresFila) {
                $errores[] = "Fila {$numero}: " . implode('; ', $erroresFila) . '.';
                continue;
            }

            $filas[] = [
                'fila' => $numero,
                'codigo_fabrica' => $codigo,
                'nombre' => $valores['nombre'],
                'categoria' => $valores['categoria'],
                'marca' => $valores['marca'],
                'precio' => number_format($precio, 2, '.', ''),
                'costo' => $costo === null ? null : number_format($costo, 2, '.', ''),
                'stock' => (int) $stock,
                'compatibilidad' => $valores['compatibilidad'] ?: null,
                'descripcion' => $valores['descripcion'] ?: null,
                'foto' => $foto,
            ];
        }

        if (! $filas && ! $errores) {
            $errores[] = 'El archivo no tiene productos debajo de los títulos.';
        }

        return ['filas' => $filas, 'errores' => $errores];
    }

    /** Cuántos registros de demostración borraría la opción «reemplazar». */
    public function contarCatalogoActual(): array
    {
        return [
            'productos' => Producto::withTrashed()->count(),
            'pedidos' => DB::table('pedidos')->count(),
            'cotizaciones' => DB::table('cotizaciones')->count(),
            'movimientos' => DB::table('movimientos_inventario')->count(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $filas  Resultado de leer() sin errores.
     * @param  bool  $reemplazar  Borra antes el catálogo actual con sus pedidos, cotizaciones y movimientos.
     * @return array{creados: int, actualizados: int, categorias: int, con_foto: int, sin_foto: int, eliminados: int}
     */
    public function importar(array $filas, Usuario $usuario, bool $reemplazar = false): array
    {
        $resumen = ['creados' => 0, 'actualizados' => 0, 'categorias' => 0, 'con_foto' => 0, 'sin_foto' => 0, 'eliminados' => 0];
        $fotosNuevas = [];
        $fotosViejas = [];

        try {
            DB::transaction(function () use ($filas, $usuario, $reemplazar, &$resumen, &$fotosNuevas, &$fotosViejas) {
                if ($reemplazar) {
                    [$resumen['eliminados'], $fotosViejas] = $this->vaciarCatalogo();
                }

                $categorias = [];
                foreach ($filas as $fila) {
                    $clave = mb_strtolower($fila['categoria']);
                    $categorias[$clave] ??= $this->categoria($fila['categoria']);

                    $imagen = null;
                    if ($fila['foto']) {
                        $imagen = $this->imagenes->guardar(new UploadedFile($fila['foto'], basename($fila['foto']), null, null, true));
                        $fotosNuevas[] = $imagen;
                        $resumen['con_foto']++;
                    } else {
                        $resumen['sin_foto']++;
                    }

                    $datos = [
                        'nombre' => $fila['nombre'],
                        'descripcion' => $fila['descripcion'],
                        'compatibilidad' => $fila['compatibilidad'],
                        'marca' => $fila['marca'],
                        'precio' => $fila['precio'],
                        'costo' => $fila['costo'],
                        'id_categoria' => $categorias[$clave]->id,
                        'activo' => true,
                    ];

                    $producto = Producto::withTrashed()->where('codigo_fabrica', $fila['codigo_fabrica'])->first();

                    if (! $producto) {
                        $producto = Producto::create($datos + ['codigo_fabrica' => $fila['codigo_fabrica'], 'imagen' => $imagen, 'stock' => 0]);
                        if ($fila['stock'] > 0) {
                            $this->inventario->registrar($producto, MovimientoInventario::ENTRADA, $fila['stock'], 'Inventario inicial (carga del catálogo)', $usuario);
                        }
                        $resumen['creados']++;
                        continue;
                    }

                    if ($producto->trashed()) {
                        $producto->restore();
                    }
                    if ($imagen) {
                        $fotosViejas[] = $producto->imagen;
                        $datos['imagen'] = $imagen;
                    }
                    $producto->update($datos);
                    if ($producto->stock !== $fila['stock']) {
                        $this->inventario->registrar($producto, MovimientoInventario::AJUSTE, $fila['stock'], 'Ajuste por carga del catálogo', $usuario);
                    }
                    $resumen['actualizados']++;
                }

                $resumen['categorias'] = count($categorias);
            });
        } catch (Throwable $e) {
            foreach ($fotosNuevas as $ruta) {
                $this->imagenes->eliminar($ruta);   // Sin fotos huérfanas si la carga se revierte
            }
            throw $e;
        }

        // Las fotos anteriores se borran solo cuando la carga ya quedó guardada
        foreach (array_filter($fotosViejas) as $ruta) {
            $this->imagenes->eliminar($ruta);
        }
        Cache::forget('catalogo.marcas');
        Cache::forget('catalogo.categorias');

        return $resumen;
    }

    /**
     * Borra los productos de demostración con todo lo que depende de ellos.
     * Se usa DELETE (no TRUNCATE) para que quede dentro de la transacción.
     *
     * @return array{0: int, 1: list<string>} productos borrados y sus fotos
     */
    private function vaciarCatalogo(): array
    {
        $fotos = Producto::withTrashed()->whereNotNull('imagen')->pluck('imagen')->all();
        $total = Producto::withTrashed()->count();

        DB::table('movimientos_inventario')->delete();
        DB::table('detalle_pedidos')->delete();
        DB::table('pedidos')->delete();
        DB::table('detalle_cotizaciones')->delete();
        DB::table('cotizaciones')->delete();
        DB::table('productos')->delete();
        DB::table('categorias')->delete();

        return [$total, $fotos];
    }

    private function categoria(string $nombre): Categoria
    {
        $categoria = Categoria::withTrashed()->where('nombre', $nombre)->first();

        if ($categoria?->trashed()) {
            $categoria->restore();
        }

        return $categoria ?? Categoria::create(['nombre' => $nombre]);
    }

    /** Busca la fila de títulos entre las primeras 10 (la primera con al menos 3 columnas reconocidas). */
    private function ubicarTitulos(array $celdas): array
    {
        foreach (array_slice($celdas, 0, 10, true) as $indice => $fila) {
            $columnas = [];
            foreach ($fila as $posicion => $titulo) {
                $normal = PlantillaCatalogo::normalizarTitulo(is_scalar($titulo) ? (string) $titulo : '');
                foreach (PlantillaCatalogo::SINONIMOS as $clave => $sinonimos) {
                    if ($normal !== '' && ! isset($columnas[$clave]) && in_array($normal, $sinonimos, true)) {
                        $columnas[$clave] = $posicion;
                        break;
                    }
                }
            }
            if (count($columnas) >= 3) {
                return [$indice, $columnas];
            }
        }

        return [0, []];
    }

    private function texto(mixed $valor): string
    {
        if (is_float($valor) && floor($valor) == $valor && abs($valor) < 1e15) {
            return (string) (int) $valor;   // Excel guarda 504836 como 504836.0
        }

        return trim(preg_replace('/\s+/u', ' ', (string) $valor));
    }

    /** Acepta 18.90, 18,90, 1.250,00, 1,250.00 o «$ 18.90». */
    private function numero(string $valor): ?float
    {
        $limpio = preg_replace('/[^0-9.,\-]/', '', $valor);
        if ($limpio === '' || $limpio === '-') {
            return null;
        }

        $coma = strrpos($limpio, ',');
        $punto = strrpos($limpio, '.');
        if ($coma !== false && $punto !== false) {
            $decimal = $coma > $punto ? ',' : '.';
            $limpio = str_replace($decimal === ',' ? '.' : ',', '', $limpio);
            $limpio = str_replace(',', '.', $limpio);
        } elseif ($coma !== false) {
            $limpio = str_replace(',', '.', $limpio);
        }

        return is_numeric($limpio) ? (float) $limpio : null;
    }

    /** @return array<string, string> nombre en minúsculas (con y sin extensión) => ruta */
    private function indexarFotos(?string $carpeta): array
    {
        if (! $carpeta || ! is_dir($carpeta)) {
            return [];
        }

        $indice = [];
        foreach (scandir($carpeta) as $archivo) {
            $ruta = $carpeta . DIRECTORY_SEPARATOR . $archivo;
            $extension = mb_strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            if (is_file($ruta) && in_array($extension, self::EXTENSIONES_FOTO, true)) {
                $indice[mb_strtolower($archivo)] = $ruta;
                $indice[mb_strtolower(pathinfo($archivo, PATHINFO_FILENAME))] ??= $ruta;
            }
        }

        return $indice;
    }

    private function buscarFoto(string $nombre, string $codigo, array $fotos): ?string
    {
        if ($nombre !== '') {
            return $fotos[mb_strtolower($nombre)] ?? $fotos[mb_strtolower(pathinfo($nombre, PATHINFO_FILENAME))] ?? null;
        }

        return $codigo !== '' ? ($fotos[mb_strtolower($codigo)] ?? null) : null;
    }
}
