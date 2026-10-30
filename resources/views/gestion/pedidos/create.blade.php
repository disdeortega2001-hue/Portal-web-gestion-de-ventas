@extends('layouts.app')
@section('titulo', 'Venta de mostrador')

@php
    $catalogo = $productos->map(fn ($p) => ['id' => $p->id, 'codigo' => $p->codigo_fabrica, 'nombre' => $p->nombre, 'marca' => $p->marca, 'precio' => (float) $p->precio, 'exento' => $p->exento_iva, 'stock' => $p->stock])->values();
    $lineasPrevias = collect(old('items', []))->map(fn ($i) => ['id' => (int) ($i['id_producto'] ?? 0), 'cantidad' => (int) ($i['cantidad'] ?? 1)])->values();
@endphp

@section('contenido')
<x-encabezado titulo="Venta de mostrador" :volver="route('gestion.pedidos.index')" volver-texto="Pedidos">
    Busca al cliente y los productos escribiendo; los totales se calculan mientras armas la venta.
</x-encabezado>

<form method="POST" action="{{ route('gestion.pedidos.store') }}" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]"
      x-data="mostrador(@js($catalogo), @js($parametros), @js($clienteElegido), @js($lineasPrevias), @js(route('gestion.clientes.buscar')))">
    @csrf
    <div class="space-y-5">
        {{-- 1. Cliente --}}
        <section class="lobulo panel-tapa p-5 pt-7" aria-labelledby="t-cliente">
            <h2 id="t-cliente" class="seccion-titulo flex items-center gap-3"><span class="ruedo size-9 text-base">1</span> Cliente</h2>
            <input type="hidden" name="id_cliente" :value="cliente?.id">
            <template x-if="cliente">
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border border-tierra bg-tierra-clara px-4 py-3">
                    <div><p class="font-bold" x-text="cliente.nombre"></p><p class="cifra text-sm text-tinta-suave" x-text="cliente.identificacion + ' · ' + cliente.telefono"></p></div>
                    <button type="button" class="btn btn-linea btn-mini" @click="cliente = null; $nextTick(() => $refs.qCliente.focus())">Cambiar</button>
                </div>
            </template>
            <div x-show="!cliente" class="relative mt-4">
                <label for="q-cliente" class="etiqueta">Nombre, cédula o teléfono</label>
                <input id="q-cliente" x-ref="qCliente" type="search" class="campo" autocomplete="off" placeholder="Ej. Rondón o 12345678"
                       x-model="qCliente" @input.debounce.250ms="buscarCliente()" @keydown.enter.prevent="clientes[0] && elegirCliente(clientes[0])">
                <ul x-show="clientes.length" x-cloak class="absolute inset-x-0 top-full z-20 mt-1 max-h-72 overflow-y-auto border border-linea bg-white">
                    <template x-for="c in clientes" :key="c.id">
                        <li><button type="button" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left hover:bg-agua" @click="elegirCliente(c)">
                            <span class="font-semibold" x-text="c.nombre"></span><span class="cifra text-sm text-tinta-suave" x-text="c.identificacion"></span>
                        </button></li>
                    </template>
                </ul>
                <p x-show="qCliente.length >= 2 && buscado && !clientes.length" x-cloak class="ayuda">Ningún cliente coincide.</p>
                <a href="{{ route('gestion.clientes.create', ['volver' => 'pedido']) }}" class="enlace mt-3 inline-block text-sm">Registrar un cliente nuevo</a>
                @error('id_cliente')<p class="error-campo !block">{{ $message }}</p>@enderror
            </div>
        </section>

        {{-- 2. Productos --}}
        <section class="lobulo panel-tapa p-5 pt-7" aria-labelledby="t-productos">
            <h2 id="t-productos" class="seccion-titulo flex items-center gap-3"><span class="ruedo size-9 text-base">2</span> Productos</h2>
            <div class="relative mt-4">
                <label for="q-producto" class="etiqueta">Código de fábrica o nombre</label>
                <input id="q-producto" type="search" class="campo" autocomplete="off" placeholder="Ej. 0602030250R o cardán"
                       x-model="qProducto" @keydown.enter.prevent="sugeridos[0] && agregar(sugeridos[0])" @keydown.escape="qProducto = ''">
                <ul x-show="sugeridos.length" x-cloak class="absolute inset-x-0 top-full z-20 mt-1 max-h-80 overflow-y-auto border border-linea bg-white">
                    <template x-for="p in sugeridos" :key="p.id">
                        <li><button type="button" class="grid w-full grid-cols-[1fr_auto] gap-x-4 px-4 py-2.5 text-left hover:bg-agua" @click="agregar(p)">
                            <span><span class="font-rotulo text-lg font-extrabold uppercase text-tierra cifra" x-text="p.codigo"></span> <span class="font-semibold" x-text="p.nombre"></span></span>
                            <span class="cifra text-right text-sm"><span class="font-bold" x-text="usd(p.precio)"></span><br><span class="text-tinta-suave" x-text="'Hay ' + p.stock"></span></span>
                        </button></li>
                    </template>
                </ul>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="tabla" x-show="lineas.length">
                    <thead><tr><th>Producto</th><th class="!text-right">Precio</th><th class="!text-right">Cantidad</th><th class="!text-right">Subtotal</th><th></th></tr></thead>
                    <tbody>
                        <template x-for="(l, i) in lineas" :key="l.p.id">
                            <tr>
                                <td><span class="codigo" x-text="l.p.codigo"></span><br><span class="font-semibold" x-text="l.p.nombre"></span>
                                    <input type="hidden" :name="`items[${i}][id_producto]`" :value="l.p.id"></td>
                                <td class="num" x-text="usd(l.p.precio)"></td>
                                <td class="num"><input type="number" class="campo cifra !w-24 !min-h-10 text-right" min="1" :max="l.p.stock" x-model.number="l.cantidad" :name="`items[${i}][cantidad]`" required>
                                    <span class="block text-xs text-tinta-suave" x-text="'de ' + l.p.stock"></span></td>
                                <td class="num font-bold" x-text="usd(l.p.precio * (l.cantidad || 0))"></td>
                                <td class="text-right"><button type="button" class="grid size-10 place-items-center border-[1.5px] border-linea text-coral-tinta hover:bg-coral-clara" @click="lineas.splice(i, 1)" :aria-label="'Quitar ' + l.p.codigo"><x-icono nombre="papelera" class="size-4" /></button></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <p x-show="!lineas.length" class="apoyo border border-dashed border-linea py-8 text-center">Escribe un código arriba y elige el producto para agregarlo.</p>
            </div>
            @error('items')<p class="error-campo !block">{{ $message }}</p>@enderror
        </section>
    </div>

    {{-- 3. Totales en vivo --}}
    <aside class="xl:sticky xl:top-6 xl:self-start">
        <section class="lobulo-tierra panel-tapa p-6 pt-8" aria-labelledby="t-totales">
            <h2 id="t-totales" class="seccion-titulo flex items-center gap-3"><span class="ruedo size-9 text-base">3</span> Total</h2>
            {{-- La moneda de pago define el impuesto: IGTF en dólares, IVA en bolívares --}}
            <fieldset class="mt-4">
                <legend class="text-sm font-bold text-white/80">El cliente paga en</legend>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    @foreach(['USD' => 'Dólares', 'VES' => 'Bolívares'] as $valor => $texto)
                        <label class="flex cursor-pointer items-center justify-center gap-2 rounded-[6px] border border-white/30 px-3 py-2 font-bold has-[:checked]:border-sol has-[:checked]:bg-sol has-[:checked]:text-tinta">
                            <input type="radio" name="moneda_pago" value="{{ $valor }}" x-model="moneda" class="sr-only" @checked(old('moneda_pago', 'USD') === $valor)> {{ $texto }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            {{-- Solo para clientes con crédito autorizado: se lleva la mercancía y abona después --}}
            <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-[6px] border border-white/30 px-3 py-2.5" x-show="cliente && cliente.credito_habilitado" x-cloak>
                <input type="checkbox" name="a_credito" value="1" class="mt-1" @checked(old('a_credito'))>
                <span><span class="font-bold">Venta a crédito</span>
                    <span class="block text-sm text-white/75">Se lleva la mercancía y abona después; le recordamos el saldo cada 30 días.</span></span>
            </label>
            <dl class="mt-4 space-y-2 cifra">
                <div class="flex justify-between"><dt class="text-white/80">Subtotal</dt><dd x-text="usd(t.subtotal)"></dd></div>
                <div class="flex justify-between"><dt class="text-white/80" x-text="(moneda === 'USD' ? 'IGTF (' + p.igtf : 'IVA (' + p.iva) + ' %)'"></dt><dd x-text="usd(t.impuesto)"></dd></div>
                <div class="flex items-end justify-between border-t border-white/20 pt-3"><dt class="font-bold">Total</dt><dd class="font-rotulo text-4xl font-extrabold" x-text="usd(t.total)"></dd></div>
                <div class="text-right text-sm text-white/80" x-text="bs(t.total * p.tasa) + ' · tasa ' + p.tasa.toLocaleString('es-VE', {minimumFractionDigits: 2})"></div>
            </dl>
            <button class="btn btn-sol btn-grande mt-6 w-full" :disabled="!cliente || !lineas.length">Crear pedido <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></button>
            <p class="mt-3 text-sm text-white/75">El pedido queda pendiente; la existencia se descuenta al confirmar la venta.</p>
        </section>
    </aside>
</form>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    // Mismo cálculo que CalculadoraComercial (en centavos; IGTF si paga en dólares, IVA si paga en bolívares); el servidor lo vuelve a hacer al guardar
    function mostrador(catalogo, p, clienteInicial, previas, urlClientes) {
        const formato = (n) => n.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return {
            p, cliente: clienteInicial, qCliente: '', clientes: [], buscado: false, qProducto: '', moneda: @js(old('moneda_pago', 'USD')),
            lineas: previas.map((x) => ({ p: catalogo.find((c) => c.id === x.id), cantidad: x.cantidad })).filter((l) => l.p),
            usd: (n) => 'USD ' + formato(n), bs: (n) => 'Bs. ' + formato(n),
            get sugeridos() {
                const q = this.qProducto.trim().toLowerCase();
                if (q.length < 2) return [];
                return catalogo.filter((c) => (c.codigo + ' ' + c.nombre + ' ' + c.marca).toLowerCase().includes(q)).slice(0, 8);
            },
            get t() {
                const sub = this.lineas.reduce((s, l) => s + Math.round(l.p.precio * 100) * (parseInt(l.cantidad) || 0), 0);
                // Los exentos de IVA (Marchesan Tatu) no suman a la base del IVA; el IGTF sí grava todo
                const exento = this.lineas.reduce((s, l) => s + (l.p.exento ? Math.round(l.p.precio * 100) * (parseInt(l.cantidad) || 0) : 0), 0);
                const impuesto = Math.round((this.moneda === 'USD' ? sub : sub - exento) * (this.moneda === 'USD' ? p.igtf : p.iva) / 100);
                return { subtotal: sub / 100, impuesto: impuesto / 100, total: (sub + impuesto) / 100 };
            },
            agregar(prod) {
                const existente = this.lineas.find((l) => l.p.id === prod.id);
                if (existente) existente.cantidad = Math.min(prod.stock, (parseInt(existente.cantidad) || 0) + 1);
                else this.lineas.push({ p: prod, cantidad: 1 });
                this.qProducto = '';
            },
            async buscarCliente() {
                this.buscado = false;
                if (this.qCliente.trim().length < 2) { this.clientes = []; return; }
                const r = await fetch(urlClientes + '?q=' + encodeURIComponent(this.qCliente), { headers: { Accept: 'application/json' } });
                this.clientes = r.ok ? await r.json() : [];
                this.buscado = true;
            },
            elegirCliente(c) { this.cliente = c; this.clientes = []; this.qCliente = ''; },
        };
    }
</script>
@endpush
