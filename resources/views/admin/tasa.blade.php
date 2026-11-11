@extends('layouts.app')
@section('titulo', 'Tasa del día')

@section('contenido')
@php
    $tasa = $configuraciones['tasa_cambio'] ?? null;
    $fuente = $configuraciones['tasa_fuente']->valor ?? 'manual';
    $fechaValor = isset($configuraciones['tasa_fecha_valor']) ? \Illuminate\Support\Carbon::parse($configuraciones['tasa_fecha_valor']->valor) : null;
    $consultada = isset($configuraciones['tasa_consultada_en']) ? \Illuminate\Support\Carbon::parse($configuraciones['tasa_consultada_en']->valor) : $tasa?->updated_at;
    $vieja = ! $consultada || $consultada->lt(now()->subDay());
    $actual = (float) ($tasa?->valor ?? 0);
@endphp
<x-encabezado titulo="Tasa del día">
    <span class="block max-w-[70ch]">Se aplica de inmediato en el catálogo y en las cotizaciones nuevas. Lo ya emitido conserva la tasa con que se creó.</span>
</x-encabezado>

{{-- Formulario aparte (no se anidan formularios): lo envía el botón «Traer del BCV» --}}
<form id="form-bcv" method="POST" action="{{ route('admin.configuracion.tasa-bcv') }}">@csrf</form>

<div class="lobulo max-w-3xl overflow-hidden">
    {{-- Lo primero: cuál es la tasa, de dónde salió y si está al día --}}
    <section class="p-5 lg:p-6" aria-labelledby="t-vigente">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 id="t-vigente" class="font-bold text-tinta-suave">Tasa vigente</h2>
                <p class="titular mt-1 text-4xl normal-case text-tierra-oscura cifra sm:text-5xl">Bs. {{ number_format($actual, 4, ',', '.') }}</p>
                <p class="apoyo mt-1">por cada dólar · USD 100 = <span class="cifra">Bs. {{ number_format($actual * 100, 2, ',', '.') }}</span></p>
            </div>
            <button type="submit" form="form-bcv" class="btn btn-sol" data-cargando="Consultando el BCV…"><x-icono nombre="restaurar" class="size-4" /> Traer del BCV</button>
        </div>
        <dl class="mt-5 grid gap-x-6 gap-y-3 border-t border-linea pt-4 text-sm sm:grid-cols-3">
            <div><dt class="apoyo">Origen</dt><dd class="mt-0.5"><span @class(['estado', 'estado-confirmado' => $fuente === 'BCV', 'estado-bajo' => $fuente !== 'BCV'])>{{ $fuente === 'BCV' ? 'BCV' : 'Escrita a mano' }}</span></dd></div>
            <div><dt class="apoyo">Fecha valor</dt><dd class="mt-0.5 font-bold cifra">{{ $fuente === 'BCV' && $fechaValor ? $fechaValor->format('d/m/Y') : '—' }}</dd></div>
            <div><dt class="apoyo">Última actualización</dt><dd class="mt-0.5 font-bold cifra">{{ $consultada?->format('d/m/Y h:i a') ?? 'Nunca' }}</dd></div>
        </dl>
        @if($vieja)
            <div class="aviso aviso-atencion mt-4" role="status"><x-icono nombre="alerta" class="size-5 shrink-0" />
                <p><strong>Lleva más de un día sin actualizarse.</strong> Revisa la conexión a internet, o tráela del BCV o escríbela abajo.</p></div>
        @endif
        <p class="ayuda mt-4">El portal la consulta solo cada hora, de 6:00 a 20:00, si está encendido y con internet.</p>
    </section>

    {{-- Corregirla a mano: avisa antes de guardar si el cambio parece un error de tipeo --}}
    <form method="POST" action="{{ route('admin.tasa.update') }}" class="needs-validation border-t border-linea bg-agua p-5 lg:p-6" novalidate
          x-data="{ nueva: @js((string) old('tasa_cambio', '')), actual: @js($actual),
                    get cambio() { const n = parseFloat(this.nueva); return n > 0 && this.actual > 0 ? (n - this.actual) / this.actual * 100 : null; } }">
        @csrf @method('PUT')
        <h2 class="font-bold">Escribirla a mano</h2>
        <p class="apoyo mt-0.5 text-sm">Úsalo solo si el BCV no responde. En la próxima consulta automática, el portal la reemplaza por la del BCV.</p>
        <div class="mt-3 flex flex-wrap items-start gap-3">
            <div class="min-w-0 max-w-xs flex-1">
                <label for="tasa_cambio" class="sr-only">Tasa oficial USD → Bs.</label>
                <div class="grupo-campo">
                    <span class="unidad">Bs.</span>
                    <input type="number" id="tasa_cambio" name="tasa_cambio" value="{{ old('tasa_cambio') }}" placeholder="{{ number_format($actual, 4, '.', '') }}" class="campo cifra @error('tasa_cambio') is-invalid @enderror" required min="0.0001" step="0.0001" x-model="nueva">
                </div>
                <p @class(['error-campo', '!block' => $errors->has('tasa_cambio')])>@error('tasa_cambio'){{ $message }}@else Mayor que cero, hasta 4 decimales. @enderror</p>
            </div>
            <button class="btn btn-tierra">Guardar tasa</button>
        </div>
        <p class="mt-2 text-sm" aria-live="polite" x-show="cambio !== null" x-cloak>
            <span :class="Math.abs(cambio) > 10 ? 'font-bold text-coral-tinta' : 'apoyo'"
                  x-text="(cambio > 0 ? 'Sube ' : 'Baja ') + Math.abs(cambio).toLocaleString('es-VE', { maximumFractionDigits: 2 }) + ' % respecto de la vigente.' + (Math.abs(cambio) > 10 ? ' Revisa que esté bien escrita.' : '')"></span>
        </p>
    </form>
</div>

@if(auth()->user()->esDirectivo())
    <p class="ayuda mt-3 max-w-3xl">El IVA, el IGTF, el WhatsApp y los métodos de pago están en <a href="{{ route('admin.configuracion.edit') }}" class="enlace">Configuración del portal</a>.</p>
@else
    <p class="ayuda mt-3 max-w-3xl">El IVA, el IGTF, el WhatsApp y los métodos de pago los cambia el directivo.</p>
@endif
@endsection
