@extends('layouts.app')
@section('titulo', 'Catálogo')

@php
    // Filtros que viven en el panel (la categoría y la búsqueda tienen su propio control)
    $filtrosPanel = array_filter([
        'marca' => $filtros['marca'] ?? null,
        'modelo' => $filtros['modelo'] ?? null,
        'disponibles' => ($filtros['disponibles'] ?? null) === '1' ? '1' : null,
    ]);
    $categoriaActual = $categorias->firstWhere('id', (int) ($filtros['categoria'] ?? 0));
    // URL del catálogo conservando los filtros actuales, salvo los indicados
    $urlSin = fn (array $quitar, array $poner = []) => route('catalogo.index', array_merge(
        \Illuminate\Support\Arr::except(request()->query(), array_merge($quitar, ['page'])), $poner
    ));
    $etiquetas = ['marca' => fn ($v) => $v, 'modelo' => fn ($v) => "Modelo: {$v}", 'disponibles' => fn () => 'Solo disponibles'];
@endphp

@section('cabecera')
    <h1 class="titular max-w-3xl text-[2.25rem] sm:text-5xl lg:text-[4rem]">Repuestos con existencia real</h1>
    <p class="mt-3 max-w-2xl text-white/80 sm:text-lg">Precio en USD y en bolívares a la tasa del día, sin salir de tu finca.</p>

    {{-- Búsqueda: siempre visible. Los campos del panel se asocian a este formulario con form="filtros". --}}
    <form id="filtros" method="GET" action="{{ route('catalogo.index') }}" role="search" class="mt-6 flex max-w-3xl gap-2 lg:mt-8">
        @if($categoriaActual)<input type="hidden" name="categoria" value="{{ $categoriaActual->id }}">@endif
        <label for="q" class="sr-only">Buscar por código, nombre o modelo</label>
        <div class="relative flex-1">
            <x-icono nombre="buscar" class="pointer-events-none absolute left-5 top-1/2 size-5 -translate-y-1/2 text-tinta-suave" />
            <input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Código de fábrica, pieza o modelo" maxlength="100" enterkeyhint="search" autocomplete="off"
                   class="h-14 w-full rounded-full border-0 bg-white pl-13 pr-4 text-base text-tinta placeholder:text-tinta-suave focus:outline-none focus:ring-4 focus:ring-sol/70 lg:h-16 lg:text-lg">
        </div>
        <button class="btn btn-sol !min-h-14 !px-4 sm:!px-5 lg:!min-h-16 lg:!px-7" type="submit">
            <x-icono nombre="buscar" class="size-6 sm:hidden" /><span class="sr-only sm:not-sr-only">Buscar</span>
            <span class="chip hidden sm:grid lg:!-mr-4"><x-icono nombre="flecha" class="size-4" /></span>
        </button>
    </form>

    <p class="mt-5 flex max-w-3xl flex-wrap items-center gap-x-2 gap-y-1 text-sm text-white/80 lg:mt-6">
        <x-icono nombre="check-circulo" class="size-4 text-sol" />
        <span>Agente autorizado exclusivo en Venezuela:</span>
        @foreach(['Marchesan Tatu', 'Colombo Aemco', 'Vicon', 'Lumagi'] as $marca)
            <a href="{{ route('catalogo.index', ['marca' => $marca]) }}" class="font-bold text-white hover:text-sol">{{ $marca }}</a>@unless($loop->last)<span aria-hidden="true">·</span>@endunless
        @endforeach
        <a href="{{ route('nosotros') }}" class="ml-1 font-semibold text-sol underline decoration-2 underline-offset-4">Conócenos</a>
    </p>
@endsection

