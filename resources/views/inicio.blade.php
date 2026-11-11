@extends('layouts.app')
@section('titulo', 'Repuestos y maquinaria agrícola')
@section('ancho_completo', true)

@php
    $whatsapp = \App\Support\Contacto::whatsapp('Hola, quiero consultar un repuesto.');
    // Portada: primero la sede real; después fotos de campo libres (Unsplash) hasta tener más fotos propias
    $diapositivas = [
        ['img/portada/sede-fachada.jpg', 'img/portada/sede-fachada-movil.jpg', 'Nuestra sede: Edificio Campagna, Valle de la Pascua'],
        ['img/portada/campo-1.webp', 'img/portada/campo-1-movil.webp', null],
        ['img/portada/campo-2.webp', 'img/portada/campo-2-movil.webp', null],
        ['img/portada/campo-3.webp', 'img/portada/campo-3-movil.webp', null],
        ['img/portada/campo-4.webp', 'img/portada/campo-4-movil.webp', null],
    ];
    $logoMarca = fn ($m) => $m['nombre'] === 'Marchesan Tatu' ? 'bg-[#173C8A] object-contain' : 'bg-white object-cover';
@endphp

@section('contenido')
{{-- ======== Portada: fotos a sangre bajo el azul; el buscador es una placa de fábrica remachada ======== --}}
<section id="portada" aria-labelledby="t-portada" class="portada sobre-azul bajo-barra relative isolate overflow-hidden bg-noche text-white"
         x-data="portada({{ count($diapositivas) }})">
    <div class="absolute inset-0 -z-10" aria-hidden="true">
        @foreach($diapositivas as $i => [$grande, $movil, $leyenda])
            <picture class="diapositiva absolute inset-0" :class="actual === {{ $i }} && 'activa'" @if($i === 0) data-primera @endif>
                <source media="(max-width: 767px)" srcset="{{ asset($movil) }}">
                <img src="{{ asset($grande) }}" alt="" class="size-full object-cover" @if($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
            </picture>
        @endforeach
        <div class="absolute inset-0 bg-noche/55 lg:bg-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-noche/95 via-tierra-oscura/75 to-tierra/25"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-noche/90 via-noche/10 to-transparent"></div>
        <x-arcos class="absolute inset-y-0 right-0 hidden h-full w-[48rem] lg:block" />
    </div>
    {{-- Sin JavaScript se ve la primera foto --}}
    <noscript><style>[data-primera]{opacity:1;transform:none}</style></noscript>

    <div class="mx-auto grid w-full max-w-[1320px] gap-10 px-4 pb-20 pt-12 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end lg:px-8 lg:pb-28 lg:pt-20">
        <div class="max-w-2xl">
            <p class="flex items-center gap-3 text-xs font-bold uppercase tracking-[0.18em] text-white/80" data-entrar style="--orden:0">
                <span class="h-0.5 w-8 bg-sol" aria-hidden="true"></span> Valle de la Pascua · Guárico
            </p>
            <h1 id="t-portada" class="titular mt-5 text-[2.15rem] sm:text-5xl lg:text-[3.5rem]" data-entrar style="--orden:1">El campo no se detiene, <em>nosotros tampoco</em></h1>
            <p class="mt-5 max-w-xl text-lg text-white/85" data-entrar style="--orden:2">Desde 1963 llevamos maquinaria y repuestos al productor guariqueño, temporada tras temporada.</p>

            {{-- Placa de fábrica: el buscador, con sus cuatro remaches --}}
            <div class="placa mt-9 max-w-xl" data-entrar style="--orden:3">
                <form method="GET" action="{{ route('catalogo.index') }}" role="search">
                    <div class="flex items-baseline justify-between gap-3">
                        <label for="q-inicio" class="text-sm font-black uppercase tracking-[0.08em] text-tierra-oscura">Busca tu repuesto</label>
                        <span class="hidden text-xs font-semibold text-tinta-suave sm:block">Código de fábrica, pieza o modelo</span>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <input type="search" id="q-inicio" name="q" placeholder="Código, pieza o modelo" maxlength="100" enterkeyhint="search" autocomplete="off"
                               class="campo !min-h-14 flex-1 !text-lg">
                        <button class="btn btn-sol btn-grande !min-h-14" type="submit"><x-icono nombre="buscar" class="size-5" /><span class="sr-only sm:not-sr-only">Buscar</span></button>
                    </div>
                </form>
                <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-dashed border-linea pt-4 text-sm font-bold">
                    <a href="{{ route('catalogo.index') }}" class="enlace inline-flex items-center gap-1.5">Ver el catálogo completo <x-icono nombre="flecha" class="size-4" /></a>
                    @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="enlace inline-flex items-center gap-1.5"><x-icono nombre="whatsapp" class="size-4" /> Pregunta por WhatsApp</a>@endif
                </div>
            </div>
        </div>

        {{-- Selector de fotos: número, avance y leyenda --}}
        <div class="flex flex-wrap items-center gap-4 lg:flex-col lg:items-end" x-cloak>
            <p class="cifra text-sm font-bold text-white/80" aria-hidden="true"><span class="text-2xl font-black text-white" x-text="String(actual + 1).padStart(2, '0')">01</span> / {{ str_pad(count($diapositivas), 2, '0', STR_PAD_LEFT) }}</p>
            <div class="flex gap-1.5">
                @foreach($diapositivas as $i => $d)
                    <button type="button" class="relative h-1.5 w-8 overflow-hidden rounded-full sm:w-10" @click="ir({{ $i }})" aria-label="Ver foto {{ $i + 1 }}" :aria-current="actual === {{ $i }}">
                        <span class="absolute inset-0 rounded-full bg-white/30"></span>
                        <template x-if="actual === {{ $i }}"><span class="punto-progreso absolute inset-0 rounded-full bg-sol" :style="`--duracion:${duracion}ms`"></span></template>
                    </button>
                @endforeach
            </div>
            @foreach($diapositivas as $i => [, , $leyenda])
                @if($leyenda)<p x-show="actual === {{ $i }}" x-transition.opacity class="hidden max-w-xs items-center gap-2 text-sm font-semibold text-white/80 sm:flex"><x-icono nombre="ubicacion" class="size-4 shrink-0" /> {{ $leyenda }}</p>@endif
            @endforeach
        </div>
    </div>
</section>

{{-- ======== Marcas exclusivas ======== --}}
<section id="marcas" aria-labelledby="t-marcas" class="fondo-foto border-b border-linea" style="--foto: url('{{ asset('img/portada/campo-2.webp') }}')">
    <div class="mx-auto grid max-w-[1320px] items-center gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)] lg:px-8">
        <div data-revelar>
            <h2 id="t-marcas" class="titular subrayado text-3xl text-rialto">Agente exclusivo en Venezuela</h2>
            <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-rialto-claro px-3 py-1 text-xs font-extrabold uppercase tracking-[0.1em] text-rialto"><x-icono nombre="check-circulo" class="size-4" /> Distribuidor oficial de las cuatro marcas</p>
        </div>
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
            @foreach($marcas as $k => $marca)
                <li data-revelar style="--orden:{{ $k }}">
                    <a href="{{ route('catalogo.index', ['marca' => $marca['nombre']]) }}" class="lobulo tarjeta-viva block overflow-hidden p-1.5" title="Ver productos de {{ $marca['nombre'] }}">
                        <img src="{{ asset($marca['logo']) }}" alt="{{ $marca['nombre'] }}" class="h-20 w-full rounded-[5px] {{ $logoMarca($marca) }}" loading="lazy">
                        <span class="block px-2 py-2 text-center text-xs font-bold uppercase tracking-[0.06em] text-tinta-suave">{{ $marca['productos'] }} {{ $marca['productos'] === 1 ? 'producto' : 'productos' }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ======== Compra por tipo de equipo: las categorías reales, con la foto de un producto suyo ======== --}}
