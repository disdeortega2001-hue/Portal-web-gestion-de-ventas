@extends('layouts.app')
@section('titulo', 'Pedidos')

@section('contenido')
<x-encabezado titulo="Pedidos">
    <x-slot:acciones>
        <a href="{{ route('gestion.pedidos.create') }}" class="btn btn-sol"><x-icono nombre="mas" class="size-4" /> Pedido de mostrador</a>
    </x-slot:acciones>
</x-encabezado>
<x-pestanas :opciones="['todos' => 'Todos', 'pendiente' => 'Pendientes', 'confirmado' => 'Confirmados', 'anulado' => 'Anulados']" :actual="$estado" ruta="gestion.pedidos.index" />

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Código</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th class="!text-right">Total</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse($pedidos as $pedido)
            <tr>
                <td><a href="{{ route('gestion.pedidos.show', $pedido) }}" class="enlace cifra whitespace-nowrap">{{ $pedido->codigo }}</a></td>
                <td class="cifra whitespace-nowrap">{{ $pedido->fecha->format('d/m/Y h:i a') }}</td>
                <td class="font-semibold">{{ $pedido->cliente->nombre }}</td>
                <td>{{ $pedido->vendedor->nombre }}</td>
                <td class="num font-bold">@usd($pedido->total)</td>
                <td><span class="estado estado-{{ $pedido->estado }}">{{ ucfirst($pedido->estado) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" class="apoyo py-10 text-center">No hay pedidos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $pedidos->links() }}
@endsection
