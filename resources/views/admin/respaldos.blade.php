@extends('layouts.app')
@section('titulo', 'Respaldos')

@section('contenido')
<x-encabezado titulo="Respaldos">
    <span class="block max-w-[70ch]">Cada respaldo guarda la base de datos completa y las fotos de los productos. El sistema crea uno solo cada noche y conserva los {{ \App\Services\RespaldoService::CONSERVAR }} más recientes.</span>
    <x-slot:acciones>
        <form method="POST" action="{{ route('admin.respaldos.store') }}">
            @csrf
            <button class="btn btn-sol"><x-icono nombre="escudo" class="size-4" /> Crear respaldo ahora</button>
        </form>
    </x-slot:acciones>
</x-encabezado>

<div class="aviso aviso-info mb-5 max-w-4xl" role="note">
    <x-icono nombre="info" class="mt-0.5" />
    <div>Descarga un respaldo al menos una vez por semana y guárdalo fuera del servidor (un pendrive o la nube). Si el servidor falla, con ese archivo se recupera todo.</div>
</div>

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Fecha</th><th class="hidden !text-right sm:table-cell">Tamaño</th><th class="!text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse($respaldos as $respaldo)
            <tr>
                <td>
                    <span class="block whitespace-nowrap font-bold">{{ $respaldo['fecha']->format('d/m/Y h:i a') }}</span>
                    <span class="apoyo hidden text-xs sm:block">{{ $respaldo['nombre'] }}</span>
                </td>
                <td class="num hidden sm:table-cell">{{ number_format($respaldo['bytes'] / 1048576, 2, ',', '.') }} MB</td>
                <td class="text-right">
                    <div class="flex flex-wrap items-center justify-end gap-x-4 gap-y-2">
                    <a href="{{ route('admin.respaldos.descargar', $respaldo['nombre']) }}" class="btn btn-linea btn-mini"><x-icono nombre="descarga" class="size-4" /> Descargar</a>
                    <form method="POST" action="{{ route('admin.respaldos.restaurar', $respaldo['nombre']) }}"
                          data-confirmar="¿Restaurar el respaldo del {{ $respaldo['fecha']->format('d/m/Y h:i a') }}? Todos los datos actuales se reemplazarán por los de ese momento (antes se guarda un respaldo del estado actual).">
                        @csrf
                        <button class="enlace-peligro text-sm">Restaurar</button>
                    </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="3"class="apoyo py-10 text-center">Todavía no hay respaldos. Crea el primero con «Crear respaldo ahora».</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
