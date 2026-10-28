@extends('layouts.app')
@section('titulo', 'Revisar carga de productos')

@section('contenido')
<x-encabezado titulo="Revisar la carga" :volver="route('admin.importacion.create')" volver-texto="Cargar desde Excel">
    @if($errores) El archivo tiene errores y no se cargó nada. Corrígelos en el Excel y súbelo otra vez. @else Todo está correcto. Revisa los productos y confirma. @endif
</x-encabezado>

@if($errores)
    <div class="aviso aviso-error" role="alert">
        <x-icono nombre="alerta" class="mt-0.5" />
        <div><strong>{{ count($errores) }} {{ count($errores) === 1 ? 'fila con error' : 'filas con errores' }}:</strong>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach($errores as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    </div>
    <a href="{{ route('admin.importacion.create') }}" class="btn btn-sol mt-5">Subir el archivo corregido</a>
@else
    <div class="lobulo overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Fila</th><th>Código</th><th>Nombre</th><th>Categoría</th><th>Marca</th><th class="!text-right">USD</th><th class="!text-right">Existencia</th><th>Foto</th></tr></thead>
            <tbody>
            @foreach($filas as $f)
                <tr>
                    <td class="num">{{ $f['fila'] }}</td>
                    <td class="codigo">{{ $f['codigo_fabrica'] }}</td>
                    <td>{{ $f['nombre'] }}</td>
                    <td>{{ $f['categoria'] }}</td>
                    <td>{{ $f['marca'] }}</td>
                    <td class="num font-bold">{{ number_format((float) $f['precio'], 2, ',', '.') }}</td>
                    <td class="num">{{ $f['stock'] }}</td>
                    <td>@if($f['foto'])<span class="estado estado-disponible">Sí</span>@else<span class="apoyo text-sm">Silueta</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.importacion.confirmar') }}" class="lobulo-tierra panel-tapa mt-6 p-6 pt-8" x-data="{ reemplazar: false }"
          :data-confirmar="reemplazar ? @js('¿Reemplazar todo el catálogo? Se borrarán ' . $actual['productos'] . ' productos con sus ' . $actual['pedidos'] . ' pedidos, ' . $actual['cotizaciones'] . ' cotizaciones y ' . $actual['movimientos'] . ' movimientos. Antes se crea un respaldo automático.') : null">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <p class="font-rotulo text-2xl font-extrabold uppercase">{{ count($filas) }} {{ count($filas) === 1 ? 'producto listo' : 'productos listos' }}</p>
        <label class="mt-4 flex max-w-2xl cursor-pointer items-start gap-3">
            <input type="checkbox" name="reemplazar" value="1" x-model="reemplazar" class="mt-1">
            <span><strong>Reemplazar todo el catálogo actual.</strong> <span class="text-white/80">Borra los {{ $actual['productos'] }} productos actuales con sus pedidos, cotizaciones y movimientos. Úsalo solo para empezar de cero. Si no lo marcas, se agregan los nuevos y se actualizan los existentes por su código.</span></span>
        </label>
        <div class="mt-6 flex flex-wrap gap-2">
            <button class="btn btn-sol btn-grande" data-cargando="Cargando…">Confirmar carga</button>
            <a href="{{ route('admin.importacion.create') }}" class="btn btn-linea-clara btn-grande">Cancelar</a>
        </div>
    </form>
@endif
@endsection
