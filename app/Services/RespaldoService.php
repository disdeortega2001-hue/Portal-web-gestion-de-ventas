<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Respaldo y recuperación (Cuadro 28: disponibilidad con mecanismos de respaldo).
 * Cada respaldo es un .zip con la base de datos completa en SQL y las fotos de los productos.
 * El SQL se genera con PHP, sin depender de mysqldump, para que funcione en un hosting compartido,
 * y también se puede importar a mano desde phpMyAdmin.
 */
class RespaldoService
{
    public const CARPETA = 'respaldos';

    /** Cuántos respaldos se conservan; los más viejos se borran solos. */
    public const CONSERVAR = 14;

    private const CARPETA_FOTOS = 'productos';
    private const FILAS_POR_INSERT = 200;

    /** @return string nombre del archivo creado (dentro de storage/app/respaldos) */
    public function crear(): string
    {
        File::ensureDirectoryExists($this->ruta());
        $nombre = 'rialto7-' . now()->format('Y-m-d-His') . '.zip';
        $destino = $this->ruta($nombre);

        $sql = tempnam(sys_get_temp_dir(), 'r7sql');
        try {
            $this->volcarBaseDeDatos($sql);

            $zip = new ZipArchive();
            if ($zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("No se pudo crear {$destino}.");
            }
            $zip->addFile($sql, 'base-de-datos.sql');
            foreach (Storage::disk('public')->files(self::CARPETA_FOTOS) as $foto) {
                $zip->addFile(Storage::disk('public')->path($foto), 'fotos/' . basename($foto));
            }
            $zip->close();
        } finally {
            @unlink($sql);
        }

        $this->podar();

        return $nombre;
    }

    /**
     * Respaldos disponibles, del más nuevo al más viejo.
     *
     * @return list<array{nombre: string, fecha: Carbon, bytes: int}>
     */
    public function listar(): array
    {
        return collect(File::glob($this->ruta('rialto7-*.zip')))
            ->map(fn ($archivo) => [
                'nombre' => basename($archivo),
                'fecha' => Carbon::createFromTimestamp(filemtime($archivo), config('app.timezone')),
                'bytes' => filesize($archivo),
            ])
            ->sortByDesc('nombre')
            ->values()
            ->all();
    }

    /** Ruta absoluta de un respaldo existente; null si el nombre no es válido o no existe. */
    public function archivo(string $nombre): ?string
    {
        if (! preg_match('/^rialto7-[\d-]+\.zip$/', $nombre)) {
            return null;
        }

        return is_file($this->ruta($nombre)) ? $this->ruta($nombre) : null;
    }

