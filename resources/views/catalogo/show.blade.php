@extends('layouts.app')
@section('titulo', $producto->nombre)
@section('descripcion', "{$producto->nombre} ({$producto->codigo_fabrica}), {$producto->marca}. " . ($producto->stock > 0 ? "Disponible: {$producto->stock}." : 'Agotado por ahora.') . ' Precio en USD y Bs. y cotización en línea en Inversiones Rialto 7.')
@section('imagen_og', $producto->urlImagen())
@section('tipo_og', 'product')

@push('meta')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $producto->nombre,
    'sku' => $producto->codigo_fabrica,
    'mpn' => $producto->codigo_fabrica,
    'brand' => ['@type' => 'Brand', 'name' => $producto->marca],
    'category' => $producto->categoria->nombre,
    'description' => $producto->descripcion,
    'image' => $producto->urlImagen(),
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'USD',
        'price' => number_format((float) $producto->precio, 2, '.', ''),
        'availability' => $producto->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => route('catalogo.show', $producto),
        'seller' => ['@type' => 'Organization', 'name' => 'Inversiones Rialto 7 C.A.'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('cabecera')
    <nav aria-label="Ruta" class="text-[0.9375rem] font-semibold text-white/75">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li><a href="{{ route('catalogo.index') }}" class="hover:text-white hover:underline">Catálogo</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('catalogo.index', ['categoria' => $producto->id_categoria]) }}" class="hover:text-white hover:underline">{{ $producto->categoria->nombre }}</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="text-white">{{ $producto->codigo_fabrica }}</li>
        </ol>
    </nav>
    <h1 class="titular mt-4 max-w-4xl text-5xl sm:text-6xl">{{ $producto->nombre }}</h1>
    <p class="mt-3 text-lg text-white/80">{{ $producto->marca }} · {{ $producto->categoria->nombre }}</p>
@endsection

