@extends('layouts.app')
@section('titulo', $producto->exists ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
<x-encabezado :titulo="$producto->exists ? 'Editar ' . $producto->codigo_fabrica : 'Nuevo producto'" :volver="route('admin.productos.index')" volver-texto="Productos" />

<form method="POST" enctype="multipart/form-data"
      action="{{ $producto->exists ? route('admin.productos.update', $producto) : route('admin.productos.store') }}"
      class="needs-validation" novalidate>
    @csrf
    @if($producto->exists) @method('PUT') @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="lobulo grid gap-5 p-5 sm:grid-cols-2 lg:p-6">
            <div>
                <label for="codigo_fabrica" class="etiqueta">Código de fábrica *</label>
                <input type="text" id="codigo_fabrica" name="codigo_fabrica" value="{{ old('codigo_fabrica', $producto->codigo_fabrica) }}"
                       class="campo uppercase cifra @error('codigo_fabrica') is-invalid @enderror" required maxlength="50"
                       pattern="[A-Za-z0-9][A-Za-z0-9\-\.\/]*" placeholder="JD-AH212345">
                <p class="error-campo">@error('codigo_fabrica'){{ $message }}@else Obligatorio. Solo letras, números, guiones, puntos o barras. @enderror</p>
            </div>
            <div>
                <label for="marca" class="etiqueta">Marca / fabricante *</label>
                <input type="text" id="marca" name="marca" value="{{ old('marca', $producto->marca) }}" class="campo @error('marca') is-invalid @enderror" required maxlength="80" list="marcas-sugeridas">
                <p class="error-campo">@error('marca'){{ $message }}@else Indica la marca. @enderror</p>
            </div>
            <div class="sm:col-span-2">
                <label for="nombre" class="etiqueta">Nombre *</label>
                <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $producto->nombre) }}" class="campo @error('nombre') is-invalid @enderror" required minlength="3" maxlength="150">
                <p class="error-campo">@error('nombre'){{ $message }}@else Mínimo 3 letras. @enderror</p>
            </div>
            <div>
                <label for="id_categoria" class="etiqueta">Categoría *</label>
                <select id="id_categoria" name="id_categoria" class="campo @error('id_categoria') is-invalid @enderror" required>
                    <option value="">Selecciona…</option>
                    @foreach($categorias as $c)
                        <option value="{{ $c->id }}" @selected(old('id_categoria', $producto->id_categoria) == $c->id)>{{ $c->nombre }}</option>
                    @endforeach
                </select>
                <p class="error-campo">@error('id_categoria'){{ $message }}@else Selecciona una categoría. @enderror</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="precio" class="etiqueta">Precio USD *</label>
                    <input type="number" id="precio" name="precio" value="{{ old('precio', $producto->precio) }}" class="campo cifra @error('precio') is-invalid @enderror" required min="0.01" step="0.01">
                    <p class="error-campo">@error('precio'){{ $message }}@else Mayor que 0, hasta 2 decimales. @enderror</p>
                </div>
                <div>
                    <label for="costo" class="etiqueta">Costo USD</label>
                    <input type="number" id="costo" name="costo" value="{{ old('costo', $producto->costo) }}" class="campo cifra @error('costo') is-invalid @enderror" min="0" step="0.01">
                    <p class="ayuda">Interno, nunca se publica.</p>
                </div>
            </div>
            <div class="sm:col-span-2">
                <label for="descripcion" class="etiqueta">Descripción técnica</label>
                <textarea id="descripcion" name="descripcion" rows="4" maxlength="5000" class="campo">{{ old('descripcion', $producto->descripcion) }}</textarea>
            </div>
            <div class="sm:col-span-2">
                <label for="compatibilidad" class="etiqueta">Compatibilidad con maquinaria</label>
                <input type="text" id="compatibilidad" name="compatibilidad" value="{{ old('compatibilidad', $producto->compatibilidad) }}" class="campo" maxlength="2000" placeholder="John Deere 5075E, 5090E, Massey Ferguson 290">
                <p class="ayuda">Separa los modelos con comas: cada uno aparece en la ficha y sirve para el filtro «Modelo».</p>
            </div>
        </div>

        <div class="space-y-4">
            <div class="lobulo p-5" x-data="{ error: '' }">
                <label for="imagen" class="etiqueta">Imagen</label>
                <div @class(['mb-3 aspect-[4/3] overflow-hidden rounded-[3px]', 'bg-sabana-clara' => ! $producto->imagen])>
                    <img src="{{ $producto->exists ? $producto->urlImagen() : asset('img/producto-generico.svg') }}" alt="Vista previa" id="vista-previa" class="size-full object-contain">
                </div>
                <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm file:mr-3 file:cursor-pointer file:rounded-[2px] file:border-0 file:bg-tierra file:px-4 file:py-2.5 file:font-bold file:text-white @error('imagen') is-invalid @enderror"
                       @change="const a = $event.target.files[0]; if (!a) return;
                                if (a.size > 2 * 1024 * 1024) { $event.target.setCustomValidity('La imagen supera 2 MB.'); error = 'La imagen supera 2 MB.'; return; }
                                $event.target.setCustomValidity(''); error = ''; document.getElementById('vista-previa').src = URL.createObjectURL(a);">
                <p class="ayuda">JPG, PNG o WEBP, máximo 2 MB.</p>
                <p class="mt-1.5 text-sm font-semibold text-coral-tinta" x-show="error" x-text="error"></p>
                @error('imagen')<p class="mt-1.5 text-sm font-semibold text-coral-tinta">{{ $message }}</p>@enderror
                @if($producto->imagen)
                    <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" name="quitar_imagen" value="1" id="quitar_imagen"> Quitar la imagen actual
                    </label>
                @endif
            </div>

            <div class="lobulo p-5">
                @if($producto->exists)
                    <p class="etiqueta">Stock actual</p>
                    <div class="mb-3">@include('partials.stock', ['producto' => $producto])</div>
                    <a href="{{ route('gestion.inventario.index', ['q' => $producto->codigo_fabrica]) }}" class="enlace text-sm">Registrar entrada, salida o ajuste en Inventario</a>
                @else
                    <label for="stock" class="etiqueta">Stock inicial *</label>
                    <input type="number" id="stock" name="stock" value="{{ old('stock', 0) }}" class="campo cifra @error('stock') is-invalid @enderror" required min="0" step="1">
                    <p class="ayuda">Se registra como movimiento de entrada «Inventario inicial».</p>
                @endif
            </div>

            <div class="lobulo p-5">
                <input type="hidden" name="activo" value="0">
                <label class="flex cursor-pointer items-center justify-between gap-4 font-semibold" for="activo">
                    Visible en el catálogo público
                    <span class="relative inline-flex">
                        <input type="checkbox" role="switch" name="activo" value="1" id="activo" class="peer sr-only" @checked(old('activo', $producto->activo))>
                        <span class="h-7 w-12 rounded-full bg-linea transition-colors peer-checked:bg-tierra peer-focus-visible:outline-3 peer-focus-visible:outline-laguna"></span>
                        <span class="absolute left-1 top-1 size-5 rounded-full bg-white transition-transform duration-300 ease-[var(--ease-rebote)] peer-checked:translate-x-5"></span>
                    </span>
                </label>
            </div>

            <div class="lobulo p-5">
                <input type="hidden" name="exento_iva" value="0">
                <label class="flex cursor-pointer items-center justify-between gap-4 font-semibold" for="exento_iva">
                    <span>Exento de IVA <span class="ayuda !mt-0.5 block font-normal">Como los implementos Marchesan Tatu. El IGTF se cobra igual si paga en dólares.</span></span>
                    <span class="relative inline-flex">
                        <input type="checkbox" role="switch" name="exento_iva" value="1" id="exento_iva" class="peer sr-only" @checked(old('exento_iva', $producto->exento_iva))>
                        <span class="h-7 w-12 rounded-full bg-linea transition-colors peer-checked:bg-tierra peer-focus-visible:outline-3 peer-focus-visible:outline-laguna"></span>
                        <span class="absolute left-1 top-1 size-5 rounded-full bg-white transition-transform duration-300 ease-[var(--ease-rebote)] peer-checked:translate-x-5"></span>
                    </span>
                </label>
            </div>

            <button class="btn btn-sol btn-grande w-full">{{ $producto->exists ? 'Guardar cambios' : 'Crear producto' }}</button>
        </div>
    </div>
</form>
@endsection