@if($categorias->isNotEmpty())
    <section aria-labelledby="t-tipos" class="bg-white py-16 lg:py-24">
        <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4" data-revelar>
                <div>
                    <h2 id="t-tipos" class="titular subrayado text-4xl text-rialto sm:text-5xl">Compra por <em>tipo de equipo</em></h2>
                    <p class="mt-3 text-tinta-suave">Entra directo a lo que necesitas para tu maquinaria.</p>
                </div>
            </div>
            <ul class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-5">
                @foreach($categorias as $k => $c)
                    <li data-revelar style="--orden:{{ $k }}">
                        <a href="{{ route('catalogo.index', ['categoria' => $c['categoria']->id]) }}" class="tipo-equipo group block h-full overflow-hidden rounded-[10px] border border-linea bg-agua">
                            <span class="block aspect-[4/3] overflow-hidden bg-white">
                                @if($c['foto'])
                                    <img src="{{ $c['foto']->urlImagen() }}" alt="" class="size-full object-contain p-4 mix-blend-multiply transition-transform duration-500 group-hover:scale-105" loading="lazy">
                                @endif
                            </span>
                            <span class="flex items-end justify-between gap-2 border-t-4 border-rialto-vivo px-4 py-3">
                                <span>
                                    <span class="block font-black uppercase leading-tight tracking-[0.02em] text-rialto">{{ $c['categoria']->nombre }}</span>
                                    <span class="mt-0.5 block text-sm text-tinta-suave">{{ $c['productos'] }} {{ $c['productos'] === 1 ? 'producto' : 'productos' }}</span>
                                </span>
                                <x-icono nombre="flecha" class="size-5 shrink-0 text-tierra transition-transform group-hover:translate-x-1" />
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

