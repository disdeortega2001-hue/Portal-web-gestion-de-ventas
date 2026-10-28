@extends('layouts.app')
@section('titulo', 'Inventario')

@section('contenido')
<x-encabezado titulo="Inventario">Toda variación de existencia queda registrada con su motivo y su responsable.</x-encabezado>

<div class="grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1fr)_22rem]">
    {{-- Registrar movimiento --}}
    <form method="POST" action="{{ route('gestion.inventario.store') }}" class="lobulo needs-validation grid max-w-4xl gap-4 p-5 md:grid-cols-2 2xl:max-w-none 2xl:grid-cols-1 2xl:order-2 2xl:sticky 2xl:top-6 2xl:self-start" novalidate
          x-data="{ tipo: '{{ old('tipo', 'entrada') }}', ayudas: { entrada: 'Unidades que entran.', salida: 'Unidades que salen.', ajuste: 'Stock contado físicamente (reemplaza al actual).' } }">
        @csrf
        <h2 class="seccion-titulo md:col-span-2 2xl:col-span-1">Registrar movimiento</h2>
        <div>
            <label for="id_producto" class="etiqueta">Producto *</label>
            <select id="id_producto" name="id_producto" class="campo" required>
                <option value="">Selecciona…</option>
                @foreach($listaProductos as $p)
                    <option value="{{ $p->id }}" @selected(old('id_producto') == $p->id)>{{ $p->codigo_fabrica }} — {{ $p->nombre }} ({{ $p->stock }})</option>
                @endforeach
            </select>
            <p class="error-campo">Selecciona el producto.</p>
        </div>
        <fieldset>
            <legend class="etiqueta">Tipo *</legend>
            <div class="grid gap-2">
                @foreach(['entrada' => 'Entrada de mercancía', 'salida' => 'Salida (merma, uso interno)', 'ajuste' => 'Ajuste por conteo físico'] as $valor => $texto)
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-[3px] border-[1.5px] border-linea px-4 font-semibold has-[:checked]:border-tierra has-[:checked]:bg-tierra-clara">
                        <input type="radio" name="tipo" id="tipo-{{ $valor }}" value="{{ $valor }}" x-model="tipo" required class="size-4 accent-[#0b4a3b]" @checked(old('tipo', 'entrada') === $valor)> {{ $texto }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div>
            <label for="cantidad" class="etiqueta" id="etiqueta-cantidad">Cantidad *</label>
            <input type="number" id="cantidad" name="cantidad" value="{{ old('cantidad') }}" class="campo cifra" :min="tipo === 'ajuste' ? 0 : 1" min="1" required>
            <p class="ayuda" id="ayuda-cantidad" x-text="ayudas[tipo]">Unidades que entran.</p>
        </div>
        <div>
            <label for="motivo" class="etiqueta">Motivo *</label>
            <input type="text" id="motivo" name="motivo" value="{{ old('motivo') }}" class="campo" required minlength="5" maxlength="255" placeholder="Ej. Factura proveedor N° 1234">
            <p class="error-campo">Explica el motivo (mínimo 5 caracteres).</p>
        </div>
        <button class="btn btn-tierra w-full md:col-span-2 md:w-auto md:justify-self-start 2xl:col-span-1 2xl:w-full">Registrar movimiento</button>
    </form>

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
