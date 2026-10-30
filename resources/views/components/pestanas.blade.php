{{--
    Pestañas de estado: un control segmentado con el color de cada estado y cuántos hay en cada uno.
    $opciones: [valor => texto]; $actual: valor activo; $ruta: nombre de ruta; $param: parámetro de la URL;
    $conteos: [valor => número]; $atencion: estados que esperan acción (su número se resalta);
    la búsqueda escrita (?q=) se conserva al cambiar de pestaña.
--}}
@props(['opciones', 'actual', 'ruta', 'param' => 'estado', 'conteos' => [], 'atencion' => []])
@php($busqueda = trim((string) request('q')))
<nav class="sin-barra -mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Filtrar por estado">
    <ul class="segmentos">
        @foreach($opciones as $valor => $texto)
            @php($n = $conteos[$valor] ?? null)
            <li>
                <a href="{{ route($ruta, array_filter([$param => $valor, 'q' => $busqueda])) }}" class="segmento" data-estado="{{ $valor }}"
                   @if($actual === $valor) aria-current="page" @endif>
                    <span class="punto" aria-hidden="true"></span>
                    {{ $texto }}
                    @if($n !== null)
                        <span @class(['conteo cifra', 'conteo-atencion' => $n > 0 && in_array($valor, $atencion, true)])
                              aria-label="{{ $n }} {{ $n === 1 ? 'registro' : 'registros' }}">{{ $n }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
