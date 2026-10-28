@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('contenido')
<x-encabezado :titulo="$usuario->exists ? 'Editar usuario' : 'Nuevo usuario'" :volver="route('admin.usuarios.index')" volver-texto="Usuarios" />

<form method="POST" action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}"
      class="lobulo needs-validation max-w-3xl p-5 lg:p-6" novalidate>
    @csrf
    @if($usuario->exists) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="nombre" class="etiqueta">Nombre *</label>
            <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $usuario->nombre) }}" class="campo @error('nombre') is-invalid @enderror" required minlength="3" maxlength="120">
            <p class="error-campo">@error('nombre'){{ $message }}@else Mínimo 3 letras. @enderror</p>
        </div>
        <div>
            <label for="correo" class="etiqueta">Correo *</label>
            <input type="email" id="correo" name="correo" value="{{ old('correo', $usuario->correo) }}" class="campo @error('correo') is-invalid @enderror" required maxlength="150" autocomplete="off">
            <p class="error-campo">@error('correo'){{ $message }}@else Escribe un correo válido. @enderror</p>
        </div>
        <div>
            <label for="rol" class="etiqueta">Perfil *</label>
            <select id="rol" name="rol" class="campo @error('rol') is-invalid @enderror" required>
                @foreach(\Illuminate\Support\Arr::only(\App\Models\Usuario::NOMBRES_ROL, \App\Models\Usuario::PERFILES_PANEL) as $valor => $texto)
                    <option value="{{ $valor }}" @selected(old('rol', $usuario->rol) === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
            <p class="error-campo">@error('rol'){{ $message }}@enderror</p>
            <p class="ayuda">Los clientes no se crean aquí: se registran solos al comprar.</p>
        </div>
        <div>
            <label for="estado" class="etiqueta">Estado *</label>
            <select id="estado" name="estado" class="campo @error('estado') is-invalid @enderror" required>
                <option value="activo" @selected(old('estado', $usuario->estado) === 'activo')>Activo</option>
                <option value="inactivo" @selected(old('estado', $usuario->estado) === 'inactivo')>Inactivo (sin acceso)</option>
            </select>
            <p class="error-campo">@error('estado'){{ $message }}@enderror</p>
        </div>
        <div>
            <label for="contrasena" class="etiqueta">Contraseña {{ $usuario->exists ? '(vacía = no cambiarla)' : '*' }}</label>
            <input type="password" id="contrasena" name="contrasena" class="campo @error('contrasena') is-invalid @enderror"
                   minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" autocomplete="new-password" @unless($usuario->exists) required @endunless>
            <p class="error-campo">@error('contrasena'){{ $message }}@else Mínimo 8 caracteres, con letras y números. @enderror</p>
        </div>
        <div>
            <label for="contrasena_confirmation" class="etiqueta">Repite la contraseña</label>
            <input type="password" id="contrasena_confirmation" name="contrasena_confirmation" class="campo" autocomplete="new-password">
        </div>
    </div>
    <p class="apoyo mt-5 text-sm">Los clientes normalmente se registran solos desde el portal. Para un cliente creado aquí, sus datos de contacto se completan en su primera cotización.</p>
    <button class="btn btn-sol mt-5">{{ $usuario->exists ? 'Guardar cambios' : 'Crear usuario' }}</button>
</form>
@endsection
