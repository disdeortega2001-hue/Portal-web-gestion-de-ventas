@extends('layouts.app')
@section('titulo', $cotizacion->codigo)

@php
    use App\Support\Contacto;

    $enlaceEsta = url()->full();   // Para el invitado es un enlace firmado: su única llave para volver
    $esInvitado = ! auth()->check();
    $escribirVendedor = Contacto::whatsapp("Hola, envié la cotización {$cotizacion->codigo} desde el portal.");
    $guardarEnWhatsapp = Contacto::compartir("Mi cotización {$cotizacion->codigo} de Inversiones Rialto 7: {$enlaceEsta}");
    $telefonoEmpresa = Contacto::telefonoVisible();
@endphp

@section('cabecera')
    <div class="flex flex-wrap items-center gap-3">
        <span class="estado estado-{{ $cotizacion->estado }}">{{ ucfirst($cotizacion->estado) }}</span>
        <span class="text-sm text-white/75 cifra">{{ $cotizacion->fecha->format('d/m/Y h:i a') }}</span>
    </div>
    <h1 class="titular mt-3 text-4xl sm:text-5xl">Cotización <span class="cifra whitespace-nowrap">{{ $cotizacion->codigo }}</span></h1>
@endsection

@section('contenido')
{{-- Qué sigue: el final del recorrido debe dejar tranquilo al cliente, no con dudas --}}
<section class="rounded-lobulo bg-sabana-clara p-6 sm:p-8" aria-labelledby="que-sigue">
    @switch($cotizacion->estado)
        @case('pendiente')
            <h2 id="que-sigue" class="titular text-3xl">Recibimos tu cotización</h2>
            <p class="mt-3 max-w-2xl text-lg">Un vendedor revisa la existencia y te llama al <strong class="cifra whitespace-nowrap">{{ $cotizacion->cliente->telefono }}</strong> para confirmar el pedido y la forma de pago. Los precios quedan fijos en esta cotización.</p>
            @break
        @case('convertida')
            <h2 id="que-sigue" class="titular text-3xl">Ya es un pedido</h2>
            <p class="mt-3 max-w-2xl text-lg">Un vendedor confirmó tu cotización. @if($telefonoEmpresa) Para coordinar el retiro, llama al <a href="{{ Contacto::llamada() }}" class="enlace">{{ $telefonoEmpresa }}</a>. @endif</p>
            @break
        @default
            <h2 id="que-sigue" class="titular text-3xl">No pudimos atender esta cotización</h2>
            <p class="mt-3 max-w-2xl text-lg">Puede que algún producto ya no esté disponible. @if($telefonoEmpresa) Escríbenos y te ofrecemos una alternativa. @endif</p>
    @endswitch

    <div class="mt-6 flex flex-wrap gap-2">
        @if($escribirVendedor)
            <a href="{{ $escribirVendedor }}" class="btn btn-tierra" target="_blank" rel="noopener"><x-icono nombre="whatsapp" /> Escribir al vendedor</a>
        @endif
        <a href="{{ $urlPdf }}" class="btn btn-linea"><x-icono nombre="pdf" /> Descargar PDF</a>
        @if($esInvitado)
            <a href="{{ $guardarEnWhatsapp }}" class="btn btn-linea" target="_blank" rel="noopener"><x-icono nombre="whatsapp" /> Guardar en mi WhatsApp</a>
        @endif
    </div>

    @if($esInvitado)
        <p class="mt-4 max-w-2xl text-sm text-tierra-oscura">Para volver a ver esta cotización durante 30 días, envíate este enlace por WhatsApp o descarga el PDF. Si creas una cuenta, la verás siempre en «Mis cotizaciones».</p>
    @else
        <p class="mt-4 text-sm text-tierra-oscura">También la encuentras en <a href="{{ route('cuenta.cotizaciones') }}" class="enlace">Mis cotizaciones</a>.</p>
    @endif
</section>

<div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_24rem]">
    <div>
        <h2 class="seccion-titulo">Productos</h2>
        <ul class="lobulo mt-3 divide-y divide-linea">
            @foreach($cotizacion->detalles as $detalle)
                <li class="flex justify-between gap-4 p-4 sm:p-5">
                    <div class="min-w-0">
                        <span class="codigo">{{ $detalle->producto->codigo_fabrica }}</span>
                        <div class="mt-0.5 font-bold">{{ $detalle->producto->nombre }}</div>
                        <div class="apoyo text-sm cifra">{{ $detalle->cantidad }} × @usd($detalle->precio_unitario)</div>
                    </div>
                    <div class="font-extrabold whitespace-nowrap cifra">@usd($detalle->subtotal)</div>
                </li>
            @endforeach
        </ul>
        <h2 class="seccion-titulo mt-8">Datos del cliente</h2>
        <p class="mt-2 font-semibold">{{ $cotizacion->cliente->nombre }} · <span class="cifra">{{ $cotizacion->cliente->identificacion }}</span></p>
        <p class="apoyo"><span class="cifra">{{ $cotizacion->cliente->telefono }}</span> · {{ $cotizacion->cliente->direccion }}</p>
    </div>
    <div class="lg:sticky lg:top-6 lg:self-start">@include('partials.resumen', ['r' => $cotizacion])</div>
</div>
@endsection
