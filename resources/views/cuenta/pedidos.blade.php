@extends('layouts.app')
@section('titulo', 'Mis pedidos')

@section('cabecera')
    <h1 class="titular text-4xl sm:text-5xl">Mis pedidos</h1>
@endsection

@section('contenido')
@include('cuenta._verificacion')
@if($pedidos->isEmpty())
    <div class="rounded-lobulo bg-agua-honda px-6 py-10 sm:px-10">
        <h2 class="titular text-3xl">Aún no tienes pedidos</h2>
        <p class="apoyo mt-2">Aquí aparecen tus compras en el portal y las cotizaciones que un vendedor convierta en pedido.</p>
    </div>
@else
    <ul class="space-y-3">
        @foreach($pedidos as $pedido)
            <li class="lobulo p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><a href="{{ route('cuenta.pedidos.show', $pedido) }}" class="font-extrabold text-tierra cifra hover:underline">{{ $pedido->codigo }}</a> <span class="apoyo text-sm cifra">· {{ $pedido->fecha->format('d/m/Y') }}</span></div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="estado estado-{{ $pedido->estado }}">{{ $pedido->estado === 'confirmado' ? 'Confirmado — listo para retirar' : ucfirst($pedido->estado) }}</span>
                        <span class="estado estado-{{ $pedido->estado_pago }}">{{ $pedido->etiquetaPago() }}</span>
                        <strong class="text-lg cifra">@usd($pedido->total)</strong>
                    </div>
                </div>
                <ul class="apoyo mt-3 space-y-0.5 text-sm">
                    @foreach($pedido->detalles as $detalle)
                        <li class="cifra">{{ $detalle->cantidad }} × {{ $detalle->producto->nombre }} <span class="codigo !text-xs">{{ $detalle->producto->codigo_fabrica }}</span></li>
                    @endforeach
                </ul>
                @if($pedido->aceptaReporteDePago())
                    <a href="{{ route('cuenta.pedidos.show', $pedido) }}" class="btn btn-sol btn-mini mt-3">Reportar pago</a>
                @else
                    <a href="{{ route('cuenta.pedidos.show', $pedido) }}" class="enlace mt-3 inline-block text-sm">Ver detalle</a>
                @endif
            </li>
        @endforeach
    </ul>
@endif
@if($pedidos instanceof \Illuminate\Pagination\LengthAwarePaginator){{ $pedidos->links() }}@endif
@endsection
