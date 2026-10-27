@extends('layouts.app')
@section('titulo', $producto->nombre)

@section('cabecera')
    <nav aria-label="Ruta" class="text-sm font-semibold text-white/75">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li><a href="{{ route('catalogo.index') }}" class="hover:text-white hover:underline">Catálogo</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('catalogo.index', ['categoria' => $producto->id_categoria]) }}" class="hover:text-white hover:underline">{{ $producto->categoria->nombre }}</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="text-white">{{ $producto->codigo_fabrica }}</li>
        </ol>
    </nav>
@endsection

@section('contenido')
<div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-14">
    <div @class(['aspect-[4/3] self-start', 'bg-sabana-clara img-lobulo' => ! $producto->imagen])>
        <img src="{{ $producto->urlImagen() }}" alt="{{ $producto->nombre }}" width="800" height="600" decoding="async"
             @class(['size-full', 'object-cover img-lobulo' => $producto->imagen, 'object-contain p-12 opacity-80' => ! $producto->imagen])>
    </div>

    <div>
        <h1 class="titular text-4xl sm:text-5xl">{{ $producto->nombre }}</h1>
        <p class="apoyo mt-3 text-lg">{{ $producto->marca }} · {{ $producto->categoria->nombre }} · Código de fábrica <span class="codigo !text-base">{{ $producto->codigo_fabrica }}</span></p>

        <div class="mt-5" id="estado-stock">@include('partials.stock', ['producto' => $producto])</div>

        <div class="mt-5 cifra">
            <div class="titular text-5xl">@usd($producto->precio) <small class="text-base font-semibold tracking-normal text-tinta-suave">+ IVA</small></div>
            <div class="apoyo mt-1">@bs($producto->precioBolivares()) a la tasa del día</div>
        </div>

        <div class="mt-7">
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
                        <div class="flex h-13 items-center rounded-full border-[1.5px] border-linea bg-white p-1">
                            <button type="button" class="grid size-11 place-items-center rounded-full hover:bg-agua disabled:opacity-40" @click="n = Math.max(1, n - 1)" :disabled="n <= 1" aria-label="Una unidad menos"><x-icono nombre="menos" /></button>
                            <input type="number" id="cantidad" name="cantidad" x-model.number="n" value="1" min="1" max="{{ $producto->stock }}" inputmode="numeric" required
                                   class="w-14 border-0 bg-transparent text-center text-lg font-bold cifra focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                            <button type="button" class="grid size-11 place-items-center rounded-full hover:bg-agua disabled:opacity-40" @click="n = Math.min(max, n + 1)" :disabled="n >= max" aria-label="Una unidad más"><x-icono nombre="mas" /></button>
                        </div>
                        <button class="btn btn-sol btn-grande flex-1" data-cargando="Agregando…">Agregar a cotización <span class="chip"><x-icono nombre="mas" class="size-4" /></span></button>
                    </div>
                    <p class="ayuda">Máximo {{ $producto->stock }} {{ $producto->stock === 1 ? 'unidad' : 'unidades' }}.</p>
                </form>
            @endif
        </div>

        <div class="mt-10 grid gap-6 border-t border-linea pt-8 sm:grid-cols-2">
            <section>
                <h2 class="seccion-titulo">Compatibilidad</h2>
                @if($producto->compatibilidad)
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach(explode(',', $producto->compatibilidad) as $modelo)
                            <li class="rounded-full bg-laguna-clara px-3 py-1.5 text-sm font-bold text-laguna-tinta">{{ trim($modelo) }}</li>
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
        <h2 id="relacionados" class="titular text-3xl">Otros de {{ $producto->categoria->nombre }}</h2>
        <ul class="mt-5 grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4" role="list">
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
