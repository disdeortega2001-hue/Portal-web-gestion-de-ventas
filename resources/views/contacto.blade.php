@extends('layouts.app')
@section('titulo', 'Contacto')
@section('descripcion', 'Escríbenos por WhatsApp, llámanos o visítanos en Av. Las Industrias, Edificio Campagna, Valle de la Pascua, Guárico.')

@php
    $wa = \App\Support\Contacto::whatsapp('Hola, quiero consultar un repuesto.');
    $telefono = \App\Support\Contacto::telefonoVisible();
    $direccion = 'Av. Las Industrias, Edificio Campagna, Valle de la Pascua, Guárico';
    $mapa = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode('Inversiones Rialto 7, ' . $direccion . ', Venezuela');
@endphp

@section('cabecera')
    <h1 class="titular max-w-3xl text-5xl sm:text-6xl">Hablemos de tu repuesto</h1>
    <p class="mt-3 max-w-2xl text-lg text-white/80">Ten a mano el código de fábrica o el modelo de tu máquina: así te respondemos en minutos.</p>
@endsection

@section('contenido')
<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
    {{-- Canales directos --}}
    <div class="space-y-4">
        @if($wa)
            <a href="{{ $wa }}" target="_blank" rel="noopener" class="lobulo-tierra panel-tapa flex items-center gap-5 p-6 pt-8 no-underline hover:bg-tierra-oscura">
                <span class="ruedo size-16"><x-icono nombre="whatsapp" class="size-8 text-tierra" /></span>
                <span>
                    <span class="block font-rotulo text-3xl font-extrabold uppercase">WhatsApp</span>
                    <span class="block text-white/80">{{ $telefono }} · la forma más rápida de cotizar</span>
                </span>
            </a>
            <a href="{{ \App\Support\Contacto::llamada() }}" class="lobulo flex items-center gap-5 p-6 no-underline hover:border-tierra">
                <span class="ruedo size-16"><x-icono nombre="telefono" class="size-7" /></span>
                <span>
                    <span class="block font-rotulo text-3xl font-extrabold uppercase">Llámanos</span>
                    <span class="block text-tinta-suave cifra">{{ $telefono }}</span>
                </span>
            </a>
        @endif
        <div class="lobulo p-6">
            <div class="flex items-start gap-5">
                <span class="ruedo size-16"><x-icono nombre="ubicacion" class="size-7" /></span>
                <div>
                    <p class="font-rotulo text-3xl font-extrabold uppercase">Visítanos</p>
                    <p class="mt-1 text-tinta-suave">{{ $direccion }}</p>
                    <p class="mt-1 text-sm text-tinta-suave">Inversiones Rialto 7 C.A. · RIF J-50668510-8</p>
                    <a href="{{ $mapa }}" target="_blank" rel="noopener" class="btn btn-linea btn-mini mt-4"><x-icono nombre="ubicacion" class="size-4" /> Cómo llegar</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Formulario: arma el mensaje y lo abre en WhatsApp; no guarda nada en el servidor --}}
    <section aria-labelledby="t-consulta" class="lobulo panel-tapa p-6 pt-8 lg:p-8 lg:pt-10">
        <h2 id="t-consulta" class="titular text-3xl">Escríbenos tu consulta</h2>
        <p class="mt-2 text-tinta-suave">Te abrimos WhatsApp con el mensaje listo para enviar.</p>
        @if($wa)
            <form method="GET" action="{{ route('contacto.whatsapp') }}" target="_blank" class="mt-6 space-y-4" data-sin-bloqueo>
                <div>
                    <label for="nombre" class="etiqueta">Tu nombre</label>
                    <input id="nombre" name="nombre" class="campo" maxlength="80" autocomplete="name" required>
                </div>
                <div>
                    <label for="codigo" class="etiqueta">Código de fábrica o modelo <span class="font-normal text-tinta-suave">(opcional)</span></label>
                    <input id="codigo" name="codigo" class="campo" maxlength="60" placeholder="Ej. 0602030250R o Rastra Tatu">
                </div>
                <div>
                    <label for="mensaje" class="etiqueta">¿Qué necesitas?</label>
                    <textarea id="mensaje" name="mensaje" rows="4" class="campo" maxlength="600" required placeholder="Ej. Necesito 12 discos para una rastra de 24 discos."></textarea>
                </div>
                <button class="btn btn-sol btn-grande w-full sm:w-auto"><x-icono nombre="whatsapp" /> Abrir en WhatsApp</button>
            </form>
        @else
            <p class="aviso aviso-info mt-6"><x-icono nombre="info" class="mt-0.5" /> Pronto podrás escribirnos desde aquí. Mientras tanto, visítanos en la sede.</p>
        @endif
    </section>
</div>
@endsection