    /**
     * Restaura la base de datos y las fotos desde un respaldo.
     * Los datos se reemplazan dentro de una transacción: si algo falla, la base queda como estaba.
     *
     * @return array{tablas: int, filas: int, fotos: int}
     */
    public function restaurar(string $zipRuta): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipRuta) !== true || ($sql = $zip->getFromName('base-de-datos.sql')) === false) {
            throw new RuntimeException('El archivo no es un respaldo del portal (falta base-de-datos.sql).');
        }

        $sentencias = $this->separarSentencias($sql);

        // Las tablas que falten se crean antes (CREATE TABLE no admite transacción en MySQL)
        foreach ($sentencias as $sentencia) {
            if (preg_match('/^CREATE TABLE IF NOT EXISTS `([^`]+)`/', $sentencia, $m) && ! Schema::hasTable($m[1])) {
                DB::unprepared($sentencia);
            }
        }

        $tablas = 0;
        $filas = 0;
        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($sentencias, &$tablas, &$filas) {
                foreach ($sentencias as $sentencia) {
                    if (str_starts_with($sentencia, 'DELETE FROM')) {
                        DB::statement($sentencia);
                        $tablas++;
                    } elseif (str_starts_with($sentencia, 'INSERT INTO')) {
                        $filas += DB::affectingStatement($sentencia);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $fotos = $this->restaurarFotos($zip);
        $zip->close();

        // Las listas del catálogo y los parámetros pueden haber cambiado
        cache()->flush();

        return compact('tablas', 'filas', 'fotos');
    }

    private function volcarBaseDeDatos(string $destino): void
    {
        $pdo = DB::connection()->getPdo();
        $salida = fopen($destino, 'w');

        fwrite($salida, "-- Respaldo del portal Rialto 7 · " . now()->format('d/m/Y h:i a') . "\n"
            . "-- Base de datos: " . DB::connection()->getDatabaseName() . "\n"
            . "-- Se puede restaurar con «php artisan respaldo:restaurar» o importándolo en phpMyAdmin.\n\n"
            . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach (array_map(fn ($fila) => array_values((array) $fila)[0], DB::select('SHOW TABLES')) as $tabla) {
            $crear = array_values((array) DB::selectOne("SHOW CREATE TABLE `{$tabla}`"))[1];
            fwrite($salida, preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $crear) . ";\n");
            fwrite($salida, "DELETE FROM `{$tabla}`;\n");

            // Por lotes, para no cargar tablas grandes completas en memoria
            $lote = [];
            foreach (DB::table($tabla)->cursor() as $fila) {
                $lote[] = '(' . implode(',', array_map(
                    fn ($valor) => $valor === null ? 'NULL' : $pdo->quote((string) $valor),
                    (array) $fila
                )) . ')';
                if (count($lote) === self::FILAS_POR_INSERT) {
                    $this->escribirInsert($salida, $tabla, $lote);
                    $lote = [];
                }
            }
            $this->escribirInsert($salida, $tabla, $lote);
            fwrite($salida, "\n");
        }

        fwrite($salida, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($salida);
    }

    private function escribirInsert($salida, string $tabla, array $lote): void
    {
        if ($lote) {
            fwrite($salida, "INSERT INTO `{$tabla}` VALUES " . implode(',', $lote) . ";\n");
        }
    }

    /**
     * Cada INSERT y DELETE ocupa una sola línea (quote() escapa los saltos de línea);
     * el CREATE TABLE ocupa varias y termina en «;» al final de la última.
     *
     * @return list<string>
     */
    private function separarSentencias(string $sql): array
    {
        $sentencias = [];
        $actual = '';
        foreach (preg_split('/\r?\n/', $sql) as $linea) {
            if ($actual === '' && ($linea === '' || str_starts_with($linea, '--'))) {
                continue;
            }
            $actual .= ($actual === '' ? '' : "\n") . $linea;
            if (str_ends_with($linea, ';')) {
                $sentencias[] = rtrim($actual, ';');
                $actual = '';
            }
        }

        return $sentencias;
    }

    private function restaurarFotos(ZipArchive $zip): int
    {
        $disco = Storage::disk('public');
        $fotos = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entrada = $zip->getNameIndex($i);
            // Solo archivos planos de fotos/: nunca rutas con «..» ni subcarpetas
            if (preg_match('#^fotos/([\w.-]+\.(?:webp|jpe?g|png))$#i', $entrada, $m)) {
                $disco->put(self::CARPETA_FOTOS . '/' . $m[1], $zip->getFromIndex($i));
                $fotos++;
            }
        }

        return $fotos;
    }

    private function podar(): void
    {
        foreach (array_slice($this->listar(), self::CONSERVAR) as $viejo) {
            File::delete($this->ruta($viejo['nombre']));
        }
    }

    /** Carpeta de respaldos: storage/app/respaldos (las pruebas la cambian para no tocar los reales). */
    private function ruta(string $archivo = ''): string
    {
        $carpeta = config('filesystems.respaldos') ?: storage_path('app/' . self::CARPETA);

        return $carpeta . ($archivo === '' ? '' : DIRECTORY_SEPARATOR . $archivo);
    }
}
