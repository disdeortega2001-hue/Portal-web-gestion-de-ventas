@extends('layouts.app')
@section('titulo', 'Tablero')

@php
    $usd = fn ($v) => 'USD ' . number_format((float) $v, 2, ',', '.');
    $m = $metricas;
@endphp

@section('contenido')
<x-encabezado titulo="Tablero">
    Actualizado: <span data-m="actualizado" class="cifra">{{ $m['actualizado'] }}</span>
    <x-slot:acciones>
        {{-- Lo que se hace a diario, a un toque --}}
        <a href="{{ route('gestion.pedidos.create') }}" class="btn btn-sol"><x-icono nombre="mas" class="size-4" /> Nueva venta</a>
        @if(auth()->user()->puedeAdministrar())
            <a href="{{ route('gestion.inventario.index') }}" class="btn btn-linea"><x-icono nombre="caja" class="size-4" /> Registrar entrada</a>
            <a href="{{ route('admin.tasa.edit') }}" class="btn btn-linea"><x-icono nombre="moneda" class="size-4" /> Tasa del día</a>
        @endif
    </x-slot:acciones>
</x-encabezado>

<div class="space-y-3 empty:hidden mb-6">
    {{-- Cuentas nuevas con una cédula que ya tenía historial: esperan una llamada de verificación --}}
    @php($porVerificar = \App\Models\Cliente::whereNotNull('id_usuario_solicitante')->count())
    @if($porVerificar)
        <div class="aviso aviso-info flex-col sm:flex-row sm:items-center" role="status">
            <x-icono nombre="usuario-check" class="size-6 shrink-0" />
            <div class="flex-1"><strong>{{ $porVerificar }} {{ $porVerificar === 1 ? 'cuenta espera' : 'cuentas esperan' }} verificación.</strong>
                Llama al teléfono de la ficha para confirmar que es la misma persona antes de darle acceso a su historial.</div>
            <a href="{{ route('gestion.verificaciones.index') }}" class="btn btn-linea btn-mini">Verificar cuentas</a>
        </div>
    @endif

    {{-- Primer uso: sin WhatsApp configurado, los clientes no ven ningún botón de contacto --}}
    @unless(\App\Support\Contacto::numero())
        <div class="aviso aviso-atencion flex-col sm:flex-row sm:items-center" role="status">
            <x-icono nombre="whatsapp" class="size-6 shrink-0" />
            <div class="flex-1"><strong>Los clientes todavía no pueden escribirte.</strong>
                Falta el WhatsApp de atención: sin él no aparecen el botón de contacto, «Preguntar cuándo llega» en los agotados ni «Escribir al vendedor» al cotizar.</div>
            @if(auth()->user()->esDirectivo())
                <a href="{{ route('admin.configuracion.edit') }}#telefono_whatsapp" class="btn btn-tierra btn-mini">Configurar WhatsApp</a>
            @else
                <span class="text-sm font-semibold">Pídele al directivo que lo configure.</span>
            @endif
        </div>
    @endunless
</div>

