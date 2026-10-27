<?php

namespace App\Exports;

use App\Exports\Hojas\ExistenciasHoja;
use App\Exports\Hojas\MovimientosHoja;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte de inventario en un libro con dos hojas: existencias actuales
 * y movimientos del período.
 */
class InventarioExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $existencias,
        private readonly Collection $movimientos,
    ) {
    }

    public function sheets(): array
    {
        return [
            new ExistenciasHoja($this->existencias),
            new MovimientosHoja($this->movimientos),
        ];
    }
}
