<?php

namespace App\Exports;

use App\Exports\Concerns\TextoSeguroEnExcel;
use Illuminate\Support\Carbon;
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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reporte de ventas en Excel: una fila por producto vendido.
 * Fechas y montos se exportan como valores numéricos (no texto) para que
 * la gerencia pueda filtrar, sumar y hacer tablas dinámicas.
 */
class VentasExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithStyles, WithTitle, WithCustomValueBinder
{
    use TextoSeguroEnExcel;   // Nombres de clientes como "=HYPERLINK(...)" quedan como texto, no como fórmula

    public function __construct(private readonly Collection $filas)
    {
    }

    public function collection(): Collection
    {
        return $this->filas;
    }

    public function title(): string
    {
        return 'Ventas';
    }

    public function headings(): array
    {
        return ['Fecha', 'Pedido', 'Cédula/RIF', 'Cliente', 'Vendedor', 'Código de fábrica', 'Producto', 'Cantidad', 'Precio unitario USD', 'Subtotal USD'];
    }

    public function map($fila): array
    {
        return [
            Date::dateTimeToExcel(Carbon::parse($fila->fecha_confirmacion)),
            $fila->pedido,
            $fila->identificacion,
            $fila->cliente,
            $fila->vendedor,
            $fila->codigo_fabrica,
            $fila->producto,
            (int) $fila->cantidad,
            (float) $fila->precio_unitario,
            (float) $fila->subtotal,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => 'dd/mm/yyyy hh:mm',
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $hoja): array
    {
        $hoja->freezePane('A2');
        $hoja->setAutoFilter($hoja->calculateWorksheetDimension());

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0B4A3B']]]];
    }
}
