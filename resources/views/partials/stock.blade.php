{{--
    Insignia de stock en la escala fija de tres niveles. Uso: @include('partials.stock', ['producto' => $producto])
    El cliente lee "Quedan 3"; el personal (gestión/admin) lee "Bajo stock: 3", que activa una acción interna.
--}}
@php
    $personal = request()->routeIs('gestion.*', 'admin.*');
    $estado = $personal ? $producto->estadoStock() : $producto->estadoDisponible();
    $unidades = $personal ? $producto->stock : $producto->disponible();
    $reservado = $personal ? $producto->reservado() : 0;
@endphp
<span class="estado estado-{{ $estado }} cifra" data-stock-insignia>
    @switch($estado)
        @case('agotado') Agotado @break
        @case('bajo') {{ $personal ? 'Bajo stock: ' . $unidades : 'Quedan ' . $unidades }} @break
        @default {{ $personal ? 'Disponible: ' : 'Disponibles: ' }}{{ $unidades }}
    @endswitch
</span>
@if($reservado > 0)<span class="apoyo mt-1 block text-right text-sm cifra">{{ $reservado }} {{ $reservado === 1 ? 'reservada' : 'reservadas' }}</span>@endif
