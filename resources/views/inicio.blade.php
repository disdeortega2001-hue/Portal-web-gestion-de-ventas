@extends('layouts.app')
@section('titulo', 'Repuestos y maquinaria agrícola')
@section('ancho_completo', true)

@php
    $mensaje = \App\Support\Contacto::whatsapp('Hola, quiero consultar un repuesto.');
    // El logo de Tatu es un cuadro azul completo; los demás traen margen blanco y se recortan a su banda
    $marcaLogo = fn ($m) => $m['nombre'] === 'Marchesan Tatu' ? 'bg-[#173C8A] object-contain' : 'bg-white object-cover';
@endphp

@section('contenido')
{{-- ======== Portada: la librea de Rialto recorre el borde; el buscador trabaja desde el primer vistazo ======== --}}
<section aria-labelledby="t-portada" class="relative bg-white">
    {{-- Franja vertical de la librea con el círculo del 7, como en la carrocería --}}
    <div class="absolute inset-y-0 left-0 hidden w-24 bg-tierra-clara lg:block xl:w-28" aria-hidden="true">
        <div class="calcar absolute inset-y-0 left-1/2 w-10 -translate-x-1/2"
             style="background: linear-gradient(to right, #fff 0 14%, var(--color-sol) 14% 86%, #fff 86% 100%)"></div>
        <span class="ruedo absolute left-1/2 top-10 size-20 -translate-x-1/2 text-5xl xl:size-24 xl:text-6xl">7</span>
        <div class="absolute inset-x-0 bottom-16 space-y-1.5" >
            <div class="h-3 bg-tierra"></div><div class="h-1.5 bg-white"></div><div class="h-3 bg-sol"></div><div class="h-1.5 bg-white"></div><div class="h-3 bg-tierra"></div>
        </div>
    </div>

    <div class="mx-auto grid max-w-[1320px] gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)] lg:pl-32 lg:pr-8 lg:py-14 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.95fr)_minmax(0,0.7fr)] xl:pl-36">
        <div class="flex flex-col justify-center">
            <h1 id="t-portada" class="titular text-[3.25rem] sm:text-7xl xl:text-[5.5rem]">El repuesto exacto <span class="text-tierra">desde 1963</span></h1>
            <div class="franja mt-6 max-w-md" aria-hidden="true"></div>
            <p class="mt-5 max-w-xl text-lg text-tinta-suave sm:text-xl">Existencia real, precio en dólares y bolívares, y tu cotización en minutos sin salir de la finca.</p>

            <form method="GET" action="{{ route('catalogo.index') }}" role="search" class="mt-7 flex max-w-xl gap-2">
                <label for="q-inicio" class="sr-only">Buscar por código de fábrica, pieza o modelo</label>
                <input type="search" id="q-inicio" name="q" placeholder="Código de fábrica, pieza o modelo" maxlength="100" enterkeyhint="search" autocomplete="off"
                       class="campo !min-h-14 flex-1 !text-lg">
                <button class="btn btn-sol btn-grande !min-h-14" type="submit"><x-icono nombre="buscar" class="sm:hidden" /><span class="sr-only sm:not-sr-only">Buscar</span></button>
            </form>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($categorias->take(4) as $categoria)
                    <a href="{{ route('catalogo.index', ['categoria' => $categoria->id]) }}" class="capsula !min-h-10 !text-[0.9375rem]">{{ $categoria->nombre }} <span class="conteo !size-7">{{ $categoria->productos_count }}</span></a>
                @endforeach
            </div>
        </div>

        {{-- El equipo estrella, en su box --}}
        @if($estrella)
            <a href="{{ route('catalogo.show', $estrella) }}" class="group relative block overflow-hidden bg-agua no-underline" aria-label="{{ $estrella->nombre }}, {{ $estrella->codigo_fabrica }}">
                <div class="franja franja-fina" aria-hidden="true"></div>
                <img src="{{ $estrella->urlImagen() }}" alt="" class="aspect-[4/3] w-full object-contain p-6 mix-blend-multiply transition-transform duration-700 ease-[var(--ease-salida)] group-hover:scale-[1.03] lg:aspect-auto lg:h-[26rem]" fetchpriority="high">
                <span class="ruedo absolute right-5 top-8 size-20 text-3xl">{{ $estrella->stock }}</span>
                <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-4 bg-tierra px-5 py-4 text-white">
                    <div class="min-w-0">
                        <p class="font-rotulo text-2xl font-extrabold uppercase leading-none">{{ $estrella->nombre }}</p>
                        <p class="mt-1 text-sm text-white/80">{{ $estrella->marca }} · <span class="cifra">{{ $estrella->codigo_fabrica }}</span></p>
                    </div>
                    <p class="shrink-0 font-rotulo text-2xl font-extrabold text-sol cifra">@usd($estrella->precio)</p>
                </div>
            </a>
        @endif

        {{-- Pizarra del día: los números de hoy, tal como los calcula el sistema --}}
        <aside aria-labelledby="t-pizarra" class="lobulo panel-tapa self-start lg:col-span-2 xl:col-span-1">
            <div class="flex items-center justify-between gap-2 px-5 pb-3 pt-6">
                <h2 id="t-pizarra" class="font-rotulo text-xl font-extrabold uppercase">Pizarra del día</h2>
                <span class="cifra text-sm font-semibold text-tinta-suave">{{ now()->format('d/m/Y') }}</span>
            </div>
            <dl class="grid grid-cols-2 border-t border-linea xl:grid-cols-1">
                <div class="border-b border-linea bg-tierra-clara px-5 py-4 xl:col-span-1">
                    <dt class="text-sm font-semibold text-tierra">Tasa oficial</dt>
                    <dd class="font-rotulo text-3xl font-extrabold text-tierra cifra">Bs. {{ number_format($tasa, 2, ',', '.') }}</dd>
                    @if($tasaActualizada)<dd class="text-xs text-tinta-suave">por dólar · actualizada {{ $tasaActualizada->format('d/m h:i a') }}</dd>@endif
                </div>
                <div class="border-b border-l border-linea px-5 py-4 xl:border-l-0">
                    <dt class="text-sm font-semibold text-tinta-suave">Con existencia</dt>
                    <dd class="font-rotulo text-3xl font-extrabold cifra">{{ $disponibles }} <span class="text-lg text-tinta-suave">de {{ $totalProductos }}</span></dd>
                </div>
                <div class="border-b border-linea px-5 py-4">
                    <dt class="text-sm font-semibold text-tinta-suave">Trayectoria</dt>
                    <dd class="font-rotulo text-3xl font-extrabold cifra">{{ $anios }} años</dd>
                </div>
                <div class="border-b border-l border-linea px-5 py-4 xl:border-l-0">
                    <dt class="text-sm font-semibold text-tinta-suave">Precios</dt>
                    <dd class="font-rotulo text-3xl font-extrabold cifra">USD <span class="text-lg text-tinta-suave">+ IVA {{ rtrim(rtrim(number_format($iva, 2, ',', ''), '0'), ',') }} %</span></dd>
                </div>
            </dl>
            @if($mensaje)
                <div class="p-5"><a href="{{ $mensaje }}" class="btn btn-tierra w-full" target="_blank" rel="noopener"><x-icono nombre="whatsapp" /> Escríbenos por WhatsApp</a></div>
            @endif
        </aside>
    </div>

    {{-- Fila de las marcas exclusivas, como la fila de patrocinadores de la carrocería --}}
    <div class="border-y border-linea bg-agua">
        <div class="mx-auto flex max-w-[1320px] flex-col gap-4 px-4 py-5 sm:px-6 lg:flex-row lg:items-center lg:gap-8 lg:pl-32 lg:pr-8 xl:pl-36">
            <p class="shrink-0 font-rotulo text-lg font-bold uppercase leading-tight">Agente exclusivo<br class="hidden lg:block"> en Venezuela</p>
            <ul class="grid flex-1 grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($marcas as $marca)
                    <li><a href="{{ route('catalogo.index', ['marca' => $marca['nombre']]) }}" class="block border border-linea bg-white p-1 transition-colors hover:border-tierra" title="Ver productos de {{ $marca['nombre'] }}">
                        <img src="{{ asset($marca['logo']) }}" alt="{{ $marca['nombre'] }}" class="h-16 w-full {{ $marcaLogo($marca) }}" loading="lazy">
                    </a></li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

