@extends('errors.layout')
@section('codigo', '500')
@section('titulo', 'Algo falló de nuestro lado')
@section('texto', 'No es culpa tuya. Ya quedó registrado. Intenta de nuevo en unos minutos; si sigue pasando, escríbenos por WhatsApp o visítanos en la sede.')
@section('acciones')
<a href="{{ url('/') }}" class="btn btn-sol btn-grande">Volver al inicio</a>
@endsection
