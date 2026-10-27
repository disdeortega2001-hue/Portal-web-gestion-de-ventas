@extends('layouts.app')
@section('titulo', 'Mi cotización')

@section('cabecera')
    <h1 class="titular text-4xl sm:text-5xl">Mi cotización</h1>
    <p class="mt-2 text-white/80">Revisa las cantidades y envíala: un vendedor confirma la existencia y te llama.</p>
@endsection

@section('contenido')
@if($lineas->isEmpty())
    <div class="relative overflow-hidden rounded-lobulo bg-agua-honda px-6 py-12 sm:px-12">
        <svg class="absolute -bottom-12 -right-8 size-64 text-sabana/40" viewBox="0 0 200 200" aria-hidden="true"><path fill="currentColor" d="M44 30C70 4 120 10 150 30s46 70 26 104-70 60-110 44S-4 116 6 80s12-34 38-50Z"/></svg>
        <h2 class="titular relative text-3xl">Tu cotización está vacía</h2>
        <p class="relative mt-3 max-w-lg text-tinta-suave">Busca el repuesto por su código o por el modelo de tu maquinaria y tócalo en «Agregar».</p>
        <a href="{{ route('catalogo.index') }}" class="btn btn-tierra relative mt-6">Ir al catálogo <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
    </div>
