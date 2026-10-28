<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Reporte de ventas</title>@include('gestion.reportes.pdf._estilos')</head>
<body>
@include('gestion.reportes.pdf._encabezado', ['titulo' => 'Reporte de ventas'])

@php($f = $resumen['facturado'])
<table class="resumen">
    <tr>
        <td>Pedidos confirmados<span class="cifra">{{ $resumen['pedidos'] }}</span></td>
        <td>Unidades vendidas<span class="cifra">{{ number_format($resumen['unidades'], 0, ',', '.') }}</span></td>
        <td>Subtotal (sin IVA)<span class="cifra">USD {{ number_format($f['subtotal'], 2, ',', '.') }}</span></td>
        <td>IVA<span class="cifra">USD {{ number_format($f['impuesto'], 2, ',', '.') }}</span></td>
        <td>Total facturado<span class="cifra">USD {{ number_format($f['total'], 2, ',', '.') }}</span><span class="muted">Bs. {{ number_format($f['total_bs'], 2, ',', '.') }}</span></td>
    </tr>
</table>
@if($resumen['filtrado_por_producto'])
    <p class="muted">Filtrado por producto: el monto del producto es USD {{ number_format($resumen['monto_lineas'], 2, ',', '.') }} (sin IVA). Los totales facturados corresponden a los pedidos completos que lo incluyen.</p>
@endif

@if($resumen['mas_vendidos']->isNotEmpty())
    <h2>Los más vendidos del período</h2>
    <table style="width:70%">
        <thead><tr><th>Código</th><th>Producto</th><th class="der">Unidades</th><th class="der">Monto USD</th></tr></thead>
        <tbody>
        @foreach($resumen['mas_vendidos'] as $m)
            <tr><td>{{ $m->codigo_fabrica }}</td><td>{{ $m->nombre }}</td><td class="der">{{ $m->unidades }}</td><td class="der">{{ number_format($m->monto, 2, ',', '.') }}</td></tr>
        @endforeach
        </tbody>
    </table>
@endif

@if($resumen['por_vendedor']->count() > 1)
    <h2>Ventas por vendedor</h2>
    <table style="width:50%">
        <thead><tr><th>Vendedor</th><th class="der">Pedidos</th><th class="der">Total USD</th></tr></thead>
        <tbody>
        @foreach($resumen['por_vendedor'] as $v)
            <tr><td>{{ $v->nombre }}</td><td class="der">{{ $v->pedidos }}</td><td class="der">{{ number_format($v->total_vendido, 2, ',', '.') }}</td></tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Detalle</h2>
<table>
    <thead><tr><th>Fecha</th><th>Pedido</th><th>Cliente</th><th>Vendedor</th><th>Código</th><th>Producto</th><th class="der">Cant.</th><th class="der">Precio USD</th><th class="der">Subtotal USD</th></tr></thead>
    <tbody>
    @forelse($filas as $fila)
        <tr>
            <td>{{ \Illuminate\Support\Carbon::parse($fila->fecha_confirmacion)->format('d/m/Y') }}</td>
            <td>{{ $fila->pedido }}</td>
            <td>{{ $fila->cliente }}</td>
            <td>{{ $fila->vendedor }}</td>
            <td>{{ $fila->codigo_fabrica }}</td>
            <td>{{ $fila->producto }}</td>
            <td class="der">{{ $fila->cantidad }}</td>
            <td class="der">{{ number_format($fila->precio_unitario, 2, ',', '.') }}</td>
            <td class="der">{{ number_format($fila->subtotal, 2, ',', '.') }}</td>
        </tr>
    @empty
        <tr><td colspan="9" class="muted">No hay ventas confirmadas con estos filtros.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
