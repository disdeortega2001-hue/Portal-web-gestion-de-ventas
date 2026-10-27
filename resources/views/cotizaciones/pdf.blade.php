<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $cotizacion->codigo }}</title>
{{-- DomPDF solo entiende CSS 2.1 básico: sin flexbox ni variables CSS --}}
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1d211c; }
    .encabezado { border-bottom: 3px solid #0b4a3b; padding-bottom: 8px; margin-bottom: 14px; }
    .marca { font-size: 22px; font-weight: bold; color: #0b4a3b; }
    .muted { color: #545b52; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #0b4a3b; color: #fff; text-align: left; padding: 6px; }
    td { border-bottom: 1px solid #d6d1c2; padding: 6px; }
    .der { text-align: right; }
    .totales { width: 45%; margin-left: 55%; margin-top: 12px; }
    .totales td { border: none; padding: 3px 6px; }
    .total { font-size: 14px; font-weight: bold; border-top: 2px solid #1d211c !important; }
</style>
</head>
<body>
<div class="encabezado">
    <span class="marca">Inversiones Rialto 7 C.A.</span><br>
    <span class="muted">Maquinaria agrícola y repuestos · Valle de la Pascua, Guárico</span>
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
            <td>{{ $detalle->producto->nombre }}</td>
            <td class="der">{{ $detalle->cantidad }}</td>
            <td class="der">{{ number_format($detalle->precio_unitario, 2, ',', '.') }}</td>
            <td class="der">{{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totales">
    <tr><td>Subtotal</td><td class="der">USD {{ number_format($cotizacion->subtotal, 2, ',', '.') }}</td></tr>
    @if((float) $cotizacion->descuento > 0)
        <tr><td>Descuento</td><td class="der">− USD {{ number_format($cotizacion->descuento, 2, ',', '.') }}</td></tr>
    @endif
    <tr><td>IVA ({{ (float) $cotizacion->porcentaje_iva }}%)</td><td class="der">USD {{ number_format($cotizacion->impuesto, 2, ',', '.') }}</td></tr>
    <tr><td class="total">Total</td><td class="der total">USD {{ number_format($cotizacion->total, 2, ',', '.') }}</td></tr>
    <tr><td class="muted">Total en Bs. (tasa {{ number_format($cotizacion->tasa_cambio, 2, ',', '.') }})</td><td class="der muted">Bs. {{ number_format($cotizacion->total_bs, 2, ',', '.') }}</td></tr>
</table>

<p class="muted" style="margin-top:24px">Cotización referencial sujeta a disponibilidad de inventario al momento de la confirmación. Válida por 5 días.</p>
</body>
</html>
