@extends('layouts.app')
@section('titulo', 'Reportes')

@section('contenido')
<x-encabezado titulo="Reportes">Lo que ves en pantalla es exactamente lo que se exporta a PDF y Excel.</x-encabezado>

@php
    $tipo = request('tipo', 'ventas');
    // Períodos de un toque: llenan «Desde» y «Hasta»; el reporte se genera con «Ver en pantalla»
    $hoy = now();
    $periodos = [
        'Hoy' => [$hoy->toDateString(), $hoy->toDateString()],
        'Esta semana' => [$hoy->copy()->startOfWeek()->toDateString(), $hoy->toDateString()],
        'Este mes' => [$hoy->copy()->startOfMonth()->toDateString(), $hoy->toDateString()],
        'Mes pasado' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        'Este año' => [$hoy->copy()->startOfYear()->toDateString(), $hoy->toDateString()],
    ];
@endphp
<form method="GET" action="{{ route('gestion.reportes.index') }}" class="lobulo panel-tapa p-5 pt-7 needs-validation lg:p-6 lg:pt-8" novalidate id="form-reporte"
      x-data="{ tipo: '{{ $tipo }}', periodo(d, h) { $refs.desde.value = d; $refs.hasta.min = d; $refs.hasta.value = h; } }">
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="mr-1 text-sm font-semibold text-tinta-suave">Período:</span>
        @foreach($periodos as $texto => [$d, $h])
            <button type="button" class="capsula !min-h-9 !pr-3.5 !text-[0.9375rem]" @click="periodo('{{ $d }}', '{{ $h }}')"
                    @if(request('desde') === $d && request('hasta') === $h) aria-current="true" @endif>{{ $texto }}</button>
        @endforeach
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <div>
            <label for="tipo" class="etiqueta">Reporte *</label>
            <select id="tipo" name="tipo" class="campo" required x-model="tipo">
                <option value="ventas" @selected($tipo === 'ventas')>Ventas</option>
                <option value="inventario" @selected($tipo === 'inventario')>Inventario</option>
            </select>
        </div>
        <div>
            <label for="desde" class="etiqueta">Desde *</label>
            <input type="date" id="desde" name="desde" class="campo cifra" required max="{{ now()->toDateString() }}"
                   value="{{ request('desde', now()->startOfMonth()->toDateString()) }}" x-ref="desde" @change="$refs.hasta.min = $event.target.value">
        </div>
        <div>
            <label for="hasta" class="etiqueta">Hasta *</label>
            <input type="date" id="hasta" name="hasta" class="campo cifra" required x-ref="hasta" min="{{ request('desde', now()->startOfMonth()->toDateString()) }}"
                   value="{{ request('hasta', now()->toDateString()) }}">
        </div>
        <div>
            <label for="id_producto" class="etiqueta">Producto</label>
            <select id="id_producto" name="id_producto" class="campo">
                <option value="">Todos</option>
                @foreach($productos as $p)
                    <option value="{{ $p->id }}" @selected(request('id_producto') == $p->id)>{{ $p->codigo_fabrica }} — {{ $p->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="id_vendedor" class="etiqueta" id="etiqueta-vendedor" x-text="tipo === 'ventas' ? 'Vendedor' : 'Usuario responsable'">{{ $tipo === 'ventas' ? 'Vendedor' : 'Usuario responsable' }}</label>
            <select id="id_vendedor" name="id_vendedor" class="campo">
                <option value="">Todos</option>
                @foreach($vendedores as $v)
                    <option value="{{ $v->id }}" @selected(request('id_vendedor') == $v->id)>{{ $v->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div id="campo-categoria" x-show="tipo === 'inventario'" @if($tipo === 'ventas') x-cloak @endif>
            <label for="id_categoria" class="etiqueta">Categoría</label>
            <select id="id_categoria" name="id_categoria" class="campo">
                <option value="">Todas</option>
                @foreach($categorias as $c)
                    <option value="{{ $c->id }}" @selected(request('id_categoria') == $c->id)>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="mt-5 flex flex-wrap gap-2">
        <button class="btn btn-tierra"><x-icono nombre="tabla" class="size-4" /> Ver en pantalla</button>
        <button class="btn btn-linea" formaction="{{ route('gestion.reportes.exportar') }}" name="formato" value="pdf"><x-icono nombre="pdf" class="size-4" /> Descargar PDF</button>
        <button class="btn btn-linea" formaction="{{ route('gestion.reportes.exportar') }}" name="formato" value="excel"><x-icono nombre="excel" class="size-4" /> Descargar Excel</button>
    </div>
</form>

@if($resultado && $resultado['tipo'] === 'ventas')
    @php($r = $resultado['resumen'])
    <section aria-label="Resumen de ventas" class="lobulo-tierra mt-6 grid overflow-hidden sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-white/15 p-5 sm:border-r"><p class="text-sm font-bold text-white/75">Pedidos confirmados</p><p class="titular mt-2 text-[2rem] cifra">{{ $r['pedidos'] }}</p></div>
        <div class="border-t border-white/15 p-5 sm:border-t-0 xl:border-r"><p class="text-sm font-bold text-white/75">Unidades vendidas</p><p class="titular mt-2 text-[2rem] cifra">{{ number_format($r['unidades'], 0, ',', '.') }}</p></div>
        <div class="border-t border-white/15 p-5 sm:border-r xl:border-t-0"><p class="text-sm font-bold text-white/75">Impuestos</p><p class="titular mt-2 text-[2rem] cifra">@usd($r['facturado']['impuesto'])</p><p class="mt-1 text-sm text-white/75 cifra">IVA @usd($r['facturado']['iva']) · IGTF @usd($r['facturado']['igtf'])</p></div>
        <div class="border-t border-white/15 bg-sol p-5 text-tinta xl:border-t-0"><p class="text-sm font-bold">Total facturado</p><p class="titular mt-2 text-[2rem] cifra">@usd($r['facturado']['total'])</p><p class="mt-1 text-sm cifra">@bs($r['facturado']['total_bs'])</p></div>
    </section>
    @if($r['filtrado_por_producto'])
        <p class="apoyo mt-3 text-sm">Monto del producto filtrado: @usd($r['monto_lineas']) sin impuestos. Los totales facturados son de los pedidos completos que lo incluyen.</p>
    @endif

    @if($r['por_vendedor']->isNotEmpty())
        <section class="mt-8" aria-labelledby="t-vend">
            <h2 id="t-vend" class="seccion-titulo">Por vendedor</h2>
            <div class="lobulo mt-3 max-w-xl overflow-x-auto">
                <table class="tabla"><thead><tr><th>Vendedor</th><th class="!text-right">Pedidos</th><th class="!text-right">Total</th></tr></thead>
                    <tbody>@foreach($r['por_vendedor'] as $v)<tr><td class="font-semibold">{{ $v->nombre }}</td><td class="num">{{ $v->pedidos }}</td><td class="num font-bold">@usd($v->total_vendido)</td></tr>@endforeach</tbody>
                </table>
            </div>
        </section>
    @endif

    @if($r['mas_vendidos']->isNotEmpty())
        <section class="mt-8" aria-labelledby="t-top">
            <h2 id="t-top" class="seccion-titulo">Los más vendidos del período</h2>
            <div class="lobulo mt-3 max-w-3xl overflow-x-auto">
                <table class="tabla"><thead><tr><th>Código</th><th>Producto</th><th class="!text-right">Unidades</th><th class="!text-right">Monto</th></tr></thead>
                    <tbody>@foreach($r['mas_vendidos'] as $m)<tr><td><span class="codigo">{{ $m->codigo_fabrica }}</span></td><td>{{ $m->nombre }}</td><td class="num font-bold">{{ $m->unidades }}</td><td class="num">@usd($m->monto)</td></tr>@endforeach</tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="mt-8" aria-labelledby="t-det">
        <h2 id="t-det" class="seccion-titulo">Detalle (<span class="cifra">{{ $resultado['filas']->total() }}</span> líneas)</h2>
        <div class="lobulo mt-3 overflow-x-auto">
            <table class="tabla text-sm">
                <thead><tr><th>Fecha</th><th>Pedido</th><th>Cliente</th><th>Vendedor</th><th>Código</th><th>Producto</th><th class="!text-right">Cant.</th><th class="!text-right">Precio</th><th class="!text-right">Subtotal</th></tr></thead>
                <tbody>
                @forelse($resultado['filas'] as $fila)
                    <tr>
                        <td class="cifra whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($fila->fecha_confirmacion)->format('d/m/Y') }}</td>
                        <td class="cifra whitespace-nowrap">{{ $fila->pedido }}</td><td>{{ $fila->cliente }}</td><td>{{ $fila->vendedor }}</td>
                        <td><span class="codigo">{{ $fila->codigo_fabrica }}</span></td><td>{{ $fila->producto }}</td>
                        <td class="num">{{ $fila->cantidad }}</td><td class="num">@usd($fila->precio_unitario)</td><td class="num font-bold">@usd($fila->subtotal)</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="apoyo py-8 text-center">No hay ventas confirmadas con estos filtros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $resultado['filas']->links() }}
    </section>
@endif

@if($resultado && $resultado['tipo'] === 'inventario')
    @php($r = $resultado['resumen'])
    <section aria-label="Resumen de inventario" class="lobulo-tierra mt-6 grid overflow-hidden sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-white/15 p-5 sm:border-r"><p class="text-sm font-bold text-white/75">Unidades en existencia</p><p class="titular mt-2 text-[2rem] cifra">{{ number_format($r['unidades'], 0, ',', '.') }}</p><p class="mt-1 text-sm text-white/75">{{ $r['productos'] }} productos</p></div>
        <div class="border-t border-white/15 p-5 sm:border-t-0 xl:border-r"><p class="text-sm font-bold text-white/75">Valor a precio de venta</p><p class="titular mt-2 text-[2rem] cifra">@usd($r['valor_venta'])</p></div>
        <div class="border-t border-white/15 p-5 sm:border-r xl:border-t-0"><p class="text-sm font-bold text-white/75">Bajo stock / agotados</p><p class="titular mt-2 text-[2rem] cifra">{{ $r['bajo'] }} / <span class="text-coral">{{ $r['agotados'] }}</span></p><p class="mt-1 text-sm text-white/75">Umbral: {{ $resultado['umbral'] }}</p></div>
        <div class="border-t border-white/15 p-5 xl:border-t-0"><p class="text-sm font-bold text-white/75">Movimientos del período</p><p class="titular mt-2 text-[2rem] cifra">{{ $r['movimientos'] }}</p><p class="mt-1 text-sm text-white/75 cifra">+{{ $r['entradas'] }} / −{{ $r['salidas'] }} · ajuste {{ $r['ajuste_neto'] }}</p></div>
    </section>

    <section class="mt-8" aria-labelledby="t-rot">
        <h2 id="t-rot" class="seccion-titulo">Baja rotación: con existencia y sin ventas en el período</h2>
        @if($r['baja_rotacion']->isEmpty())
            <p class="apoyo mt-2">Todos los productos con existencia tuvieron ventas en el período.</p>
        @else
            <div class="lobulo mt-3 max-w-3xl overflow-x-auto">
                <table class="tabla text-sm"><thead><tr><th>Código</th><th>Producto</th><th class="!text-right">Existencia</th><th class="!text-right">Valor inmovilizado</th></tr></thead>
                    <tbody>@foreach($r['baja_rotacion'] as $p)<tr><td><span class="codigo">{{ $p->codigo_fabrica }}</span></td><td>{{ $p->nombre }}</td><td class="num">{{ $p->stock }}</td><td class="num font-bold">@usd($p->stock * $p->precio)</td></tr>@endforeach</tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="mt-8" aria-labelledby="t-exist">
        <h2 id="t-exist" class="seccion-titulo">Existencias actuales</h2>
        <div class="lobulo mt-3 overflow-x-auto">
            <table class="tabla text-sm">
                <thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th class="!text-right">Stock</th><th class="!text-right">Valor</th></tr></thead>
                <tbody>
                @foreach($resultado['existencias'] as $p)
                    <tr>
                        <td><span class="codigo">{{ $p->codigo_fabrica }}</span></td><td class="font-semibold">{{ $p->nombre }}</td><td class="apoyo">{{ $p->categoria?->nombre }}</td>
                        <td class="text-right">@include('partials.stock', ['producto' => $p])</td>
                        <td class="num font-bold">@usd($p->stock * $p->precio)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $resultado['existencias']->links() }}
    </section>

    <section class="mt-8" aria-labelledby="t-movp">
        <h2 id="t-movp" class="seccion-titulo">Movimientos del período</h2>
        <div class="lobulo mt-3 overflow-x-auto">
            <table class="tabla text-sm">
                <thead><tr><th>Fecha</th><th>Código</th><th>Tipo</th><th class="!text-right">Cant.</th><th class="!text-right">Stock</th><th>Motivo</th><th>Usuario</th></tr></thead>
                <tbody>
                @forelse($resultado['movimientos'] as $m)
                    <tr>
                        <td class="cifra whitespace-nowrap">{{ $m->fecha->format('d/m/Y h:i a') }}</td><td><span class="codigo">{{ $m->producto?->codigo_fabrica }}</span></td><td>{{ ucfirst($m->tipo) }}</td>
                        <td class="num">{{ $m->cantidad }}</td><td class="num">{{ $m->stock_anterior }} → {{ $m->stock_nuevo }}</td>
                        <td>{{ $m->motivo }}</td><td class="whitespace-nowrap">{{ $m->usuario?->nombre }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="apoyo py-8 text-center">Sin movimientos en el período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $resultado['movimientos']->links() }}
    </section>
@endif
@endsection
