@extends('errors.layout')
@section('codigo', '403')
@section('titulo', 'No tienes acceso aquí')
@section('texto', 'Esta sección es para otro perfil de usuario, o el enlace de la cotización venció. Si crees que es un error, ingresa con tu cuenta o pide un enlace nuevo.')
@section('acciones')
<a href="{{ url('/ingresar') }}" class="btn btn-sol btn-grande">Ingresar</a>
<a href="{{ url('/') }}" class="btn btn-linea btn-grande">Volver al inicio</a>
@endsection
