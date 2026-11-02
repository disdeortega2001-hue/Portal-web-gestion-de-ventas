<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Cuentas por cobrar</title></head>
<body>
@include('pedidos._membrete', ['titulo' => 'CUENTAS POR COBRAR'])

<p class="muted">Saldo en USD por cliente, según los días de atraso desde el vencimiento de cada compra a crédito.</p>
<table>
    <thead><tr><th>Cliente</th><th>Teléfono</th><th class="der">Al día</th><th class="der">1 a 30 días</th><th class="der">31 a 60 días</th><th class="der">Más de 60</th><th class="der">Total</th></tr></thead>
    <tbody>
    @forelse($cuentas as $c)
        <tr>
            <td><span class="fuerte">{{ $c['cliente']->nombre }}</span><br><span class="muted">{{ $c['cliente']->identificacion }}</span></td>
            <td>{{ $c['cliente']->telefono }}</td>
            @foreach(['al_dia', 'd30', 'd60', 'mas'] as $tramo)
                <td class="der">{{ $c[$tramo] > 0 ? number_format($c[$tramo], 2, ',', '.') : '—' }}</td>
            @endforeach
            <td class="der fuerte">{{ number_format($c['total'], 2, ',', '.') }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted">No hay cuentas por cobrar.</td></tr>
    @endforelse
    </tbody>
    @if($cuentas->isNotEmpty())
        <tfoot><tr>
            <td colspan="2" class="fuerte">Total</td>
            @foreach(['al_dia', 'd30', 'd60', 'mas', 'total'] as $tramo)
                <td class="der fuerte">{{ number_format($cuentas->sum($tramo), 2, ',', '.') }}</td>
            @endforeach
        </tr></tfoot>
    @endif
</table>
</body>
</html>
