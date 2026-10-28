<?php

namespace App\Exports\Hojas;

use App\Exports\Concerns\TextoSeguroEnExcel;
use App\Models\MovimientoInventario;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MovimientosHoja extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithStyles, WithTitle, WithCustomValueBinder
{
    use TextoSeguroEnExcel;   // El motivo lo escribe el personal; igual se guarda como texto

    public function __construct(private readonly Collection $movimientos)
    {
    }

    public function collection(): Collection
    {
        return $this->movimientos;
    }

    public function title(): string
    {
        return 'Movimientos';
    }

    public function headings(): array
    {
        return ['Fecha', 'Código de fábrica', 'Producto', 'Tipo', 'Cantidad', 'Stock anterior', 'Stock nuevo', 'Motivo', 'Pedido', 'Usuario responsable'];
    }

    /** @param  MovimientoInventario  $mov */
    public function map($mov): array
    {
        return [
            Date::dateTimeToExcel($mov->fecha),
            $mov->producto?->codigo_fabrica,
            $mov->producto?->nombre,
            ucfirst($mov->tipo),
            $mov->cantidad,
            $mov->stock_anterior,
            $mov->stock_nuevo,
            $mov->motivo,
            $mov->pedido?->codigo,
            $mov->usuario?->nombre,
        ];
    }

    public function columnFormats(): array
    {
        return ['A' => 'dd/mm/yyyy hh:mm'];
    }

    public function styles(Worksheet $hoja): array
    {
        $hoja->freezePane('A2');
        $hoja->setAutoFilter($hoja->calculateWorksheetDimension());

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0D5192']]]];
    }
}
