@extends('layouts.app')
@section('titulo', 'Cotizaciones')

@section('contenido')
<x-encabezado titulo="Cotizaciones">Las pendientes más antiguas primero: el cliente espera tu llamada.</x-encabezado>
<x-pestanas :opciones="['pendiente' => 'Pendientes', 'convertida' => 'Convertidas', 'rechazada' => 'Rechazadas', 'todas' => 'Todas']" :actual="$estado" ruta="gestion.cotizaciones.index" />

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Código</th><th>Fecha</th><th>Cliente</th><th>Teléfono</th><th class="!text-right">Ítems</th><th class="!text-right">Total</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse($cotizaciones as $cotizacion)
            <tr>
                <td><a href="{{ route('gestion.cotizaciones.show', $cotizacion) }}" class="enlace cifra whitespace-nowrap">{{ $cotizacion->codigo }}</a></td>
                <td class="cifra whitespace-nowrap">{{ $cotizacion->fecha->format('d/m/Y h:i a') }}</td>
                <td><span class="font-semibold">{{ $cotizacion->cliente->nombre }}</span><br><span class="apoyo text-sm cifra">{{ $cotizacion->cliente->identificacion }}</span></td>
                <td><a href="tel:{{ $cotizacion->cliente->telefono }}" class="cifra whitespace-nowrap font-semibold hover:underline">{{ $cotizacion->cliente->telefono }}</a></td>
                <td class="num">{{ $cotizacion->detalles_count }}</td>
                <td class="num font-bold">@usd($cotizacion->total)</td>
                <td><span class="estado estado-{{ $cotizacion->estado }}">{{ ucfirst($cotizacion->estado) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="7" class="apoyo py-10 text-center">No hay cotizaciones en este estado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $cotizaciones->links() }}
@endsection
