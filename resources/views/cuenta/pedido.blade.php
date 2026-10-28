@extends('layouts.app')
@section('titulo', 'Pedido ' . $pedido->codigo)

@section('cabecera')
    <a href="{{ route('cuenta.pedidos') }}" class="mb-3 inline-flex items-center gap-1.5 text-sm font-bold text-white/80 hover:text-white"><x-icono nombre="flecha-izq" class="size-4" /> Mis pedidos</a>
    <h1 class="titular text-4xl sm:text-5xl">Pedido <em class="cifra">{{ $pedido->codigo }}</em></h1>
    <p class="mt-3 flex flex-wrap items-center gap-2 text-white/85">
        <span class="cifra">{{ $pedido->fecha->format('d/m/Y h:i a') }}</span>
        <span class="estado estado-{{ $pedido->estado }}">{{ $pedido->estado === 'confirmado' ? 'Confirmado' : ucfirst($pedido->estado) }}</span>
        <span class="estado estado-{{ $pedido->estado_pago }}">{{ $pedido->etiquetaPago() }}</span>
    </p>
@endsection

@section('contenido')
<div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:gap-10">
    <div class="space-y-8">
        {{-- Cómo pagar: lo primero que necesita quien acaba de comprar --}}
        @if($pedido->estado !== 'anulado' && ! $pedido->estaPagado())
            <section aria-labelledby="t-pagar" class="sobre-azul relative overflow-hidden rounded-[10px] bg-tierra-oscura p-6 text-white sm:p-8">
                <x-arcos class="absolute inset-y-0 right-0 h-full w-80" />
                <div class="relative">
                    <h2 id="t-pagar" class="titular text-3xl">Paga con <em>{{ $pedido->nombreMetodoPago() }}</em></h2>
                    <p class="mt-2 text-white/80">Monto a pagar</p>
                    <p class="titular text-4xl text-sol cifra sm:text-5xl">{{ $pedido->montoAPagar() }}</p>
                    @if(\App\Support\MetodosPago::enLinea($pedido->metodo_pago))
                        @if($datosPago)
                            <dl class="mt-5 grid gap-3 sm:grid-cols-2">
                                @foreach($datosPago as $etiqueta => $valor)
                                    <div class="rounded-[8px] bg-white/10 px-4 py-3"><dt class="text-xs font-extrabold uppercase tracking-[0.08em] text-white/70">{{ $etiqueta }}</dt><dd class="mt-0.5 text-lg font-bold cifra">{{ $valor }}</dd></div>
                                @endforeach
                            </dl>
                        @else
                            <p class="mt-5 flex items-start gap-2 rounded-[8px] bg-white/10 px-4 py-3"><x-icono nombre="whatsapp" class="mt-0.5 size-5 shrink-0" />
                                Te enviamos los datos de la cuenta por WhatsApp al teléfono {{ $pedido->cliente->telefono }}.</p>
                        @endif
                        <p class="mt-4 text-sm text-white/75">Cuando pagues, repórtalo abajo con la referencia. Verificamos el pago y confirmamos tu pedido.</p>
                    @else
                        <p class="mt-4 text-white/85">Pagas al retirar en la tienda: Av. Las Industrias, Edificio Campagna, Valle de la Pascua. Te escribimos cuando tu pedido esté listo.</p>
                    @endif
                </div>
            </section>
        @endif

        {{-- Reportar el pago --}}
        @if($pedido->aceptaReporteDePago())
            <section aria-labelledby="t-reporte">
                <h2 id="t-reporte" class="titular subrayado text-3xl text-tierra-oscura">Reporta tu pago</h2>
                <form method="POST" action="{{ route('cuenta.pedidos.pago', $pedido) }}" enctype="multipart/form-data" class="needs-validation lobulo mt-8 grid gap-4 p-5 sm:grid-cols-2 sm:p-6" novalidate>
                    @csrf
                    <div>
                        <label for="referencia" class="etiqueta">Número de referencia *</label>
                        <input type="text" id="referencia" name="referencia" value="{{ old('referencia') }}" class="campo cifra @error('referencia') is-invalid @enderror" required minlength="4" maxlength="60" autocomplete="off">
                        <p class="error-campo">@error('referencia'){{ $message }}@else Escribe la referencia que te dio el banco. @enderror</p>
                    </div>
                    <div>
                        <label for="fecha_pago" class="etiqueta">Fecha del pago *</label>
                        <input type="date" id="fecha_pago" name="fecha_pago" value="{{ old('fecha_pago', now()->toDateString()) }}" max="{{ now()->toDateString() }}" min="{{ $pedido->fecha->toDateString() }}" class="campo cifra @error('fecha_pago') is-invalid @enderror" required>
                        <p class="error-campo">@error('fecha_pago'){{ $message }}@else Indica la fecha del pago. @enderror</p>
                    </div>
                    <div>
                        @php($moneda = \App\Support\MetodosPago::moneda($pedido->metodo_pago))
                        <label for="monto" class="etiqueta">Monto pagado *</label>
                        <div class="grupo-campo">
                            <span class="unidad">{{ $moneda === 'USD' ? 'USD' : 'Bs.' }}</span>
                            <input type="number" id="monto" name="monto" value="{{ old('monto', \App\Support\MetodosPago::montoEnMoneda($moneda, $pedido->total, $pedido->total_bs)) }}" class="campo cifra @error('monto') is-invalid @enderror" required min="0.01" step="0.01">
                        </div>
                        <p class="error-campo">@error('monto'){{ $message }}@else Indica el monto. @enderror</p>
                    </div>
                    <div>
                        <label for="comprobante" class="etiqueta">Comprobante (opcional)</label>
                        <input type="file" id="comprobante" name="comprobante" accept="image/jpeg,image/png,image/webp,application/pdf" class="campo @error('comprobante') is-invalid @enderror">
                        <p class="error-campo">@error('comprobante'){{ $message }}@enderror</p>
                        <p class="ayuda">Captura o PDF, hasta 5 MB.</p>
                    </div>
                    <button class="btn btn-sol btn-grande sm:col-span-2 sm:justify-self-start" data-cargando="Enviando…">Reportar pago <span class="chip"><x-icono nombre="enviar" class="size-4" /></span></button>
                </form>
            </section>
        @endif

        {{-- Pagos reportados y su estado --}}
        @if($pedido->pagos->isNotEmpty())
            <section aria-labelledby="t-pagos">
                <h2 id="t-pagos" class="seccion-titulo">Tus pagos</h2>
                <ul class="mt-3 space-y-2">
                    @foreach($pedido->pagos as $pago)
                        <li class="lobulo flex flex-wrap items-center justify-between gap-3 p-4">
                            <div>
                                <p class="font-bold">{{ $pago->nombreMetodo() }} · <span class="cifra">{{ $pago->montoFormateado() }}</span></p>
                                <p class="apoyo text-sm cifra">{{ $pago->fecha_pago->format('d/m/Y') }}@if($pago->referencia) · Ref. {{ $pago->referencia }}@endif</p>
                                @if($pago->motivo_rechazo)<p class="mt-1 text-sm font-semibold text-coral-tinta">Motivo: {{ $pago->motivo_rechazo }}</p>@endif
                            </div>
                            <span class="estado estado-{{ $pago->estado }}">{{ $pago->etiquetaEstado() }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <div class="space-y-5 lg:sticky lg:top-24 lg:self-start">
        <section class="lobulo p-4 sm:p-5" aria-labelledby="t-detalle">
            <h2 id="t-detalle" class="seccion-titulo">Productos</h2>
            <ul class="mt-3 divide-y divide-linea">
                @foreach($pedido->detalles as $detalle)
                    <li class="flex justify-between gap-3 py-2 text-sm">
                        <span><span class="font-bold cifra">{{ $detalle->cantidad }} ×</span> {{ $detalle->producto->nombre }}</span>
                        <span class="whitespace-nowrap font-bold cifra">@usd($detalle->subtotal)</span>
                    </li>
                @endforeach
            </ul>
            <p class="apoyo mt-3 text-sm">{{ $pedido->entrega === 'envio' ? 'Envío a coordinar' : 'Retiro en la tienda' }}@if($pedido->observaciones) · {{ $pedido->observaciones }}@endif</p>
        </section>
        @include('partials.resumen', ['r' => $pedido])
    </div>
</div>
@endsection
