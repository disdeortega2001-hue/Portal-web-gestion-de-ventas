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
        <a href="{{ route('gestion.inventario.index') }}" class="btn btn-linea"><x-icono nombre="caja" class="size-4" /> Registrar entrada</a>
        @if(auth()->user()->esAdministrador())
            <a href="{{ route('admin.configuracion.edit') }}" class="btn btn-linea"><x-icono nombre="moneda" class="size-4" /> Tasa del día</a>
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
            @if(auth()->user()->esAdministrador())
                <a href="{{ route('admin.configuracion.edit') }}#telefono_whatsapp" class="btn btn-tierra btn-mini">Configurar WhatsApp</a>
            @else
                <span class="text-sm font-semibold">Pídele al administrador que lo configure.</span>
            @endif
        </div>
    @endunless
</div>

{{--
    Las cifras se imprimen desde el servidor: el tablero se lee aunque JavaScript o los gráficos no carguen.
    JavaScript solo las refresca cada 60 s (data-m) y dibuja los gráficos.
--}}
<section aria-label="Indicadores" class="lobulo-tierra grid overflow-hidden sm:grid-cols-2 xl:grid-cols-4">
    <div class="border-white/15 p-5 sm:border-r xl:p-6">
        <p class="text-sm font-bold text-white/75">Ventas de hoy</p>
        <p class="titular mt-2 text-[2rem] cifra" data-m="ventas_hoy.total" data-formato="usd">{{ $usd($m['ventas_hoy']['total']) }}</p>
        <p class="mt-1 text-sm text-white/75"><span data-m="ventas_hoy.pedidos" class="cifra">{{ $m['ventas_hoy']['pedidos'] }}</span> pedidos confirmados</p>
    </div>
    <div class="border-t border-white/15 p-5 sm:border-t-0 xl:border-r xl:p-6">
        <p class="text-sm font-bold text-white/75">Ventas del mes</p>
        <p class="titular mt-2 text-[2rem] cifra" data-m="ventas_mes.total" data-formato="usd">{{ $usd($m['ventas_mes']['total']) }}</p>
        <p class="mt-1 text-sm text-white/75"><span data-m="ventas_mes.pedidos" class="cifra">{{ $m['ventas_mes']['pedidos'] }}</span> pedidos confirmados</p>
    </div>
    <a href="{{ route('gestion.cotizaciones.index') }}" class="group border-t border-white/15 p-5 transition-colors hover:bg-white/5 sm:border-r xl:border-t-0 xl:p-6">
        <p class="flex items-center justify-between text-sm font-bold text-white/75">Cotizaciones pendientes <x-icono nombre="flecha" class="size-4 transition-transform group-hover:translate-x-1" /></p>
        <p class="titular mt-2 text-[2rem] cifra" data-m="cotizaciones.pendientes">{{ $m['cotizaciones']['pendientes'] }}</p>
        <p class="mt-1 text-sm"><span class="bg-sol px-2 py-0.5 font-bold text-tinta"><span data-m="cotizaciones.atrasadas" class="cifra">{{ $m['cotizaciones']['atrasadas'] }}</span> con más de 24 h</span></p>
    </a>
    <a href="{{ route('gestion.inventario.index', ['bajo_stock' => 1]) }}" class="group border-t border-white/15 p-5 transition-colors hover:bg-white/5 xl:border-t-0 xl:p-6">
        <p class="flex items-center justify-between text-sm font-bold text-white/75">Bajo stock / agotados <x-icono nombre="flecha" class="size-4 transition-transform group-hover:translate-x-1" /></p>
        <p class="titular mt-2 text-[2rem] cifra"><span data-m="stock.bajo">{{ $m['stock']['bajo'] }}</span> / <span data-m="stock.agotados" class="text-coral">{{ $m['stock']['agotados'] }}</span></p>
        <p class="mt-1 text-sm text-white/75">Umbral: <span data-m="stock.umbral" class="cifra">{{ $m['stock']['umbral'] }}</span> unidades</p>
    </a>
