<?php

namespace App\Console\Commands;

use App\Support\PlantillaCatalogo;
use Illuminate\Console\Command;

/** Genera el Excel vacío (con instrucciones) y la carpeta "fotos" para cargar el catálogo real. */
class CatalogoPlantilla extends Command
{
    protected $signature = 'catalogo:plantilla {carpeta : Carpeta donde se crean productos.xlsx y fotos/}';

    protected $description = 'Crea la plantilla de Excel y la carpeta de fotos para cargar el catálogo';

    public function handle(): int
    {
        $carpeta = rtrim($this->argument('carpeta'), '\\/');
        if (! is_dir("{$carpeta}/fotos")) {
            mkdir("{$carpeta}/fotos", 0775, true);
        }

        $archivo = "{$carpeta}/productos.xlsx";
        if (is_file($archivo) && ! $this->confirm("Ya existe {$archivo}. ¿Reemplazarlo por una plantilla vacía?", false)) {
            return self::SUCCESS;
        }

        PlantillaCatalogo::generar($archivo);
        $this->info("Plantilla creada: {$archivo}");
        $this->line("Fotos: {$carpeta}" . DIRECTORY_SEPARATOR . 'fotos');

        return self::SUCCESS;
    }
}
