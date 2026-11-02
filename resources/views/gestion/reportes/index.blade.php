@extends('layouts.app')
@section('titulo', 'Reportes')

@section('contenido')
<x-encabezado titulo="Reportes">Lo que ves en pantalla es exactamente lo que se exporta a PDF y Excel.</x-encabezado>

@php
    $tipo = request('tipo', 'ventas');
    // Períodos de un toque: llenan «Desde» y «Hasta» y quedan marcados; el reporte se genera con «Ver en pantalla»
    $hoy = now();
    $periodos = [
        'Hoy' => [$hoy->toDateString(), $hoy->toDateString()],
        'Esta semana' => [$hoy->copy()->startOfWeek()->toDateString(), $hoy->toDateString()],
        'Este mes' => [$hoy->copy()->startOfMonth()->toDateString(), $hoy->toDateString()],
        'Mes pasado' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        'Este año' => [$hoy->copy()->startOfYear()->toDateString(), $hoy->toDateString()],
    ];
    $desde = request('desde', $hoy->copy()->startOfMonth()->toDateString());
    $hasta = request('hasta', $hoy->toDateString());
    // Dos períodos pueden coincidir (el día 1, «Hoy» y «Este mes»): al abrir se marca solo el más amplio
    $periodoElegido = collect($periodos)->reverse()->search(fn ($p) => $p === [$desde, $hasta]) ?: null;
