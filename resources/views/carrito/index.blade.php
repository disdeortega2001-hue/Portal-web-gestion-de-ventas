@extends('layouts.app')
@section('titulo', 'Mi cotización')

@section('cabecera')
    <h1 class="titular text-5xl sm:text-6xl">Mi cotización</h1>
    {{-- Los tres pasos, con el actual marcado: elegir ya pasó; aquí se revisa y se envía --}}
    <ol class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-2" aria-label="Pasos de la cotización">
        @foreach(['Elegir', 'Revisar', 'Enviar'] as $i => $paso)
            <li class="flex items-center gap-2 {{ $i === 0 ? 'text-white/70' : 'text-white' }}" @if($i === 1) aria-current="step" @endif>
                <span @class(['ruedo size-8 text-sm', '!border-sol !bg-sol' => $i === 1])>@if($i === 0)<x-icono nombre="check" class="size-4" />@else{{ $i + 1 }}@endif</span>
                <span class="font-rotulo text-lg font-bold uppercase tracking-wide">{{ $paso }}</span>
                @unless($loop->last)<span class="h-0.5 w-6 bg-white/40 sm:w-10" aria-hidden="true"></span>@endunless
            </li>
        @endforeach
    </ol>
    <p class="mt-4 text-white/80">Cómprala ahora con tu cuenta, o envíala como cotización sin registrarte: un vendedor confirma la existencia y te escribe.</p>
@endsection

@section('contenido')
@if($lineas->isEmpty())
    <div class="lobulo panel-tapa px-6 py-12 sm:px-12">
        <h2 class="titular text-4xl">Tu cotización está vacía</h2>
        <p class="mt-3 max-w-lg text-tinta-suave">Busca el repuesto por su código o por el modelo de tu maquinaria y tócalo en «Agregar».</p>
        <a href="{{ route('catalogo.index') }}" class="btn btn-tierra mt-6">Ir al catálogo <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
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
                            <span class="font-rotulo text-xl font-extrabold uppercase text-tierra cifra">{{ $producto->codigo_fabrica }}</span>
                            <div class="mt-0.5 text-[1.0625rem] font-semibold">{{ $producto->nombre }}</div>
                            <div class="apoyo text-sm cifra">@usd($linea['precio_unitario']) c/u · hay {{ $producto->stock }}</div>
                        </div>
                        <div class="font-rotulo text-2xl font-extrabold whitespace-nowrap cifra">@usd($linea['subtotal'])</div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        {{-- Cantidad: − / + de un toque, o se escribe el número (útil para 40 discos) --}}
                        <form method="POST" action="{{ route('carrito.actualizar', $producto) }}" class="flex h-12 items-stretch border-[1.5px] border-tinta bg-white" data-cantidad>
                            @csrf @method('PATCH')
                            {{-- Botón por defecto (Enter) sin ajuste: así Enter no resta una unidad --}}
                            <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true">Actualizar</button>
                            <button type="submit" name="ajuste" value="-1" class="grid w-11 place-items-center hover:bg-agua" aria-label="{{ $linea['cantidad'] === 1 ? 'Quitar' : 'Una unidad menos de' }} {{ $producto->nombre }}">
                                <x-icono :nombre="$linea['cantidad'] === 1 ? 'papelera' : 'menos'" />
                            </button>
                            <label class="sr-only" for="cant-{{ $producto->id }}">Cantidad de {{ $producto->nombre }}</label>
                            <input type="number" id="cant-{{ $producto->id }}" name="cantidad" value="{{ $linea['cantidad'] }}" min="1" max="{{ $producto->stock }}" inputmode="numeric"
                                   class="w-14 border-x border-linea bg-transparent text-center font-rotulo text-xl font-extrabold cifra focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                            <button type="submit" name="ajuste" value="1" class="grid w-11 place-items-center hover:bg-agua disabled:opacity-40" aria-label="Una unidad más de {{ $producto->nombre }}" @disabled($linea['cantidad'] >= $producto->stock)>
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

        {{-- Comprar: pide la cuenta en ese momento (ingresar o registrarse) y vuelve a la compra --}}
        @unless(auth()->user()?->esPersonal())
            <div class="lobulo space-y-3 p-4 sm:p-5">
                <a href="{{ route('compra.create') }}" class="btn btn-tierra btn-grande w-full">Comprar ahora <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
                <p class="apoyo text-sm">Pago Móvil, transferencia, Zelle, efectivo o punto de venta.@guest Te pedimos crear tu cuenta (o ingresar) al comprar.@endguest</p>
            </div>
            <p class="flex items-center gap-3 text-sm font-bold uppercase tracking-[0.08em] text-tinta-suave" aria-hidden="true"><span class="h-px flex-1 bg-linea"></span>o pide una cotización<span class="h-px flex-1 bg-linea"></span></p>
        @endunless
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
                <div class="lobulo panel-tapa p-4 pt-6">
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
            <button class="btn btn-linea btn-grande w-full" data-cargando="Enviando cotización…">Enviar cotización <span class="chip"><x-icono nombre="enviar" class="size-4" /></span></button>
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
