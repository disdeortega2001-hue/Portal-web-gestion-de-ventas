@extends('layouts.app')
@section('titulo', $cliente->nombre)

@section('contenido')
<x-encabezado :titulo="$cliente->nombre" :volver="route('gestion.clientes.index')" volver-texto="Clientes">
    <span class="cifra">{{ $cliente->identificacion }}</span>
    @if($cliente->usuario) · tiene cuenta en el portal (entra con su cédula) @else · sin cuenta en el portal @endif
    <x-slot:acciones>
        @if($wa = \App\Support\Contacto::whatsappA($cliente->telefono, 'Hola ' . strtok($cliente->nombre, ' ') . ', te escribimos de Inversiones Rialto 7.'))
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-tierra"><x-icono nombre="whatsapp" class="size-4" /> WhatsApp</a>
        @endif
        <a href="tel:{{ $cliente->telefono }}" class="btn btn-linea"><x-icono nombre="telefono" class="size-4" /> Llamar</a>
        <a href="{{ route('gestion.pedidos.create', ['cliente' => $cliente->id]) }}" class="btn btn-sol"><x-icono nombre="mas" class="size-4" /> Nueva venta</a>
    </x-slot:acciones>
</x-encabezado>

<div class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
    <aside class="space-y-4">
        <div class="lobulo panel-tapa p-5 pt-7">
            <h2 class="seccion-titulo">Datos de contacto</h2>
            <dl class="mt-3 space-y-3 text-[0.9375rem]">
                <div><dt class="apoyo text-sm">Teléfono</dt><dd class="cifra font-semibold">{{ $cliente->telefono }}</dd></div>
                <div><dt class="apoyo text-sm">Correo</dt><dd class="font-semibold">{{ $cliente->correo ?: '—' }}</dd></div>
                <div><dt class="apoyo text-sm">Dirección</dt><dd class="font-semibold">{{ $cliente->direccion }}</dd></div>
                <div><dt class="apoyo text-sm">Cliente desde</dt><dd class="cifra font-semibold">{{ $cliente->created_at->format('d/m/Y') }}</dd></div>
            </dl>
            <a href="{{ route('gestion.clientes.edit', $cliente) }}" class="btn btn-linea btn-mini mt-5"><x-icono nombre="lapiz" class="size-4" /> Corregir datos</a>
            @if($cliente->usuario)
                {{-- Para quien olvidó su contraseña y no tiene correo: verificar antes por teléfono que es la persona --}}
                <form method="POST" action="{{ route('gestion.clientes.contrasena', $cliente) }}" class="mt-3"
                      data-confirmar="¿Ya verificaste por teléfono que hablas con {{ $cliente->nombre }}? Se creará una contraseña temporal nueva.">
                    @csrf
                    <button class="enlace text-sm">Crear contraseña temporal</button>
                </form>
            @endif
        </div>
        <dl class="grid grid-cols-2 gap-px border border-linea bg-linea">
            @foreach([['Compras', $resumen['compras']], ['Cotizaciones', $resumen['cotizaciones']], ['Pendientes', $resumen['pendientes']]] as [$etiqueta, $valor])
                <div class="bg-white p-4"><dt class="apoyo text-sm">{{ $etiqueta }}</dt><dd class="font-rotulo text-3xl font-extrabold cifra">{{ $valor }}</dd></div>
            @endforeach
            <div class="bg-tierra-clara p-4"><dt class="text-sm text-tierra">Total comprado</dt><dd class="font-rotulo text-2xl font-extrabold text-tierra cifra">@usd($resumen['total'])</dd></div>
        </dl>
    </aside>

    <div class="space-y-6">
        <section aria-labelledby="t-cot" class="lobulo overflow-x-auto">
            <h2 id="t-cot" class="seccion-titulo px-5 pb-2 pt-5">Cotizaciones</h2>
            <table class="tabla">
                <thead><tr><th>Código</th><th>Fecha</th><th class="!text-right">Ítems</th><th class="!text-right">Total</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse($cotizaciones as $cotizacion)
                    <tr>
                        <td><a href="{{ route('gestion.cotizaciones.show', $cotizacion) }}" class="enlace cifra">{{ $cotizacion->codigo }}</a></td>
                        <td class="cifra whitespace-nowrap">{{ $cotizacion->fecha->format('d/m/Y h:i a') }}</td>
                        <td class="num">{{ $cotizacion->detalles_count }}</td>
                        <td class="num font-bold">@usd($cotizacion->total)</td>
                        <td><span class="estado estado-{{ $cotizacion->estado }}">{{ ucfirst($cotizacion->estado) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="apoyo py-8 text-center">Sin cotizaciones.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
        <section aria-labelledby="t-ped" class="lobulo overflow-x-auto">
            <h2 id="t-ped" class="seccion-titulo px-5 pb-2 pt-5">Pedidos</h2>
            <table class="tabla">
                <thead><tr><th>Código</th><th>Fecha</th><th class="!text-right">Ítems</th><th class="!text-right">Total</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse($pedidos as $pedido)
                    <tr>
                        <td><a href="{{ route('gestion.pedidos.show', $pedido) }}" class="enlace cifra">{{ $pedido->codigo }}</a></td>
                        <td class="cifra whitespace-nowrap">{{ $pedido->fecha->format('d/m/Y h:i a') }}</td>
                        <td class="num">{{ $pedido->detalles_count }}</td>
                        <td class="num font-bold">@usd($pedido->total)</td>
                        <td><span class="estado estado-{{ $pedido->estado }}">{{ ucfirst($pedido->estado) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="apoyo py-8 text-center">Sin pedidos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>
    </div>
</div>
@endsection
