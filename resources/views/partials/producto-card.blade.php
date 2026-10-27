{{--
    Tarjeta del catálogo. Celular: horizontal (imagen en lóbulo a la izquierda). Desde 640 px: vertical, en rejilla.
--}}
<article class="lobulo group grid h-full grid-cols-[6.5rem_1fr] gap-x-4 p-3 sm:grid-cols-1 sm:gap-x-0 sm:p-4" id="producto-{{ $producto->id }}">
    <a href="{{ route('catalogo.show', $producto) }}" tabindex="-1" aria-hidden="true"
       @class(['row-span-2 block aspect-square self-start sm:row-span-1 sm:aspect-[4/3]', 'bg-sabana-clara img-lobulo' => ! $producto->imagen])>
        <img src="{{ $producto->urlImagen('mini') }}"
             @if($srcset = $producto->srcsetImagen()) srcset="{{ $srcset }}" sizes="(max-width: 639px) 104px, (max-width: 1023px) 45vw, 300px" @endif
             alt="" width="240" height="180" loading="lazy" decoding="async"
             @class(['size-full transition-transform duration-500 ease-[var(--ease-rebote)] group-hover:scale-[1.03]', 'object-cover img-lobulo' => $producto->imagen, 'object-contain p-4 sm:p-6 opacity-80' => ! $producto->imagen])>
    </a>

    <div class="flex min-w-0 flex-col gap-1 sm:mt-4">
        <span class="codigo">{{ $producto->codigo_fabrica }}</span>
        <h3 class="text-[1.0625rem] font-bold leading-snug"><a href="{{ route('catalogo.show', $producto) }}" class="hover:underline">{{ $producto->nombre }}</a></h3>
        <span class="apoyo text-sm">{{ $producto->marca }} · {{ $producto->categoria->nombre }}</span>
        <div class="mt-1">@include('partials.stock', ['producto' => $producto])</div>
    </div>

    <div class="col-start-2 mt-3 flex flex-wrap items-end justify-between gap-x-3 gap-y-2 border-t border-dashed border-linea pt-3 sm:col-start-1 sm:mt-auto sm:pt-4">
        <div class="cifra">
            <div class="text-xl font-extrabold tracking-tight">@usd($producto->precio) <small class="text-sm font-semibold text-tinta-suave">+ IVA</small></div>
            <div class="text-sm font-semibold text-tinta">@bs($producto->precioBolivares())</div>
        </div>

        @if($producto->estaAgotado())
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
