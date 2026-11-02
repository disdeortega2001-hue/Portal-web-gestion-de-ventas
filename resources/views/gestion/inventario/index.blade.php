@extends('layouts.app')
@section('titulo', 'Inventario')

@section('contenido')
<x-encabezado titulo="Inventario">Toda variación de existencia queda registrada con su motivo y su responsable.</x-encabezado>

<div class="grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1fr)_22rem]">
    {{-- Registrar movimiento: administrativo y directivo; el vendedor solo consulta las existencias --}}
    @if(auth()->user()->puedeAdministrar())
    {{-- Primero qué pasó; luego con qué producto, cuánto y por qué. Abajo se ve cómo quedará la existencia antes de guardar --}}
    <form method="POST" action="{{ route('gestion.inventario.store') }}" class="lobulo needs-validation max-w-4xl overflow-hidden 2xl:order-2 2xl:sticky 2xl:top-6 2xl:max-w-none 2xl:self-start" novalidate
          x-data="{ tipo: @js(old('tipo', 'entrada')), producto: @js((string) old('id_producto', '')), cantidad: @js((string) old('cantidad', '')), stocks: @js($listaProductos->pluck('stock', 'id')),
                    ayudas: { entrada: 'Unidades que entran.', salida: 'Unidades que salen.', ajuste: 'Stock contado físicamente (reemplaza al actual).' },
                    ejemplos: { entrada: 'Ej. Factura proveedor N° 1234', salida: 'Ej. Merma por daño en el almacén', ajuste: 'Ej. Conteo físico de fin de mes' },
                    get nuevo() { const s = this.stocks[this.producto], c = parseInt(this.cantidad); if (s === undefined || isNaN(c)) return null; return this.tipo === 'entrada' ? s + c : (this.tipo === 'salida' ? s - c : c); } }">
        @csrf
        <div class="p-5 lg:px-6">
            <h2 class="seccion-titulo">Registrar movimiento</h2>
            <fieldset class="mt-4">
                <legend class="etiqueta">Tipo *</legend>
                <div class="grid gap-2 sm:grid-cols-3 2xl:grid-cols-1">
                    @foreach(['entrada' => ['Entrada de mercancía', 'mas'], 'salida' => ['Salida (merma, uso interno)', 'menos'], 'ajuste' => ['Ajuste por conteo físico', 'lapiz']] as $valor => [$texto, $icono])
                        <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-[6px] border-2 border-linea px-3.5 py-2 font-semibold leading-tight transition-colors hover:border-tierra has-[:checked]:border-tierra has-[:checked]:bg-tierra-clara has-[:checked]:text-tierra-oscura has-[:focus-visible]:outline-3 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-tierra">
                            <input type="radio" name="tipo" id="tipo-{{ $valor }}" value="{{ $valor }}" x-model="tipo" required class="sr-only" @checked(old('tipo', 'entrada') === $valor)>
                            <x-icono :nombre="$icono" class="size-5 shrink-0" /> {{ $texto }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </div>
        <div class="grid gap-4 border-t border-linea p-5 sm:grid-cols-[minmax(0,1fr)_10rem] lg:px-6 2xl:grid-cols-1">
            <div class="min-w-0">
                <label for="id_producto" class="etiqueta">Producto *</label>
                <select id="id_producto" name="id_producto" class="campo" required x-model="producto">
                    <option value="">Selecciona…</option>
                    @foreach($listaProductos as $p)
                        <option value="{{ $p->id }}" @selected(old('id_producto') == $p->id)>{{ $p->codigo_fabrica }} — {{ $p->nombre }} ({{ $p->stock }})</option>
                    @endforeach
                </select>
                <p class="error-campo">Selecciona el producto.</p>
            </div>
            <div>
                <label for="cantidad" class="etiqueta" id="etiqueta-cantidad">Cantidad *</label>
                <input type="number" id="cantidad" name="cantidad" value="{{ old('cantidad') }}" class="campo cifra" :min="tipo === 'ajuste' ? 0 : 1" min="1" required x-model="cantidad">
            </div>
            <p class="ayuda !mt-0 sm:col-span-2 sm:text-right 2xl:col-span-1 2xl:text-left" id="ayuda-cantidad" x-text="ayudas[tipo]">Unidades que entran.</p>
            <div class="sm:col-span-2 2xl:col-span-1">
                <label for="motivo" class="etiqueta">Motivo *</label>
                <input type="text" id="motivo" name="motivo" value="{{ old('motivo') }}" class="campo" required minlength="5" maxlength="255" placeholder="Ej. Factura proveedor N° 1234" :placeholder="ejemplos[tipo]">
                <p class="error-campo">Explica el motivo (mínimo 5 caracteres).</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-linea bg-agua px-5 py-4 lg:px-6">
            <p class="min-h-6 text-sm" aria-live="polite">
                <span x-show="producto" x-cloak>
                    <span class="apoyo">Existencia:</span> <strong class="cifra" x-text="stocks[producto]"></strong>
                    <template x-if="nuevo !== null && nuevo >= 0"><span> → <strong class="cifra text-tierra-oscura" x-text="nuevo"></strong> <span class="apoyo">al guardar</span></span></template>
                    <template x-if="nuevo !== null && nuevo < 0"><strong class="text-coral-tinta"> · No hay tantas unidades para sacar</strong></template>
                </span>
            </p>
            <button class="btn btn-sol w-full sm:w-auto">Registrar movimiento</button>
        </div>
    </form>
    @endif

    {{-- Existencias --}}
    <div class="min-w-0 space-y-6 2xl:order-1">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="relative w-full sm:w-80">
                <x-icono nombre="buscar" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-tinta-suave" />
                <input type="search" name="q" value="{{ request('q') }}" class="campo !pl-11" placeholder="Buscar código o nombre" aria-label="Buscar código o nombre">
            </div>
            <label class="flex min-h-11 cursor-pointer items-center gap-2 font-semibold">
                <input type="checkbox" name="bajo_stock" value="1" id="bajo_stock" @checked(request()->boolean('bajo_stock'))> Solo bajo stock (≤ {{ $umbral }})
            </label>
            <button class="btn btn-linea btn-mini">Filtrar</button>
        </form>

        <div>
            <div class="lobulo overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Código</th><th>Producto</th><th>Categoría</th><th class="!text-right">Stock</th></tr></thead>
                    <tbody>
                    @foreach($productos as $producto)
                        <tr>
                            <td><span class="codigo">{{ $producto->codigo_fabrica }}</span></td>
                            <td class="font-semibold">{{ $producto->nombre }}</td>
                            <td class="apoyo">{{ $producto->categoria->nombre }}</td>
                            <td class="text-right">@include('partials.stock', ['producto' => $producto])</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $productos->links() }}
        </div>

        <section aria-labelledby="t-movimientos">
            <h2 id="t-movimientos" class="seccion-titulo">Últimos movimientos</h2>
            <div class="lobulo mt-3 overflow-x-auto">
                <table class="tabla text-sm">
                    <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th class="!text-right">Cant.</th><th class="!text-right">Stock</th><th>Motivo</th><th>Usuario</th></tr></thead>
                    <tbody>
                    @forelse($movimientos as $mov)
                        <tr>
                            <td class="cifra whitespace-nowrap">{{ $mov->fecha->format('d/m/Y h:i a') }}</td>
                            <td><span class="codigo">{{ $mov->producto->codigo_fabrica }}</span></td>
                            <td>{{ ucfirst($mov->tipo) }}</td>
                            <td class="num">{{ $mov->cantidad }}</td>
                            <td class="num">{{ $mov->stock_anterior }} → {{ $mov->stock_nuevo }}</td>
                            <td class="min-w-[16rem]">{{ $mov->motivo }}</td>
                            <td class="whitespace-nowrap">{{ $mov->usuario->nombre }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="apoyo py-8 text-center">Sin movimientos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movimientos->links() }}
        </section>
    </div>
</div>
@endsection