@section('contenido')
{{-- lg: en escritorio el panel es una barra lateral siempre visible; en el celular es una hoja que se abre --}}
<div x-data="{ panel: false, lg: window.matchMedia('(min-width: 1024px)').matches }"
     x-init="window.matchMedia('(min-width: 1024px)').addEventListener('change', e => lg = e.matches)"
     @keydown.escape.window="panel = false" class="relative">

    {{-- Lóbulo de sabana que se sale por el borde derecho, detrás de las categorías --}}
    <svg class="pointer-events-none absolute -right-40 -top-24 -z-10 w-[34rem] text-sabana/30 sm:-right-28 lg:-right-20" viewBox="0 0 400 260" aria-hidden="true"><path fill="currentColor" d="M58 64C96 14 170 0 236 18c58 16 86 -4 128 26 40 28 40 84 14 118-24 32-4 70-48 88-50 20-104-12-160 0C114 262 40 244 14 196-10 152 26 104 58 64Z"/></svg>

    {{-- Categorías: un toque, sin escribir. El chip de conteo cruza al frente en la categoría activa. --}}
    <nav class="-mx-4 overflow-x-auto px-4 pb-2 sm:-mx-6 sm:px-6 lg:mx-0 lg:px-0 lg:pr-56" aria-label="Categorías">
        <ul class="flex gap-2 lg:flex-wrap">
            <li><a href="{{ $urlSin(['categoria']) }}" class="capsula" @if(! $categoriaActual) aria-current="page" @endif>Todas <span class="conteo">{{ $categorias->sum('productos_count') }}</span></a></li>
            @foreach($categorias as $categoria)
                <li><a href="{{ $urlSin(['categoria'], ['categoria' => $categoria->id]) }}" class="capsula" @if($categoriaActual?->id === $categoria->id) aria-current="page" @endif>{{ $categoria->nombre }} <span class="conteo">{{ $categoria->productos_count }}</span></a></li>
            @endforeach
        </ul>
    </nav>

    <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
        {{-- Panel "Ajustar búsqueda": hoja inferior en el celular, barra lateral desde escritorio --}}
        <div x-show="panel" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-tinta/50 lg:hidden" @click="panel = false"></div>
        <aside id="panel-filtros" aria-labelledby="titulo-filtros" :inert="!panel && !lg"
               class="fixed inset-x-0 bottom-0 z-50 max-h-[85dvh] translate-y-full overflow-y-auto rounded-t-[1.75rem] bg-agua p-6 transition-transform duration-400 ease-[var(--ease-salida)] lg:static lg:z-auto lg:max-h-none lg:translate-y-0 lg:overflow-visible lg:rounded-none lg:bg-transparent lg:p-0"
               :class="panel && '!translate-y-0'">
            <div class="lg:sticky lg:top-6 space-y-5">
                <div class="flex items-center justify-between">
                    <h2 id="titulo-filtros" class="seccion-titulo">Ajustar búsqueda</h2>
                    <button type="button" class="grid size-11 place-items-center rounded-full hover:bg-agua-honda lg:hidden" @click="panel = false" aria-label="Cerrar"><x-icono nombre="x" /></button>
                </div>
                <div>
                    <label for="marca" class="etiqueta">Fabricante</label>
                    <select id="marca" name="marca" form="filtros" class="campo">
                        <option value="">Todos</option>
                        @foreach($marcas as $marca)
                            <option value="{{ $marca }}" @selected(($filtros['marca'] ?? null) === $marca)>{{ $marca }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="modelo" class="etiqueta">Modelo de tu maquinaria</label>
                    <input type="text" id="modelo" name="modelo" form="filtros" value="{{ $filtros['modelo'] ?? '' }}" class="campo" placeholder="Ej. MF 290" maxlength="80">
                </div>
                <label class="flex min-h-11 cursor-pointer items-center gap-3 font-semibold">
                    <input type="checkbox" name="disponibles" value="1" id="disponibles" form="filtros" @checked(($filtros['disponibles'] ?? null) === '1')>
                    Solo con existencia
                </label>
                <button type="submit" form="filtros" class="btn btn-tierra w-full">Ver resultados</button>
                @if(count($filtrosPanel))
                    <a href="{{ $urlSin(array_keys($filtrosPanel)) }}" class="enlace block text-center">Quitar ajustes</a>
                @endif
            </div>
        </aside>

        <section aria-labelledby="resultados" class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <p id="resultados" aria-live="polite" class="text-lg">
                    <strong class="cifra">{{ $productos->total() }}</strong> {{ $productos->total() === 1 ? 'producto' : 'productos' }}@if($categoriaActual) en <strong>{{ $categoriaActual->nombre }}</strong>@endif
                </p>
                {{-- "Ajustar", no "Filtros": la empresa tiene una categoría llamada Filtros justo arriba --}}
                <button type="button" class="btn btn-linea btn-mini ml-auto lg:hidden" @click="panel = true" aria-controls="panel-filtros" :aria-expanded="panel">
                    <x-icono nombre="deslizadores" class="size-4" /> Ajustar
                    @if(count($filtrosPanel))<span class="grid size-6 place-items-center rounded-full bg-tierra text-xs text-white">{{ count($filtrosPanel) }}<span class="sr-only"> {{ count($filtrosPanel) === 1 ? 'ajuste activo' : 'ajustes activos' }}</span></span>@endif
                </button>
            </div>

            {{-- Búsqueda y ajustes activos: cada uno se quita con un toque --}}
            @if(count($filtrosPanel) || filled($filtros['q'] ?? null))
                <ul class="mt-3 flex flex-wrap gap-2" aria-label="Búsqueda y ajustes activos">
                    @if(filled($filtros['q'] ?? null))
                        <li><a href="{{ $urlSin(['q']) }}" class="capsula capsula-quitar">“{{ $filtros['q'] }}” <x-icono nombre="x" class="size-4" /><span class="sr-only">Quitar búsqueda</span></a></li>
                    @endif
                    @foreach($filtrosPanel as $clave => $valor)
                        <li><a href="{{ $urlSin([$clave]) }}" class="capsula capsula-quitar">{{ $etiquetas[$clave]($valor) }} <x-icono nombre="x" class="size-4" /><span class="sr-only">Quitar este ajuste</span></a></li>
                    @endforeach
                </ul>
            @endif

            @if($productos->isEmpty())
                <div class="relative mt-6 overflow-hidden rounded-lobulo bg-agua-honda px-6 py-10 sm:px-10">
                    <svg class="absolute -right-10 -top-10 size-48 text-laguna/25" viewBox="0 0 200 200" aria-hidden="true"><path fill="currentColor" d="M44 30C70 4 120 10 150 30s46 70 26 104-70 60-110 44S-4 116 6 80s12-34 38-50Z"/></svg>
                    <h2 class="titular relative text-3xl">No encontramos ese repuesto</h2>
                    <p class="relative mt-3 max-w-xl text-tinta-suave">Revisa el código o prueba con el modelo de tu maquinaria. También puede que lo tengamos y aún no esté publicado.</p>
                    <div class="relative mt-6 flex flex-wrap gap-2">
                        <a href="{{ route('catalogo.index') }}" class="btn btn-linea">Ver todo el catálogo</a>
                        @if($wa = \App\Support\Contacto::whatsapp('Hola, busco el repuesto: ' . ($filtros['q'] ?? '')))
                            <a href="{{ $wa }}" class="btn btn-sol" target="_blank" rel="noopener"><x-icono nombre="whatsapp" /> Preguntar por WhatsApp</a>
                        @endif
                    </div>
                </div>
            @else
                <ul class="mt-5 grid gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3" role="list">
                    @foreach($productos as $producto)
                        <li>@include('partials.producto-card', ['producto' => $producto])</li>
                    @endforeach
                </ul>
                {{ $productos->onEachSide(1)->links() }}
            @endif
        </section>
    </div>
</div>
@endsection
