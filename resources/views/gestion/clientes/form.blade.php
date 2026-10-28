@extends('layouts.app')
@section('titulo', $cliente->exists ? 'Corregir cliente' : 'Nuevo cliente')

@php
    // Para precargar la cédula en dos partes (letra + número) al editar
    $r = $cliente->exists ? [
        'tipo_identificacion' => substr($cliente->identificacion, 0, 1),
        'numero_identificacion' => preg_replace('/\D/', '', $cliente->identificacion),
        'nombre' => $cliente->nombre, 'telefono' => $cliente->telefono, 'correo' => $cliente->correo, 'direccion' => $cliente->direccion,
    ] : [];
@endphp

@section('contenido')
<x-encabezado :titulo="$cliente->exists ? 'Corregir datos de ' . $cliente->nombre : 'Nuevo cliente'"
              :volver="$cliente->exists ? route('gestion.clientes.show', $cliente) : route('gestion.clientes.index')"
              :volver-texto="$cliente->exists ? $cliente->nombre : 'Clientes'">
    Los cambios aplican a las próximas cotizaciones y pedidos; los ya emitidos conservan sus datos.
</x-encabezado>

<form method="POST" action="{{ $cliente->exists ? route('gestion.clientes.update', $cliente) : route('gestion.clientes.store') }}" class="needs-validation lobulo panel-tapa max-w-3xl p-6 pt-8" novalidate>
    @csrf
    @if($cliente->exists) @method('PUT') @endif
    @if(request('volver') === 'pedido')<input type="hidden" name="volver" value="pedido">@endif
    @include('partials.campos-cliente', ['recordado' => $r])
    <div class="mt-6 flex flex-wrap gap-2">
        <button class="btn btn-sol btn-grande">{{ $cliente->exists ? 'Guardar cambios' : 'Registrar cliente' }}</button>
        <a href="{{ $cliente->exists ? route('gestion.clientes.show', $cliente) : route('gestion.clientes.index') }}" class="btn btn-linea btn-grande">Cancelar</a>
    </div>
</form>
@endsection
