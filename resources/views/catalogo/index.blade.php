@extends('layouts.app')
@section('titulo', 'Catálogo')
@section('descripcion', 'Catálogo de repuestos y maquinaria agrícola con existencia real y precios en USD y Bs.: cuchillas, discos, cardanes, repuestos de abonadoras y más.')
@if(request()->except('page'))@section('canonica', route('catalogo.index'))@endif

@php
    // Filtros que viven en el panel «Ajustar búsqueda» (la búsqueda y el orden tienen su propio control)
    $filtrosPanel = array_filter([
        'categoria' => $filtros['categoria'] ?? null,
        'marca' => $filtros['marca'] ?? null,
        'modelo' => $filtros['modelo'] ?? null,
        'disponibles' => ($filtros['disponibles'] ?? null) === '1' ? '1' : null,
    ]);
    $categoriaActual = $categorias->firstWhere('id', (int) ($filtros['categoria'] ?? 0));
    // URL del catálogo conservando los filtros actuales, salvo los indicados
    $urlSin = fn (array $quitar, array $poner = []) => route('catalogo.index', array_merge(
        \Illuminate\Support\Arr::except(request()->query(), array_merge($quitar, ['page'])), $poner
    ));
    $etiquetas = ['categoria' => fn ($v) => $categorias->firstWhere('id', (int) $v)?->nombre ?? 'Categoría', 'marca' => fn ($v) => $v, 'modelo' => fn ($v) => "Modelo: {$v}", 'disponibles' => fn () => 'Solo disponibles'];
@endphp

@section('cabecera')
    <h1 class="titular max-w-3xl text-5xl sm:text-6xl lg:text-7xl">Catálogo de repuestos</h1>
    <p class="mt-3 max-w-2xl text-lg text-white/80">Existencia real y precio en USD y bolívares a la tasa del día.</p>

    {{-- Búsqueda con sugerencias en vivo. Los campos del panel se asocian a este formulario con form="filtros". --}}
    <form id="filtros" method="GET" action="{{ route('catalogo.index') }}" role="search" class="relative mt-7 flex max-w-3xl gap-2"
          x-data="sugerencias(@js(route('catalogo.sugerencias')))" @click.outside="abierto = false" @keydown.escape="abierto = false">
        @if(filled($filtros['orden'] ?? null))<input type="hidden" name="orden" value="{{ $filtros['orden'] }}">@endif
        <label for="q" class="sr-only">Buscar por código, nombre o modelo</label>
        <div class="relative flex-1">
            <x-icono nombre="buscar" class="pointer-events-none absolute left-4 top-1/2 z-10 size-5 -translate-y-1/2 text-tinta-suave" />
            <input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Código de fábrica, pieza o modelo" maxlength="100" enterkeyhint="search" autocomplete="off"
                   role="combobox" aria-autocomplete="list" aria-controls="lista-sugerencias" :aria-expanded="abierto"
                   x-model="q" @input.debounce.250ms="buscar()" @focus="abierto = items.length > 0"
                   @keydown.arrow-down.prevent="mover(1)" @keydown.arrow-up.prevent="mover(-1)" @keydown.enter="if (activo >= 0) { $event.preventDefault(); window.location = items[activo].url }"
                   class="campo !min-h-14 !pl-12 !text-lg lg:!min-h-16">
            <ul id="lista-sugerencias" role="listbox" x-show="abierto" x-cloak class="absolute inset-x-0 top-full z-30 mt-1 border border-linea bg-white text-tinta">
                <template x-for="(s, i) in items" :key="s.url">
                    <li role="option" :aria-selected="i === activo">
                        <a :href="s.url" class="grid grid-cols-[3rem_1fr_auto] items-center gap-3 px-3 py-2.5 no-underline hover:bg-agua" :class="i === activo && 'bg-agua'">
                            <img :src="s.imagen" alt="" class="size-12 bg-agua object-contain mix-blend-multiply">
                            <span class="min-w-0"><span class="block font-rotulo text-lg font-extrabold uppercase leading-tight text-tierra" x-text="s.codigo"></span><span class="block truncate text-sm" x-text="s.nombre"></span></span>
                            <span class="text-right text-sm cifra"><span class="block font-bold" x-text="s.precio"></span><span class="block" :class="s.stock > 0 ? 'text-tierra' : 'text-coral-tinta'" x-text="s.stock > 0 ? 'Hay ' + s.stock : 'Agotado'"></span></span>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
        <button class="btn btn-sol !min-h-14 !px-4 sm:!px-6 lg:!min-h-16" type="submit">
            <x-icono nombre="buscar" class="size-6 sm:hidden" /><span class="sr-only sm:not-sr-only">Buscar</span>
        </button>
    </form>

    <p class="mt-5 flex max-w-3xl flex-wrap items-center gap-x-2 gap-y-1 text-[0.9375rem] text-white/80">
        <x-icono nombre="check-circulo" class="size-4 text-sol" />
        <span>Agente autorizado exclusivo en Venezuela:</span>
        @foreach(['Marchesan Tatu', 'Colombo Aemco', 'Vicon', 'Lumagi'] as $marca)
            <a href="{{ route('catalogo.index', ['marca' => $marca]) }}" class="font-bold text-white hover:text-sol">{{ $marca }}</a>@unless($loop->last)<span aria-hidden="true">·</span>@endunless
        @endforeach
        <a href="{{ route('nosotros') }}" class="ml-1 font-semibold text-sol underline">Conócenos</a>
    </p>
@endsection

