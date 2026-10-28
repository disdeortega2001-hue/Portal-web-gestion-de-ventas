<?php

namespace App\Console\Commands;

use App\Services\TasaBcvService;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Actualiza la tasa del dólar con la publicada por el Banco Central de Venezuela.
 * Se ejecuta sola dos veces al día desde el programador (app/Console/Kernel.php).
 */
class TasaBcv extends Command
{
    protected $signature = 'tasa:bcv';

    protected $description = 'Actualiza la tasa USD → Bs. con la tasa oficial del BCV';

    public function handle(TasaBcvService $bcv): int
    {
        try {
            $datos = $bcv->actualizar();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Tasa BCV: Bs. ' . number_format($datos['tasa'], 4, ',', '.')
            . ($datos['fecha_valor'] ? ' (fecha valor ' . $datos['fecha_valor']->format('d/m/Y') . ')' : ''));

        return self::SUCCESS;
    }
}
