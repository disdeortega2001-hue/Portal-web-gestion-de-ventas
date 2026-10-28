@extends('layouts.app')
@section('titulo', $pedido->codigo)

@section('contenido')
<x-encabezado :titulo="'Pedido ' . $pedido->codigo" :volver="route('gestion.pedidos.index')" volver-texto="Pedidos">
    <span class="cifra">{{ $pedido->fecha->format('d/m/Y h:i a') }}</span> · {{ $pedido->etiquetaOrigen() }} · Vendedor: {{ $pedido->vendedor?->nombre ?? 'por asignar' }}
    @if($pedido->cotizacion) · Desde <span class="cifra">{{ $pedido->cotizacion->codigo }}</span> @endif
    · <span class="estado estado-{{ $pedido->estado }}">{{ ucfirst($pedido->estado) }}</span>
    <span class="estado estado-{{ $pedido->estado_pago }}">{{ $pedido->etiquetaPago() }}</span>
</x-encabezado>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="space-y-6">
        <section class="lobulo p-5" aria-label="Cliente">
            <p class="text-lg font-extrabold">{{ $pedido->cliente->nombre }}</p>
            <p class="mt-1 cifra">{{ $pedido->cliente->identificacion }} · {{ $pedido->cliente->telefono }}</p>
            <p class="apoyo">{{ $pedido->cliente->direccion }}</p>
            @if($pedido->metodo_pago || $pedido->entrega)
                <p class="mt-3 border-t border-linea pt-3 text-sm">
                    @if($pedido->metodo_pago)<strong>Paga con:</strong> {{ $pedido->nombreMetodoPago() }} ({{ $pedido->montoAPagar() }})@endif
                    @if($pedido->entrega) · <strong>Entrega:</strong> {{ $pedido->entrega === 'envio' ? 'envío a coordinar' : 'retiro en la tienda' }}@endif
                </p>
            @endif
            @if($pedido->observaciones)<p class="apoyo mt-1 text-sm">«{{ $pedido->observaciones }}»</p>@endif
        </section>

        {{-- Pagos del pedido: reportados por el cliente o cobrados en la tienda --}}
        <section aria-labelledby="t-pagos">
            <h2 id="t-pagos" class="seccion-titulo">Pagos</h2>
            @forelse($pedido->pagos as $pago)
                <div class="lobulo mt-3 flex flex-wrap items-center justify-between gap-3 p-4">
                    <div>
                        <p class="font-bold">{{ $pago->nombreMetodo() }} · <span class="cifra">{{ $pago->montoFormateado() }}</span></p>
                        <p class="apoyo text-sm cifra">{{ $pago->fecha_pago->format('d/m/Y') }}@if($pago->referencia) · Ref. {{ $pago->referencia }}@endif
                            @if($pago->registradoPor) · cobrado por {{ $pago->registradoPor->nombre }}@endif</p>
                        @if($pago->motivo_rechazo)<p class="mt-1 text-sm font-semibold text-coral-tinta">Rechazado: {{ $pago->motivo_rechazo }}</p>@endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="estado estado-{{ $pago->estado }}">{{ $pago->etiquetaEstado() }}</span>
                        @if($pago->comprobante && auth()->user()->puedeAdministrar())
                            <a href="{{ route('gestion.pagos.comprobante', $pago) }}" target="_blank" rel="noopener" class="btn btn-linea btn-mini"><x-icono nombre="ojo" class="size-4" /> Comprobante</a>
                        @endif
                        @if($pago->estado === 'por_verificar' && auth()->user()->puedeAdministrar())
                            <a href="{{ route('gestion.pagos.index') }}" class="btn btn-sol btn-mini">Verificar</a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="apoyo mt-2">Todavía no hay pagos registrados.</p>
            @endforelse

            @if(auth()->user()->puedeAdministrar() && $pedido->estado !== 'anulado' && ! $pedido->estaPagado())
                <form method="POST" action="{{ route('gestion.pedidos.pagos.store', $pedido) }}" class="needs-validation lobulo mt-4 grid gap-4 p-4 sm:grid-cols-2 sm:items-end" novalidate
                      x-data="{ metodo: @js(old('metodo', $pedido->metodo_pago ?? 'efectivo')), monedas: @js(collect(\App\Support\MetodosPago::METODOS)->map(fn ($m) => $m['moneda'])), usd: @js(number_format((float) $pedido->total, 2, '.', '')), bs: @js(number_format((float) $pedido->total_bs, 2, '.', '')) }">
                    @csrf
                    <div>
                        <label for="metodo" class="etiqueta">Cobrado con</label>
                        <select id="metodo" name="metodo" class="campo" required x-model="metodo" @change="$refs.monto.value = monedas[$event.target.value] === 'USD' ? usd : bs">
                            @foreach(\App\Support\MetodosPago::METODOS as $clave => $metodo)
                                <option value="{{ $clave }}" @selected(old('metodo', $pedido->metodo_pago ?? 'efectivo') === $clave)>{{ $metodo['nombre'] }} ({{ $metodo['moneda'] === 'USD' ? 'USD' : 'Bs.' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="monto" class="etiqueta">Monto (<span x-text="monedas[metodo] === 'USD' ? 'USD' : 'Bs.'">USD</span>)</label>
                        {{-- El monto se llena con el total del pedido en la moneda del método (USD o Bs.); se puede cambiar --}}
                        <input type="number" id="monto" name="monto" value="{{ old('monto') }}" class="campo cifra @error('monto') is-invalid @enderror" required min="0.01" step="0.01"
                               x-ref="monto" x-init="$el.value ||= (monedas[metodo] === 'USD' ? usd : bs)">
                        <p class="error-campo">@error('monto'){{ $message }}@else Indica el monto. @enderror</p>
                    </div>
                    <div>
                        <label for="referencia" class="etiqueta">Referencia (opcional)</label>
                        <input type="text" id="referencia" name="referencia" value="{{ old('referencia') }}" class="campo cifra" maxlength="60">
                    </div>
                    <button class="btn btn-tierra">Registrar pago</button>
                    <p class="ayuda sm:col-span-2">Para cobros en la tienda o pagos ya confirmados con el banco: quedan verificados al registrarlos{{ $pedido->estaPendiente() ? ' y la venta se confirma' : '' }}.</p>
                </form>
            @endif
        </section>
        <div class="lobulo overflow-x-auto">
            <table class="tabla">
                <thead><tr><th>Código</th><th>Producto</th><th class="!text-right">Cant.</th><th class="!text-right">Precio</th><th class="!text-right">Subtotal</th>@if($pedido->estaPendiente())<th class="!text-right">Stock</th>@endif</tr></thead>
                <tbody>
                @foreach($pedido->detalles as $detalle)
                    <tr @class(['!bg-coral-clara' => $pedido->estaPendiente() && $detalle->cantidad > $detalle->producto->stock])>
                        <td><span class="codigo">{{ $detalle->producto->codigo_fabrica }}</span></td>
                        <td>{{ $detalle->producto->nombre }}</td>
                        <td class="num">{{ $detalle->cantidad }}</td>
                        <td class="num">@usd($detalle->precio_unitario)</td>
                        <td class="num font-bold">@usd($detalle->subtotal)</td>
                        @if($pedido->estaPendiente())<td class="num">{{ $detalle->producto->stock }}</td>@endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($pedido->movimientos->isNotEmpty())
            <section aria-labelledby="t-mov">
                <h2 id="t-mov" class="seccion-titulo">Movimientos de inventario</h2>
                <ul class="mt-3 space-y-2">
                    @foreach($pedido->movimientos as $mov)
                        <li class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-[3px] bg-agua-honda px-4 py-2.5 text-sm">
                            <span class="cifra">{{ $mov->fecha->format('d/m/Y h:i a') }}</span>
                            <span class="font-bold">{{ ucfirst($mov->tipo) }} de {{ $mov->cantidad }}</span>
                            <span class="cifra">{{ $mov->stock_anterior }} → {{ $mov->stock_nuevo }}</span>
                            <span class="apoyo">{{ $mov->usuario->nombre }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <div class="space-y-4 lg:sticky lg:top-6 lg:self-start">
        @include('partials.resumen', ['r' => $pedido])

        @if($pedido->estaPendiente())
            <form method="POST" action="{{ route('gestion.pedidos.confirmar', $pedido) }}"
                  data-confirmar="¿Confirmar la venta? Se descontará el stock de inmediato.">
                @csrf
                <button class="btn btn-sol btn-grande w-full">Confirmar venta <span class="chip"><x-icono nombre="check" class="size-4" /></span></button>
            </form>
        @endif
        {{-- Anular: aislado y en contorno, lejos de «Confirmar venta» --}}
        @if($pedido->estado !== 'anulado')
            <form method="POST" action="{{ route('gestion.pedidos.anular', $pedido) }}" class="border-t border-linea pt-5 !mt-8"
                  data-confirmar="¿Anular el pedido {{ $pedido->codigo }}?{{ $pedido->estado === 'confirmado' ? ' El stock será repuesto.' : '' }}">
                @csrf
                <button class="btn btn-linea w-full !border-coral-tinta !text-coral-tinta hover:!bg-coral-clara">Anular pedido</button>
            </form>
        @endif
    </div>
</div>
@endsection