</section>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
    {{-- Orden en el celular: lo que pide acción primero (alertas), los gráficos después --}}
    <section class="lobulo order-1 p-5 lg:order-3 lg:col-span-6 xl:p-6" aria-labelledby="t-alertas">
        <h2 id="t-alertas" class="seccion-titulo">Alertas de inventario</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="tabla">
                <thead><tr><th class="hidden sm:table-cell">Código</th><th>Producto</th><th class="!text-right">Stock</th></tr></thead>
                <tbody id="tabla-alertas">
                @forelse($m['stock']['alertas'] as $p)
                    <tr>
                        <td class="hidden sm:table-cell"><span class="codigo">{{ $p['codigo_fabrica'] }}</span></td>
                        <td>{{ $p['nombre'] }}<span class="codigo mt-0.5 block sm:hidden">{{ $p['codigo_fabrica'] }}</span></td>
                        <td class="text-right">
                            @if($p['stock'] === 0)<span class="estado estado-agotado">Agotado</span>
                            @else<span class="estado estado-bajo cifra">Bajo stock: {{ $p['stock'] }}</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="apoyo">Sin alertas: todo el inventario está sobre el umbral.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="lobulo order-2 p-5 lg:order-1 lg:col-span-8 xl:p-6" aria-labelledby="t-ventas">
        <h2 id="t-ventas" class="seccion-titulo">Ventas de los últimos <span class="sm:hidden">14</span><span class="hidden sm:inline">30</span> días (USD)</h2>
        <div class="grafico relative mt-4 h-64"><canvas id="grafico-ventas" aria-label="Gráfico de ventas diarias" role="img"></canvas></div>
        <p class="apoyo mt-2 text-sm" data-sin-graficos hidden>No se pudieron cargar los gráficos. Las cifras de arriba están al día.</p>
    </section>

    <section class="order-3 rounded-lobulo bg-tierra-clara p-5 text-tinta lg:order-2 lg:col-span-4 xl:p-6" aria-labelledby="t-conversion">
        <h2 id="t-conversion" class="seccion-titulo">Conversión del mes</h2>
        <p class="titular mt-4 text-6xl cifra"><span data-m="cotizaciones.conversion_mes">{{ number_format($m['cotizaciones']['conversion_mes'], 1, ',', '.') }}</span> %</p>
        <p class="mt-3 max-w-xs">De las cotizaciones recibidas este mes, este porcentaje terminó en pedido.</p>
    </section>

    <section class="lobulo order-4 p-5 lg:col-span-6 xl:p-6" aria-labelledby="t-top">
        <h2 id="t-top" class="seccion-titulo">Más vendidos del mes (unidades)</h2>
        <div class="grafico relative mt-4 h-60"><canvas id="grafico-top" aria-label="Productos más vendidos del mes" role="img"></canvas></div>
        <p class="apoyo mt-2 text-sm" id="sin-ventas" @unless(empty($m['mas_vendidos'])) hidden @endunless>Aún no hay ventas confirmadas este mes.</p>
    </section>
</div>
@endsection

@push('scripts')
@vite('resources/js/graficos.js')
<script>
// Los módulos (gráficos) corren antes de DOMContentLoaded: aquí window.Chart ya existe si cargó
document.addEventListener('DOMContentLoaded', function () {
    const URL_DATOS = @json(route('gestion.tablero.datos'));
    const usd = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const decimal1 = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
    const valor = (obj, ruta) => ruta.split('.').reduce((o, k) => (o == null ? o : o[k]), obj);
    const celular = window.matchMedia('(max-width: 639.98px)');
    function escapar(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    // Gráficos: solo si Chart.js cargó. Sin él, el tablero sigue mostrando todas las cifras.
    let graficoVentas = null, graficoTop = null;
    if (window.Chart) {
        graficoVentas = new Chart(document.getElementById('grafico-ventas'), {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Ventas USD', data: [], backgroundColor: '#0b4a3b', hoverBackgroundColor: '#ffd23f', borderRadius: 999, borderSkipped: false, maxBarThickness: 22 }] },
            options: {
                maintainAspectRatio: false, animation: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#2c2e2a', padding: 10, cornerRadius: 12, callbacks: { label: c => 'USD ' + usd.format(c.parsed.y) } } },
                scales: { y: { beginAtZero: true, border: { display: false }, ticks: { callback: v => usd.format(v) } }, x: { grid: { display: false }, border: { color: '#2c2e2a' }, ticks: { maxTicksLimit: 8 } } }
            }
        });
        graficoTop = new Chart(document.getElementById('grafico-top'), {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Unidades', data: [], backgroundColor: '#ffd23f', hoverBackgroundColor: '#0b4a3b', borderRadius: 999, borderSkipped: false, maxBarThickness: 26 }] },
            options: {
                indexAxis: 'y', maintainAspectRatio: false, animation: false,
                plugins: { legend: { display: false }, tooltip: { backgroundColor: '#2c2e2a', padding: 10, cornerRadius: 12 } },
                scales: { x: { beginAtZero: true, border: { display: false }, ticks: { precision: 0 } }, y: { grid: { display: false }, border: { color: '#2c2e2a' }, ticks: { font: { weight: 700 } } } }
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
        });
        if (graficoVentas) {
            // En el celular, 30 barras quedan de ~8 px: se muestran los últimos 14 días
            const dias = celular.matches ? -14 : 0;
            graficoVentas.data.labels = m.serie_ventas.etiquetas.slice(dias);
            graficoVentas.data.datasets[0].data = m.serie_ventas.valores.slice(dias);
            graficoVentas.update();
        }
        if (graficoTop) {
            graficoTop.data.labels = m.mas_vendidos.map(p => p.codigo);
            graficoTop.data.datasets[0].data = m.mas_vendidos.map(p => p.unidades);
            graficoTop.options.plugins.tooltip.callbacks = { title: i => m.mas_vendidos[i[0].dataIndex].nombre };
            graficoTop.update();
        }
        document.getElementById('sin-ventas').hidden = m.mas_vendidos.length > 0;
        document.getElementById('tabla-alertas').innerHTML = m.stock.alertas.length
            ? m.stock.alertas.map(p => '<tr><td class="hidden sm:table-cell"><span class="codigo">' + escapar(p.codigo_fabrica) + '</span></td><td>' + escapar(p.nombre)
                + '<span class="codigo mt-0.5 block sm:hidden">' + escapar(p.codigo_fabrica) + '</span></td><td class="text-right">'
                + (p.stock === 0 ? '<span class="estado estado-agotado">Agotado</span>' : '<span class="estado estado-bajo cifra">Bajo stock: ' + p.stock + '</span>') + '</td></tr>').join('')
            : '<tr><td colspan="3" class="apoyo">Sin alertas: todo el inventario está sobre el umbral.</td></tr>';
    }

    let ultimas = @json($metricas);
    pintar(ultimas);
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