{{--
    Las cifras se imprimen desde el servidor: el tablero se lee aunque JavaScript o los gráficos no carguen.
    JavaScript solo las refresca cada 60 s (data-m) y dibuja el gráfico.
--}}
<div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
    {{-- Por atender: lo primero en el celular. Solo lo que espera a alguien se pinta de amarillo --}}
    <section class="lobulo order-1 overflow-hidden lg:order-2 lg:col-span-4" aria-labelledby="t-atender">
        <h2 id="t-atender" class="seccion-titulo bg-rialto px-5 py-3.5 text-white">Por atender</h2>
        <ul class="divide-y divide-linea">
            <li>
                <a href="{{ route('gestion.cotizaciones.index') }}" class="fila-atender">
                    <span @class(['cuenta-atender', 'con-pendientes' => $m['cotizaciones']['pendientes'] > 0]) data-m="cotizaciones.pendientes" data-contador>{{ $m['cotizaciones']['pendientes'] }}</span>
                    <span class="min-w-0 flex-1"><span class="block font-bold">Cotizaciones pendientes</span>
                        <span class="apoyo block text-sm"><span data-m="cotizaciones.atrasadas" class="cifra">{{ $m['cotizaciones']['atrasadas'] }}</span> con más de 24 h</span></span>
                    <x-icono nombre="flecha" class="size-4 shrink-0" />
                </a>
            </li>
            @if(auth()->user()->puedeAdministrar())
                @php($pagosPorVerificar = \App\Models\Pago::where('estado', \App\Models\Pago::POR_VERIFICAR)->count())
                @php($creditosPorRecordar = \App\Http\Controllers\Gestion\CreditoController::porRecordar())
                <li>
                    <a href="{{ route('gestion.pagos.index') }}" class="fila-atender">
                        <span @class(['cuenta-atender', 'con-pendientes' => $pagosPorVerificar > 0])>{{ $pagosPorVerificar }}</span>
                        <span class="min-w-0 flex-1 font-bold">Pagos por verificar</span>
                        <x-icono nombre="flecha" class="size-4 shrink-0" />
                    </a>
                </li>
                <li>
                    <a href="{{ route('gestion.creditos.index', ['estado' => 'por_recordar']) }}" class="fila-atender">
                        <span @class(['cuenta-atender', 'con-pendientes' => $creditosPorRecordar > 0])>{{ $creditosPorRecordar }}</span>
                        <span class="min-w-0 flex-1 font-bold">Créditos por recordar</span>
                        <x-icono nombre="flecha" class="size-4 shrink-0" />
                    </a>
                </li>
            @endif
            <li>
                <a href="{{ route('gestion.inventario.index', ['bajo_stock' => 1]) }}" class="fila-atender">
                    <span @class(['cuenta-atender', 'con-pendientes' => $m['stock']['bajo'] > 0]) data-m="stock.bajo" data-contador>{{ $m['stock']['bajo'] }}</span>
                    <span class="min-w-0 flex-1"><span class="block font-bold">Productos con bajo stock</span>
                        <span class="apoyo block text-sm">Umbral: <span data-m="stock.umbral" class="cifra">{{ $m['stock']['umbral'] }}</span> unidades</span></span>
                    <x-icono nombre="flecha" class="size-4 shrink-0" />
                </a>
            </li>
            <li>
                <a href="{{ route('gestion.inventario.index', ['bajo_stock' => 1]) }}" class="fila-atender">
                    <span @class(['cuenta-atender', 'con-pendientes' => $m['stock']['agotados'] > 0]) data-m="stock.agotados" data-contador>{{ $m['stock']['agotados'] }}</span>
                    <span class="min-w-0 flex-1 font-bold">Productos agotados</span>
                    <x-icono nombre="flecha" class="size-4 shrink-0" />
                </a>
            </li>
        </ul>
    </section>

    {{-- Ventas: las cifras y su gráfico juntos, en una sola hoja --}}
    <section class="lobulo order-2 lg:order-1 lg:col-span-8" aria-labelledby="t-ventas">
        <dl class="grid divide-y divide-linea border-b border-linea sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="p-5 xl:px-6">
                <dt class="text-sm font-bold text-tinta-suave">Ventas de hoy</dt>
                <dd class="titular mt-2 text-2xl text-rialto cifra" data-m="ventas_hoy.total" data-formato="usd">{{ $usd($m['ventas_hoy']['total']) }}</dd>
                <dd class="apoyo mt-1 text-sm"><span data-m="ventas_hoy.pedidos" class="cifra">{{ $m['ventas_hoy']['pedidos'] }}</span> pedidos confirmados</dd>
            </div>
            <div class="p-5 xl:px-6">
                <dt class="text-sm font-bold text-tinta-suave">Ventas del mes</dt>
                <dd class="titular mt-2 text-2xl text-rialto cifra" data-m="ventas_mes.total" data-formato="usd">{{ $usd($m['ventas_mes']['total']) }}</dd>
                <dd class="apoyo mt-1 text-sm"><span data-m="ventas_mes.pedidos" class="cifra">{{ $m['ventas_mes']['pedidos'] }}</span> pedidos confirmados</dd>
            </div>
            <div class="p-5 xl:px-6">
                <dt class="text-sm font-bold text-tinta-suave">Conversión del mes</dt>
                <dd class="titular mt-2 text-2xl text-rialto cifra"><span data-m="cotizaciones.conversion_mes">{{ number_format($m['cotizaciones']['conversion_mes'], 1, ',', '.') }}</span> %</dd>
                <dd class="apoyo mt-1 text-sm">de las cotizaciones terminó en pedido</dd>
            </div>
        </dl>
        <div class="p-5 xl:p-6">
            <h2 id="t-ventas" class="font-bold">Ventas de los últimos <span class="sm:hidden">14</span><span class="hidden sm:inline">30</span> días (USD)</h2>
            <div class="grafico relative mt-3 h-64"><canvas id="grafico-ventas" aria-label="Gráfico de ventas diarias" role="img"></canvas></div>
            <p class="apoyo mt-2 text-sm" data-sin-graficos hidden>No se pudo cargar el gráfico. Las cifras de arriba están al día.</p>
        </div>
    </section>

    {{-- Más vendidos: lista con su barra, se lee sin depender de ningún gráfico --}}
    <section class="lobulo order-4 p-5 lg:order-3 lg:col-span-6 xl:p-6" aria-labelledby="t-top">
        <h2 id="t-top" class="seccion-titulo">Más vendidos del mes</h2>
        @php($maximo = max(array_column($m['mas_vendidos'], 'unidades') ?: [1]))
        <ol id="lista-top" class="mt-4 space-y-4">
            @foreach($m['mas_vendidos'] as $p)
                <li>
                    <div class="flex items-baseline justify-between gap-3">
                        <span class="min-w-0"><span class="codigo">{{ $p['codigo'] }}</span> <span class="apoyo text-sm">{{ $p['nombre'] }}</span></span>
                        <span class="shrink-0 font-bold cifra">{{ $p['unidades'] }} und.</span>
                    </div>
                    <div class="mt-1.5 h-2 rounded-[2px] bg-agua-honda"><div class="h-full rounded-[2px] bg-tierra" style="width: {{ round($p['unidades'] / $maximo * 100) }}%"></div></div>
                </li>
            @endforeach
        </ol>
        <p class="apoyo mt-4" id="sin-ventas" @unless(empty($m['mas_vendidos'])) hidden @endunless>Aún no hay ventas confirmadas este mes.</p>
    </section>

    {{-- Alertas de inventario: cuáles son, con su existencia --}}
    <section class="lobulo order-3 p-5 lg:order-4 lg:col-span-6 xl:p-6" aria-labelledby="t-alertas">
        <h2 id="t-alertas" class="seccion-titulo">Alertas de inventario</h2>
        <ul id="tabla-alertas" class="mt-3 divide-y divide-linea">
            @forelse($m['stock']['alertas'] as $p)
                <li class="flex items-center justify-between gap-3 py-2.5">
                    <span class="min-w-0"><span class="block">{{ $p['nombre'] }}</span><span class="codigo block">{{ $p['codigo_fabrica'] }}</span></span>
                    @if($p['stock'] === 0)<span class="estado estado-agotado shrink-0">Agotado</span>
                    @else<span class="estado estado-bajo shrink-0 cifra">Bajo stock: {{ $p['stock'] }}</span>@endif
                </li>
            @empty
                <li class="apoyo py-2.5">Sin alertas: todo el inventario está sobre el umbral.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection

