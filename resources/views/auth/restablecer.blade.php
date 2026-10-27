@extends('layouts.app', ['ocultarErroresGlobales' => true])
@section('titulo', 'Nueva contraseña')

@section('cabecera')
    <h1 class="titular text-4xl sm:text-5xl">Crea tu nueva contraseña</h1>
@endsection

@section('contenido')
<div class="mx-auto max-w-md">
    <form method="POST" action="{{ route('password.store') }}" class="lobulo needs-validation space-y-5 p-6 sm:p-8" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="correo" class="etiqueta">Correo electrónico</label>
            <input type="email" id="correo" name="correo" value="{{ old('correo', $correo) }}" class="campo @error('correo') is-invalid @enderror" required autocomplete="username" maxlength="150">
            <p class="error-campo">@error('correo'){{ $message }}@else Escribe tu correo. @enderror</p>
        </div>
        <div>
            <label for="contrasena" class="etiqueta">Nueva contraseña</label>
            <input type="password" id="contrasena" name="contrasena" class="campo @error('contrasena') is-invalid @enderror" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" autocomplete="new-password" autofocus>
            <p class="error-campo">@error('contrasena'){{ $message }}@else Mínimo 8 caracteres, con letras y números. @enderror</p>
        </div>
        <div>
            <label for="contrasena_confirmation" class="etiqueta">Repite la contraseña</label>
            <input type="password" id="contrasena_confirmation" name="contrasena_confirmation" class="campo" required minlength="8" autocomplete="new-password">
        </div>
        <button class="btn btn-tierra btn-grande w-full">Guardar contraseña</button>
    </form>
</div>
@endsection