@else
<div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:gap-10">
    <div>
        <ul class="lobulo divide-y divide-linea">
            @foreach($resumen['lineas'] as $linea)
                @php($producto = $linea['producto'])
                <li class="p-4 sm:p-5" id="linea-{{ $producto->id }}">
                    <div class="flex justify-between gap-4">
                        <div class="min-w-0">
                            <span class="codigo">{{ $producto->codigo_fabrica }}</span>
                            <div class="mt-0.5 text-[1.0625rem] font-bold">{{ $producto->nombre }}</div>
                            <div class="apoyo text-sm cifra">@usd($linea['precio_unitario']) c/u · hay {{ $producto->stock }}</div>
                        </div>
                        <div class="text-lg font-extrabold whitespace-nowrap cifra">@usd($linea['subtotal'])</div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        {{-- Cantidad: − / + de un toque, o se escribe el número (útil para 40 discos) --}}
                        <form method="POST" action="{{ route('carrito.actualizar', $producto) }}" class="flex h-12 items-center rounded-full border-[1.5px] border-linea bg-white p-1" data-cantidad>
                            @csrf @method('PATCH')
                            {{-- Botón por defecto (Enter) sin ajuste: así Enter no resta una unidad --}}
                            <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">Actualizar</button>
                            <button type="submit" name="ajuste" value="-1" class="grid size-10 place-items-center rounded-full hover:bg-agua" aria-label="{{ $linea['cantidad'] === 1 ? 'Quitar' : 'Una unidad menos de' }} {{ $producto->nombre }}">
                                <x-icono :nombre="$linea['cantidad'] === 1 ? 'papelera' : 'menos'" />
                            </button>
                            <label class="sr-only" for="cant-{{ $producto->id }}">Cantidad de {{ $producto->nombre }}</label>
                            <input type="number" id="cant-{{ $producto->id }}" name="cantidad" value="{{ $linea['cantidad'] }}" min="1" max="{{ $producto->stock }}" inputmode="numeric"
                                   class="w-14 border-0 bg-transparent text-center text-lg font-bold cifra focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                            <button type="submit" name="ajuste" value="1" class="grid size-10 place-items-center rounded-full hover:bg-agua disabled:opacity-40" aria-label="Una unidad más de {{ $producto->nombre }}" @disabled($linea['cantidad'] >= $producto->stock)>
                                <x-icono nombre="mas" />
                            </button>
                        </form>
                        <span class="apoyo hidden text-sm sm:inline cifra">de {{ $producto->stock }} disponibles</span>
                        @if($linea['cantidad'] > 1)
                            <form method="POST" action="{{ route('carrito.quitar', $producto) }}" class="ml-auto sm:ml-0">
                                @csrf @method('DELETE')
                                <button class="enlace-peligro text-sm" data-cargando="Quitando…">Quitar</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        {{-- Acción destructiva aislada, lejos de «Enviar» --}}
        <form method="POST" action="{{ route('carrito.vaciar') }}" class="mt-6" data-confirmar="¿Quitar todos los productos de tu cotización?">
            @csrf @method('DELETE')
            <button class="enlace-peligro text-sm" data-cargando="Vaciando…">Vaciar cotización</button>
        </form>
    </div>

    <div class="space-y-5 lg:sticky lg:top-6 lg:self-start">
        @include('partials.resumen', ['r' => $resumen])
        <p class="apoyo text-sm">Montos calculados por el sistema. Los precios quedan fijos cuando envías la cotización.</p>

        {{-- Formulario aparte (no se pueden anidar formularios): lo envía el botón "Borrar mis datos" --}}
        @if($recordado)
            <form id="olvidar-datos" method="POST" action="{{ route('cotizaciones.olvidar-datos') }}" data-confirmar="¿Borrar tus datos de este teléfono?">
                @csrf @method('DELETE')
            </form>
        @endif

        <form method="POST" action="{{ route('cotizaciones.store') }}" class="needs-validation space-y-5" novalidate>
            @csrf
            @if($cliente)
                <div class="lobulo p-4">
                    <div class="font-bold">{{ $cliente->nombre }}</div>
                    <div class="apoyo text-sm">{{ $cliente->identificacion }} · {{ $cliente->telefono }}</div>
                </div>
            @else
                <div>
                    <h2 class="seccion-titulo">Tus datos</h2>
                    @if($recordado)
                        <p class="apoyo mt-1 text-sm">
                            Usamos los datos que guardaste en este teléfono; revísalos antes de enviar.
                            <button type="submit" form="olvidar-datos" class="enlace">¿No eres {{ $recordado['nombre'] ?? 'tú' }}? Borrar mis datos</button>
                        </p>
                    @else
                        <p class="apoyo mt-1 text-sm">¿Ya tienes cuenta? <a href="{{ route('login') }}" class="enlace">Ingresa</a> para no escribirlos de nuevo.</p>
                    @endif
                </div>
                @include('partials.campos-cliente', ['recordado' => $recordado, 'angosto' => true])
                <label class="flex cursor-pointer gap-3">
                    <input type="checkbox" name="recordar" value="1" id="recordar" class="mt-0.5" @checked(old('recordar', $recordado ? '1' : null))>
                    <span><span class="font-semibold">Recordar mis datos en este teléfono</span>
                        <span class="ayuda !mt-0.5 block">Para no escribirlos la próxima vez. No lo marques si el teléfono es prestado.</span></span>
                </label>
            @endif
            <div>
                <label for="observaciones" class="etiqueta">Observaciones (opcional)</label>
                <textarea id="observaciones" name="observaciones" class="campo" rows="2" maxlength="500" placeholder="Ej. necesito entrega en la finca">{{ old('observaciones') }}</textarea>
            </div>
            <button class="btn btn-sol btn-grande w-full" data-cargando="Enviando cotización…">Enviar cotización <span class="chip"><x-icono nombre="enviar" class="size-4" /></span></button>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    // Al escribir una cantidad, se guarda sola al salir del campo o con Enter (sin botón "Actualizar")
    document.querySelectorAll('form[data-cantidad]').forEach(function (form) {
        const campo = form.querySelector('input[name="cantidad"]');
        let enviado = false;   // Enter y luego "change" al perder el foco no deben enviar dos veces
        function enviar() { if (!enviado && campo.checkValidity()) { enviado = true; form.requestSubmit(); } }
        campo.addEventListener('change', enviar);
        campo.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); enviar(); } });
    });
</script>
@endpush
