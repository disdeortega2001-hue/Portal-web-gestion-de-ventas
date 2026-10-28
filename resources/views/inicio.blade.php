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
    $cifras = [[$anios, '+', 'años de trayectoria'], [count($marcas), '', 'marcas exclusivas'], [$totalProductos, '', 'productos en línea'], [null, '24/7', 'catálogo abierto']];
    $logoMarca = fn ($m) => $m['nombre'] === 'Marchesan Tatu' ? 'bg-[#173C8A] object-contain' : 'bg-white object-cover';
@endphp

@section('contenido')
{{-- ======== Portada: fotos a sangre que se relevan con zoom lento; el texto entra escalonado ======== --}}
<section id="portada" aria-labelledby="t-portada" class="sobre-azul relative isolate flex min-h-[calc(100svh-4rem)] items-center overflow-hidden bg-noche text-white lg:min-h-[calc(100svh-4.5rem)]"
         x-data="portada({{ count($diapositivas) }})">
    <div class="absolute inset-0 -z-10" aria-hidden="true">
        @foreach($diapositivas as $i => [$grande, $movil, $leyenda])
            <picture class="diapositiva absolute inset-0" :class="actual === {{ $i }} && 'activa'" @if($i === 0) data-primera @endif>
                <source media="(max-width: 767px)" srcset="{{ asset($movil) }}">
                <img src="{{ asset($grande) }}" alt="" class="size-full object-cover" @if($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
            </picture>
        @endforeach
        <div class="absolute inset-0 bg-gradient-to-r from-noche/95 via-tierra-oscura/75 to-rialto/45"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-noche/90 via-transparent to-transparent"></div>
        <x-arcos class="absolute inset-y-0 right-0 hidden h-full w-[48rem] lg:block" />
    </div>
    {{-- Sin JavaScript se ve la primera foto --}}
    <noscript><style>[data-primera]{opacity:1;transform:none}</style></noscript>

    <div class="mx-auto w-full max-w-[1320px] px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <div class="max-w-3xl">
            <p class="inline-flex items-center gap-2.5 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] backdrop-blur-sm" data-entrar style="--orden:0">
                <span class="relative flex size-2.5"><span class="latido absolute inset-0 rounded-full bg-[#3ddc84]"></span><span class="relative size-2.5 rounded-full bg-[#3ddc84]"></span></span>
                Valle de la Pascua desde 1963
            </p>
            <h1 id="t-portada" class="titular mt-6 text-[2.6rem] sm:text-6xl lg:text-7xl" data-entrar style="--orden:1">El repuesto exacto, <em>desde 1963</em> junto al productor</h1>
            <p class="mt-5 max-w-xl text-lg text-white/85 sm:text-xl" data-entrar style="--orden:2">Existencia real, precio en dólares y bolívares, y tu cotización en minutos sin salir de la finca.</p>

            <form method="GET" action="{{ route('catalogo.index') }}" role="search" class="mt-8 flex max-w-xl gap-2" data-entrar style="--orden:3">
                <label for="q-inicio" class="sr-only">Buscar por código de fábrica, pieza o modelo</label>
                <input type="search" id="q-inicio" name="q" placeholder="Código de fábrica, pieza o modelo" maxlength="100" enterkeyhint="search" autocomplete="off"
                       class="campo !min-h-14 flex-1 !border-transparent !text-lg">
                <button class="btn btn-sol btn-grande !min-h-14" type="submit"><x-icono nombre="buscar" class="size-5" /><span class="sr-only sm:not-sr-only">Buscar</span></button>
            </form>
            <div class="mt-4 flex flex-wrap gap-3" data-entrar style="--orden:4">
                <a href="{{ route('catalogo.index') }}" class="btn btn-linea-clara">Ver el catálogo <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
                @if($whatsapp)<a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="btn btn-linea-clara"><x-icono nombre="whatsapp" class="size-5" /> Escríbenos</a>@endif
            </div>

            <dl class="mt-12 grid max-w-2xl grid-cols-2 gap-x-6 gap-y-6 border-t border-white/15 pt-8 sm:grid-cols-4" data-entrar style="--orden:5">
                @foreach($cifras as [$numero, $extra, $etiqueta])
                    <div>
                        <dt class="sr-only">{{ $etiqueta }}</dt>
                        <dd class="text-4xl font-black text-sol cifra">@if($numero !== null)<span data-contar="{{ $numero }}">{{ $numero }}</span>@endif{{ $extra }}</dd>
                        <dd class="mt-1 text-xs font-bold uppercase tracking-[0.1em] text-white/70">{{ $etiqueta }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    {{-- Leyenda de la foto actual (solo la de la sede la tiene) --}}
    @foreach($diapositivas as $i => [, , $leyenda])
        @if($leyenda)<p x-show="actual === {{ $i }}" x-transition.opacity class="absolute bottom-7 left-4 hidden max-w-sm items-center gap-2 text-sm font-semibold text-white/80 sm:flex lg:left-8"><x-icono nombre="ubicacion" class="size-4 text-sol" /> {{ $leyenda }}</p>@endif
    @endforeach

    {{-- Puntos con barra de avance --}}
    <div class="absolute bottom-8 left-1/2 flex -translate-x-1/2 gap-2" x-cloak>
        @foreach($diapositivas as $i => $d)
            <button type="button" class="relative h-2 overflow-hidden rounded-full transition-all duration-500" :class="actual === {{ $i }} ? 'w-10' : 'w-2'"
                    @click="ir({{ $i }})" aria-label="Ver foto {{ $i + 1 }}" :aria-current="actual === {{ $i }}">
                <span class="absolute inset-0 rounded-full bg-white/35"></span>
                <template x-if="actual === {{ $i }}"><span class="punto-progreso absolute inset-0 rounded-full bg-sol" :style="`--duracion:${duracion}ms`"></span></template>
            </button>
        @endforeach
    </div>
    {{-- Indicador para bajar --}}
    <a href="#marcas" class="absolute bottom-8 right-8 hidden flex-col items-center gap-2 text-white/60 lg:flex" aria-label="Bajar a las marcas">
        <span class="text-[0.6875rem] font-bold uppercase tracking-[0.2em] [writing-mode:vertical-lr]">Desliza</span>
        <span class="flex h-9 w-6 justify-center rounded-full border-2 border-white/40 pt-1.5"><span class="rueda-scroll h-2 w-1 rounded-full bg-sol"></span></span>
    </a>
</section>

{{-- ======== Marcas exclusivas ======== --}}
<section id="marcas" aria-labelledby="t-marcas" class="fondo-foto border-b border-linea" style="--foto: url('{{ asset('img/portada/campo-2.webp') }}')">
    <div class="mx-auto grid max-w-[1320px] items-center gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)] lg:px-8">
        <div data-revelar>
            <h2 id="t-marcas" class="titular subrayado text-3xl text-tierra-oscura">Agente exclusivo en Venezuela</h2>
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

{{-- ======== Equipo estrella, al estilo de las fichas de línea de los fabricantes ======== --}}
@if($estrella)
    <section aria-labelledby="t-estrella" class="fondo-foto fondo-foto-fijo overflow-hidden" style="--foto: url('{{ asset('img/portada/campo-1.webp') }}')">
        <div class="mx-auto grid max-w-[1320px] items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div data-revelar>
                <h2 id="t-estrella" class="titular text-4xl text-tierra-oscura sm:text-5xl">{{ $estrella->nombre }}</h2>
                <p class="mt-2 text-lg font-extrabold uppercase tracking-[0.04em] text-tierra">{{ $estrella->categoria->nombre }} · {{ $estrella->marca }}</p>
                <p class="mt-5 max-w-lg text-lg text-tinta-suave">{{ $estrella->descripcion ?: 'Equipo original con el respaldo directo del fabricante.' }}</p>
                <dl class="mt-6 flex flex-wrap gap-x-10 gap-y-4">
                    <div><dt class="text-sm font-semibold text-tinta-suave">Código de fábrica</dt><dd class="text-xl font-black text-tierra cifra">{{ $estrella->codigo_fabrica }}</dd></div>
                    <div><dt class="text-sm font-semibold text-tinta-suave">Precio</dt><dd class="text-xl font-black cifra">@usd($estrella->precio) <span class="text-sm font-semibold text-tinta-suave">+ IVA</span></dd></div>
                    <div><dt class="text-sm font-semibold text-tinta-suave">Disponibles</dt><dd class="text-xl font-black cifra">{{ $estrella->stock }}</dd></div>
                </dl>
                <a href="{{ route('catalogo.show', $estrella) }}" class="btn btn-tierra mt-8">Saber más <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
            </div>
            <div class="relative" data-revelar style="--orden:1">
                <div class="relative overflow-hidden rounded-[10px] bg-white">
                    <p class="relative z-10 bg-tierra px-6 py-3 text-right text-lg font-black uppercase tracking-[0.04em] text-sol">{{ $estrella->categoria->nombre }}</p>
                    <x-arcos tono="oscuro" class="absolute inset-0 h-full w-full" />
                    <img src="{{ $estrella->urlImagen() }}" alt="{{ $estrella->nombre }}" class="relative aspect-[4/3] w-full object-contain p-8 mix-blend-multiply" loading="lazy">
                </div>
            </div>
        </div>
    </section>
@endif

{{-- ======== Por tipo de repuesto: carrusel de tarjetas con pestaña azul ======== --}}
<section aria-labelledby="t-categorias" class="fondo-foto fondo-foto-verde py-16 lg:py-24" style="--foto: url('{{ asset('img/portada/campo-4.webp') }}')" x-data="{ mover(d) { const p = this.$refs.pista; p.scrollBy({ left: d * p.clientWidth * 0.8, behavior: 'smooth' }); } }">
    <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4" data-revelar>
            <h2 id="t-categorias" class="titular subrayado text-4xl text-tierra-oscura sm:text-5xl">Por tipo de repuesto</h2>
            <div class="flex gap-2">
                <button type="button" class="grid size-12 place-items-center rounded-full border-2 border-tierra text-tierra hover:bg-tierra hover:text-white" @click="mover(-1)" aria-label="Anteriores"><x-icono nombre="flecha-izq" /></button>
                <button type="button" class="grid size-12 place-items-center rounded-full bg-tierra text-white hover:bg-tierra-oscura" @click="mover(1)" aria-label="Siguientes"><x-icono nombre="flecha" /></button>
            </div>
        </div>
        <ul x-ref="pista" class="-mx-4 mt-10 flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-px-4 px-4 pb-4 [scrollbar-width:none] sm:-mx-6 sm:px-6 lg:mx-0 lg:px-0 [&::-webkit-scrollbar]:hidden">
            @foreach($categorias as $k => $categoria)
                <li class="w-[72%] shrink-0 snap-start sm:w-[42%] lg:w-[calc((100%-3.75rem)/4)]" data-revelar style="--orden:{{ $k }}">
                    <a href="{{ route('catalogo.index', ['categoria' => $categoria->id]) }}" class="lobulo tarjeta-viva group block overflow-hidden">
                        <p class="flex items-center justify-between gap-2 bg-tierra px-4 py-3 text-sm font-black uppercase tracking-[0.04em] text-white">
                            {{ $categoria->nombre }} <span class="rounded-[4px] bg-sol px-2 py-0.5 text-tierra-oscura cifra">{{ $categoria->productos_count }}</span>
                        </p>
                        <div class="aspect-[4/5] bg-agua">
                            @if($categoria->muestra)
                                <img src="{{ $categoria->muestra->urlImagen() }}" alt="" class="size-full object-contain p-6 mix-blend-multiply grayscale transition duration-500 group-hover:scale-105 group-hover:grayscale-0" loading="lazy">
                            @endif
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ======== Los más pedidos ======== --}}
@if($destacados->isNotEmpty())
    <section aria-labelledby="t-destacados" class="fondo-foto fondo-foto-fijo py-16 lg:py-24" style="--foto: url('{{ asset('img/portada/campo-3.webp') }}')">
        <div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4" data-revelar>
                <div>
                    <h2 id="t-destacados" class="titular subrayado text-4xl text-tierra-oscura sm:text-5xl">Los más pedidos</h2>
                    <p class="mt-3 text-tinta-suave">Con existencia hoy. Agrégalos a tu cotización con un toque.</p>
                    @if($tasa > 0)
                        {{-- Los precios en bolívares salen de esta tasa: se muestra con su fecha --}}
                        <p class="mt-3 inline-flex flex-wrap items-center gap-x-2 rounded-[6px] bg-white px-3 py-1.5 text-sm ring-1 ring-linea">
                            <span class="font-extrabold uppercase tracking-[0.06em] text-tierra">Tasa del día</span>
                            <span class="cifra font-black">@bs($tasa)</span>
                            <span class="text-tinta-suave">por dólar · IVA {{ rtrim(rtrim(number_format($iva, 2, ',', ''), '0'), ',') }} %@if($tasaActualizada) · {{ $tasaActualizada->format('d/m/Y') }}@endif</span>
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
            <div class="absolute -bottom-6 left-6 rounded-[8px] bg-sol px-6 py-4 text-tierra-oscura shadow-[0_18px_40px_-20px_rgb(5_43_112/0.6)] sm:left-auto sm:right-6">
                <p class="text-4xl font-black leading-none cifra"><span data-contar="{{ $anios }}">{{ $anios }}</span></p>
                <p class="text-xs font-extrabold uppercase tracking-[0.08em]">años de trayectoria</p>
            </div>
        </div>
        <div data-revelar style="--orden:1">
            <h2 id="t-historia" class="titular subrayado text-4xl text-tierra-oscura sm:text-5xl">Una empresa <em>familiar</em> del llano guariqueño</h2>
            <p class="mt-6 text-lg text-tinta-suave">Empezamos en 1963 como Inversiones Campagna. Hoy, como Inversiones Rialto 7 C.A., seguimos en manos de la familia fundadora, atendemos desde el Edificio Campagna en Valle de la Pascua y somos el único agente autorizado en Venezuela de cuatro marcas de maquinaria y repuestos.</p>
            <ul class="mt-6 space-y-3">
                @foreach(['Repuestos originales con el respaldo del fabricante', 'Atención de gente del llano que conoce tu trabajo', 'Existencias y precios reales, a la vista'] as $punto)
                    <li class="flex items-start gap-3 font-semibold"><span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-sol text-tierra-oscura"><x-icono nombre="check" class="size-4" /></span>{{ $punto }}</li>
                @endforeach
            </ul>
            <a href="{{ route('nosotros') }}" class="btn btn-tierra mt-8">Nuestra historia <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
        </div>
    </div>
