@extends('layouts.app')
@section('titulo', 'Pagos')

@section('contenido')
<x-encabezado titulo="Pagos">
    Pagos que reportan los clientes desde el portal. Revisa la referencia en el banco y verifícalo: el pedido queda pagado y la venta se confirma.
</x-encabezado>
<x-pestanas :opciones="['por_verificar' => 'Por verificar', 'verificado' => 'Verificados', 'rechazado' => 'Rechazados', 'todos' => 'Todos']" :actual="$estado" ruta="gestion.pagos.index" />

@if($pagos->isEmpty())
    <div class="lobulo px-6 py-10 text-center">
        <p class="text-lg font-bold">{{ $estado === 'por_verificar' ? 'No hay pagos esperando verificación.' : 'No hay pagos en esta lista.' }}</p>
        <p class="apoyo mt-1">Cuando un cliente reporte un pago, aparece aquí.</p>
    </div>
@else
    <ul class="space-y-3">
        @foreach($pagos as $pago)
            @php($pedido = $pago->pedido)
            <li class="lobulo p-4 sm:p-5" x-data="{ rechazar: false }">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('gestion.pedidos.show', $pedido) }}" class="enlace font-extrabold cifra">{{ $pedido->codigo }}</a>
                            <span class="estado estado-{{ $pago->estado }}">{{ $pago->etiquetaEstado() }}</span>
                        </p>
                        <p class="mt-1 font-semibold">{{ $pedido->cliente->nombre }} <span class="apoyo cifra text-sm">· {{ $pedido->cliente->identificacion }} · {{ $pedido->cliente->telefono }}</span></p>
                        <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
                            <div><dt class="apoyo">Método</dt><dd class="font-bold">{{ $pago->nombreMetodo() }}</dd></div>
                            <div><dt class="apoyo">Reportó</dt><dd class="font-bold cifra">{{ $pago->montoFormateado() }}</dd></div>
                            <div><dt class="apoyo">Debía pagar</dt><dd class="font-bold cifra">{{ $pedido->montoAPagar() }}</dd></div>
                            <div><dt class="apoyo">Referencia · fecha</dt><dd class="font-bold cifra">{{ $pago->referencia ?? '—' }} · {{ $pago->fecha_pago->format('d/m/Y') }}</dd></div>
                        </dl>
                        @if($pago->motivo_rechazo)<p class="mt-2 text-sm font-semibold text-coral-tinta">Motivo del rechazo: {{ $pago->motivo_rechazo }}</p>@endif
                        @if($pago->verificadoPor)<p class="apoyo mt-2 text-sm">{{ $pago->etiquetaEstado() }} por {{ $pago->verificadoPor->nombre }} el <span class="cifra">{{ $pago->verificado_en->format('d/m/Y h:i a') }}</span></p>@endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if($pago->comprobante)
                            <a href="{{ route('gestion.pagos.comprobante', $pago) }}" target="_blank" rel="noopener" class="btn btn-linea btn-mini"><x-icono nombre="ojo" class="size-4" /> Captura del cliente</a>
                        @endif
                        @if($pago->estado === 'por_verificar')
                            <a href="{{ route('gestion.pagos.vista-previa', $pago) }}" target="_blank" rel="noopener" class="btn btn-linea btn-mini"><x-icono nombre="pdf" class="size-4" /> Vista previa del comprobante</a>
                        @elseif($pago->estado === 'verificado' && $pedido->tieneComprobante())
                            <a href="{{ route('gestion.pedidos.comprobante', $pedido) }}" target="_blank" rel="noopener" class="btn btn-linea btn-mini"><x-icono nombre="pdf" class="size-4" /> Comprobante de pago</a>
                        @endif
                        @if($pago->estado === 'por_verificar')
                            <form method="POST" action="{{ route('gestion.pagos.verificar', $pago) }}" data-confirmar="¿Confirmas que el pago de {{ $pago->montoFormateado() }} está en la cuenta? El pedido {{ $pedido->codigo }} quedará pagado y se descontará la existencia.">
                                @csrf
                                <button class="btn btn-sol btn-mini"><x-icono nombre="check" class="size-4" /> Verificar</button>
                            </form>
                            <button type="button" class="enlace-peligro text-sm" @click="rechazar = ! rechazar" :aria-expanded="rechazar">Rechazar</button>
                        @endif
                    </div>
                </div>
                @if($pago->estado === 'por_verificar')
                    <form method="POST" action="{{ route('gestion.pagos.rechazar', $pago) }}" class="mt-4 flex flex-col gap-3 border-t border-linea pt-4 sm:flex-row sm:items-end" x-show="rechazar" x-cloak>
                        @csrf
                        <div class="flex-1">
                            <label for="motivo-{{ $pago->id }}" class="etiqueta">Motivo (el cliente lo verá)</label>
                            <input type="text" id="motivo-{{ $pago->id }}" name="motivo" class="campo" required minlength="5" maxlength="255" placeholder="Ej. la referencia no aparece en el banco">
                        </div>
                        <button class="btn btn-linea !border-coral-tinta !text-coral-tinta">Rechazar pago</button>
                    </form>
                @endif
            </li>
        @endforeach
    </ul>
    {{ $pagos->links() }}
@endif
@endsection
