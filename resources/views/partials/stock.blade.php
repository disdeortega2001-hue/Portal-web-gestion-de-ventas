{{--
    Insignia de stock en la escala fija de tres niveles. Uso: @include('partials.stock', ['producto' => $producto])
    El cliente lee "Quedan 3"; el personal (gestión/admin) lee "Bajo stock: 3", que activa una acción interna.
--}}
@php
    $estado = $producto->estadoStock();
    $personal = request()->routeIs('gestion.*', 'admin.*');
@endphp
<span class="estado estado-{{ $estado }} cifra" data-stock-insignia>
    @switch($estado)
        @case('agotado') Agotado @break
        @case('bajo') {{ $personal ? 'Bajo stock: ' . $producto->stock : 'Quedan ' . $producto->stock }} @break
        @default {{ $personal ? 'Disponible: ' : 'Disponibles: ' }}{{ $producto->stock }}
    @endswitch
</span>
