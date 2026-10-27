@extends('layouts.app')
@section('titulo', 'Usuarios')

@section('contenido')
<x-encabezado titulo="Usuarios">
    <x-slot:acciones>
        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-sol"><x-icono nombre="mas" class="size-4" /> Nuevo usuario</a>
    </x-slot:acciones>
</x-encabezado>

<form method="GET" class="mb-5 flex flex-wrap items-center gap-3">
    <div class="relative w-full sm:w-72">
        <x-icono nombre="buscar" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-tinta-suave" />
        <input type="search" name="q" value="{{ request('q') }}" class="campo !pl-11" placeholder="Nombre o correo" aria-label="Nombre o correo">
    </div>
    <select name="rol" class="campo sm:!w-52" aria-label="Perfil">
        <option value="">Todos los perfiles</option>
        @foreach(['administrador' => 'Administrador', 'vendedor' => 'Vendedor', 'cliente' => 'Cliente'] as $valor => $texto)
            <option value="{{ $valor }}" @selected(request('rol') === $valor)>{{ $texto }}</option>
        @endforeach
    </select>
    <button class="btn btn-linea btn-mini">Filtrar</button>
</form>

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Perfil</th><th>Estado</th><th class="!text-right">Acciones</th></tr></thead>
        <tbody>
        @foreach($usuarios as $usuario)
            <tr>
                <td class="font-semibold">{{ $usuario->nombre }} @if($usuario->is(auth()->user()))<span class="apoyo text-sm font-normal">(tú)</span>@endif</td>
                <td>{{ $usuario->correo }}</td>
                <td>{{ ucfirst($usuario->rol) }}</td>
                <td><span class="estado estado-{{ $usuario->estado === 'activo' ? 'activo' : 'inactivo' }}">{{ ucfirst($usuario->estado) }}</span></td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('admin.usuarios.edit', $usuario) }}" class="btn btn-linea btn-mini"><x-icono nombre="lapiz" class="size-4" /> Editar</a>
                    @unless($usuario->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.usuarios.destroy', $usuario) }}" class="ml-3 inline" data-confirmar="¿Eliminar a {{ $usuario->nombre }}? Perderá el acceso, pero su historial se conserva.">
                            @csrf @method('DELETE')
                            <button class="enlace-peligro text-sm">Eliminar</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
{{ $usuarios->links() }}
@endsection