<div class="mx-auto max-w-[1320px] px-4 sm:px-6 lg:px-8">
    {{-- ======== Categorías: paneles de carrocería ======== --}}
    <section aria-labelledby="t-categorias" class="pt-14 lg:pt-20">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="t-categorias" class="titular text-4xl sm:text-5xl">Busca por tipo de repuesto</h2>
            <a href="{{ route('catalogo.index') }}" class="btn btn-linea">Ver todo el catálogo <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
        </div>
        <ul class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
            @foreach($categorias as $categoria)
                <li>
                    <a href="{{ route('catalogo.index', ['categoria' => $categoria->id]) }}" class="lobulo panel-tapa group flex h-full flex-col no-underline transition-colors hover:border-tierra">
                        <div class="relative mt-1.5 grid aspect-[4/3] place-items-center bg-agua">
                            @if($categoria->muestra)
                                <img src="{{ $categoria->muestra->urlImagen('mini') }}" alt="" class="size-full object-contain p-4 mix-blend-multiply transition-transform duration-500 group-hover:scale-105" loading="lazy">
                            @endif
                            <span class="ruedo absolute -bottom-4 right-3 size-11 text-lg">{{ $categoria->productos_count }}</span>
                        </div>
                        <p class="p-4 pt-5 font-rotulo text-lg font-extrabold uppercase leading-tight">{{ $categoria->nombre }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ======== Los más pedidos ======== --}}
    @if($destacados->isNotEmpty())
        <section aria-labelledby="t-destacados" class="pt-14 lg:pt-20">
            <h2 id="t-destacados" class="titular text-4xl sm:text-5xl">Los más pedidos</h2>
            <p class="mt-2 text-tinta-suave">Con existencia hoy. Agrégalos a tu cotización con un toque.</p>
            <ul class="mt-8 grid gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
                @foreach($destacados as $producto)
                    <li>@include('partials.producto-card', ['producto' => $producto])</li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ======== Cómo cotizar: tres pasos que sí importan en orden ======== --}}
    <section aria-labelledby="t-pasos" class="mt-14 grid gap-8 bg-tierra p-7 text-white lg:mt-20 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,2fr)] lg:p-10">
        <div>
            <h2 id="t-pasos" class="titular text-4xl">Tu cotización en tres pasos</h2>
            <p class="mt-3 text-white/80">Sin crear cuenta. Un vendedor confirma la existencia y te escribe.</p>
        </div>
        <ol class="grid gap-6 sm:grid-cols-3">
            @foreach([['Elige', 'Busca por código o modelo y agrega los repuestos.'], ['Revisa', 'Ves el subtotal, el IVA y el total en dólares y bolívares.'], ['Envía', 'Recibes tu cotización en PDF y te contactamos por WhatsApp.']] as $i => [$paso, $texto])
                <li class="flex gap-4 sm:block">
                    <span class="ruedo size-14 text-2xl">{{ $i + 1 }}</span>
                    <div><p class="mt-0 font-rotulo text-2xl font-extrabold uppercase sm:mt-4">{{ $paso }}</p><p class="mt-1 text-white/80">{{ $texto }}</p></div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- ======== La historia, en corto ======== --}}
    <section aria-labelledby="t-historia" class="mt-14 grid items-center gap-8 lg:mt-20 lg:grid-cols-2">
        <div>
            <h2 id="t-historia" class="titular text-4xl sm:text-5xl">{{ $anios }} años junto al productor llanero</h2>
            <p class="mt-4 max-w-xl text-lg text-tinta-suave">Empezamos en 1963 como Inversiones Campagna. Hoy, como Inversiones Rialto 7 C.A., seguimos en manos de la familia fundadora y somos el único agente autorizado en Venezuela de cuatro marcas de maquinaria y repuestos.</p>
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('nosotros') }}" class="btn btn-tierra">Nuestra historia <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
                <a href="{{ route('contacto') }}" class="btn btn-linea">Cómo llegar</a>
            </div>
        </div>
        <div class="lobulo panel-tapa grid grid-cols-[auto_1fr] items-center gap-5 p-6 pt-8">
            <span class="ruedo size-16 text-2xl">63</span>
            <p class="text-tinta-suave"><strong class="text-tinta">Valle de la Pascua, Guárico.</strong> Av. Las Industrias, Edificio Campagna. RIF J-50668510-8.</p>
        </div>
    </section>
</div>
@endsection