@section('contenido')
@php($compartir = \App\Support\Contacto::compartir("{$producto->nombre} ({$producto->codigo_fabrica}) en Inversiones Rialto 7: " . route('catalogo.show', $producto)))
<div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:gap-12">
    {{-- La foto en su box, con el número de carrera: cuántas hay --}}
    <div class="lobulo panel-tapa relative self-start">
        <div class="mt-1.5 aspect-[4/3] bg-agua">
            <img src="{{ $producto->urlImagen() }}" alt="{{ $producto->nombre }}" width="800" height="600" decoding="async"
                 @class(['size-full mix-blend-multiply', 'object-contain p-6' => $producto->imagen, 'object-contain p-16 opacity-70' => ! $producto->imagen])>
        </div>
        <span @class(['ruedo absolute right-5 top-7 size-20 text-3xl', '!border-coral text-coral-tinta' => $producto->estaAgotado()]) aria-hidden="true">{{ $producto->stock }}</span>
    </div>

    <div>
        {{-- El código de fábrica es la verdad: grande y copiable --}}
        <div class="flex flex-wrap items-center gap-3" x-data="{ copiado: false }">
            <p class="font-rotulo text-4xl font-extrabold uppercase leading-none text-tierra cifra sm:text-5xl">{{ $producto->codigo_fabrica }}</p>
            <button type="button" class="btn btn-linea btn-mini" @click="navigator.clipboard?.writeText(@js($producto->codigo_fabrica)).then(() => { copiado = true; setTimeout(() => copiado = false, 2000) })">
                <x-icono nombre="check" class="size-4" x-show="copiado" x-cloak /><span x-text="copiado ? 'Copiado' : 'Copiar código'">Copiar código</span>
            </button>
        </div>
        <p class="apoyo mt-2">Código de fábrica</p>

        <div class="mt-5" id="estado-stock">@include('partials.stock', ['producto' => $producto])</div>

        <div class="mt-5 border-y border-linea py-5 cifra">
            <div class="font-rotulo text-5xl font-extrabold leading-none">@usd($producto->precio) <small class="font-sans text-base font-semibold text-tinta-suave">+ IVA</small></div>
            <div class="apoyo mt-2 text-lg">@bs($producto->precioBolivares()) a la tasa del día</div>
        </div>

        <div class="mt-6">
            @if($producto->estaAgotado())
                @if($wa = \App\Support\Contacto::whatsapp("Hola, ¿cuándo llega el repuesto {$producto->codigo_fabrica} ({$producto->nombre})?"))
                    <a href="{{ $wa }}" class="btn btn-sol btn-grande w-full sm:w-auto" target="_blank" rel="noopener"><x-icono nombre="whatsapp" /> Preguntar cuándo llega</a>
                    <p class="apoyo mt-3 text-sm">Está agotado por ahora. Te respondemos por WhatsApp con la fecha estimada.</p>
                @else
                    <button class="btn btn-linea btn-grande w-full sm:w-auto" disabled>Agotado</button>
                    <p class="apoyo mt-3 text-sm">Está agotado por ahora. Vuelve a consultar en unos días.</p>
                @endif
            @else
                <form method="POST" action="{{ route('carrito.agregar', $producto) }}" class="needs-validation" novalidate data-agregar
                      x-data="{ n: 1, max: {{ (int) $producto->stock }} }">
                    @csrf
                    <label for="cantidad" class="etiqueta">Cantidad</label>
                    <div class="flex flex-wrap gap-3">
                        <div class="flex h-14 items-stretch border-[1.5px] border-tinta bg-white">
                            <button type="button" class="grid w-12 place-items-center hover:bg-agua disabled:opacity-40" @click="n = Math.max(1, n - 1)" :disabled="n <= 1" aria-label="Una unidad menos"><x-icono nombre="menos" /></button>
                            <input type="number" id="cantidad" name="cantidad" x-model.number="n" value="1" min="1" max="{{ $producto->stock }}" inputmode="numeric" required
                                   class="w-16 border-x border-linea bg-transparent text-center font-rotulo text-2xl font-extrabold cifra focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                            <button type="button" class="grid w-12 place-items-center hover:bg-agua disabled:opacity-40" @click="n = Math.min(max, n + 1)" :disabled="n >= max" aria-label="Una unidad más"><x-icono nombre="mas" /></button>
                        </div>
                        <button class="btn btn-sol btn-grande !min-h-14 flex-1" data-cargando="Agregando…">Agregar a cotización <span class="chip"><x-icono nombre="mas" class="size-4" /></span></button>
                    </div>
                    <p class="ayuda">Máximo {{ $producto->stock }} {{ $producto->stock === 1 ? 'unidad' : 'unidades' }}.</p>
                </form>
            @endif
            <a href="{{ $compartir }}" target="_blank" rel="noopener" class="enlace mt-4 inline-flex items-center gap-1.5 text-[0.9375rem]"><x-icono nombre="whatsapp" class="size-4" /> Compartir por WhatsApp</a>
        </div>

        <div class="mt-8 grid gap-6 border-t border-linea pt-6 sm:grid-cols-2">
            <section>
                <h2 class="seccion-titulo">Compatibilidad</h2>
                @if($producto->compatibilidad)
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach(explode(',', $producto->compatibilidad) as $modelo)
                            <li class="border border-laguna/40 bg-laguna-clara px-3 py-1.5 text-sm font-bold text-laguna-tinta">{{ trim($modelo) }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="apoyo mt-3">Pregúntanos si sirve para tu equipo antes de cotizar.</p>
                @endif
            </section>
            <section>
                <h2 class="seccion-titulo">Descripción</h2>
                <p class="mt-3 max-w-prose">{!! nl2br(e($producto->descripcion)) !!}</p>
            </section>
        </div>
    </div>
</div>

@if($relacionados->isNotEmpty())
    <section class="mt-16" aria-labelledby="relacionados">
        <h2 id="relacionados" class="titular text-4xl">Otros de {{ $producto->categoria->nombre }}</h2>
        <ul class="mt-6 grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4" role="list">
            @foreach($relacionados as $relacionado)
                @php($relacionado->setRelation('categoria', $producto->categoria))
                <li>@include('partials.producto-card', ['producto' => $relacionado])</li>
            @endforeach
        </ul>
    </section>
@endif
@endsection

@push('scripts')
<script>
    // Stock en tiempo real: consulta cada 30 s, solo con la pestaña visible
    (function () {
        const textos = { disponible: n => 'Disponibles: ' + n, bajo: n => 'Quedan ' + n, agotado: () => 'Agotado' };
        const URL_STOCK = @json(route('catalogo.stock', $producto));
        setInterval(async function () {
            if (document.hidden) return;
            try {
                const r = await fetch(URL_STOCK, { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                const insignia = document.querySelector('#estado-stock [data-stock-insignia]');
                insignia.className = 'estado estado-' + d.estado + ' cifra';
                insignia.textContent = textos[d.estado](d.stock);
                const campo = document.getElementById('cantidad');
                if (campo) campo.max = d.stock;
            } catch (e) { /* sin conexión: se mantiene el último valor */ }
        }, 30000);
    })();
</script>
@endpush
