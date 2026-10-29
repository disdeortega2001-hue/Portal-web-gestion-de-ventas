@extends('layouts.app')
@section('titulo', 'Tasa del día')

@section('contenido')
@php
    $tasa = $configuraciones['tasa_cambio'] ?? null;
    $fuente = $configuraciones['tasa_fuente']->valor ?? 'manual';
@endphp
<x-encabezado titulo="Tasa del día">
    <span class="block max-w-[70ch]">La tasa se aplica de inmediato en el catálogo y en las cotizaciones. Las cotizaciones y pedidos ya emitidos conservan la tasa con que se crearon.</span>
</x-encabezado>

{{-- Formulario aparte (no se anidan formularios): lo envía el botón «Traer del BCV» --}}
<form id="form-bcv" method="POST" action="{{ route('admin.configuracion.tasa-bcv') }}">@csrf</form>

<form method="POST" action="{{ route('admin.tasa.update') }}" class="needs-validation lobulo max-w-2xl p-5 lg:p-6" novalidate>
    @csrf @method('PUT')
    <label for="tasa_cambio" class="etiqueta">Tasa oficial USD → Bs. *</label>
    <div class="flex flex-wrap items-start gap-3">
        <div class="grupo-campo max-w-xs flex-1">
            <span class="unidad">Bs.</span>
            <input type="number" id="tasa_cambio" name="tasa_cambio" value="{{ old('tasa_cambio', $tasa?->valor) }}" class="campo cifra @error('tasa_cambio') is-invalid @enderror" required min="0.0001" step="0.0001">
        </div>
        <button type="submit" form="form-bcv" class="btn btn-tierra" data-cargando="Consultando el BCV…"><x-icono nombre="restaurar" class="size-4" /> Traer del BCV</button>
    </div>
    <p @class(['error-campo', '!block' => $errors->has('tasa_cambio')])>@error('tasa_cambio'){{ $message }}@else Mayor que cero, hasta 4 decimales. @enderror</p>
    <p class="ayuda">Por 1 USD.
        @if($fuente === 'BCV')
            <strong>Tomada del Banco Central de Venezuela</strong>@isset($configuraciones['tasa_fecha_valor']) (fecha valor <span class="cifra">{{ \Illuminate\Support\Carbon::parse($configuraciones['tasa_fecha_valor']->valor)->format('d/m/Y') }}</span>)@endisset.
        @else
            Escrita a mano.
        @endif
        Última actualización: <span class="cifra">{{ $tasa?->updated_at?->format('d/m/Y h:i a') ?? 'nunca' }}</span>. El portal la trae sola del BCV a las 7:00 y a las 17:00; si escribes otra, se usa la tuya hasta la próxima actualización.</p>
    <button class="btn btn-sol mt-5">Guardar tasa</button>
    <p class="ayuda mt-3">El IVA, el IGTF, el WhatsApp y los métodos de pago los cambia el directivo.</p>
</form>
@endsection
