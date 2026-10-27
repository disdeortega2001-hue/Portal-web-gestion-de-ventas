{{-- Estilos compartidos de los reportes PDF (DomPDF: CSS 2.1, sin flexbox ni variables) --}}
<style>
    @page { margin: 28px 32px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1d211c; }
    .encabezado { border-bottom: 3px solid #0b4a3b; padding-bottom: 6px; margin-bottom: 10px; }
    .marca { font-size: 16px; font-weight: bold; color: #0b4a3b; }
    .titulo { font-size: 13px; font-weight: bold; margin: 0; }
    .muted { color: #545b52; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #0b4a3b; color: #fff; text-align: left; padding: 4px; font-size: 8.5px; }
    td { border-bottom: 1px solid #d6d1c2; padding: 3px 4px; }
    tr:nth-child(even) td { background: #f7f5ef; }
    .der { text-align: right; }
    .resumen td { border: 1px solid #d6d1c2; padding: 6px; background: #ece8dc; width: 20%; }
    .resumen .cifra { font-size: 13px; font-weight: bold; display: block; }
    h2 { font-size: 11px; margin: 14px 0 4px; }
    .pie { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #545b52; }
    thead { display: table-header-group; } /* Repite el encabezado de la tabla en cada página */
</style>
