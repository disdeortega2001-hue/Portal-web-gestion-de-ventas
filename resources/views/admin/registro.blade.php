@extends('layouts.app')
@section('titulo', 'Registro de cambios')

@section('contenido')
<x-encabezado titulo="Registro de cambios">Quién cambió precios, configuración y créditos. Este registro no se puede editar.</x-encabezado>

<div class="lobulo overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Fecha</th><th>Usuario</th><th>Dónde</th><th>Qué cambió</th><th>Antes</th><th>Ahora</th></tr></thead>
        <tbody>
        @forelse($cambios as $cambio)
            <tr>
                <td class="cifra whitespace-nowrap">{{ $cambio->creado_en->format('d/m/Y h:i a') }}</td>
                <td class="whitespace-nowrap font-semibold">{{ $cambio->usuario?->nombre ?? 'Usuario eliminado' }}</td>
                <td>{{ $cambio->entidad }}<br><span class="apoyo text-sm cifra">{{ $cambio->referencia }}</span></td>
                <td>{{ $cambio->campo }}</td>
                <td class="apoyo cifra">{{ $cambio->antes ?? '—' }}</td>
                <td class="font-bold cifra">{{ $cambio->despues ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="apoyo py-10 text-center">Todavía no hay cambios registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $cambios->links() }}
@endsection