@push('scripts')
@vite('resources/js/graficos.js')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
// Los módulos (gráfico) corren antes de DOMContentLoaded: aquí window.Chart ya existe si cargó
document.addEventListener('DOMContentLoaded', function () {
    const URL_DATOS = @json(route('gestion.tablero.datos'));
    const usd = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const decimal1 = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
    const valor = (obj, ruta) => ruta.split('.').reduce((o, k) => (o == null ? o : o[k]), obj);
    const celular = window.matchMedia('(max-width: 639.98px)');
    function escapar(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    // Gráfico: solo si Chart.js cargó. Sin él, el tablero sigue mostrando todas las cifras.
    let graficoVentas = null;
    if (window.Chart) {
        graficoVentas = new Chart(document.getElementById('grafico-ventas'), {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Ventas USD', data: [], backgroundColor: '#0d5192', hoverBackgroundColor: '#052b70', borderRadius: 3, borderSkipped: false, maxBarThickness: 22 }] },
            options: {
                maintainAspectRatio: false, animation: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f1b2d', padding: 10, cornerRadius: 12, callbacks: { label: c => 'USD ' + usd.format(c.parsed.y) } } },
                scales: { y: { beginAtZero: true, border: { display: false }, ticks: { callback: v => usd.format(v) } }, x: { grid: { display: false }, border: { color: '#0f1b2d' }, ticks: { maxTicksLimit: 8 } } }
            }
        });
    } else {
        document.querySelectorAll('.grafico').forEach(el => el.hidden = true);
        document.querySelectorAll('[data-sin-graficos]').forEach(el => el.hidden = false);
    }

    function pintar(m) {
        document.querySelectorAll('[data-m]').forEach(function (el) {
            const v = valor(m, el.dataset.m);
            if (v === undefined) return;
            el.textContent = el.dataset.formato === 'usd' ? 'USD ' + usd.format(v)
                : (el.dataset.m === 'cotizaciones.conversion_mes' ? decimal1.format(v) : v);
            if (el.hasAttribute('data-contador')) el.classList.toggle('con-pendientes', Number(v) > 0);
        });
        if (graficoVentas) {
            // En el celular, 30 barras quedan de ~8 px: se muestran los últimos 14 días
            const dias = celular.matches ? -14 : 0;
            graficoVentas.data.labels = m.serie_ventas.etiquetas.slice(dias);
            graficoVentas.data.datasets[0].data = m.serie_ventas.valores.slice(dias);
            graficoVentas.update();
        }
        const maximo = Math.max(1, ...m.mas_vendidos.map(p => p.unidades));
        document.getElementById('lista-top').innerHTML = m.mas_vendidos.map(p => '<li><div class="flex items-baseline justify-between gap-3"><span class="min-w-0"><span class="codigo">' + escapar(p.codigo)
            + '</span> <span class="apoyo text-sm">' + escapar(p.nombre) + '</span></span><span class="shrink-0 font-bold cifra">' + p.unidades + ' und.</span></div>'
            + '<div class="mt-1.5 h-2 rounded-[2px] bg-agua-honda"><div class="h-full rounded-[2px] bg-tierra" style="width: ' + Math.round(p.unidades / maximo * 100) + '%"></div></div></li>').join('');
        document.getElementById('sin-ventas').hidden = m.mas_vendidos.length > 0;
        document.getElementById('tabla-alertas').innerHTML = m.stock.alertas.length
            ? m.stock.alertas.map(p => '<li class="flex items-center justify-between gap-3 py-2.5"><span class="min-w-0"><span class="block">' + escapar(p.nombre)
                + '</span><span class="codigo block">' + escapar(p.codigo_fabrica) + '</span></span>'
                + (p.stock === 0 ? '<span class="estado estado-agotado shrink-0">Agotado</span>' : '<span class="estado estado-bajo shrink-0 cifra">Bajo stock: ' + p.stock + '</span>') + '</li>').join('')
            : '<li class="apoyo py-2.5">Sin alertas: todo el inventario está sobre el umbral.</li>';
    }

    let ultimas = @json($metricas);
    pintar(ultimas);
    // Chart.js mide las etiquetas al dibujar: se repite al cargar la fuente para que no queden cortadas
    if (graficoVentas && document.fonts) document.fonts.ready.then(() => graficoVentas.update());
    celular.addEventListener('change', function () { pintar(ultimas); });

    // Tiempo real: cada 60 s, solo con la pestaña visible (ahorra datos del plan móvil)
    let intervalo = null;
    async function refrescar() {
        try {
            const r = await fetch(URL_DATOS, { headers: { 'Accept': 'application/json' } });
            if (r.ok) { ultimas = await r.json(); pintar(ultimas); }
        } catch (e) { /* sin conexión: se conservan los últimos datos */ }
    }
    function iniciar() { if (!intervalo) intervalo = setInterval(refrescar, 60000); }
    function detener() { clearInterval(intervalo); intervalo = null; }
    document.addEventListener('visibilitychange', function () { if (document.hidden) { detener(); } else { refrescar(); iniciar(); } });
    iniciar();
});
</script>
@endpush