</section>

{{-- ======== Cómo cotizar ======== --}}
<section aria-labelledby="t-pasos" class="sobre-azul relative isolate overflow-hidden bg-rialto-oscuro py-16 text-white lg:py-24">
    {{-- Foto de fondo con el verde Rialto encima --}}
    <img src="{{ asset('img/portada/campo-3.webp') }}" alt="" class="absolute inset-0 -z-10 size-full object-cover" loading="lazy">
    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-rialto-oscuro/95 via-rialto/85 to-tierra-oscura/80" aria-hidden="true"></div>
    <x-arcos class="absolute inset-y-0 right-0 h-full w-[40rem]" />
    <div class="relative mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
        <h2 id="t-pasos" class="titular subrayado text-4xl sm:text-5xl" data-revelar>Tu cotización en <em>tres pasos</em></h2>
        <ol class="mt-12 grid gap-8 sm:grid-cols-3">
            @foreach([['Elige', 'Busca por código o modelo y agrega los repuestos.'], ['Revisa', 'Ves el subtotal, el IVA y el total en dólares y bolívares.'], ['Envía', 'Recibes tu cotización en PDF y te contactamos por WhatsApp.']] as $i => [$paso, $texto])
                <li class="rounded-[10px] border border-white/15 bg-white/5 p-7 backdrop-blur-sm" data-revelar style="--orden:{{ $i }}">
                    <span class="text-6xl font-black text-sol cifra">0{{ $i + 1 }}</span>
                    <p class="mt-3 text-2xl font-black uppercase">{{ $paso }}</p>
                    <p class="mt-2 text-white/80">{{ $texto }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- ======== Visítanos: la fachada de la sede ======== --}}
<section aria-labelledby="t-visita" class="sobre-azul relative isolate overflow-hidden text-white">
    <img src="{{ asset('img/portada/sede-fachada.jpg') }}" alt="" class="absolute inset-0 -z-10 size-full object-cover" loading="lazy">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-noche/95 via-tierra-oscura/80 to-rialto/60"></div>
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
