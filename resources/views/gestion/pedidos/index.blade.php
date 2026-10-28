@extends('layouts.app')
@section('titulo', 'Pedidos')

@section('contenido')
<x-encabezado titulo="Pedidos">
    <x-slot:acciones>
        <a href="{{ route('gestion.pedidos.create') }}" class="btn btn-sol"><x-icono nombre="mas" class="size-4" /> Venta de mostrador</a>
    </x-slot:acciones>
</x-encabezado>
<x-pestanas :opciones="['todos' => 'Todos', 'pendiente' => 'Pendientes', 'confirmado' => 'Confirmados', 'anulado' => 'Anulados']" :actual="$estado" ruta="gestion.pedidos.index" />
<x-buscador :accion="route('gestion.pedidos.index')" :valor="$busqueda" placeholder="Código, cliente, cédula o teléfono" :mantener="['estado' => $estado]" />

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Código</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th><th class="!text-right">Total</th><th>Estado</th><th class="!text-right"><span class="sr-only">Contactar</span></th></tr></thead>
        <tbody>
        @forelse($pedidos as $pedido)
            <tr>
                <td><a href="{{ route('gestion.pedidos.show', $pedido) }}" class="enlace cifra whitespace-nowrap">{{ $pedido->codigo }}</a></td>
                <td class="cifra whitespace-nowrap">{{ $pedido->fecha->format('d/m/Y h:i a') }}</td>
                <td><a href="{{ route('gestion.clientes.show', $pedido->id_cliente) }}" class="font-semibold hover:underline">{{ $pedido->cliente->nombre }}</a><br><span class="apoyo cifra text-sm">{{ $pedido->cliente->identificacion }}</span></td>
                <td>{{ $pedido->vendedor->nombre }}</td>
                <td class="num font-bold">@usd($pedido->total)</td>
                <td><span class="estado estado-{{ $pedido->estado }}">{{ ucfirst($pedido->estado) }}</span></td>
                <td class="text-right">
                    @if($wa = \App\Support\Contacto::whatsappA($pedido->cliente->telefono, 'Hola ' . strtok($pedido->cliente->nombre, ' ') . ', te escribimos de Inversiones Rialto 7 por tu pedido ' . $pedido->codigo . '.'))
                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-linea btn-mini" aria-label="WhatsApp a {{ $pedido->cliente->nombre }} por {{ $pedido->codigo }}"><x-icono nombre="whatsapp" class="size-4" /></a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="apoyo py-10 text-center">{{ $busqueda !== '' ? 'Ningún pedido coincide con «' . $busqueda . '».' : 'No hay pedidos.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $pedidos->links() }}
@endsection
