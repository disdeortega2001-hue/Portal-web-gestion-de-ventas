{{-- Encabezado de los documentos PDF: logo, empresa y título. DomPDF solo entiende CSS 2.1 básico. --}}
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f1b2d; }
    .encabezado { border-bottom: 3px solid #0b4a3b; padding-bottom: 8px; margin-bottom: 14px; }
    .marca { font-size: 22px; font-weight: bold; color: #0b4a3b; }
    .titulo { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
    .muted { color: #4a5668; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #0b4a3b; color: #fff; text-align: left; padding: 6px; }
    td { border-bottom: 1px solid #d3dbe5; padding: 6px; }
    .der { text-align: right; }
    .fuerte { font-weight: bold; }
    .atraso { color: #a2271b; font-weight: bold; }
    .totales { width: 45%; margin-left: 55%; margin-top: 12px; }
    .totales td { border: none; padding: 3px 6px; }
    .total { font-size: 14px; font-weight: bold; border-top: 2px solid #0f1b2d !important; }
</style>
<div class="encabezado">
    <table><tr>
        <td style="border:none;padding:0 12px 0 0;width:78px;vertical-align:middle"><img src="{{ public_path('img/logo-documento.png') }}" alt="Inversiones Rialto 7" style="width:72px;height:72px"></td>
        <td style="border:none;padding:0;vertical-align:middle"><span class="marca">Inversiones Rialto 7 C.A.</span> <span class="muted">RIF J-50668510-8</span><br>
            <span class="muted">Maquinaria agrícola y repuestos · Av. Las Industrias, Edificio Campagna, Valle de la Pascua, Guárico</span></td>
        <td style="border:none;padding:0;width:30%;white-space:nowrap;vertical-align:middle" class="der"><span class="titulo">{{ $titulo }}</span><br><span class="muted">Emitido: {{ now()->format('d/m/Y h:i a') }}</span></td>
    </tr></table>
</div>
