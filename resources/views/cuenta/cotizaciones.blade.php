@extends('layouts.app')
@section('titulo', 'Mis cotizaciones')

@section('cabecera')
    <h1 class="titular text-4xl sm:text-5xl">Mis cotizaciones</h1>
@endsection

@section('contenido')
@include('cuenta._verificacion')
@if($cotizaciones->isEmpty())
    <div class="rounded-lobulo bg-agua-honda px-6 py-10 sm:px-10">
        <h2 class="titular text-3xl">Aún no tienes cotizaciones</h2>
        <p class="apoyo mt-2">Busca tus repuestos y envía tu primera cotización en un minuto.</p>
        <a href="{{ route('catalogo.index') }}" class="btn btn-tierra mt-5">Explorar el catálogo <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
    </div>
@else
    <ul class="lobulo divide-y divide-linea">
        @foreach($cotizaciones as $cotizacion)
            <li>
                <a href="{{ route('cotizaciones.show', $cotizacion) }}" class="flex flex-wrap items-center justify-between gap-3 p-4 transition-colors hover:bg-agua sm:p-5">
                    <div>
                        <span class="font-extrabold cifra">{{ $cotizacion->codigo }}</span>
                        <span class="apoyo text-sm cifra"> · {{ $cotizacion->fecha->format('d/m/Y') }} · {{ $cotizacion->detalles_count }} productos</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="estado estado-{{ $cotizacion->estado }}">{{ ucfirst($cotizacion->estado) }}</span>
                        <strong class="text-lg cifra">@usd($cotizacion->total)</strong>
                        <x-icono nombre="flecha" class="size-4 text-tinta-suave" />
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
@endif
@if($cotizaciones instanceof \Illuminate\Pagination\LengthAwarePaginator){{ $cotizaciones->links() }}@endif
@endsection
