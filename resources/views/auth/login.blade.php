@extends('layouts.app', ['ocultarErroresGlobales' => true])
@section('titulo', 'Ingresar')

@section('cabecera')
    <h1 class="titular text-4xl sm:text-5xl">Ingresar</h1>
    <p class="mt-2 text-white/80">Clientes y personal de Rialto 7 (vendedores, administrativos y directivos) entran por aquí.</p>
@endsection

@section('contenido')
<div class="mx-auto max-w-md">
    {{-- Viene del acceso del personal (/panel o una página del panel) --}}
    @if(str_contains((string) session('url.intended'), '/gestion') || str_contains((string) session('url.intended'), '/admin'))
        <div class="aviso aviso-info mb-5" role="status">
            <x-icono nombre="usuario-check" class="size-5 shrink-0" />
            <p><strong>Acceso del personal.</strong> Ingresa con tu correo de la empresa y tu contraseña; entrarás directo al panel de gestión.</p>
        </div>
    @endif
    <form method="POST" action="{{ route('login') }}" class="lobulo needs-validation space-y-5 p-6 sm:p-8" novalidate>
        @csrf
        <div>
            <label for="usuario" class="etiqueta">Cédula o RIF</label>
            <input type="text" id="usuario" name="usuario" value="{{ old('usuario') }}" class="campo @error('usuario') is-invalid @enderror" required autofocus autocomplete="username" autocapitalize="characters" placeholder="Ej. V-12345678">
            <p class="error-campo">@error('usuario'){{ $message }}@else Escribe tu cédula o RIF. @enderror</p>
            <p class="ayuda">Personal de Rialto 7: escribe tu correo.</p>
        </div>
        <div>
            <div class="flex items-baseline justify-between gap-3">
                <label for="contrasena" class="etiqueta">Contraseña</label>
                <a href="{{ route('password.request') }}" class="enlace text-sm">¿Olvidaste tu contraseña?</a>
            </div>
            <input type="password" id="contrasena" name="contrasena" class="campo @error('contrasena') is-invalid @enderror" required autocomplete="current-password">
            <p class="error-campo">@error('contrasena'){{ $message }}@else Escribe tu contraseña. @enderror</p>
        </div>
        <label class="flex min-h-11 cursor-pointer items-center gap-3 font-semibold">
            <input type="checkbox" name="recordar" id="recordar" value="1"> Mantener la sesión abierta
        </label>
        <button class="btn btn-tierra btn-grande w-full">Ingresar <span class="chip"><x-icono nombre="flecha" class="size-4" /></span></button>
    </form>
    <p class="mt-5 text-center">¿Eres cliente nuevo? <a href="{{ route('registro') }}" class="enlace">Crea tu cuenta</a></p>
</div>
@endsection
