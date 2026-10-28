{{-- Buscador de los listados del personal. Conserva los filtros indicados en $mantener ([nombre => valor]). --}}
@props(['accion', 'valor' => '', 'placeholder' => 'Buscar', 'mantener' => []])
<form method="GET" action="{{ $accion }}" role="search" class="mb-5 flex max-w-2xl gap-2">
    @foreach($mantener as $nombre => $v)
        @if(filled($v))<input type="hidden" name="{{ $nombre }}" value="{{ $v }}">@endif
    @endforeach
    <label for="q-listado" class="sr-only">{{ $placeholder }}</label>
    <input type="search" id="q-listado" name="q" value="{{ $valor }}" class="campo flex-1" placeholder="{{ $placeholder }}">
    <button class="btn btn-tierra"><x-icono nombre="buscar" class="size-4" /> <span class="hidden sm:inline">Buscar</span></button>
    @if($valor !== '')<a href="{{ $accion }}{{ count(array_filter($mantener)) ? '?' . http_build_query(array_filter($mantener)) : '' }}" class="btn btn-linea">Limpiar</a>@endif
</form>
