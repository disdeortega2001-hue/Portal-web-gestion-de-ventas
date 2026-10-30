<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $cotizacion->codigo }}</title>
{{-- DomPDF solo entiende CSS 2.1 básico: sin flexbox ni variables CSS --}}
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f1b2d; }
    .encabezado { border-bottom: 3px solid #0d5192; padding-bottom: 8px; margin-bottom: 14px; }
    .marca { font-size: 22px; font-weight: bold; color: #0d5192; }
    .muted { color: #4a5668; }
    .titulo { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #0d5192; color: #fff; text-align: left; padding: 6px; }
    td { border-bottom: 1px solid #d3dbe5; padding: 6px; }
    .der { text-align: right; }
    .totales { width: 45%; margin-left: 55%; margin-top: 12px; }
    .totales td { border: none; padding: 3px 6px; }
    .total { font-size: 14px; font-weight: bold; border-top: 2px solid #0f1b2d !important; }
</style>
</head>
<body>
<div class="encabezado">
    <table><tr>
        <td style="border:none;padding:0 12px 0 0;width:78px;vertical-align:middle"><img src="{{ public_path('img/logo-documento.png') }}" alt="Inversiones Rialto 7" style="width:72px;height:72px"></td>
        <td style="border:none;padding:0;vertical-align:middle"><span class="marca">Inversiones Rialto 7 C.A.</span> <span class="muted">RIF J-50668510-8</span><br>
            <span class="muted">Maquinaria agrícola y repuestos · Av. Las Industrias, Edificio Campagna, Valle de la Pascua, Guárico</span></td>
        <td style="border:none;padding:0;width:30%;white-space:nowrap;vertical-align:middle" class="der"><span class="titulo">COTIZACIÓN</span></td>
    </tr></table>
</div>

<table style="margin-bottom:14px">
    <tr>
        <td style="border:none;width:50%"><strong>Cotización {{ $cotizacion->codigo }}</strong><br>
            Fecha: {{ $cotizacion->fecha->format('d/m/Y') }}<br>Estado: {{ ucfirst($cotizacion->estado) }}</td>
        <td style="border:none"><strong>{{ $cotizacion->cliente->nombre }}</strong><br>
            {{ $cotizacion->cliente->identificacion }} · {{ $cotizacion->cliente->telefono }}<br>{{ $cotizacion->cliente->direccion }}</td>
    </tr>
</table>

<table>
    <thead><tr><th>Código</th><th>Descripción</th><th class="der">Cant.</th><th class="der">Precio USD</th><th class="der">Subtotal USD</th></tr></thead>
    <tbody>
    @foreach($cotizacion->detalles as $detalle)
        <tr>
            <td>{{ $detalle->producto->codigo_fabrica }}</td>
            <td>{{ $detalle->producto->nombre }}@if($detalle->exento_iva) <span style="font-size:8px;color:#5b6b7f">(E)</span>@endif</td>
            <td class="der">{{ $detalle->cantidad }}</td>
            <td class="der">{{ number_format($detalle->precio_unitario, 2, ',', '.') }}</td>
            <td class="der">{{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totales">
    <tr><td>Subtotal</td><td class="der">USD {{ number_format($cotizacion->subtotal, 2, ',', '.') }}</td></tr>
    @if((float) $cotizacion->monto_exento > 0 && $cotizacion->tipo_impuesto === 'IVA')
    <tr><td>Exento de IVA (E)</td><td class="der">USD {{ number_format($cotizacion->monto_exento, 2, ',', '.') }}</td></tr>
    @endif
    <tr><td>{{ $cotizacion->tipo_impuesto }} ({{ (float) $cotizacion->porcentaje_impuesto }}%) · {{ $cotizacion->tipo_impuesto === 'IGTF' ? 'pago en dólares' : 'pago en bolívares' }}</td><td class="der">USD {{ number_format($cotizacion->impuesto, 2, ',', '.') }}</td></tr>
    <tr><td class="total">Total</td><td class="der total">USD {{ number_format($cotizacion->total, 2, ',', '.') }}</td></tr>
    <tr><td class="muted">Total en Bs. (tasa BCV {{ number_format($cotizacion->tasa_cambio, 2, ',', '.') }})</td><td class="der muted">Bs. {{ number_format($cotizacion->total_bs, 2, ',', '.') }}</td></tr>
</table>

<p class="muted" style="margin-top:24px">Cotización referencial sujeta a disponibilidad de inventario al momento de la confirmación. Válida por 5 días.</p>
</body>
</html>