@section('contenido')
{{-- lg: en escritorio el panel es una barra lateral siempre visible; en el celular es una hoja que se abre --}}
<div x-data="{ panel: false, lg: window.matchMedia('(min-width: 1024px)').matches }"
     x-init="window.matchMedia('(min-width: 1024px)').addEventListener('change', e => lg = e.matches)"
     @keydown.escape.window="panel = false">


    <div class="grid grid-cols-1 gap-8 lg:grid-cols-[16rem_minmax(0,1fr)]">
        {{-- Panel "Ajustar búsqueda": hoja inferior en el celular, barra lateral desde escritorio --}}
        <div x-show="panel" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-tinta/50 lg:hidden" @click="panel = false"></div>
        <aside id="panel-filtros" aria-labelledby="titulo-filtros" :inert="!panel && !lg"
               class="fixed inset-x-0 bottom-0 z-50 max-h-[85dvh] translate-y-full overflow-y-auto bg-white p-6 transition-transform duration-300 ease-[var(--ease-salida)] lg:static lg:z-auto lg:max-h-none lg:translate-y-0 lg:overflow-visible lg:bg-transparent lg:p-0"
               :class="panel && '!translate-y-0'">
            <div class="franja franja-fina absolute inset-x-0 top-0 lg:hidden" aria-hidden="true"></div>
            <div class="space-y-5 lg:sticky lg:top-28">
                <div class="flex items-center justify-between">
                    <h2 id="titulo-filtros" class="seccion-titulo">Ajustar búsqueda</h2>
                    <button type="button" class="grid size-11 place-items-center hover:bg-agua lg:hidden" @click="panel = false" aria-label="Cerrar"><x-icono nombre="x" /></button>
                </div>
                <div>
                    <label for="categoria" class="etiqueta">Tipo de repuesto</label>
                    <select id="categoria" name="categoria" form="filtros" class="campo">
                        <option value="">Todos</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected($categoriaActual?->id === $categoria->id)>{{ $categoria->nombre }} ({{ $categoria->productos_count }})</option>
                        @endforeach
                    </select>
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
            <div class="flex flex-wrap items-center gap-3 border-b-2 border-tinta pb-3">
                <p id="resultados" aria-live="polite" class="text-lg">
                    <strong class="font-rotulo text-2xl cifra">{{ $productos->total() }}</strong> {{ $productos->total() === 1 ? 'producto' : 'productos' }}@if($categoriaActual) en <strong>{{ $categoriaActual->nombre }}</strong>@endif
                </p>
                <form method="GET" action="{{ route('catalogo.index') }}" class="ml-auto flex items-center gap-2">
                    @foreach(\Illuminate\Support\Arr::except(request()->query(), ['orden', 'page']) as $nombre => $valor)
                        @if(is_string($valor))<input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">@endif
                    @endforeach
                    <label for="orden" class="hidden text-sm font-semibold text-tinta-suave sm:block">Ordenar</label>
                    <select id="orden" name="orden" class="campo !min-h-11 !w-auto !py-0 !text-[0.9375rem]" x-data @change="$el.form.submit()">
                        @foreach(\App\Http\Controllers\CatalogoController::ORDENES as $valor => $texto)
                            <option value="{{ $valor }}" @selected(($filtros['orden'] ?? 'disponibles') === $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                    <noscript><button class="btn btn-linea btn-mini">Aplicar</button></noscript>
                </form>
                {{-- "Ajustar", no "Filtros": la empresa tiene una categoría llamada Filtros --}}
                <button type="button" class="btn btn-linea btn-mini lg:hidden" @click="panel = true" aria-controls="panel-filtros" :aria-expanded="panel">
                    <x-icono nombre="deslizadores" class="size-4" /> Ajustar
                    @if(count($filtrosPanel))<span class="ruedo size-6 !border-2 text-xs">{{ count($filtrosPanel) }}<span class="sr-only"> {{ count($filtrosPanel) === 1 ? 'ajuste activo' : 'ajustes activos' }}</span></span>@endif
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
                <div class="lobulo panel-tapa mt-6 px-6 py-10 sm:px-10">
                    <h2 class="titular text-4xl">No encontramos ese repuesto</h2>
                    <p class="mt-3 max-w-xl text-tinta-suave">Revisa el código o prueba con el modelo de tu maquinaria. También puede que lo tengamos y aún no esté publicado.</p>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <a href="{{ route('catalogo.index') }}" class="btn btn-linea">Ver todo el catálogo</a>
                        @if($wa = \App\Support\Contacto::whatsapp('Hola, busco el repuesto: ' . ($filtros['q'] ?? '')))
                            <a href="{{ $wa }}" class="btn btn-sol" target="_blank" rel="noopener"><x-icono nombre="whatsapp" /> Preguntar por WhatsApp</a>
                        @endif
                    </div>
                </div>
            @else
                <ul class="mt-6 grid gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3" role="list">
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

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    // Sugerencias del buscador: consulta al escribir 2 letras o más; Enter sin elegir busca normal
    function sugerencias(url) {
        return {
            q: document.getElementById('q')?.value ?? '', items: [], abierto: false, activo: -1, control: null,
            async buscar() {
                this.activo = -1;
                if (this.q.trim().length < 2) { this.items = []; this.abierto = false; return; }
                this.control?.abort();
                this.control = new AbortController();
                try {
                    const r = await fetch(url + '?q=' + encodeURIComponent(this.q.trim()), { headers: { Accept: 'application/json' }, signal: this.control.signal });
                    this.items = r.ok ? await r.json() : [];
                    this.abierto = this.items.length > 0;
                } catch (e) { /* búsqueda anterior cancelada o sin señal: se ignora */ }
            },
            mover(paso) {
                if (!this.items.length) return;
                this.abierto = true;
                this.activo = (this.activo + paso + this.items.length) % this.items.length;
            },
        };
    }
</script>
@endpush
