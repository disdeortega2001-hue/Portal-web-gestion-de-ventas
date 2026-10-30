{{--
    Resumen de totales. Recibe $r (cálculo, cotización o pedido) con subtotal, tipo_impuesto (IVA o IGTF),
    porcentaje_impuesto, impuesto, total, total_bs y tasa_cambio.
    En dólares se cobra IGTF; en bolívares, IVA.
--}}
@php
    $tipo = $r['tipo_impuesto'] ?? 'IVA';
    $porcentaje = rtrim(rtrim(number_format((float) ($r['porcentaje_impuesto'] ?? 0), 2, ',', ''), '0'), ',');
@endphp
<div class="lobulo-tierra panel-tapa p-5 pt-7 sm:p-6 sm:pt-8">
    <dl class="space-y-2 text-white/85 cifra">
        <div class="flex justify-between gap-4"><dt>Subtotal</dt><dd>@usd($r['subtotal'])</dd></div>
        @if((float) ($r['monto_exento'] ?? 0) > 0 && $tipo === 'IVA')
            <div class="flex justify-between gap-4 text-sm"><dt>Exento de IVA <span class="text-xs text-white/60">implementos Tatu</span></dt><dd>@usd($r['monto_exento'])</dd></div>
        @endif
        <div class="flex justify-between gap-4"><dt>{{ $tipo }} ({{ $porcentaje }}%) <span class="text-xs text-white/60">{{ $tipo === 'IGTF' ? 'pago en dólares' : 'pago en bolívares' }}</span></dt><dd>@usd($r['impuesto'])</dd></div>
    </dl>
    <div class="mt-4 border-t border-white/25 pt-4">
        <div class="flex items-baseline justify-between gap-4">
            <span class="text-lg font-bold">Total</span>
            <span class="titular text-[2rem] cifra">@usd($r['total'])</span>
        </div>
        <p class="mt-1 text-right text-sm text-white/75 cifra">@bs($r['total_bs']) · tasa BCV {{ number_format((float) $r['tasa_cambio'], 2, ',', '.') }}</p>
    </div>
</div>
