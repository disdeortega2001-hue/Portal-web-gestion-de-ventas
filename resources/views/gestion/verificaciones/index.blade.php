@extends('layouts.app')
@section('titulo', 'Verificar cuentas')

@section('contenido')
<x-encabezado titulo="Verificar cuentas">
    <span class="block max-w-[70ch]">Estas personas crearon una cuenta con una cédula que ya tenía cotizaciones o pedidos.
    <strong class="text-tinta">Llama al teléfono de la ficha</strong> (el que tenemos de antes, no el de la cuenta nueva) y pregunta si creó la cuenta.
    Aprueba solo si confirma: al aprobar, la cuenta verá todo el historial de esa cédula.</span>
</x-encabezado>

@forelse($solicitudes as $cliente)
    <article class="lobulo mb-4 grid gap-6 p-5 md:grid-cols-2 lg:p-6">
        <div>
            <h2 class="text-xl font-extrabold"><span class="cifra">{{ $cliente->identificacion }}</span> · {{ $cliente->nombre }}</h2>
            <p class="mt-2">Llamar a: <a href="tel:{{ $cliente->telefono }}" class="btn btn-sol btn-mini ml-1"><x-icono nombre="telefono" class="size-4" /> <span class="cifra">{{ $cliente->telefono }}</span></a></p>
            <p class="apoyo mt-2 text-sm">{{ $cliente->direccion }}</p>
            <p class="apoyo text-sm cifra">{{ $cliente->cotizaciones_count }} cotizaciones · {{ $cliente->pedidos_count }} pedidos</p>
        </div>
        <div class="rounded-[3px] bg-agua p-4">
            <p class="apoyo text-sm">Cuenta que pide asociarse</p>
            <p class="font-bold">{{ $cliente->solicitante?->nombre }}</p>
            <p class="text-sm">{{ $cliente->solicitante?->correo }} · <span class="cifra">{{ $cliente->solicitud_vinculacion_en?->format('d/m/Y h:i a') }}</span></p>
            <div class="mt-4 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('gestion.verificaciones.aprobar', $cliente) }}"
                      data-confirmar="¿Confirmaste por teléfono con {{ $cliente->telefono }} que {{ $cliente->solicitante?->nombre }} es el dueño de la cédula {{ $cliente->identificacion }}?">
                    @csrf
                    <button class="btn btn-tierra btn-mini" data-cargando="Asociando…">Confirmado: asociar cuenta</button>
                </form>
                <form method="POST" action="{{ route('gestion.verificaciones.rechazar', $cliente) }}"
                      data-confirmar="¿Rechazar la solicitud? La cuenta no verá el historial de {{ $cliente->identificacion }}.">
                    @csrf
                    <button class="btn btn-linea btn-mini !border-coral-tinta !text-coral-tinta hover:!bg-coral-clara">No es la misma persona</button>
                </form>
            </div>
        </div>
    </article>
@empty
    <div class="rounded-lobulo bg-agua-honda px-6 py-10">
        <h2 class="titular text-3xl">No hay cuentas por verificar</h2>
        <p class="apoyo mt-2">Cuando alguien cree una cuenta con una cédula que ya tenía historial, aparecerá aquí.</p>
    </div>
@endforelse
@endsection
