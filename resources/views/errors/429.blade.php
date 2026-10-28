@extends('errors.layout')
@section('codigo', '429')
@section('titulo', 'Demasiados intentos')
@section('texto', 'Hiciste muchas solicitudes seguidas. Espera un minuto y vuelve a intentarlo.')
@section('acciones')
<a href="{{ url('/') }}" class="btn btn-linea btn-grande">Volver al inicio</a>
@endsection
