@extends('layouts.app')
@section('titulo', $pedido->codigo)

@section('contenido')
<x-encabezado :titulo="'Pedido ' . $pedido->codigo" :volver="route('gestion.pedidos.index')" volver-texto="Pedidos">
    <span class="cifra">{{ $pedido->fecha->format('d/m/Y h:i a') }}</span> · Vendedor: {{ $pedido->vendedor->nombre }}
    @if($pedido->cotizacion) · Desde <span class="cifra">{{ $pedido->cotizacion->codigo }}</span> @endif
    · <span class="estado estado-{{ $pedido->estado }}">{{ ucfirst($pedido->estado) }}</span>
</x-encabezado>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="space-y-6">
        <section class="lobulo p-5" aria-label="Cliente">
            <p class="text-lg font-extrabold">{{ $pedido->cliente->nombre }}</p>
            <p class="mt-1 cifra">{{ $pedido->cliente->identificacion }} · {{ $pedido->cliente->telefono }}</p>
            <p class="apoyo">{{ $pedido->cliente->direccion }}</p>
        </section>
        <div class="lobulo overflow-x-auto">
            <table class="tabla">
                <thead><tr><th>Código</th><th>Producto</th><th class="!text-right">Cant.</th><th class="!text-right">Precio</th><th class="!text-right">Subtotal</th>@if($pedido->estaPendiente())<th class="!text-right">Stock</th>@endif</tr></thead>
                <tbody>
                @foreach($pedido->detalles as $detalle)
                    <tr @class(['!bg-coral-clara' => $pedido->estaPendiente() && $detalle->cantidad > $detalle->producto->stock])>
                        <td><span class="codigo">{{ $detalle->producto->codigo_fabrica }}</span></td>
                        <td>{{ $detalle->producto->nombre }}</td>
                        <td class="num">{{ $detalle->cantidad }}</td>
                        <td class="num">@usd($detalle->precio_unitario)</td>
                        <td class="num font-bold">@usd($detalle->subtotal)</td>
                        @if($pedido->estaPendiente())<td class="num">{{ $detalle->producto->stock }}</td>@endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($pedido->movimientos->isNotEmpty())
            <section aria-labelledby="t-mov">
                <h2 id="t-mov" class="seccion-titulo">Movimientos de inventario</h2>
                <ul class="mt-3 space-y-2">
                    @foreach($pedido->movimientos as $mov)
                        <li class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-[3px] bg-agua-honda px-4 py-2.5 text-sm">
                            <span class="cifra">{{ $mov->fecha->format('d/m/Y h:i a') }}</span>
                            <span class="font-bold">{{ ucfirst($mov->tipo) }} de {{ $mov->cantidad }}</span>
                            <span class="cifra">{{ $mov->stock_anterior }} → {{ $mov->stock_nuevo }}</span>
                            <span class="apoyo">{{ $mov->usuario->nombre }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
        @include('partials.resumen', ['r' => $pedido])

        @if($pedido->estaPendiente())
            <form method="POST" action="{{ route('gestion.pedidos.confirmar', $pedido) }}"
                  data-confirmar="¿Confirmar la venta? Se descontará el stock de inmediato.">
                @csrf
                <button class="btn btn-sol btn-grande w-full">Confirmar venta <span class="chip"><x-icono nombre="check" class="size-4" /></span></button>
            </form>
        @endif
        {{-- Anular: aislado y en contorno, lejos de «Confirmar venta» --}}
        @if($pedido->estado !== 'anulado')
            <form method="POST" action="{{ route('gestion.pedidos.anular', $pedido) }}" class="border-t border-linea pt-5 !mt-8"
                  data-confirmar="¿Anular el pedido {{ $pedido->codigo }}?{{ $pedido->estado === 'confirmado' ? ' El stock será repuesto.' : '' }}">
                @csrf
                <button class="btn btn-linea w-full !border-coral-tinta !text-coral-tinta hover:!bg-coral-clara">Anular pedido</button>
            </form>
        @endif
    </div>
</div>
@endsection
