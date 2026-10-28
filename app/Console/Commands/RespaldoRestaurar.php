<?php

namespace App\Console\Commands;

use App\Services\RespaldoService;
use Illuminate\Console\Command;

/**
 * Restaura un respaldo. Antes de reemplazar los datos crea un respaldo del estado actual,
 * así una restauración equivocada también se puede deshacer.
 *   php artisan respaldo:restaurar rialto7-2026-10-27-020000.zip
 *   php artisan respaldo:restaurar C:\ruta\a\otro-respaldo.zip
 */
class RespaldoRestaurar extends Command
{
    protected $signature = 'respaldo:restaurar
        {archivo : Nombre del respaldo en storage/app/respaldos o ruta completa al .zip}
        {--force : No pedir confirmación}';

    protected $description = 'Restaura la base de datos, las fotos y los comprobantes de pago desde un respaldo';

    public function handle(RespaldoService $respaldos): int
    {
        $archivo = $this->argument('archivo');
        $ruta = is_file($archivo) ? $archivo : $respaldos->archivo($archivo);
        if (! $ruta) {
            $this->error("No se encontró el respaldo «{$archivo}».");

            return self::FAILURE;
        }

        $this->warn('Se reemplazarán TODOS los datos actuales (productos, pedidos, usuarios…) por los del respaldo.');
        if (! $this->option('force') && ! $this->confirm('¿Restaurar ' . basename($ruta) . '?', false)) {
            $this->line('Cancelado. No se cambió nada.');

            return self::SUCCESS;
        }

        $previo = $respaldos->crear();
        $this->line("Estado actual guardado antes de restaurar: {$previo}");

        $r = $respaldos->restaurar($ruta);
        $this->info("Restaurado: {$r['tablas']} tablas, {$r['filas']} registros, {$r['fotos']} fotos y {$r['comprobantes']} comprobantes de pago.");

        return self::SUCCESS;
    }
}
