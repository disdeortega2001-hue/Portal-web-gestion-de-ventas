<?php

namespace App\Console\Commands;

use App\Services\RespaldoService;
use Illuminate\Console\Command;

/**
 * Crea un respaldo (.zip con la base de datos y las fotos) en storage/app/respaldos.
 * Se ejecuta solo cada noche desde el programador (app/Console/Kernel.php).
 */
class RespaldoCrear extends Command
{
    protected $signature = 'respaldo:crear';

    protected $description = 'Respalda la base de datos y las fotos de los productos';

    public function handle(RespaldoService $respaldos): int
    {
        $nombre = $respaldos->crear();
        $this->info("Respaldo creado: storage/app/respaldos/{$nombre}");
        $this->line('Se conservan los ' . RespaldoService::CONSERVAR . ' más recientes.');

        return self::SUCCESS;
    }
}
