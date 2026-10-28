<?php

namespace App\Exports\Hojas;

use App\Exports\Concerns\TextoSeguroEnExcel;
use App\Models\Producto;
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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExistenciasHoja extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithStyles, WithTitle, WithCustomValueBinder
{
    use TextoSeguroEnExcel;

    public function __construct(private readonly Collection $productos)
    {
    }

    public function collection(): Collection
    {
        return $this->productos;
    }

    public function title(): string
    {
        return 'Existencias';
    }

    public function headings(): array
    {
        return ['Código de fábrica', 'Producto', 'Marca', 'Categoría', 'Estado', 'Stock', 'Precio USD', 'Valor a precio de venta USD'];
    }

    /** @param  Producto  $producto */
    public function map($producto): array
    {
        $estado = ['disponible' => 'Disponible', 'bajo' => 'Bajo stock', 'agotado' => 'Agotado'][$producto->estadoStock()];

        return [
            $producto->codigo_fabrica,
            $producto->nombre,
            $producto->marca,
            $producto->categoria?->nombre,
            $estado,
            $producto->stock,
            (float) $producto->precio,
            round($producto->stock * (float) $producto->precio, 2),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $hoja): array
    {
        $hoja->freezePane('A2');
        $hoja->setAutoFilter($hoja->calculateWorksheetDimension());

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0D5192']]]];
    }
}
