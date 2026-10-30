@extends('layouts.app')
@section('titulo', 'Créditos')

@section('contenido')
<x-encabezado titulo="Créditos">
    Compras a crédito con saldo pendiente. Cada 30 días se le recuerda el pago al cliente: por correo sale solo; si no tiene correo, envíalo por WhatsApp desde aquí.
</x-encabezado>

<dl class="mb-6 grid grid-cols-2 gap-px border border-linea bg-linea sm:grid-cols-3">
    <div class="bg-tierra-clara p-4"><dt class="text-sm text-tierra">Por cobrar</dt><dd class="font-rotulo text-3xl font-extrabold text-tierra cifra">@usd($porCobrar)</dd></div>
    <div class="bg-white p-4"><dt class="apoyo text-sm">Créditos vigentes</dt><dd class="font-rotulo text-3xl font-extrabold cifra">{{ $conteos['vigentes'] }}</dd></div>
    <div class="col-span-2 bg-white p-4 sm:col-span-1"><dt class="apoyo text-sm">Toca recordar hoy</dt><dd class="font-rotulo text-3xl font-extrabold cifra">{{ $conteos['por_recordar'] }}</dd></div>
</dl>

<x-pestanas :opciones="['vigentes' => 'Vigentes', 'por_recordar' => 'Por recordar', 'saldados' => 'Saldados']" :actual="$estado" ruta="gestion.creditos.index" :conteos="$conteos" :atencion="['por_recordar']" />

@if($creditos->isEmpty())
    <div class="lobulo px-6 py-10 text-center">
        <p class="text-lg font-bold">{{ match ($estado) { 'por_recordar' => 'No hay recordatorios pendientes.', 'saldados' => 'Todavía no hay créditos saldados.', default => 'No hay créditos con saldo pendiente.' } }}</p>
        <p class="apoyo mt-1">Para vender a crédito, autoriza el crédito en la ficha del cliente.</p>
    </div>
@else
    <ul class="space-y-3">
        @foreach($creditos as $pedido)
            @php($proximo = $pedido->proximoRecordatorio())
            <li class="lobulo p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('gestion.pedidos.show', $pedido) }}" class="enlace font-extrabold cifra">{{ $pedido->codigo }}</a>
                            <span class="estado estado-{{ $pedido->estado }}">{{ ucfirst($pedido->estado) }}</span>
                            <span class="estado estado-{{ $pedido->estado_pago }}">{{ $pedido->etiquetaPago() }}</span>
                            @if($pedido->recordatorioVencido())<span class="estado estado-por_verificar">Toca recordar</span>@endif
                        </p>
                        <p class="mt-1 font-semibold"><a href="{{ route('gestion.clientes.show', $pedido->cliente) }}" class="hover:underline">{{ $pedido->cliente->nombre }}</a>
                            <span class="apoyo cifra text-sm">· {{ $pedido->cliente->identificacion }} · {{ $pedido->cliente->telefono }}</span></p>
                        <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-4">
                            <div><dt class="apoyo">Compra</dt><dd class="font-bold cifra">{{ $pedido->fecha->format('d/m/Y') }} · @usd($pedido->total)</dd></div>
                            <div><dt class="apoyo">Abonado</dt><dd class="font-bold cifra">@usd($pedido->abonadoUsd())</dd></div>
                            <div><dt class="apoyo">Saldo</dt><dd class="font-bold text-tierra cifra">@usd($pedido->saldoUsd())</dd></div>
                            <div><dt class="apoyo">Próximo recordatorio</dt>
                                <dd class="font-bold cifra">
                                    @if($proximo){{ $proximo->isPast() ? 'Vencido (' . $proximo->format('d/m/Y') . ')' : $proximo->format('d/m/Y') }}
                                    @elseif($pedido->estado === 'pendiente') Al confirmar la venta
                                    @else — @endif
                                </dd></div>
                        </dl>
                        @if($pedido->recordatorios_enviados)
                            <p class="apoyo mt-2 text-sm">Recordatorios enviados: <span class="cifra">{{ $pedido->recordatorios_enviados }}</span>, el último el <span class="cifra">{{ $pedido->ultimo_recordatorio_en->format('d/m/Y') }}</span>.</p>
                        @endif
                    </div>
                    @if($pedido->creditoVigente())
                        <div class="flex flex-wrap items-center gap-2">
                            <form method="POST" action="{{ route('gestion.creditos.recordar', $pedido) }}" target="_blank">
                                @csrf<button class="btn btn-tierra btn-mini"><x-icono nombre="whatsapp" class="size-4" /> Recordar por WhatsApp</button>
                            </form>
                            @if($pedido->cliente->correo || $pedido->cliente->usuario?->correo)
                                <form method="POST" action="{{ route('gestion.creditos.recordar', $pedido) }}">
                                    @csrf<input type="hidden" name="via" value="correo"><button class="btn btn-linea btn-mini"><x-icono nombre="enviar" class="size-4" /> Por correo</button>
                                </form>
                            @endif
                            <a href="{{ route('gestion.pedidos.show', $pedido) }}#t-pagos" class="btn btn-sol btn-mini">Registrar abono</a>
                        </div>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@endif
@endsection
