<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Comprobante {{ $pedido->codigo }}</title>
{{-- DomPDF solo entiende CSS 2.1 básico: sin flexbox ni variables CSS --}}
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f1b2d; }
    .encabezado { border-bottom: 3px solid #0d5192; padding-bottom: 8px; margin-bottom: 14px; }
    .marca { font-size: 22px; font-weight: bold; color: #0d5192; }
    .titulo { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
    .muted { color: #4a5668; }
    .sello { border: 2px solid #1d7a46; color: #1d7a46; font-weight: bold; padding: 4px 10px; display: inline-block; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #0d5192; color: #fff; text-align: left; padding: 6px; }
    td { border-bottom: 1px solid #d3dbe5; padding: 6px; }
    .der { text-align: right; }
    .totales { width: 45%; margin-left: 55%; margin-top: 12px; }
    .totales td { border: none; padding: 3px 6px; }
    .total { font-size: 14px; font-weight: bold; border-top: 2px solid #0f1b2d !important; }
    h2 { font-size: 12px; margin: 18px 0 6px; color: #0d5192; }
</style>
</head>
<body>
<div class="encabezado">
    <table><tr>
        <td style="border:none;padding:0"><span class="marca">Inversiones Rialto 7 C.A.</span><br>
            <span class="muted">Maquinaria agrícola y repuestos · Av. Las Industrias, Edificio Campagna, Valle de la Pascua, Guárico</span></td>
        <td style="border:none;padding:0;width:34%;white-space:nowrap" class="der"><span class="titulo">COMPROBANTE DE PAGO</span><br><span class="sello">PAGO VERIFICADO</span></td>
    </tr></table>
</div>

<table style="margin-bottom:14px">
    <tr>
        <td style="border:none;width:50%"><strong>Pedido {{ $pedido->codigo }}</strong><br>
            Fecha del pedido: {{ $pedido->fecha->format('d/m/Y') }}<br>
            Emitido: {{ $emitido->format('d/m/Y h:i a') }}<br>
            Entrega: {{ $pedido->entrega === 'envio' ? 'envío a coordinar' : 'retiro en la tienda' }}</td>
        <td style="border:none"><strong>{{ $pedido->cliente->nombre }}</strong><br>
            {{ $pedido->cliente->identificacion }} · {{ $pedido->cliente->telefono }}<br>{{ $pedido->cliente->direccion }}</td>
    </tr>
</table>

<table>
    <thead><tr><th>Código</th><th>Descripción</th><th class="der">Cant.</th><th class="der">Precio USD</th><th class="der">Subtotal USD</th></tr></thead>
    <tbody>
    @foreach($pedido->detalles as $detalle)
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
    <tr><td>Subtotal</td><td class="der">USD {{ number_format($pedido->subtotal, 2, ',', '.') }}</td></tr>
    @if((float) $pedido->descuento > 0)
        <tr><td>Descuento</td><td class="der">USD -{{ number_format($pedido->descuento, 2, ',', '.') }}</td></tr>
    @endif
    <tr><td>{{ $pedido->tipo_impuesto }} ({{ (float) $pedido->porcentaje_impuesto }}%) · {{ $pedido->tipo_impuesto === 'IGTF' ? 'pago en dólares' : 'pago en bolívares' }}</td><td class="der">USD {{ number_format($pedido->impuesto, 2, ',', '.') }}</td></tr>
    <tr><td class="total">Total</td><td class="der total">USD {{ number_format($pedido->total, 2, ',', '.') }}</td></tr>
    <tr><td class="muted">Total en Bs. (tasa BCV {{ number_format($pedido->tasa_cambio, 2, ',', '.') }})</td><td class="der muted">Bs. {{ number_format($pedido->total_bs, 2, ',', '.') }}</td></tr>
</table>

<h2>Pagos recibidos</h2>
<table>
    <thead><tr><th>Método</th><th>Referencia</th><th>Fecha del pago</th><th>Verificado</th><th class="der">Monto</th></tr></thead>
    <tbody>
    @foreach($pagos as $pago)
        <tr>
            <td>{{ $pago->nombreMetodo() }}</td>
            <td>{{ $pago->referencia ?: '—' }}</td>
            <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
            <td>{{ $pago->verificado_en?->format('d/m/Y') }}@if($pago->verificadoPor) · {{ $pago->verificadoPor->nombre }}@endif</td>
            <td class="der">{{ $pago->montoFormateado() }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

@if($pedido->estaEntregado())
    <h2>Entrega</h2>
    <p>{{ $pedido->entrega === 'envio' ? 'Entregado' : 'Retirado en la tienda' }} el {{ $pedido->entregado_en->format('d/m/Y h:i a') }}.
        Recibió: {{ $pedido->recibido_por }}@if($pedido->recibido_por_identificacion) (C.I. {{ $pedido->recibido_por_identificacion }})@endif.
        @if($pedido->entregadoPor) Entregó: {{ $pedido->entregadoPor->nombre }}.@endif</p>
@endif

<p class="muted" style="margin-top:24px">Este comprobante certifica que el pago del pedido {{ $pedido->codigo }} fue recibido y verificado por Inversiones Rialto 7 C.A. No sustituye la factura fiscal. Preséntalo (impreso o en el teléfono) al retirar tu pedido.</p>
</body>
</html>
