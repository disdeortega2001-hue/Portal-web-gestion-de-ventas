@extends('layouts.app')
@section('titulo', $categoria->exists ? 'Editar categoría' : 'Nueva categoría')

@section('contenido')
<x-encabezado :titulo="$categoria->exists ? 'Editar categoría' : 'Nueva categoría'" :volver="route('admin.categorias.index')" volver-texto="Categorías" />

<form method="POST" action="{{ $categoria->exists ? route('admin.categorias.update', $categoria) : route('admin.categorias.store') }}"
      class="lobulo needs-validation max-w-2xl space-y-5 p-5 lg:p-6" novalidate>
    @csrf
    @if($categoria->exists) @method('PUT') @endif
    <div>
        <label for="nombre" class="etiqueta">Nombre *</label>
        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $categoria->nombre) }}" class="campo @error('nombre') is-invalid @enderror" required minlength="3" maxlength="100">
        <p class="error-campo">@error('nombre'){{ $message }}@else Mínimo 3 letras. @enderror</p>
    </div>
    <div>
        <label for="descripcion" class="etiqueta">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3" maxlength="1000" class="campo">{{ old('descripcion', $categoria->descripcion) }}</textarea>
    </div>
    <button class="btn btn-sol">{{ $categoria->exists ? 'Guardar cambios' : 'Crear categoría' }}</button>
</form>
@endsection
