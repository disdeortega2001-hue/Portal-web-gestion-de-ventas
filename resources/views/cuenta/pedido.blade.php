@extends('layouts.app')
@section('titulo', 'Pedido ' . $pedido->codigo)

@section('cabecera')
    @unless($sinCuenta)<a href="{{ route('cuenta.pedidos') }}" class="mb-3 inline-flex items-center gap-1.5 text-sm font-bold text-white/80 hover:text-white"><x-icono nombre="flecha-izq" class="size-4" /> Mis pedidos</a>@endunless
    <h1 class="titular text-4xl sm:text-5xl">Pedido <em class="cifra">{{ $pedido->codigo }}</em></h1>
    <p class="mt-3 flex flex-wrap items-center gap-2 text-white/85">
        <span class="cifra">{{ $pedido->fecha->format('d/m/Y h:i a') }}</span>
        <span class="estado estado-{{ $pedido->estado }}">{{ $pedido->estado === 'confirmado' ? 'Confirmado' : ucfirst($pedido->estado) }}</span>
        <span class="estado estado-{{ $pedido->estado_pago }}">{{ $pedido->etiquetaPago() }}</span>
        @if($pedido->estaEntregado())<span class="estado estado-entregado">{{ $pedido->entrega === 'envio' ? 'Entregado' : 'Retirado' }}</span>@endif
    </p>
@endsection

