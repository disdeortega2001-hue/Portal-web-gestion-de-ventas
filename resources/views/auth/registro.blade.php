@extends('layouts.app', ['ocultarErroresGlobales' => true])
@section('titulo', 'Crear cuenta')

@section('cabecera')
    <h1 class="titular text-4xl sm:text-5xl">Crear cuenta de cliente</h1>
    <p class="mt-2 text-white/80">Solo lo necesario para comprar: tus datos, una contraseña y listo.</p>
@endsection

@section('contenido')
<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('registro') }}" class="lobulo needs-validation space-y-5 p-6 sm:p-8" novalidate>
        @csrf
        @include('partials.campos-cliente', ['correoObligatorio' => false])
        <p class="ayuda -mt-2">Entrarás con tu <strong>cédula o RIF</strong> y tu contraseña. El correo es opcional: solo sirve para recuperar la contraseña.</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="contrasena" class="etiqueta">Contraseña *</label>
                <input type="password" id="contrasena" name="contrasena" class="campo @error('contrasena') is-invalid @enderror" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" autocomplete="new-password">
                <p class="error-campo">@error('contrasena'){{ $message }}@else Mínimo 8 caracteres, con letras y números. @enderror</p>
            </div>
            <div>
                <label for="contrasena_confirmation" class="etiqueta">Repite la contraseña *</label>
                <input type="password" id="contrasena_confirmation" name="contrasena_confirmation" class="campo" required minlength="8" autocomplete="new-password">
            </div>
        </div>
        <button class="btn btn-tierra btn-grande w-full">Crear cuenta <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></button>
    </form>
</div>
@endsection
