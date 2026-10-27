{{--
    Encabezado de página del personal. Uso:
    <x-encabezado titulo="Pedidos" :volver="route(...)" volver-texto="Pedidos"> <x-slot:acciones>…</x-slot:acciones> subtítulo </x-encabezado>
--}}
@props(['titulo', 'volver' => null, 'volverTexto' => null])
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @if($volver)
            <a href="{{ $volver }}" class="mb-2 inline-flex items-center gap-1.5 text-sm font-bold text-tierra hover:underline"><x-icono nombre="flecha-izq" class="size-4" /> {{ $volverTexto }}</a>
        @endif
        <h1 class="titular text-4xl lg:text-[2.75rem]">{{ $titulo }}</h1>
        @if($slot->isNotEmpty())<div class="apoyo mt-2">{{ $slot }}</div>@endif
    </div>
    @isset($acciones)<div class="flex flex-wrap gap-2">{{ $acciones }}</div>@endisset
</div>
