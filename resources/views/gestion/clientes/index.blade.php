@extends('layouts.app')
@section('titulo', 'Clientes')

@section('contenido')
<x-encabezado titulo="Clientes">
    Busca por nombre, cédula o RIF, o teléfono. Toca un cliente para ver su historial.
    <x-slot:acciones>
        <a href="{{ route('gestion.clientes.create') }}" class="btn btn-sol"><x-icono nombre="mas" class="size-4" /> Nuevo cliente</a>
    </x-slot:acciones>
</x-encabezado>

<form method="GET" action="{{ route('gestion.clientes.index') }}" role="search" class="mb-5 flex max-w-2xl gap-2">
    <label for="q" class="sr-only">Buscar cliente</label>
    <input type="search" id="q" name="q" value="{{ $busqueda }}" class="campo flex-1" placeholder="Ej. Rondón, V-12345678 o 0426…" autofocus>
    <button class="btn btn-tierra"><x-icono nombre="buscar" class="size-4" /> Buscar</button>
    @if($busqueda !== '')<a href="{{ route('gestion.clientes.index') }}" class="btn btn-linea">Limpiar</a>@endif
</form>

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Cliente</th><th>Teléfono</th><th class="!text-right">Cotizaciones</th><th class="!text-right">Compras</th><th class="!text-right">Total comprado</th><th>Última cotización</th><th class="!text-right">Contacto</th></tr></thead>
        <tbody>
        @forelse($clientes as $cliente)
            <tr>
                <td><a href="{{ route('gestion.clientes.show', $cliente) }}" class="font-bold text-tierra hover:underline">{{ $cliente->nombre }}</a><br><span class="apoyo cifra text-sm">{{ $cliente->identificacion }}</span></td>
                <td class="cifra whitespace-nowrap">{{ $cliente->telefono }}</td>
                <td class="num">{{ $cliente->cotizaciones_count }}</td>
                <td class="num">{{ $cliente->compras_count }}</td>
                <td class="num font-bold">@usd($cliente->total_comprado ?? 0)</td>
                <td class="cifra whitespace-nowrap">{{ $cliente->ultima_cotizacion ? \Illuminate\Support\Carbon::parse($cliente->ultima_cotizacion)->format('d/m/Y') : '—' }}</td>
                <td class="text-right whitespace-nowrap">
                    @if($wa = \App\Support\Contacto::whatsappA($cliente->telefono, 'Hola ' . strtok($cliente->nombre, ' ') . ', te escribimos de Inversiones Rialto 7.'))
                        <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-linea btn-mini" aria-label="WhatsApp a {{ $cliente->nombre }}"><x-icono nombre="whatsapp" class="size-4" /></a>
                    @endif
                    <a href="tel:{{ $cliente->telefono }}" class="btn btn-linea btn-mini" aria-label="Llamar a {{ $cliente->nombre }}"><x-icono nombre="telefono" class="size-4" /></a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="apoyo py-10 text-center">{{ $busqueda !== '' ? 'Ningún cliente coincide con «' . $busqueda . '».' : 'Todavía no hay clientes.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $clientes->links() }}
@endsection
