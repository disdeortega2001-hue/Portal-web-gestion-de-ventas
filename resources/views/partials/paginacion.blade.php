@if ($paginator->hasPages())
    <nav class="mt-8 flex flex-wrap items-center justify-between gap-3" aria-label="Paginación">
        <p class="apoyo text-sm cifra">
            @if($paginator->firstItem()) {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} de {{ $paginator->total() }} @endif
        </p>
        <div class="paginas">
            @if ($paginator->onFirstPage())
                <span class="inactiva" aria-hidden="true"><x-icono nombre="flecha-izq" class="size-4" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior"><x-icono nombre="flecha-izq" class="size-4" /></a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inactiva">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente"><x-icono nombre="flecha" class="size-4" /></a>
            @else
                <span class="inactiva" aria-hidden="true"><x-icono nombre="flecha" class="size-4" /></span>
            @endif
        </div>
    </nav>
@endif