{{-- ======== Los más pedidos ======== --}}
@if($destacados->isNotEmpty())
    <section aria-labelledby="t-destacados" class="fondo-foto fondo-foto-fijo py-16 lg:py-24" style="--foto: url('{{ asset('img/portada/campo-3.webp') }}')">
        <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4" data-revelar>
                <div>
                    <h2 id="t-destacados" class="titular subrayado text-4xl text-rialto sm:text-5xl">Los más pedidos</h2>
                    <p class="mt-3 text-tinta-suave">Con existencia hoy. Agrégalos a tu cotización con un toque.</p>
                    @if($tasa > 0)
                        {{-- Los precios en bolívares salen de esta tasa: se muestra con su fecha --}}
                        <p class="mt-3 inline-flex flex-wrap items-center gap-x-2 rounded-[6px] bg-white px-3 py-1.5 text-sm ring-1 ring-linea">
                            <span class="font-extrabold uppercase tracking-[0.06em] text-tierra">Tasa BCV</span>
                            <span class="cifra font-black">@bs($tasa)</span>
                            <span class="text-tinta-suave">por dólar @if($tasaActualizada)· {{ $tasaActualizada->format('d/m/Y') }}@endif</span>
                        </p>
                    @endif
                </div>
                <a href="{{ route('catalogo.index') }}" class="btn btn-linea">Ver todo el catálogo <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
            </div>
            <ul class="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($destacados as $k => $producto)
                    <li data-revelar style="--orden:{{ $k }}">@include('partials.producto-card', ['producto' => $producto])</li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

