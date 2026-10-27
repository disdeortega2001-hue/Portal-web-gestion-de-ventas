@extends('layouts.app', ['ocultarErroresGlobales' => true])
@section('titulo', 'Recuperar contraseña')

@section('cabecera')
    <h1 class="titular text-4xl sm:text-5xl">¿Olvidaste tu contraseña?</h1>
    <p class="mt-2 max-w-xl text-white/80">Escribe el correo de tu cuenta y te enviaremos un enlace para crear una nueva.</p>
@endsection

@section('contenido')
<div class="mx-auto max-w-md">
    <form method="POST" action="{{ route('password.email') }}" class="lobulo needs-validation space-y-5 p-6 sm:p-8" novalidate>
        @csrf
        <div>
            <label for="correo" class="etiqueta">Correo electrónico</label>
            <input type="email" id="correo" name="correo" value="{{ old('correo') }}" class="campo @error('correo') is-invalid @enderror" required autofocus autocomplete="email" maxlength="150">
            <p class="error-campo">@error('correo'){{ $message }}@else Escribe tu correo. @enderror</p>
        </div>
        <button class="btn btn-tierra btn-grande w-full">Enviar enlace</button>
    </form>
    <p class="mt-5 text-center"><a href="{{ route('login') }}" class="enlace">Volver a ingresar</a></p>
</div>
@endsection
