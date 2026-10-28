@extends('layouts.app')
@section('titulo', 'Nuestra historia')

@php
    // Línea de vida (reseña histórica del Capítulo I). Cuando se conozcan los años exactos de la
    // importación desde Brasil y de la representación exclusiva, reemplaza «Oficio» y «Alianzas» por el año.
    $hitos = [
        ['1963', 'Fundación', 'Nace Inversiones Campagna, una empresa familiar al servicio del productor llanero.'],
        ['Oficio', 'Importación desde Brasil', 'Traemos repuestos y equipos difíciles de conseguir en el país.'],
        ['Alianzas', 'Agente autorizado exclusivo', 'Representamos en Venezuela a Marchesan Tatu, Colombo Aemco, Vicon y Lumagi.'],
        ['2024', 'Ahora somos Rialto 7', 'Nuevo nombre e imagen, con los valores de siempre.'],
        [(string) now()->year, 'Catálogo en línea', 'Existencias, precios y cotizaciones desde la finca.'],
    ];
    // Pilares: los valores que la reseña atribuye a la empresa desde su fundación
    $pilares = [
        ['escudo', 'Confianza', 'Más de seis décadas en manos de la misma familia.'],
        ['usuarios', 'Atención cercana', 'Te atiende gente del llano que conoce tu trabajo.'],
        ['caja', 'Conocimiento de cada pieza', 'Te ayudamos a dar con el repuesto correcto.'],
    ];
    $cifras = [
        [$anios, '+', 'años de trayectoria'],
        [count($marcas), '', 'marcas exclusivas'],
        [$totalProductos, '', 'productos en línea'],
        ['24/7', '', 'catálogo abierto'],
    ];
@endphp

@section('cabecera')
    <div class="flex items-start gap-6">
        <span class="ruedo hidden size-24 text-5xl sm:grid" aria-hidden="true">7</span>
        <div>
            <h1 class="titular max-w-3xl text-5xl sm:text-6xl lg:text-7xl">Desde 1963 junto al <span class="text-sol">productor llanero</span></h1>
            <p class="mt-3 max-w-2xl text-lg text-white/80">Una empresa familiar de Valle de la Pascua: maquinaria agrícola y repuestos para que el campo siga trabajando.</p>
        </div>
    </div>

    {{-- Las cifras como la pizarra de boxes: números grandes en celdas rectas --}}
    <dl class="mt-8 grid max-w-4xl grid-cols-2 border-l border-t border-white/20 sm:grid-cols-4 lg:mt-10">
        @foreach($cifras as [$valor, $extra, $etiqueta])
            <div class="border-b border-r border-white/20 px-4 py-4">
                <dt class="sr-only">{{ $etiqueta }}</dt>
                <dd class="titular cifra text-5xl">@if(is_int($valor))<span data-contar="{{ $valor }}">{{ $valor }}</span>@else{{ $valor }}@endif<span class="text-sol">{{ $extra }}</span></dd>
                <dd class="mt-1.5 text-sm font-semibold text-white/75">{{ $etiqueta }}</dd>
            </div>
        @endforeach
    </dl>
@endsection

@section('contenido')
{{-- Resumen: quiénes somos, en dos frases, junto a la fachada de la sede --}}
<section aria-labelledby="t-historia" class="grid grid-cols-1 items-center gap-10 lg:grid-cols-2 lg:gap-16">
    <figure class="relative" data-revelar>
        <img src="{{ asset('img/portada/sede-fachada.jpg') }}" alt="Fachada del Edificio Campagna, sede de Inversiones Rialto 7 en Valle de la Pascua" width="1600" height="1000" class="aspect-[16/10] w-full rounded-[10px] object-cover" loading="lazy">
        <figcaption class="absolute -bottom-5 left-5 rounded-[8px] bg-tierra-oscura px-5 py-3 text-sm font-bold text-white shadow-[0_18px_40px_-20px_rgb(5_43_112/0.6)]">Edificio Campagna · Valle de la Pascua</figcaption>
    </figure>
    <div data-revelar style="--orden:1">
        <h2 id="t-historia" class="titular subrayado text-4xl text-tierra-oscura sm:text-5xl">Una empresa <em>familiar</em> del llano guariqueño</h2>
        <p class="mt-6 text-lg text-tinta-suave">Empezamos en 1963 como Inversiones Campagna. Hoy, como Inversiones Rialto 7 C.A., seguimos en manos de la familia fundadora y somos el único agente autorizado en Venezuela de cuatro marcas de maquinaria y repuestos.</p>
    </div>
