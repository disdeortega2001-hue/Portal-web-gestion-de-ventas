<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use App\Services\ImportadorCatalogo;
use Illuminate\Console\Command;

/**
 * Carga el catálogo real desde el Excel de la plantilla.
 *   php artisan catalogo:importar ruta/productos.xlsx --probar        (solo revisa, no guarda)
 *   php artisan catalogo:importar ruta/productos.xlsx --reemplazar    (borra el catálogo de demostración)
 *   php artisan catalogo:importar ruta/productos.xlsx                 (agrega o actualiza por código)
 */
class CatalogoImportar extends Command
{
    protected $signature = 'catalogo:importar
        {archivo : Excel (.xlsx/.xls) o CSV con los productos}
        {--fotos= : Carpeta de fotos (por defecto, «fotos» junto al archivo)}
        {--reemplazar : Borra antes TODO el catálogo actual con sus pedidos, cotizaciones y movimientos}
        {--usuario= : Correo del administrador que queda como responsable de la existencia inicial}
        {--probar : Solo valida y muestra lo que se cargaría, sin guardar nada}
        {--force : No pedir confirmación}';

    protected $description = 'Carga el catálogo real (productos, precios, existencias y fotos) desde Excel';

    public function handle(ImportadorCatalogo $importador): int
    {
        $archivo = $this->argument('archivo');
        $fotos = $this->option('fotos') ?: dirname($archivo) . DIRECTORY_SEPARATOR . 'fotos';

        $lectura = $importador->leer($archivo, $fotos);
        if ($lectura['errores']) {
            $this->error('No se cargó nada. Corrige estas filas y vuelve a intentarlo:');
            foreach ($lectura['errores'] as $error) {
                $this->line("  · {$error}");
            }

            return self::FAILURE;
        }

        $filas = $lectura['filas'];
        $this->table(
            ['Fila', 'Código', 'Nombre', 'Categoría', 'Marca', 'USD', 'Existencia', 'Foto'],
            array_map(fn ($f) => [
                $f['fila'], $f['codigo_fabrica'], mb_strimwidth($f['nombre'], 0, 40, '…'), $f['categoria'],
                $f['marca'], $f['precio'], $f['stock'], $f['foto'] ? basename($f['foto']) : '—',
            ], $filas)
        );
        $sinFoto = count(array_filter($filas, fn ($f) => ! $f['foto']));
        $this->info(count($filas) . ' productos listos' . ($sinFoto ? " ({$sinFoto} sin foto: mostrarán la silueta de su categoría)." : '.'));

        if ($this->option('probar')) {
            $this->line('Modo prueba: no se guardó nada.');

            return self::SUCCESS;
        }

        $usuario = $this->responsable();
        if (! $usuario) {
            return self::FAILURE;
        }

        if ($this->option('reemplazar')) {
            $actual = $importador->contarCatalogoActual();
            $this->warn("Se borrarán {$actual['productos']} productos actuales, {$actual['pedidos']} pedidos, "
                . "{$actual['cotizaciones']} cotizaciones y {$actual['movimientos']} movimientos de inventario. "
                . 'Los usuarios y clientes se conservan.');
            if (! $this->option('force') && ! $this->confirm('¿Reemplazar el catálogo?', false)) {
                $this->line('Cancelado. No se cambió nada.');

                return self::SUCCESS;
            }
        }

        $r = $importador->importar($filas, $usuario, (bool) $this->option('reemplazar'));

        $this->info("Listo: {$r['creados']} creados, {$r['actualizados']} actualizados, {$r['categorias']} categorías, "
            . "{$r['con_foto']} con foto." . ($r['eliminados'] ? " Se eliminaron {$r['eliminados']} productos anteriores." : ''));

        return self::SUCCESS;
    }

    private function responsable(): ?Usuario
    {
        $consulta = Usuario::where('rol', Usuario::ROL_ADMINISTRADOR);
        $usuario = $this->option('usuario')
            ? $consulta->where('correo', $this->option('usuario'))->first()
            : $consulta->orderBy('id')->first();

        if (! $usuario) {
            $this->error('No se encontró el administrador responsable (usa --usuario=correo).');
        }

        return $usuario;
    }
}