@section('contenido')
<div class="grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_24rem] lg:gap-10">
    <div class="space-y-8">
        {{-- Compra sin cuenta: este enlace es la única forma de volver al pedido --}}
        @if($sinCuenta)
            <section class="aviso aviso-info" aria-labelledby="t-enlace">
                <x-icono nombre="info" class="size-5 shrink-0" />
                <div class="min-w-0 flex-1">
                    <p id="t-enlace" class="font-bold">Guarda este enlace para ver tu pedido y reportar el pago</p>
                    <p class="text-sm">Compraste sin cuenta: con este enlace vuelves cuando quieras. Guárdalo en favoritos o envíatelo por WhatsApp.</p>
                    <label for="enlace-pedido" class="sr-only">Enlace de tu pedido</label>
                    <input id="enlace-pedido" type="text" readonly value="{{ $urlPedido }}" class="campo mt-2 w-full text-sm" data-seleccionar>
                    <a href="https://wa.me/?text={{ rawurlencode('Mi pedido ' . $pedido->codigo . ' en Rialto 7: ' . $urlPedido) }}" target="_blank" rel="noopener" class="enlace mt-2 inline-block text-sm">Enviármelo por WhatsApp</a>
                </div>
            </section>
        @endif
        {{-- Pago verificado: el comprobante y, si ya se entregó, quién lo recibió --}}
        @if($pedido->tieneComprobante())
            <section aria-labelledby="t-comprobante" class="sobre-azul relative overflow-hidden rounded-[10px] bg-tierra-oscura p-6 text-white sm:p-8">
                <x-arcos class="absolute inset-y-0 right-0 h-full w-80" />
                <div class="relative">
                    @if($pedido->estaEntregado())
                        <h2 id="t-comprobante" class="titular text-3xl">Pedido <em>{{ $pedido->entrega === 'envio' ? 'entregado' : 'retirado' }}</em></h2>
                        <p class="mt-3 max-w-[55ch] text-white/85">El <span class="cifra">{{ $pedido->entregado_en->format('d/m/Y') }}</span> a las <span class="cifra">{{ $pedido->entregado_en->format('h:i a') }}</span>. Lo recibió <strong>{{ $pedido->recibido_por }}</strong>@if($pedido->recibido_por_identificacion) (C.I. <span class="cifra">{{ $pedido->recibido_por_identificacion }}</span>)@endif. Si no reconoces esta entrega, comunícate con nosotros.</p>
                    @else
                        <h2 id="t-comprobante" class="titular text-3xl">Pago <em>verificado</em></h2>
                        <p class="mt-3 max-w-[55ch] text-white/85">{{ $pedido->estado === 'confirmado'
                            ? ($pedido->entrega === 'envio' ? 'Tu pedido está confirmado. Te contactamos para coordinar el envío.' : 'Tu pedido está confirmado y listo para retirar en la tienda: Av. Las Industrias, Edificio Campagna, Valle de la Pascua. Si lo retira otra persona, que lleve su cédula y este comprobante.')
                            : 'Estamos preparando tu pedido y te avisamos cuando esté listo.' }}</p>
                    @endif
                    <a href="{{ $urlComprobante }}" class="btn btn-sol mt-5">Descargar comprobante de pago <span class="chip"><x-icono nombre="pdf" class="size-4" /></span></a>
                </div>
            </section>
        {{-- Compra a crédito: lo que debe, lo abonado y cuándo le recordamos --}}
        @elseif($pedido->a_credito && $pedido->estado !== 'anulado')
            <section aria-labelledby="t-credito" class="sobre-azul relative overflow-hidden rounded-[10px] bg-tierra-oscura p-6 text-white sm:p-8">
                <x-arcos class="absolute inset-y-0 right-0 h-full w-80" />
                <div class="relative">
                    <h2 id="t-credito" class="titular text-3xl">Compra a <em>crédito</em></h2>
                    @if($pedido->estaPendiente())
                        <p class="mt-3 max-w-[55ch] text-white/85">Estamos revisando tu pedido. Te escribimos al {{ $pedido->cliente->telefono }} cuando esté listo para {{ $pedido->entrega === 'envio' ? 'coordinar el envío' : 'retirar en la tienda' }}.</p>
                    @endif
                    <p class="mt-4 text-white/80">Saldo pendiente</p>
                    <p class="titular text-4xl text-sol cifra sm:text-5xl">@usd($pedido->saldoUsd())</p>
                    <p class="mt-1 text-white/75 cifra">@bs($pedido->saldoBs()) a la tasa de hoy · abonado @usd($pedido->abonadoUsd()) de @usd($pedido->total)</p>
                    @if($pedido->estado_pago === 'por_verificar')
                        <p class="mt-4 rounded-[8px] bg-white/10 px-4 py-3">Estamos verificando tu último abono; al confirmarlo se descuenta del saldo.</p>
                    @endif
                    @if($proximo = $pedido->proximoRecordatorio())
                        <p class="mt-4 text-sm text-white/75">Abona cuando quieras. Te recordamos el saldo cada 30 días; el próximo aviso es el <span class="cifra">{{ $proximo->format('d/m/Y') }}</span>.</p>
                    @endif
                    @if($pedido->estaEntregado())
                        <p class="mt-3 text-sm text-white/75">{{ $pedido->entrega === 'envio' ? 'Entregado' : 'Retirado' }} el <span class="cifra">{{ $pedido->entregado_en->format('d/m/Y') }}</span> por {{ $pedido->recibido_por }}.</p>
                    @endif
                </div>
            </section>
        {{-- Pago reportado: ya no hace falta repetir las instrucciones --}}
        @elseif($pedido->estado_pago === 'por_verificar')
            <section aria-labelledby="t-verificando" class="sobre-azul relative overflow-hidden rounded-[10px] bg-tierra-oscura p-6 text-white sm:p-8">
                <x-arcos class="absolute inset-y-0 right-0 h-full w-80" />
                <div class="relative">
                    <h2 id="t-verificando" class="titular text-3xl">Estamos verificando <em>tu pago</em></h2>
                    <p class="mt-3 max-w-[55ch] text-white/85">Revisamos la referencia en el banco. Cuando quede verificado, confirmamos tu pedido y te escribimos al {{ $pedido->cliente->telefono }} para coordinar {{ $pedido->entrega === 'envio' ? 'el envío' : 'el retiro en la tienda' }}.</p>
                </div>
            </section>
        {{-- Cómo pagar: lo primero que necesita quien acaba de comprar --}}
        @elseif($pedido->estado !== 'anulado' && ! $pedido->estaPagado())
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
            @php($credito = $pedido->a_credito)
            <section aria-labelledby="t-reporte">
                <h2 id="t-reporte" class="titular subrayado text-3xl text-tierra-oscura">{{ $credito ? 'Reporta un abono' : 'Reporta tu pago' }}</h2>
                <form method="POST" action="{{ $urlPago }}" enctype="multipart/form-data" class="needs-validation lobulo mt-8 grid gap-4 p-5 sm:grid-cols-2 sm:p-6" novalidate
                      @if($credito) x-data="{ metodo: @js(old('metodo', array_key_first($metodosAbono))), metodos: @js($metodosAbono), usd: @js($pedido->saldoUsd()), bs: @js($pedido->saldoBs()) }" @endif>
                    @csrf
                    {{-- A crédito elige con qué abonó y ve los datos para pagar --}}
                    @if($credito)
                        <div class="sm:col-span-2">
                            <label for="metodo" class="etiqueta">¿Con qué pagaste? *</label>
                            <select id="metodo" name="metodo" class="campo @error('metodo') is-invalid @enderror" required x-model="metodo" @change="$refs.monto.value = metodos[metodo].moneda === 'USD' ? usd : bs">
                                @foreach($metodosAbono as $clave => $metodo)
                                    <option value="{{ $clave }}" @selected(old('metodo') === $clave)>{{ $metodo['nombre'] }} ({{ $metodo['moneda'] === 'USD' ? 'USD' : 'Bs.' }})</option>
                                @endforeach
                            </select>
                            <p class="error-campo">@error('metodo'){{ $message }}@else Elige el método. @enderror</p>
                            @foreach($metodosAbono as $clave => $metodo)
                                <dl class="mt-3 grid gap-2 sm:grid-cols-3" x-show="metodo === @js($clave)" @if($loop->index) x-cloak @endif>
                                    @forelse($metodo['datos'] as $etiqueta => $valor)
                                        <div class="rounded-[6px] bg-agua-honda px-3 py-2"><dt class="text-xs font-extrabold uppercase tracking-[0.08em] text-tinta-suave">{{ $etiqueta }}</dt><dd class="font-bold cifra">{{ $valor }}</dd></div>
                                    @empty
                                        <p class="apoyo text-sm sm:col-span-3">Te enviamos los datos de la cuenta por WhatsApp.</p>
                                    @endforelse
                                </dl>
                            @endforeach
                        </div>
                    @endif
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
                        @php($moneda = $credito ? \App\Support\MetodosPago::moneda(old('metodo', array_key_first($metodosAbono))) : \App\Support\MetodosPago::moneda($pedido->metodo_pago))
                        <label for="monto" class="etiqueta">Monto pagado *</label>
                        <div class="grupo-campo">
                            <span class="unidad" @if($credito) x-text="metodos[metodo].moneda === 'USD' ? 'USD' : 'Bs.'" @endif>{{ $moneda === 'USD' ? 'USD' : 'Bs.' }}</span>
                            <input type="number" id="monto" name="monto" x-ref="monto" value="{{ old('monto', $credito ? ($moneda === 'USD' ? $pedido->saldoUsd() : $pedido->saldoBs()) : \App\Support\MetodosPago::montoEnMoneda($moneda, $pedido->total, $pedido->total_bs)) }}" class="campo cifra @error('monto') is-invalid @enderror" required min="0.01" step="0.01">
                        </div>
                        @if($credito)<p class="ayuda">Puedes abonar una parte; se propone el saldo completo.</p>@endif
                        <p class="error-campo">@error('monto'){{ $message }}@else Indica el monto. @enderror</p>
                    </div>
                    <div>
                        <label for="comprobante" class="etiqueta">Comprobante (opcional)</label>
                        <input type="file" id="comprobante" name="comprobante" accept="image/jpeg,image/png,image/webp,application/pdf" class="campo @error('comprobante') is-invalid @enderror">
                        <p class="error-campo">@error('comprobante'){{ $message }}@enderror</p>
                        <p class="ayuda">Captura o PDF, hasta 5 MB.</p>
                    </div>
                    <button class="btn btn-sol btn-grande sm:col-span-2 sm:justify-self-start" data-cargando="Enviando…">{{ $credito ? 'Reportar abono' : 'Reportar pago' }} <span class="chip"><x-icono nombre="enviar" class="size-4" /></span></button>
                </form>
            </section>
        @endif

        {{-- Cancelar o devolver --}}
        @if($pedido->estado === 'anulado')
            <div class="aviso aviso-error" role="status"><x-icono nombre="alerta" class="size-5 shrink-0" />
                <p><strong>Pedido anulado.</strong> @if($pedido->motivo_anulacion){{ $pedido->motivo_anulacion }}@endif
                @if($pedido->estado_pago === 'reembolsado') Te devolvemos el dinero por el mismo medio de pago.@endif</p></div>
        @elseif($pedido->devolucionPendiente())
            <div class="aviso aviso-atencion" role="status"><x-icono nombre="info" class="size-5 shrink-0" />
                <p><strong>Solicitaste cancelar o devolver este pedido</strong> el <span class="cifra">{{ $pedido->devolucion_solicitada_en->format('d/m/Y') }}</span>: «{{ $pedido->devolucion_motivo }}». Te contactaremos para coordinarlo.</p></div>
        @else
            <details class="lobulo p-4 sm:p-5">
                <summary class="cursor-pointer font-bold text-coral-tinta">{{ $pedido->clientePuedeCancelar() ? '¿Quieres cancelar este pedido?' : '¿Necesitas devolver este pedido?' }}</summary>
                <form method="POST" action="{{ $urlDevolucion }}" class="needs-validation mt-4 space-y-3" novalidate>
                    @csrf
                    <label for="motivo" class="etiqueta">Motivo *</label>
                    <textarea id="motivo" name="motivo" rows="2" class="campo @error('motivo') is-invalid @enderror" required minlength="5" maxlength="500" placeholder="Ej. pedí el repuesto equivocado">{{ old('motivo') }}</textarea>
                    <p class="error-campo">@error('motivo'){{ $message }}@else Cuéntanos el motivo. @enderror</p>
                    <p class="ayuda">{{ $pedido->clientePuedeCancelar() ? 'Como aún no lo pagas, se cancela de inmediato.' : 'Revisamos tu solicitud y te contactamos para recibir los productos y devolverte el dinero.' }}</p>
                    <button class="btn btn-linea !border-coral-tinta !text-coral-tinta">{{ $pedido->clientePuedeCancelar() ? 'Cancelar pedido' : 'Solicitar devolución' }}</button>
                </form>
            </details>
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
