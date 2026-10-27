{{-- Pestañas de estado como cápsulas. $opciones: [valor => texto]; $actual: valor activo; $ruta: nombre de ruta; $param: parámetro de la URL --}}
@props(['opciones', 'actual', 'ruta', 'param' => 'estado'])
<nav class="-mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Filtrar por estado">
    <ul class="flex gap-2">
        @foreach($opciones as $valor => $texto)
            <li><a href="{{ route($ruta, [$param => $valor]) }}" class="capsula !pr-4" @if($actual === $valor) aria-current="page" @endif>{{ $texto }}</a></li>
        @endforeach
    </ul>
</nav>
