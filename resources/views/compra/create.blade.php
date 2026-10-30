@extends('layouts.app')
@section('titulo', 'Finalizar compra')

@section('cabecera')
    <h1 class="titular text-5xl sm:text-6xl">Finalizar <em>compra</em></h1>
    <p class="mt-3 max-w-2xl text-lg text-white/80">Elige cómo pagas y cómo recibes. Tu pedido queda reservado a tu nombre y lo confirmamos al verificar el pago.</p>
@endsection

@section('contenido')
<form method="POST" action="{{ route('compra.store') }}" class="needs-validation grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:gap-10" novalidate
      x-data="{ metodo: @js(old('metodo_pago', array_key_first($metodos))) }">
    @csrf
    <div class="space-y-8">
        @error('carrito')<div class="aviso aviso-error" role="alert"><x-icono nombre="alerta" class="size-5 shrink-0" /> {{ $message }}</div>@enderror

        {{-- Quién compra --}}
        <section aria-labelledby="t-cliente">
            <h2 id="t-cliente" class="seccion-titulo">Tus datos</h2>
            @if($cliente)
                <div class="lobulo mt-3 flex flex-wrap items-center justify-between gap-3 p-4 sm:p-5">
                    <div>
                        <p class="font-bold">{{ $cliente->nombre }}</p>
                        <p class="apoyo text-sm cifra">{{ $cliente->identificacion }} · {{ $cliente->telefono }}</p>
                        <p class="apoyo text-sm">{{ $cliente->direccion }}</p>
                    </div>
                </div>
            @else
                {{-- Sin cuenta: solo lo necesario para entregar y confirmar el pago --}}
                <p class="apoyo mt-1 text-sm">No necesitas crear cuenta. ¿Ya tienes una? <a href="{{ route('login') }}" class="enlace">Ingresa</a> para no escribirlos.</p>
                <div class="lobulo mt-3 space-y-4 p-4 sm:p-5">
                    @include('partials.campos-cliente', ['recordado' => $recordado, 'sinCorreo' => true])
                    <label class="flex cursor-pointer gap-3">
                        <input type="checkbox" name="recordar" value="1" class="mt-0.5" @checked(old('recordar', $recordado ? '1' : null))>
                        <span><span class="font-semibold">Recordar mis datos en este teléfono</span>
                            <span class="ayuda !mt-0.5 block">Para no escribirlos la próxima vez. No lo marques si el teléfono es prestado.</span></span>
                    </label>
                </div>
            @endif
        </section>

        {{-- Cómo paga --}}
        <fieldset>
            <legend class="seccion-titulo">¿Cómo vas a pagar?</legend>
            @error('metodo_pago')<p class="error-campo !block">{{ $message }}</p>@enderror
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach($metodos as $clave => $metodo)
                    <label class="lobulo tarjeta-viva flex cursor-pointer gap-3 p-4 has-[:checked]:border-tierra has-[:checked]:shadow-[0_0_0_1px_var(--color-tierra)]">
                        <input type="radio" name="metodo_pago" value="{{ $clave }}" x-model="metodo" class="mt-1" required>
                        <span>
                            <span class="block font-extrabold uppercase">{{ $metodo['nombre'] }}</span>
                            <span class="apoyo block text-sm">{{ $metodo['descripcion'] }}</span>
                            @php($r = $resumenes[$metodo['moneda']])
                            <span class="mt-2 block text-sm font-bold text-tierra cifra">
                                {{ $metodo['moneda'] === 'USD' ? 'Pagas USD ' . number_format((float) $r['total'], 2, ',', '.') . ' (con IGTF)' : 'Pagas Bs. ' . number_format((float) $r['total_bs'], 2, ',', '.') . ' (con IVA)' }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
            {{-- Crédito: solo para clientes autorizados por la empresa, dentro de su límite --}}
            @if($credito)
                <div class="mt-5">
                    <p class="flex flex-wrap items-baseline justify-between gap-2 text-sm font-extrabold uppercase tracking-[0.06em] text-tierra-oscura">
                        Tu crédito con Rialto 7
                        <span class="apoyo font-semibold normal-case tracking-normal cifra">
                            @if($credito['disponible'] === null) Sin límite@else Disponible: USD {{ number_format($credito['disponible'], 2, ',', '.') }}@endif
                            @if($credito['deuda'] > 0) · debes USD {{ number_format($credito['deuda'], 2, ',', '.') }}@endif
                        </span>
                    </p>
                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                        @foreach($credito['opciones'] as $clave => $opcion)
                            @php($r = $resumenes[$opcion['moneda']])
                            <label @class(['lobulo tarjeta-viva flex gap-3 p-4 has-[:checked]:border-tierra has-[:checked]:shadow-[0_0_0_1px_var(--color-tierra)]', 'cursor-pointer' => $opcion['alcanza'], 'opacity-60' => ! $opcion['alcanza']])>
                                <input type="radio" name="metodo_pago" value="{{ $clave }}" x-model="metodo" class="mt-1" required @disabled(! $opcion['alcanza'])>
                                <span>
                                    <span class="block font-extrabold uppercase">{{ $opcion['nombre'] }}</span>
                                    <span class="apoyo block text-sm">{{ $opcion['descripcion'] }}</span>
                                    <span class="mt-2 block text-sm font-bold text-tierra cifra">
                                        {{ $opcion['moneda'] === 'USD' ? 'Debes USD ' . number_format((float) $r['total'], 2, ',', '.') . ' (con IGTF)' : 'Debes Bs. ' . number_format((float) $r['total_bs'], 2, ',', '.') . ' (con IVA)' }}
                                    </span>
                                    @unless($opcion['alcanza'])<span class="mt-1 block text-sm font-semibold text-coral-tinta">Pasa de tu crédito disponible.</span>@endunless
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
            {{-- Qué pasa después, según el método elegido --}}
            <div class="aviso aviso-info mt-4" aria-live="polite">
                <x-icono nombre="info" class="size-5 shrink-0" />
                @if($credito)
                    <p x-show="metodo.startsWith('credito')" x-cloak>
                        Confirmamos tu pedido y te avisamos para retirarlo. Abonas cuando quieras, desde tu pedido o en la tienda, y cada 30 días te recordamos el saldo.
                    </p>
                @endif
                <p x-show="@js(collect($metodos)->filter(fn ($m) => $m['en_linea'])->keys()->values()).includes(metodo)">
                    Al confirmar te mostramos los datos para pagar. Luego reportas la referencia y el comprobante desde tu pedido; lo verificamos y te avisamos por teléfono.
                </p>
                <p x-show="! metodo.startsWith('credito') && ! @js(collect($metodos)->filter(fn ($m) => $m['en_linea'])->keys()->values()).includes(metodo)" x-cloak>
                    Pagas al retirar en el Edificio Campagna. Te escribimos por WhatsApp cuando tu pedido esté listo.
                </p>
            </div>
        </fieldset>

        {{-- Cómo recibe --}}
        <fieldset>
            <legend class="seccion-titulo">¿Cómo lo recibes?</legend>
            @error('entrega')<p class="error-campo !block">{{ $message }}</p>@enderror
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach(['retiro' => ['Retiro en la tienda', 'Av. Las Industrias, Edificio Campagna, Valle de la Pascua.'], 'envio' => ['Envío a coordinar', 'Te contactamos para acordar el transporte y su costo.']] as $valor => [$titulo, $texto])
                    <label class="lobulo flex cursor-pointer gap-3 p-4 has-[:checked]:border-tierra has-[:checked]:shadow-[0_0_0_1px_var(--color-tierra)]">
                        <input type="radio" name="entrega" value="{{ $valor }}" class="mt-1" required @checked(old('entrega', 'retiro') === $valor)>
                        <span><span class="block font-extrabold uppercase">{{ $titulo }}</span><span class="apoyo block text-sm">{{ $texto }}</span></span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div>
            <label for="observaciones" class="etiqueta">Observaciones (opcional)</label>
            <textarea id="observaciones" name="observaciones" class="campo" rows="2" maxlength="500" placeholder="Ej. retiro el sábado en la mañana">{{ old('observaciones') }}</textarea>
        </div>
    </div>

    <div class="space-y-5 lg:sticky lg:top-24 lg:self-start">
        <section class="lobulo p-4 sm:p-5" aria-labelledby="t-productos">
            <h2 id="t-productos" class="seccion-titulo">Tu pedido</h2>
            <ul class="mt-3 divide-y divide-linea">
                @foreach($resumenes['USD']['lineas'] as $linea)
                    <li class="flex justify-between gap-3 py-2 text-sm">
                        <span><span class="font-bold cifra">{{ $linea['cantidad'] }} ×</span> {{ $linea['producto']->nombre }}</span>
                        <span class="whitespace-nowrap font-bold cifra">@usd($linea['subtotal'])</span>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('carrito.index') }}" class="enlace mt-2 inline-block text-sm">Cambiar cantidades</a>
        </section>
        {{-- El resumen sigue al método elegido: IGTF si paga en dólares, IVA si paga en bolívares --}}
        @php($monedas = collect($metodos)->map(fn ($m) => $m['moneda'])->merge(collect(\App\Support\Credito::OPCIONES)->map(fn ($m) => $m['moneda'])))
        <div x-show="@js($monedas)[metodo] !== 'VES'">@include('partials.resumen', ['r' => $resumenes['USD']])</div>
        <div x-show="@js($monedas)[metodo] === 'VES'" x-cloak>@include('partials.resumen', ['r' => $resumenes['VES']])</div>
        <button class="btn btn-sol btn-grande w-full" data-cargando="Registrando tu pedido…">Confirmar compra <span class="chip"><x-icono nombre="check" class="size-4" /></span></button>
        <p class="apoyo text-sm">Al confirmar aceptas los <a href="{{ route('legal.terminos') }}" class="enlace">términos de venta</a>. El precio queda fijo en tu pedido.</p>
    </div>
</form>
@endsection
