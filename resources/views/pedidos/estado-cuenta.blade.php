<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Estado de cuenta {{ $cliente->identificacion }}</title></head>
<body>
@include('pedidos._membrete', ['titulo' => 'ESTADO DE CUENTA'])

<p><strong>{{ $cliente->nombre }}</strong><br>
    {{ $cliente->identificacion }} · {{ $cliente->telefono }}<br>{{ $cliente->direccion }}</p>

<table>
    <thead><tr><th>Pedido</th><th>Fecha</th><th>Vence</th><th class="der">Total USD</th><th class="der">Abonado USD</th><th class="der">Saldo USD</th></tr></thead>
    <tbody>
    @forelse($pedidos as $pedido)
        <tr>
            <td class="fuerte">{{ $pedido->codigo }}</td>
            <td>{{ $pedido->fecha->format('d/m/Y') }}</td>
            <td>@if($pedido->credito_vence_en){{ $pedido->credito_vence_en->format('d/m/Y') }}@if($pedido->creditoVencido())<br><span class="atraso">{{ $pedido->diasDeAtraso() }} días de atraso</span>@endif @else Por confirmar @endif</td>
            <td class="der">{{ number_format((float) $pedido->total, 2, ',', '.') }}</td>
            <td class="der">{{ number_format((float) $pedido->abonadoUsd(), 2, ',', '.') }}</td>
            <td class="der fuerte">{{ number_format((float) $pedido->saldoUsd(), 2, ',', '.') }}</td>
        </tr>
        @foreach($pedido->pagos->where('estado', \App\Models\Pago::VERIFICADO)->sortBy('fecha_pago') as $pago)
            <tr>
                <td></td>
                <td class="muted">{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                <td class="muted" colspan="2">Abono: {{ $pago->nombreMetodo() }} · {{ $pago->montoFormateado() }}@if($pago->referencia) · Ref. {{ $pago->referencia }}@endif</td>
                <td class="der muted">{{ number_format($pago->montoEnUsd(), 2, ',', '.') }}</td>
                <td></td>
            </tr>
        @endforeach
    @empty
        <tr><td colspan="6" class="muted">No tiene compras a crédito con saldo pendiente.</td></tr>
    @endforelse
    </tbody>
</table>

<table class="totales">
    <tr class="total"><td>Total por pagar</td><td class="der">USD {{ number_format($deuda, 2, ',', '.') }}</td></tr>
    <tr><td class="muted">A la tasa de hoy ({{ number_format($tasa, 2, ',', '.') }})</td><td class="der muted">Bs. {{ number_format($deuda * $tasa, 2, ',', '.') }}</td></tr>
</table>

<p class="muted" style="margin-top:24px">La deuda se lleva en dólares; cada abono en bolívares se descuenta a la tasa del día en que se paga. Este documento no sustituye la factura fiscal.</p>
</body>
</html>
