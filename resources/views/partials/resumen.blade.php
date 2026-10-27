{{-- Resumen de totales. Recibe $r con subtotal, descuento, impuesto, total, total_bs, tasa_cambio, porcentaje_iva --}}
<div class="lobulo-tierra p-5 sm:p-6">
    <dl class="space-y-2 text-white/85 cifra">
        <div class="flex justify-between gap-4"><dt>Subtotal</dt><dd>@usd($r['subtotal'])</dd></div>
        @if((float) $r['descuento'] > 0)
            <div class="flex justify-between gap-4 text-sabana"><dt>Descuento por volumen</dt><dd>− @usd($r['descuento'])</dd></div>
        @endif
        <div class="flex justify-between gap-4"><dt>IVA ({{ rtrim(rtrim(number_format((float) $r['porcentaje_iva'], 2, ',', ''), '0'), ',') }}%)</dt><dd>@usd($r['impuesto'])</dd></div>
    </dl>
    <div class="mt-4 border-t border-white/25 pt-4">
        <div class="flex items-baseline justify-between gap-4">
            <span class="text-lg font-bold">Total</span>
            <span class="titular text-[2rem] cifra">@usd($r['total'])</span>
        </div>
        <p class="mt-1 text-right text-sm text-white/75 cifra">@bs($r['total_bs']) · tasa {{ number_format((float) $r['tasa_cambio'], 2, ',', '.') }}</p>
    </div>
</div>
