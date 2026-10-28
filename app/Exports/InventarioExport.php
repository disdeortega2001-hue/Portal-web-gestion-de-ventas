<?php

namespace App\Exports;

use App\Exports\Hojas\BajaRotacionHoja;
use App\Exports\Hojas\ExistenciasHoja;
use App\Exports\Hojas\MovimientosHoja;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte de inventario en un libro con tres hojas: existencias actuales,
 * movimientos del período y productos de baja rotación (con existencia y sin ventas).
 */
class InventarioExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $existencias,
        private readonly Collection $movimientos,
        private readonly Collection $bajaRotacion = new Collection(),
    ) {
    }

    public function sheets(): array
    {
        return [
            new ExistenciasHoja($this->existencias),
            new MovimientosHoja($this->movimientos),
            new BajaRotacionHoja($this->bajaRotacion),
        ];
    }
}
