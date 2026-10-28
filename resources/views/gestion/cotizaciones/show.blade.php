@extends('layouts.app')
@section('titulo', $cotizacion->codigo)

@section('contenido')
<x-encabezado :titulo="'Cotización ' . $cotizacion->codigo" :volver="route('gestion.cotizaciones.index')" volver-texto="Cotizaciones">
    <span class="cifra">{{ $cotizacion->fecha->format('d/m/Y h:i a') }}</span> · <span class="estado estado-{{ $cotizacion->estado }}">{{ ucfirst($cotizacion->estado) }}</span>
</x-encabezado>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="space-y-6">
        <section class="lobulo p-5" aria-label="Cliente">
            <p class="text-lg font-extrabold">{{ $cotizacion->cliente->nombre }}</p>
            <p class="mt-1 cifra">{{ $cotizacion->cliente->identificacion }} · <a href="tel:{{ $cotizacion->cliente->telefono }}" class="enlace">{{ $cotizacion->cliente->telefono }}</a> @if($cotizacion->cliente->correo)· {{ $cotizacion->cliente->correo }}@endif</p>
            <p class="apoyo">{{ $cotizacion->cliente->direccion }}</p>
            @if($cotizacion->observaciones)<p class="mt-3 rounded-[3px] bg-sol-clara px-4 py-3"><strong>Observaciones:</strong> {!! nl2br(e($cotizacion->observaciones)) !!}</p>@endif
        </section>
        <div class="lobulo overflow-x-auto">
            <table class="tabla">
                <thead><tr><th>Código</th><th>Producto</th><th class="!text-right">Cant.</th><th class="!text-right">Stock actual</th><th class="!text-right">Subtotal</th></tr></thead>
                <tbody>
                @foreach($cotizacion->detalles as $detalle)
                    @php($falta = $detalle->cantidad > $detalle->producto->stock)
                    <tr @class(['!bg-coral-clara' => $falta])>
                        <td><span class="codigo">{{ $detalle->producto->codigo_fabrica }}</span></td>
                        <td>{{ $detalle->producto->nombre }}</td>
                        <td class="num">{{ $detalle->cantidad }}</td>
                        <td class="num">{{ $detalle->producto->stock }} @if($falta)<span class="font-bold text-coral-tinta">(insuficiente)</span>@endif</td>
                        <td class="num font-bold">@usd($detalle->subtotal)</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
        @include('partials.resumen', ['r' => $cotizacion])

        @if($cotizacion->estaPendiente())
            <form method="POST" action="{{ route('gestion.cotizaciones.convertir', $cotizacion) }}">
                @csrf
                <button class="btn btn-sol btn-grande w-full">Convertir en pedido <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></button>
            </form>
            {{-- Rechazar: separado y en contorno, nunca junto a la acción principal --}}
            <form method="POST" action="{{ route('gestion.cotizaciones.rechazar', $cotizacion) }}" class="needs-validation mt-8 border-t border-linea pt-5" novalidate>
                @csrf
                <label for="motivo" class="etiqueta">Motivo del rechazo</label>
                <input type="text" id="motivo" name="motivo" class="campo" required minlength="5" maxlength="255">
                <p class="error-campo">Indica el motivo (mínimo 5 caracteres).</p>
                <button class="btn btn-linea mt-3 w-full !border-coral-tinta !text-coral-tinta hover:!bg-coral-clara">Rechazar cotización</button>
            </form>
        @elseif($cotizacion->pedido)
            <a href="{{ route('gestion.pedidos.show', $cotizacion->pedido) }}" class="btn btn-linea w-full">Ver pedido {{ $cotizacion->pedido->codigo }}</a>
        @endif
    </div>
</div>
@endsection
