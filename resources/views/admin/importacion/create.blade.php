@extends('layouts.app')
@section('titulo', 'Cargar productos desde Excel')

@section('contenido')
<x-encabezado titulo="Cargar productos desde Excel" :volver="route('admin.productos.index')" volver-texto="Productos">
    Para agregar muchos productos o actualizar precios y existencias de una vez. Antes de guardar verás una revisión: con un solo error no se carga nada.
</x-encabezado>

<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <form method="POST" action="{{ route('admin.importacion.store') }}" enctype="multipart/form-data" class="lobulo panel-tapa space-y-6 p-6 pt-8">
        @csrf
        <div class="flex gap-4">
            <span class="ruedo size-10 text-lg">1</span>
            <div class="flex-1">
                <label for="archivo" class="etiqueta">Archivo de Excel *</label>
                <input type="file" id="archivo" name="archivo" accept=".xlsx,.xls,.csv" required class="campo !py-2.5 file:mr-4 file:border-0 file:bg-tierra file:px-4 file:py-2 file:font-rotulo file:font-bold file:uppercase file:text-white">
                <p class="ayuda">Una fila por producto, con los títulos de la plantilla.</p>
            </div>
        </div>
        <div class="flex gap-4">
            <span class="ruedo size-10 text-lg">2</span>
            <div class="flex-1">
                <label for="fotos" class="etiqueta">Fotos <span class="font-normal text-tinta-suave">(opcional)</span></label>
                <input type="file" id="fotos" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple class="campo !py-2.5 file:mr-4 file:border-0 file:bg-tierra file:px-4 file:py-2 file:font-rotulo file:font-bold file:uppercase file:text-white">
                <p class="ayuda">Puedes elegir varias a la vez. Si cada foto se llama como el código del producto (0602030250R.jpg), se asocia sola.</p>
            </div>
        </div>
        <div class="flex gap-4">
            <span class="ruedo size-10 text-lg">3</span>
            <div class="flex-1"><button class="btn btn-sol btn-grande" data-cargando="Revisando…">Revisar archivo <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></button>
                <p class="ayuda">Todavía no se guarda nada.</p></div>
        </div>
    </form>

    <aside class="lobulo p-6">
        <h2 class="seccion-titulo">¿Primera vez?</h2>
        <p class="mt-2 text-tinta-suave">Descarga la plantilla: trae los títulos correctos, un ejemplo y una hoja de instrucciones.</p>
        <a href="{{ route('admin.importacion.plantilla') }}" class="btn btn-tierra mt-4 w-full"><x-icono nombre="excel" class="size-5" /> Descargar plantilla</a>
        <ul class="mt-5 space-y-2 text-[0.9375rem] text-tinta-suave">
            <li>• Obligatorios: código, nombre, categoría, marca, precio en USD y existencia.</li>
            <li>• Opcionales: modelos compatibles, descripción, foto y costo interno.</li>
            <li>• Un producto que ya existe (mismo código) se actualiza; la diferencia de existencia queda registrada en Inventario.</li>
        </ul>
    </aside>
</div>
@endsection
