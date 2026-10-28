<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Reporte de inventario</title>@include('gestion.reportes.pdf._estilos')</head>
<body>
@include('gestion.reportes.pdf._encabezado', ['titulo' => 'Reporte de inventario'])

<table class="resumen">
    <tr>
        <td>Productos<span class="cifra">{{ $resumen['productos'] }}</span></td>
        <td>Unidades en existencia<span class="cifra">{{ number_format($resumen['unidades'], 0, ',', '.') }}</span></td>
        <td>Valor a precio de venta<span class="cifra">USD {{ number_format($resumen['valor_venta'], 2, ',', '.') }}</span></td>
        <td>Bajo stock (≤ {{ $umbral }}) / agotados<span class="cifra">{{ $resumen['bajo'] }} / {{ $resumen['agotados'] }}</span></td>
        <td>Movimientos del período<span class="cifra">{{ $resumen['movimientos'] }}</span><span class="muted">+{{ $resumen['entradas'] }} entradas · −{{ $resumen['salidas'] }} salidas · ajuste neto {{ $resumen['ajuste_neto'] }}</span></td>
    </tr>
</table>

<h2>Baja rotación: con existencia y sin ventas en el período</h2>
@if($resumen['baja_rotacion']->isEmpty())
    <p class="muted">Todos los productos con existencia tuvieron ventas en el período.</p>
@else
    <table style="width:70%">
        <thead><tr><th>Código</th><th>Producto</th><th class="der">Existencia</th><th class="der">Valor inmovilizado USD</th></tr></thead>
        <tbody>
        @foreach($resumen['baja_rotacion'] as $p)
            <tr><td>{{ $p->codigo_fabrica }}</td><td>{{ $p->nombre }}</td><td class="der">{{ $p->stock }}</td><td class="der">{{ number_format($p->stock * $p->precio, 2, ',', '.') }}</td></tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Existencias actuales</h2>
<table>
    <thead><tr><th>Código</th><th>Producto</th><th>Marca</th><th>Categoría</th><th>Estado</th><th class="der">Stock</th><th class="der">Precio USD</th><th class="der">Valor USD</th></tr></thead>
    <tbody>
    @foreach($existencias as $p)
        @php($estado = $p->estadoStock())
        <tr>
            <td>{{ $p->codigo_fabrica }}</td>
            <td>{{ $p->nombre }}</td>
            <td>{{ $p->marca }}</td>
            <td>{{ $p->categoria?->nombre }}</td>
            <td style="color: {{ ['disponible' => '#1f7a43', 'bajo' => '#a35c00', 'agotado' => '#b3261e'][$estado] }}">{{ ['disponible' => 'Disponible', 'bajo' => 'Bajo stock', 'agotado' => 'Agotado'][$estado] }}</td>
            <td class="der">{{ $p->stock }}</td>
            <td class="der">{{ number_format($p->precio, 2, ',', '.') }}</td>
            <td class="der">{{ number_format($p->stock * $p->precio, 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h2>Movimientos del período</h2>
<table>
    <thead><tr><th>Fecha</th><th>Código</th><th>Producto</th><th>Tipo</th><th class="der">Cant.</th><th class="der">Stock</th><th>Motivo</th><th>Usuario</th></tr></thead>
    <tbody>
    @forelse($movimientos as $m)
        <tr>
            <td>{{ $m->fecha->format('d/m/Y h:i a') }}</td>
            <td>{{ $m->producto?->codigo_fabrica }}</td>
            <td>{{ $m->producto?->nombre }}</td>
            <td>{{ ucfirst($m->tipo) }}</td>
            <td class="der">{{ $m->cantidad }}</td>
            <td class="der">{{ $m->stock_anterior }} → {{ $m->stock_nuevo }}</td>
            <td>{{ $m->motivo }}</td>
            <td>{{ $m->usuario?->nombre }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="muted">Sin movimientos en el período.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
