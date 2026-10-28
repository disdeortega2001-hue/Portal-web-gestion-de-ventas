<?php

namespace App\Exports\Hojas;

use App\Exports\Concerns\TextoSeguroEnExcel;
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

/** Productos con existencia y sin ventas en el período (baja rotación). */
class BajaRotacionHoja extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithStyles, WithTitle, WithCustomValueBinder
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
        return 'Baja rotación';
    }

    public function headings(): array
    {
        return ['Código de fábrica', 'Producto', 'Existencia', 'Precio USD', 'Valor inmovilizado USD'];
    }

    public function map($producto): array
    {
        return [
            $producto->codigo_fabrica,
            $producto->nombre,
            $producto->stock,
            (float) $producto->precio,
            round($producto->stock * (float) $producto->precio, 2),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $hoja): array
    {
        $hoja->freezePane('A2');

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0D5192']]]];
    }
}