{{-- ======== Nuestra historia en corto, con la foto del equipo ======== --}}
<section aria-labelledby="t-historia" class="fondo-foto fondo-foto-verde py-16 lg:py-24" style="--foto: url('{{ asset('img/portada/sede-fachada.jpg') }}')">
    <div class="mx-auto grid max-w-[1320px] items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="relative" data-revelar>
            <img src="{{ asset('img/portada/sede-equipo.jpg') }}" alt="El equipo de Inversiones Rialto 7 celebrando el 63 aniversario en la tienda" class="w-full rounded-[10px] object-cover" loading="lazy">
            <div class="absolute -bottom-6 left-6 rounded-[8px] bg-rialto px-6 py-4 text-white shadow-[0_18px_40px_-20px_rgb(5_43_112/0.6)] sm:left-auto sm:right-6">
                <p class="text-4xl font-black leading-none cifra"><span data-contar="{{ $anios }}">{{ $anios }}</span></p>
                <p class="text-xs font-extrabold uppercase tracking-[0.08em]">años de trayectoria</p>
            </div>
        </div>
        <div data-revelar style="--orden:1">
            <h2 id="t-historia" class="titular subrayado text-4xl text-rialto sm:text-5xl">Una empresa <em>familiar</em> del llano guariqueño</h2>
            <p class="mt-6 text-lg text-tinta-suave">Empezamos en 1963 como Inversiones Campagna. Hoy, como Inversiones Rialto 7 C.A., seguimos en manos de la familia fundadora, atendemos desde el Edificio Campagna en Valle de la Pascua y somos el único agente autorizado en Venezuela de cuatro marcas de maquinaria y repuestos.</p>
            <ul class="mt-6 space-y-3">
                @foreach(['Repuestos originales con el respaldo del fabricante', 'Atención de gente del llano que conoce tu trabajo', 'Existencias y precios reales, a la vista'] as $punto)
                    <li class="flex items-start gap-3 font-semibold"><span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-rialto text-white"><x-icono nombre="check" class="size-4" /></span>{{ $punto }}</li>
                @endforeach
            </ul>
            <a href="{{ route('nosotros') }}" class="btn btn-tierra mt-8">Nuestra historia <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
        </div>
    </div>
</section>

{{-- ======== Cómo cotizar ======== --}}
<section aria-labelledby="t-pasos" class="border-y border-linea bg-tierra-clara/60 py-16 lg:py-24">
    <div class="relative mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
        <h2 id="t-pasos" class="titular subrayado text-4xl text-rialto sm:text-5xl" data-revelar>Tu cotización en <em>tres pasos</em></h2>
        <ol class="mt-12 grid gap-8 sm:grid-cols-3">
            @foreach([['Elige', 'Busca por código o modelo y agrega los repuestos.'], ['Revisa', 'Eliges si pagas en dólares o en bolívares y ves el impuesto y el total al instante.'], ['Envía', 'Recibes tu cotización en PDF y te contactamos por WhatsApp.']] as $i => [$paso, $texto])
                <li data-revelar style="--orden:{{ $i }}">
                    <span class="text-4xl font-black text-tierra cifra">0{{ $i + 1 }}</span>
                    <p class="mt-2 text-xl font-bold">{{ $paso }}</p>
                    <p class="apoyo mt-1">{{ $texto }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ======== Visítanos: la fachada de la sede ======== --}}
<section aria-labelledby="t-visita" class="sobre-azul relative isolate overflow-hidden text-white">
    <img src="{{ asset('img/portada/sede-fachada.jpg') }}" alt="" class="absolute inset-0 -z-10 size-full object-cover" loading="lazy">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-noche/90 via-tierra-oscura/65 to-tierra/25"></div>
    <div class="mx-auto flex max-w-[1320px] flex-col gap-8 px-4 py-20 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8 lg:py-28" data-revelar>
        <div class="max-w-2xl">
            <h2 id="t-visita" class="titular text-4xl sm:text-5xl">Visítanos en el <em>Edificio Campagna</em></h2>
            <p class="mt-4 flex items-start gap-2 text-lg text-white/85"><x-icono nombre="ubicacion" class="mt-1 size-5 shrink-0" /> Av. Las Industrias, Valle de la Pascua, Guárico · RIF J-50668510-8</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('contacto') }}" class="btn btn-sol btn-grande">Cómo llegar <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
            @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-linea-clara btn-grande"><x-icono nombre="whatsapp" class="size-5" /> WhatsApp</a>@endif
        </div>
    </div>
</section>
@endsection