@endphp
{{-- Tres pasos en una sola tarjeta: qué reporte y de cuándo; afinar si hace falta; ver o descargar --}}
<form method="GET" action="{{ route('gestion.reportes.index') }}" class="lobulo overflow-hidden needs-validation" novalidate id="form-reporte"
      x-data="{ tipo: @js($tipo), desde: @js($desde), hasta: @js($hasta), elegido: @js($periodoElegido),
                periodo(texto, d, h) { this.elegido = texto; this.desde = d; this.hasta = h; } }">
    <div class="flex flex-wrap items-end gap-x-8 gap-y-4 p-5 lg:px-6">
        <fieldset>
            <legend class="etiqueta">Reporte de</legend>
            <div class="segmentos">
                @foreach(['ventas' => 'Ventas', 'inventario' => 'Inventario'] as $valor => $texto)
                    <label class="segmento cursor-pointer has-[:checked]:bg-white has-[:checked]:text-tierra-oscura has-[:checked]:shadow-[0_1px_2px_rgb(5_43_112/0.12),0_4px_12px_-6px_rgb(5_43_112/0.35)] has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-tierra">
                        <input type="radio" name="tipo" value="{{ $valor }}" x-model="tipo" class="sr-only" required @checked($tipo === $valor)> {{ $texto }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <fieldset class="min-w-0 max-w-full">
            <legend class="etiqueta">Período</legend>
            <div class="sin-barra overflow-x-auto">
                <div class="segmentos">
                    @foreach($periodos as $texto => [$d, $h])
                        <button type="button" class="segmento" @click="periodo(@js($texto), @js($d), @js($h))" :aria-current="elegido === @js($texto) ? 'true' : null">{{ $texto }}</button>
                    @endforeach
                </div>
            </div>
        </fieldset>
    </div>

    <div class="grid gap-4 border-t border-linea p-5 sm:grid-cols-2 lg:px-6 xl:flex">
        <div class="grid grid-cols-2 gap-2 sm:col-span-2 xl:w-[23rem] xl:shrink-0">
            <div>
                <label for="desde" class="etiqueta">Desde *</label>
                <input type="date" id="desde" name="desde" class="campo cifra max-sm:!px-2 max-sm:!text-[0.9375rem]" required max="{{ now()->toDateString() }}"
                       value="{{ $desde }}" x-model="desde" @input="elegido = null">
            </div>
            <div>
                <label for="hasta" class="etiqueta">Hasta *</label>
                <input type="date" id="hasta" name="hasta" class="campo cifra max-sm:!px-2 max-sm:!text-[0.9375rem]" required :min="desde"
                       value="{{ $hasta }}" x-model="hasta" @input="elegido = null">
            </div>
        </div>
        <div class="min-w-0 xl:flex-1">
            <label for="id_producto" class="etiqueta">Producto</label>
            <select id="id_producto" name="id_producto" class="campo">
                <option value="">Todos</option>
                @foreach($productos as $p)
                    <option value="{{ $p->id }}" @selected(request('id_producto') == $p->id)>{{ $p->codigo_fabrica }} — {{ $p->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-0 xl:flex-1">
            <label for="id_vendedor" class="etiqueta" id="etiqueta-vendedor" x-text="tipo === 'ventas' ? 'Vendedor' : 'Usuario responsable'">{{ $tipo === 'ventas' ? 'Vendedor' : 'Usuario responsable' }}</label>
            <select id="id_vendedor" name="id_vendedor" class="campo">
                <option value="">Todos</option>
                @foreach($vendedores as $v)
                    <option value="{{ $v->id }}" @selected(request('id_vendedor') == $v->id)>{{ $v->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div id="campo-categoria" class="min-w-0 xl:flex-1" x-show="tipo === 'inventario'" @if($tipo === 'ventas') x-cloak @endif>
            <label for="id_categoria" class="etiqueta">Categoría</label>
            <select id="id_categoria" name="id_categoria" class="campo">
                <option value="">Todas</option>
                @foreach($categorias as $c)
                    <option value="{{ $c->id }}" @selected(request('id_categoria') == $c->id)>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-linea bg-agua px-5 py-4 lg:px-6">
        <button class="btn btn-sol"><x-icono nombre="tabla" class="size-4" /> Ver en pantalla</button>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-tinta-suave">Descargar</span>
            <button class="btn btn-linea btn-mini" formaction="{{ route('gestion.reportes.exportar') }}" name="formato" value="pdf" aria-label="Descargar PDF"><x-icono nombre="pdf" class="size-4" /> PDF</button>
            <button class="btn btn-linea btn-mini" formaction="{{ route('gestion.reportes.exportar') }}" name="formato" value="excel" aria-label="Descargar Excel"><x-icono nombre="excel" class="size-4" /> Excel</button>
        </div>
    </div>
</form>

@if($resultado && $resultado['tipo'] === 'ventas')
    @php($r = $resultado['resumen'])
    <section aria-label="Resumen de ventas" class="lobulo-tierra mt-6 grid overflow-hidden sm:grid-cols-2 xl:grid-cols-4">
        <div class="border-white/15 p-5 sm:border-r"><p class="text-sm font-bold text-white/75">Pedidos confirmados</p><p class="titular mt-2 text-[2rem] cifra">{{ $r['pedidos'] }}</p></div>
        <div class="border-t border-white/15 p-5 sm:border-t-0 xl:border-r"><p class="text-sm font-bold text-white/75">Unidades vendidas</p><p class="titular mt-2 text-[2rem] cifra">{{ number_format($r['unidades'], 0, ',', '.') }}</p></div>
        <div class="border-t border-white/15 p-5 sm:border-r xl:border-t-0"><p class="text-sm font-bold text-white/75">Impuestos</p><p class="titular mt-2 text-[2rem] cifra">@usd($r['facturado']['impuesto'])</p><p class="mt-1 text-sm text-white/75 cifra">IVA @usd($r['facturado']['iva']) · IGTF @usd($r['facturado']['igtf'])</p></div>
        <div class="border-t border-white/15 bg-tierra-clara p-5 text-tierra-oscura xl:border-t-0"><p class="text-sm font-bold">Total facturado</p><p class="titular mt-2 text-[2rem] cifra">@usd($r['facturado']['total'])</p><p class="mt-1 text-sm cifra">@bs($r['facturado']['total_bs'])</p></div>
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
