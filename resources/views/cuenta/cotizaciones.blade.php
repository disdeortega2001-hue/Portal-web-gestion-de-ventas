@extends('layouts.app')
@section('titulo', 'Mis cotizaciones')

@section('cabecera')
    <h1 class="titular text-5xl sm:text-6xl">Mis cotizaciones</h1>
    <p class="mt-3 text-lg text-white/80">¿Necesitas lo mismo otra vez? Usa «Repetir» y la armamos con los precios de hoy.</p>
@endsection

@section('contenido')
@include('cuenta._verificacion')
@if($cotizaciones->isEmpty())
    <div class="lobulo panel-tapa px-6 py-10 sm:px-10">
        <h2 class="titular text-4xl">Aún no tienes cotizaciones</h2>
        <p class="apoyo mt-2">Busca tus repuestos y envía tu primera cotización en un minuto.</p>
        <a href="{{ route('catalogo.index') }}" class="btn btn-tierra mt-5">Explorar el catálogo <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
    </div>
@else
    <ul class="lobulo divide-y divide-linea">
        @foreach($cotizaciones as $cotizacion)
            <li class="flex flex-wrap items-center justify-between gap-3 p-4 sm:p-5">
                <a href="{{ route('cotizaciones.show', $cotizacion) }}" class="min-w-0 no-underline hover:underline">
                    <span class="font-rotulo text-xl font-extrabold uppercase text-tierra cifra">{{ $cotizacion->codigo }}</span>
                    <span class="apoyo text-sm cifra"> · {{ $cotizacion->fecha->format('d/m/Y') }} · {{ $cotizacion->detalles_count }} {{ $cotizacion->detalles_count === 1 ? 'producto' : 'productos' }}</span>
                </a>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="estado estado-{{ $cotizacion->estado }}">{{ ucfirst($cotizacion->estado) }}</span>
                    <strong class="font-rotulo text-xl cifra">@usd($cotizacion->total)</strong>
                    <form method="POST" action="{{ route('cuenta.cotizaciones.repetir', $cotizacion) }}">
                        @csrf
                        <button class="btn btn-linea btn-mini" data-cargando="Agregando…"><x-icono nombre="restaurar" class="size-4" /> Repetir</button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
@endif
@if($cotizaciones instanceof \Illuminate\Pagination\LengthAwarePaginator){{ $cotizaciones->links() }}@endif
@endsection
