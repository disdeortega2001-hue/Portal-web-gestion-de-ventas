@extends('layouts.app')
@section('titulo', 'Productos')

@section('contenido')
<x-encabezado titulo="Productos">
    <x-slot:acciones>
        <a href="{{ route('admin.importacion.create') }}" class="btn btn-linea"><x-icono nombre="excel" class="size-4" /> Cargar desde Excel</a>
        <a href="{{ route('admin.productos.create') }}" class="btn btn-sol"><x-icono nombre="mas" class="size-4" /> Nuevo producto</a>
    </x-slot:acciones>
</x-encabezado>

<nav class="-mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Filtrar por estado">
    <ul class="flex gap-2">
        @foreach(['activos' => 'Activos', 'inactivos' => 'Ocultos del catálogo', 'eliminados' => 'Eliminados', 'todos' => 'Todos'] as $valor => $texto)
            <li><a href="{{ route('admin.productos.index', ['estado' => $valor] + request()->only('q', 'categoria')) }}" class="capsula !pr-4" @if($estado === $valor) aria-current="page" @endif>{{ $texto }}</a></li>
        @endforeach
    </ul>
</nav>

<form method="GET" class="mb-5 flex flex-wrap items-center gap-3">
    <input type="hidden" name="estado" value="{{ $estado }}">
    <div class="relative w-full sm:w-72">
        <x-icono nombre="buscar" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-tinta-suave" />
        <input type="search" name="q" value="{{ request('q') }}" class="campo !pl-11" placeholder="Código, nombre o marca" aria-label="Código, nombre o marca">
    </div>
    <select name="categoria" class="campo sm:!w-60" aria-label="Categoría">
        <option value="">Todas las categorías</option>
        @foreach($categorias as $c)
            <option value="{{ $c->id }}" @selected(request('categoria') == $c->id)>{{ $c->nombre }}</option>
        @endforeach
    </select>
    <button class="btn btn-linea btn-mini">Filtrar</button>
</form>

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th class="w-20"><span class="sr-only">Imagen</span></th><th>Código</th><th>Producto</th><th>Categoría</th><th class="!text-right">Precio</th><th class="!text-right">Stock</th><th class="!text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse($productos as $producto)
            <tr>
                <td><img src="{{ $producto->urlImagen('mini') }}" alt="" width="56" height="42" @class(['h-11 w-14 bg-agua object-contain mix-blend-multiply', 'bg-sabana-clara object-contain p-1' => ! $producto->imagen])></td>
                <td><span class="codigo">{{ $producto->codigo_fabrica }}</span></td>
                <td><span class="font-semibold">{{ $producto->nombre }}</span> @unless($producto->activo)<span class="estado estado-pendiente ml-1">Oculto</span>@endunless<br><span class="apoyo text-sm">{{ $producto->marca }}</span></td>
                <td class="apoyo">{{ $producto->categoria?->nombre }}</td>
                <td class="num font-bold">@usd($producto->precio)</td>
                <td class="text-right">@include('partials.stock', ['producto' => $producto])</td>
                <td class="text-right whitespace-nowrap">
                    @if($producto->trashed())
                        <form method="POST" action="{{ route('admin.productos.restaurar', $producto->id) }}" class="inline">
                            @csrf
                            <button class="btn btn-linea btn-mini"><x-icono nombre="restaurar" class="size-4" /> Restaurar</button>
                        </form>
                    @else
                        <a href="{{ route('admin.productos.edit', $producto) }}" class="btn btn-linea btn-mini"><x-icono nombre="lapiz" class="size-4" /> Editar</a>
                        <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}" class="ml-3 inline" data-confirmar="¿Eliminar {{ $producto->codigo_fabrica }} del catálogo?">
                            @csrf @method('DELETE')
                            <button class="enlace-peligro text-sm">Eliminar</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="apoyo py-10 text-center">No hay productos con estos filtros.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $productos->links() }}
@endsection