</section>

{{-- Línea de vida: el año grande arriba, la pista de años y tarjetas que se deslizan --}}
<section aria-labelledby="t-trayectoria" class="mt-16 lg:mt-20"
         x-data="{
            i: 0, n: {{ count($hitos) }},
            ir(k) {
                this.i = (k + this.n) % this.n;
                const pista = this.$refs.pista, tarjeta = pista.children[this.i];
                const destino = Math.min(tarjeta.offsetLeft - pista.offsetLeft, pista.scrollWidth - pista.clientWidth);
                pista.scrollTo({ left: destino, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                // Si el navegador no anima el desplazamiento (pestaña en segundo plano, ahorro de energía), salta directo
                setTimeout(() => { if (Math.abs(pista.scrollLeft - destino) > 4) pista.scrollLeft = destino; }, 600);
            },
            // Al deslizar con el dedo: si la tarjeta activa salió de la vista, se activa la primera visible
            seguir() {
                const pista = this.$refs.pista, x = (t) => t.offsetLeft - pista.offsetLeft;
                const activa = pista.children[this.i];
                if (x(activa) >= pista.scrollLeft - 8 && x(activa) + activa.offsetWidth <= pista.scrollLeft + pista.clientWidth + 8) return;
                const k = [...pista.children].findIndex(t => x(t) >= pista.scrollLeft - 8);
                if (k >= 0) this.i = k;
            }
         }"
         @keydown.arrow-right.prevent="ir(i + 1)" @keydown.arrow-left.prevent="ir(i - 1)">
    <div class="flex flex-wrap items-end justify-between gap-6" data-revelar>
        <h2 id="t-trayectoria" class="titular subrayado text-4xl text-tierra-oscura sm:text-5xl">Nuestra trayectoria</h2>
        <div class="flex items-center gap-3">
            <div class="lobulo panel-tapa px-5 pb-3 pt-5" aria-hidden="true">
                <p class="titular cifra text-5xl text-tierra"><span x-text="@js(array_column($hitos, 0))[i]">{{ $hitos[0][0] }}</span> <span class="text-2xl text-tinta-suave">/ {{ now()->year }}</span></p>
                <p class="mt-1 text-sm font-semibold text-tinta-suave">Año actual</p>
            </div>
            <button type="button" x-cloak class="grid size-14 place-items-center border-[1.5px] border-tinta bg-white hover:bg-tinta hover:text-white" @click="ir(i - 1)" aria-label="Momento anterior"><x-icono nombre="flecha-izq" /></button>
            <button type="button" x-cloak class="grid size-14 place-items-center bg-tierra text-white hover:bg-tierra-oscura" @click="ir(i + 1)" aria-label="Momento siguiente"><x-icono nombre="flecha" /></button>
        </div>
    </div>

    {{-- Pista de años: la línea se llena hasta el momento activo --}}
    <div class="relative mt-8 hidden sm:block" x-cloak>
        <div class="absolute inset-x-2 top-[0.6875rem] h-[3px] bg-linea" aria-hidden="true"></div>
        <div class="absolute left-2 top-[0.6875rem] h-[3px] bg-tierra transition-[width] duration-500 ease-[var(--ease-salida)]"
             :style="`width: calc((100% - 1rem) * ${i / (n - 1)})`" aria-hidden="true"></div>
        <ol class="relative flex justify-between">
            @foreach($hitos as $k => [$cuando])
                <li class="flex flex-col items-center">
                    <button type="button" class="grid size-6 place-items-center rounded-full border-[3px] bg-agua transition-transform duration-300"
                            :class="i >= {{ $k }} ? 'border-tierra' : 'border-linea'" @click="ir({{ $k }})" aria-label="Ir a {{ $cuando }}">
                        <span class="size-2.5 rounded-full" :class="i === {{ $k }} ? 'bg-sol' : (i > {{ $k }} ? 'bg-tierra' : 'bg-transparent')"></span>
                    </button>
                    <span class="cifra mt-2 text-xs font-bold" :class="i === {{ $k }} ? 'text-tierra' : 'text-tinta-suave'">{{ $cuando }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Tarjetas: se deslizan con el dedo, las flechas o los años --}}
    <ol x-ref="pista" @scroll.debounce.120ms="seguir()" tabindex="0" aria-label="Momentos de nuestra historia"
        class="-mx-4 mt-6 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-px-4 px-4 pb-4 [scrollbar-width:none] sm:-mx-6 sm:scroll-px-6 sm:px-6 lg:mx-0 lg:scroll-px-0 lg:px-0 [&::-webkit-scrollbar]:hidden">
        @foreach($hitos as $k => [$cuando, $titulo, $texto])
            <li class="lobulo panel-tapa flex w-[82%] shrink-0 snap-start flex-col p-6 pt-8 transition-[border-color,box-shadow] duration-300 sm:w-[46%] lg:w-[calc((100%-2rem)/3)]"
                :class="i === {{ $k }} && '!border-tierra shadow-[0_0_0_1px_var(--color-tierra)]'">
                <span class="font-rotulo text-lg self-start px-3 py-1 font-extrabold cifra transition-colors duration-300"
                      :class="i === {{ $k }} ? 'bg-sol text-tinta' : 'bg-agua-honda text-tinta-suave'">{{ $cuando }}</span>
                <h3 class="mt-4 font-rotulo text-2xl font-extrabold uppercase leading-tight">{{ $titulo }}</h3>
                <p class="mt-2 text-tinta-suave">{{ $texto }}</p>
                <div class="mt-auto flex items-center gap-3 pt-6">
                    <span class="h-[3px] flex-1 overflow-hidden bg-agua-honda"><span class="block h-full bg-tierra transition-[width] duration-500" :style="`width: ${i === {{ $k }} ? 100 : 0}%`"></span></span>
                    <span class="cifra text-xs font-bold text-tinta-suave">{{ sprintf('%02d', $k + 1) }}</span>
                </div>
            </li>
        @endforeach
    </ol>

    <div class="mt-2 flex items-center justify-center gap-4" x-cloak>
        <div class="flex gap-1.5">
            @foreach($hitos as $k => $hito)
                <button type="button" class="h-2.5 transition-all duration-300" :class="i === {{ $k }} ? 'w-7 bg-tierra' : 'w-2 bg-linea hover:bg-tinta-suave'" @click="ir({{ $k }})" aria-label="Momento {{ $k + 1 }}"></button>
            @endforeach
        </div>
        <span class="cifra border border-linea bg-white px-3 py-1 font-rotulo text-sm font-bold text-tinta-suave" aria-live="polite"><span class="text-tierra" x-text="String(i + 1).padStart(2, '0')">01</span> | {{ sprintf('%02d', count($hitos)) }}</span>
    </div>
