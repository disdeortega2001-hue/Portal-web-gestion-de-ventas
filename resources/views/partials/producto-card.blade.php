{{--
    Tarjeta del catálogo: panel en verde Rialto claro y semitransparente que se eleva al pasar el cursor. El círculo azul lleva la existencia,
    a la vista de un vistazo. Celular: horizontal (foto a la izquierda). Desde 640 px: vertical, en rejilla.
--}}
@php($agotado = $producto->estaAgotado())
<article class="tarjeta-producto tarjeta-viva group grid h-full grid-cols-[7rem_1fr] gap-x-4 overflow-hidden p-3 sm:grid-cols-1 sm:gap-x-0 sm:p-0" id="producto-{{ $producto->id }}">
    <a href="{{ route('catalogo.show', $producto) }}" tabindex="-1" aria-hidden="true"
       class="foto-producto relative row-span-2 block aspect-square self-start rounded-[6px] sm:row-span-1 sm:rounded-none sm:aspect-[4/3]">
        <img src="{{ $producto->urlImagen('mini') }}"
             @if($srcset = $producto->srcsetImagen()) srcset="{{ $srcset }}" sizes="(max-width: 639px) 112px, (max-width: 1023px) 45vw, 300px" @endif
             alt="" width="240" height="180" loading="lazy" decoding="async"
             @class(['size-full mix-blend-multiply transition-transform duration-500 ease-[var(--ease-salida)] group-hover:scale-[1.04]', 'object-contain p-2 sm:p-5' => $producto->imagen, 'object-contain p-4 sm:p-8 opacity-70' => ! $producto->imagen])>
        {{-- Cuántas hay, en un círculo sobre la foto --}}
        <span @class(['ruedo absolute -bottom-3 left-2 size-11 text-lg sm:-bottom-5 sm:left-4 sm:size-12 sm:text-lg', '!border-coral text-coral-tinta' => $agotado])>{{ $producto->stock }}</span>
    </a>

    <div class="flex min-w-0 flex-col gap-1 sm:px-4 sm:pt-8">
        <span class="text-lg font-black uppercase leading-none tracking-[0.02em] text-tierra cifra sm:text-xl">{{ $producto->codigo_fabrica }}</span>
        <h3 class="text-[1.0625rem] font-semibold leading-snug"><a href="{{ route('catalogo.show', $producto) }}" class="hover:underline">{{ $producto->nombre }}</a></h3>
        <span class="apoyo text-sm">{{ $producto->marca }} · {{ $producto->categoria->nombre }}</span>
        {{-- Para qué sirve, en dos líneas (completo en la ficha) --}}
        @if($producto->descripcion)<p class="line-clamp-2 text-sm text-tinta-suave" title="{{ $producto->descripcion }}">{{ $producto->descripcion }}</p>@endif
        <div class="mt-1">@include('partials.stock', ['producto' => $producto])</div>
    </div>

    <div class="col-start-2 mt-3 flex flex-wrap items-end justify-between gap-x-3 gap-y-2 border-t border-linea pt-3 sm:col-start-1 sm:mx-4 sm:mb-4 sm:mt-auto sm:pt-4">
        <div class="cifra">
            <div class="text-2xl font-black leading-none text-tierra-oscura">@usd($producto->precio) <small class="font-sans text-sm font-semibold text-tinta-suave">+ IVA</small></div>
            <div class="mt-1 text-sm font-semibold text-tinta-suave">@bs($producto->precioBolivares())</div>
        </div>

        @if($agotado)
            @if($wa = \App\Support\Contacto::whatsapp("Hola, ¿cuándo llega el repuesto {$producto->codigo_fabrica} ({$producto->nombre})?"))
                <a href="{{ $wa }}" class="btn btn-linea btn-mini" target="_blank" rel="noopener">
                    <x-icono nombre="whatsapp" class="size-4" /> Preguntar<span class="hidden xl:inline"> cuándo llega</span>
                </a>
            @else
                <button class="btn btn-linea btn-mini" disabled>Agotado</button>
            @endif
        @else
            <form method="POST" action="{{ route('carrito.agregar', $producto) }}" data-agregar>
                @csrf
                <input type="hidden" name="cantidad" value="1">
                <button class="btn btn-sol btn-mini" data-cargando="Agregando…" aria-label="Agregar {{ $producto->nombre }} a la cotización">
                    <x-icono nombre="mas" class="size-4" /> Agregar
                </button>
            </form>
        @endif
    </div>
</article>
