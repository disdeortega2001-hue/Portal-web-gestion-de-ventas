{{-- Estilos compartidos de los reportes PDF (DomPDF: CSS 2.1, sin flexbox ni variables) --}}
<style>
    @page { margin: 28px 32px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #0f1b2d; }
    .encabezado { border-bottom: 3px solid #0d5192; padding-bottom: 6px; margin-bottom: 10px; }
    .marca { font-size: 16px; font-weight: bold; color: #0d5192; }
    .titulo { font-size: 13px; font-weight: bold; margin: 0; }
    .muted { color: #4a5668; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #0d5192; color: #fff; text-align: left; padding: 4px; font-size: 8.5px; }
    td { border-bottom: 1px solid #d3dbe5; padding: 3px 4px; }
    tr:nth-child(even) td { background: #f4f6f9; }
    .der { text-align: right; }
    .resumen td { border: 1px solid #d3dbe5; padding: 6px; background: #e7ecf2; width: 20%; }
    .resumen .cifra { font-size: 13px; font-weight: bold; display: block; }
    h2 { font-size: 11px; margin: 14px 0 4px; }
    .pie { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #4a5668; }
    thead { display: table-header-group; } /* Repite el encabezado de la tabla en cada página */
</style>
