<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Evita la inyección de fórmulas en los Excel exportados.
 *
 * Por defecto PhpSpreadsheet convierte en FÓRMULA cualquier texto que empiece con "=".
 * Nombres, direcciones y observaciones los escriben clientes invitados: un nombre como
 * =HYPERLINK("http://sitio-malicioso";"Ver") se ejecutaría al abrir el reporte.
 * Aquí todo texto se guarda como texto; números y fechas siguen siendo números.
 *
 * Uso: la clase de exportación extiende DefaultValueBinder, implementa
 * WithCustomValueBinder y usa este trait.
 */
trait TextoSeguroEnExcel
{
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
