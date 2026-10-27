<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Plantilla de Excel para cargar el catálogo real de una sola vez.
 * Las columnas se reconocen por su título (sin importar mayúsculas, tildes ni el "*"),
 * así que la empresa puede reordenarlas o usar una hoja propia con los mismos títulos.
 */
class PlantillaCatalogo
{
    /** clave interna => [título en la plantilla, ancho, ejemplo] */
    public const COLUMNAS = [
        'codigo' => ['Código de fábrica *', 22, 'RE504836'],
        'nombre' => ['Nombre *', 38, 'Filtro de aceite de motor'],
        'categoria' => ['Categoría *', 22, 'Filtros'],
        'marca' => ['Marca / fabricante *', 22, 'John Deere'],
        'precio' => ['Precio USD *', 14, '18.90'],
        'stock' => ['Existencia *', 12, '42'],
        'compatibilidad' => ['Modelos compatibles', 34, 'John Deere 5075E, 5090E'],
        'descripcion' => ['Descripción', 48, 'Filtro original, rosca 3/4"'],
        'foto' => ['Foto (nombre del archivo)', 28, 'RE504836.jpg'],
        'costo' => ['Costo USD (interno)', 18, '11.20'],
    ];

    /** Sinónimos aceptados en los títulos, ya normalizados (minúsculas, sin tildes ni signos). */
    public const SINONIMOS = [
        'codigo' => ['codigo de fabrica', 'codigo', 'cod', 'referencia', 'sku'],
        'nombre' => ['nombre', 'producto', 'nombre del producto'],
        'categoria' => ['categoria', 'tipo', 'linea'],
        'marca' => ['marca fabricante', 'marca', 'fabricante'],
        'precio' => ['precio usd', 'precio', 'precio de venta', 'precio venta usd'],
        'stock' => ['existencia', 'stock', 'cantidad', 'existencias', 'disponible'],
        'compatibilidad' => ['modelos compatibles', 'compatibilidad', 'modelos', 'compatible con'],
        'descripcion' => ['descripcion', 'detalle'],
        'foto' => ['foto nombre del archivo', 'foto', 'imagen', 'archivo de foto'],
        'costo' => ['costo usd interno', 'costo usd', 'costo'],
    ];

    public static function normalizarTitulo(?string $titulo): string
    {
        $texto = mb_strtolower(trim((string) $titulo));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);

        return trim((string) $texto);
    }

    public static function generar(string $destino): void
    {
        $libro = new Spreadsheet();

        $hoja = $libro->getActiveSheet()->setTitle('Productos');
        $columna = 'A';
        foreach (self::COLUMNAS as [$titulo, $ancho]) {
            $hoja->setCellValue("{$columna}1", $titulo);
            $hoja->getColumnDimension($columna)->setWidth($ancho);
            $columna++;
        }
        $hoja->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0B4A3B']],
        ]);
        $hoja->freezePane('A2');
        $hoja->getStyle('E2:E2000')->getNumberFormat()->setFormatCode('0.00');
        $hoja->getStyle('J2:J2000')->getNumberFormat()->setFormatCode('0.00');
        $hoja->getStyle('A2:A2000')->getNumberFormat()->setFormatCode('@');   // Códigos como texto: conserva ceros a la izquierda

        foreach (['E' => ['decimal', 'El precio debe ser un número mayor que 0 (ej. 18.90).'],
                  'F' => ['whole', 'La existencia debe ser un número entero, 0 o mayor.']] as $col => [$tipo, $mensaje]) {
            $validacion = $hoja->getCell("{$col}2")->getDataValidation();
            $validacion->setType($tipo === 'decimal' ? DataValidation::TYPE_DECIMAL : DataValidation::TYPE_WHOLE)
                ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
                ->setFormula1('0')
                ->setAllowBlank(true)
                ->setShowErrorMessage(true)
                ->setErrorTitle('Valor no válido')
                ->setError($mensaje);
            $validacion->setSqref("{$col}2:{$col}2000");
        }

        $ayuda = $libro->createSheet()->setTitle('Instrucciones');
        $lineas = [
            ['CÓMO CARGAR EL CATÁLOGO DE INVERSIONES RIALTO 7'],
            [''],
            ['1. Escribe un producto por fila en la hoja "Productos", desde la fila 2. No cambies los títulos de la fila 1.'],
            ['2. Las columnas con * son obligatorias. El código de fábrica no se puede repetir.'],
            ['3. Precio y costo van en dólares, con punto o coma decimal (18.90 o 18,90). El costo nunca se muestra al público.'],
            ['4. Existencia: unidades disponibles hoy en el almacén (0 si está agotado).'],
            ['5. Categoría: escribe siempre igual el mismo nombre (ej. "Filtros"); las categorías se crean solas.'],
            ['6. Modelos compatibles: separados por coma. El cliente podrá buscar por modelo de tractor.'],
            ['7. Fotos: guárdalas en la carpeta "fotos", junto a este archivo, en JPG, PNG o WEBP.'],
            ['   Si la foto se llama igual que el código (RE504836.jpg) no hace falta escribirla en la columna Foto.'],
            ['   Una foto por producto, de frente y con fondo claro; el sistema la reduce sola para que cargue rápido.'],
            [''],
            ['Ejemplo de una fila completa:'],
            array_map(fn ($c) => $c[0], array_values(self::COLUMNAS)),
            array_map(fn ($c) => $c[2], array_values(self::COLUMNAS)),
        ];
        $ayuda->fromArray($lineas, null, 'A1');
        $ayuda->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $ayuda->getStyle('A14:J14')->getFont()->setBold(true);
        $ayuda->getColumnDimension('A')->setWidth(24);

        $libro->setActiveSheetIndex(0);
        (new Xlsx($libro))->save($destino);
        $libro->disconnectWorksheets();
    }
}
