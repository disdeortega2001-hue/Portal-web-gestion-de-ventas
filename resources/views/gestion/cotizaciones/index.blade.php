@extends('layouts.app')
@section('titulo', 'Cotizaciones')

@section('contenido')
<x-encabezado titulo="Cotizaciones">Las pendientes más antiguas primero: el cliente espera tu llamada.</x-encabezado>
<x-pestanas :opciones="['pendiente' => 'Pendientes', 'convertida' => 'Convertidas', 'rechazada' => 'Rechazadas', 'todas' => 'Todas']" :actual="$estado" ruta="gestion.cotizaciones.index" :conteos="$conteos" :atencion="['pendiente']" />
<x-buscador :accion="route('gestion.cotizaciones.index')" :valor="$busqueda" placeholder="Código, cliente, cédula o teléfono" :mantener="['estado' => $estado]" />

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Código</th><th>Fecha</th><th>Cliente</th><th>Teléfono</th><th class="!text-right">Ítems</th><th class="!text-right">Total</th><th>Estado</th><th class="!text-right"><span class="sr-only">Contactar</span></th></tr></thead>
        <tbody>
        @forelse($cotizaciones as $cotizacion)
            <tr>
                <td><a href="{{ route('gestion.cotizaciones.show', $cotizacion) }}" class="enlace cifra whitespace-nowrap">{{ $cotizacion->codigo }}</a></td>
                <td class="cifra whitespace-nowrap">{{ $cotizacion->fecha->format('d/m/Y h:i a') }}</td>
                <td><a href="{{ route('gestion.clientes.show', $cotizacion->id_cliente) }}" class="font-semibold hover:underline">{{ $cotizacion->cliente->nombre }}</a><br><span class="apoyo text-sm cifra">{{ $cotizacion->cliente->identificacion }}</span></td>
                <td><a href="tel:{{ $cotizacion->cliente->telefono }}" class="cifra whitespace-nowrap font-semibold hover:underline">{{ $cotizacion->cliente->telefono }}</a></td>
                <td class="num">{{ $cotizacion->detalles_count }}</td>
                <td class="num font-bold">@usd($cotizacion->total)</td>
                <td><span class="estado estado-{{ $cotizacion->estado }}">{{ ucfirst($cotizacion->estado) }}</span></td>
                <td class="text-right">
                    @if($wa = \App\Support\Contacto::whatsappA($cotizacion->cliente->telefono, 'Hola ' . strtok($cotizacion->cliente->nombre, ' ') . ', te escribimos de Inversiones Rialto 7 por tu cotización ' . $cotizacion->codigo . '.'))
                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-linea btn-mini" aria-label="WhatsApp a {{ $cotizacion->cliente->nombre }} por {{ $cotizacion->codigo }}"><x-icono nombre="whatsapp" class="size-4" /></a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="apoyo py-10 text-center">{{ $busqueda !== '' ? 'Ninguna cotización coincide con «' . $busqueda . '».' : 'No hay cotizaciones en este estado.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $cotizaciones->links() }}
@endsection
