@extends('layouts.app')
@section('titulo', 'Categorías')

@section('contenido')
<x-encabezado titulo="Categorías">
    <x-slot:acciones>
        <a href="{{ route('admin.categorias.create') }}" class="btn btn-sol"><x-icono nombre="mas" class="size-4" /> Nueva categoría</a>
    </x-slot:acciones>
</x-encabezado>

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Nombre</th><th>Descripción</th><th class="!text-right">Productos</th><th class="!text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse($categorias as $categoria)
            <tr>
                <td class="font-bold">{{ $categoria->nombre }}</td>
                <td class="apoyo">{{ \Illuminate\Support\Str::limit($categoria->descripcion, 80) }}</td>
                <td class="num"><a href="{{ route('admin.productos.index', ['categoria' => $categoria->id]) }}" class="enlace">{{ $categoria->productos_count }}</a></td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('admin.categorias.edit', $categoria) }}" class="btn btn-linea btn-mini"><x-icono nombre="lapiz" class="size-4" /> Editar</a>
                    <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}" class="ml-3 inline" data-confirmar="¿Eliminar la categoría {{ $categoria->nombre }}?">
                        @csrf @method('DELETE')
                        <button class="enlace-peligro text-sm disabled:cursor-not-allowed disabled:no-underline disabled:opacity-40" @disabled($categoria->productos_count > 0) title="{{ $categoria->productos_count > 0 ? 'Tiene productos' : '' }}">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="apoyo py-10 text-center">No hay categorías.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $categorias->links() }}
@endsection