</section>

{{-- Pilares, junto a la foto del equipo --}}
<section aria-labelledby="t-pilares" class="mt-16 grid grid-cols-1 items-center gap-10 lg:mt-24 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:gap-16">
    <div>
        <h2 id="t-pilares" class="titular subrayado text-4xl text-tierra-oscura sm:text-5xl" data-revelar>Lo que nos <em>define</em></h2>
        <ul class="mt-8 grid gap-4">
            @foreach($pilares as $k => [$icono, $titulo, $texto])
                <li class="lobulo tarjeta-viva flex items-start gap-5 p-5 sm:p-6" data-revelar style="--orden:{{ $k }}">
                    <span class="ruedo size-14 shrink-0"><x-icono :nombre="$icono" class="size-6 text-tierra" /></span>
                    <div>
                        <h3 class="text-xl font-black uppercase leading-tight">{{ $titulo }}</h3>
                        <p class="mt-1 text-tinta-suave">{{ $texto }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
    <figure class="relative" data-revelar style="--orden:1">
        <img src="{{ asset('img/portada/sede-equipo.jpg') }}" alt="El equipo de Inversiones Rialto 7 celebrando el 63 aniversario en la tienda" width="1000" height="750" class="w-full rounded-[10px] object-cover" loading="lazy">
        <figcaption class="absolute -bottom-6 right-6 rounded-[8px] bg-sol px-6 py-4 text-tierra-oscura shadow-[0_18px_40px_-20px_rgb(5_43_112/0.6)]">
            <span class="block text-4xl font-black leading-none cifra"><span data-contar="{{ $anios }}">{{ $anios }}</span></span>
            <span class="text-xs font-extrabold uppercase tracking-[0.08em]">años en la misma familia</span>
        </figcaption>
    </figure>
</section>

{{-- Marcas de representación exclusiva --}}
<section aria-labelledby="t-marcas" class="mt-20 lg:mt-24">
    <div class="flex flex-wrap items-end justify-between gap-4" data-revelar>
        <h2 id="t-marcas" class="titular subrayado text-4xl text-tierra-oscura sm:text-5xl">Agente autorizado <em>exclusivo</em> en Venezuela</h2>
        <p class="max-w-sm text-tinta-suave">Repuestos y equipos originales, con el respaldo directo del fabricante.</p>
    </div>

    <ul class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        @foreach($marcas as $marca)
            <li class="lobulo tarjeta-viva flex flex-col overflow-hidden p-3.5 sm:p-5" data-revelar style="--orden:{{ $loop->index }}">
                <div class="overflow-hidden rounded-[3px] ring-1 ring-tinta/10">
                    <img src="{{ asset($marca['logo']) }}" alt="Logo de {{ $marca['nombre'] }}"
                         @class(['h-20 w-full sm:h-28', 'bg-[#173C8A] object-contain' => $marca['nombre'] === 'Marchesan Tatu', 'bg-white object-cover' => $marca['nombre'] !== 'Marchesan Tatu'])>
                </div>
                <p class="mt-4 font-rotulo text-xl font-extrabold uppercase leading-tight sm:mt-5 sm:text-2xl">{{ $marca['nombre'] }}</p>
                @if($marca['categorias'])
                    <p class="mt-1 text-sm text-tinta-suave">{{ implode(' · ', $marca['categorias']) }}</p>
                @endif
                <a href="{{ route('catalogo.index', ['marca' => $marca['nombre']]) }}" class="enlace mt-auto inline-flex items-center gap-1.5 self-start pt-4 text-sm font-bold sm:pt-5 sm:text-base">
                    @if($marca['productos'])Ver {{ $marca['productos'] }} {{ $marca['productos'] === 1 ? 'producto' : 'productos' }}@else Ver productos @endif
                    <x-icono nombre="flecha" class="size-4" />
                </a>
            </li>
        @endforeach
    </ul>
</section>

{{-- Dónde estamos --}}
<section aria-labelledby="t-visitanos" class="sobre-azul relative mt-16 flex flex-col gap-6 overflow-hidden rounded-[10px] bg-tierra-oscura p-7 text-white sm:flex-row sm:items-center sm:justify-between lg:mt-20 lg:p-10" data-revelar>
    <x-arcos class="pointer-events-none absolute inset-y-0 right-0 h-full w-[28rem]" />
    <div class="relative">
        <h2 id="t-visitanos" class="titular text-3xl">Visítanos o consulta desde tu finca</h2>
        <p class="mt-2 flex items-start gap-1.5 text-white/80"><x-icono nombre="ubicacion" class="mt-0.5 size-5 shrink-0" /> Av. Las Industrias, Edificio Campagna, Valle de la Pascua, Guárico · <span class="whitespace-nowrap">RIF J-50668510-8</span></p>
    </div>
    <div class="relative flex shrink-0 flex-wrap gap-2">
        @if($wa = \App\Support\Contacto::whatsapp('Hola, vi el portal de Rialto 7 y quiero hacer una consulta.'))
            <a href="{{ $wa }}" class="btn btn-linea-clara" target="_blank" rel="noopener"><x-icono nombre="whatsapp" /> Escríbenos</a>
        @endif
        <a href="{{ route('catalogo.index') }}" class="btn btn-sol">Ver el catálogo <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></a>
    </div>
</section>
@endsection
