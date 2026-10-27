@extends('layouts.app')
@section('titulo', 'Pedido de mostrador')

@section('contenido')
<x-encabezado titulo="Pedido de mostrador" :volver="route('gestion.pedidos.index')" volver-texto="Pedidos">
    Para el cliente que compra en la sede. El sistema calcula subtotal, IVA y total al guardar.
</x-encabezado>

<form method="POST" action="{{ route('gestion.pedidos.store') }}" class="needs-validation max-w-4xl space-y-5" novalidate>
    @csrf
    <div class="lobulo p-5">
        <label for="id_cliente" class="etiqueta">Cliente *</label>
        <select id="id_cliente" name="id_cliente" class="campo @error('id_cliente') is-invalid @enderror" required>
            <option value="">Selecciona…</option>
            @foreach($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected(old('id_cliente') == $cliente->id)>{{ $cliente->identificacion }} — {{ $cliente->nombre }}</option>
            @endforeach
        </select>
        <p class="error-campo">Selecciona el cliente.</p>
    </div>

    <div class="lobulo p-5">
        <h2 class="seccion-titulo">Productos</h2>
        <div id="lineas" class="mt-4 space-y-3"></div>
        <button type="button" id="agregar-linea" class="btn btn-linea btn-mini mt-4"><x-icono nombre="mas" class="size-4" /> Agregar producto</button>
    </div>
    <button class="btn btn-sol btn-grande w-full sm:w-auto">Crear pedido <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></button>
</form>

<template id="plantilla-linea">
    <div class="linea grid grid-cols-[1fr_auto] items-end gap-3 sm:grid-cols-[1fr_8rem_auto]">
        <div class="col-span-2 sm:col-span-1">
            <label class="etiqueta">Producto</label>
            <select class="campo" data-campo="id_producto" required>
                <option value="">Selecciona…</option>
                @foreach($productos as $p)
                    <option value="{{ $p->id }}" data-stock="{{ $p->stock }}">{{ $p->codigo_fabrica }} — {{ $p->nombre }} (stock {{ $p->stock }}, @usd($p->precio))</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="etiqueta">Cantidad</label>
            <input type="number" class="campo cifra" data-campo="cantidad" min="1" value="1" required>
        </div>
        <button type="button" class="quitar grid size-12 place-items-center rounded-full border-[1.5px] border-linea text-coral-tinta hover:bg-coral-clara" aria-label="Quitar línea"><x-icono nombre="papelera" /></button>
    </div>
</template>
@endsection

@push('scripts')
<script>
    (function () {
        const contenedor = document.getElementById('lineas');
        const plantilla = document.getElementById('plantilla-linea');
        function renumerar() {
            contenedor.querySelectorAll('.linea').forEach(function (fila, i) {
                fila.querySelectorAll('[data-campo]').forEach(function (c) { c.name = 'items[' + i + '][' + c.dataset.campo + ']'; });
            });
        }
        function agregar() {
            const fila = plantilla.content.firstElementChild.cloneNode(true);
            const select = fila.querySelector('select'), cantidad = fila.querySelector('input');
            select.addEventListener('change', function () { cantidad.max = select.selectedOptions[0]?.dataset.stock || ''; });
            fila.querySelector('.quitar').addEventListener('click', function () { fila.remove(); renumerar(); });
            contenedor.appendChild(fila);
            renumerar();
        }
        document.getElementById('agregar-linea').addEventListener('click', agregar);
        agregar();
    })();
</script>
@endpush
