@extends('errors.layout')
@section('codigo', '503')
@section('titulo', 'Estamos en mantenimiento')
@section('texto', 'Estamos actualizando el portal. Vuelve en unos minutos; tus datos y cotizaciones están a salvo.')
@section('acciones')
<a href="{{ url('/') }}" class="btn btn-sol btn-grande">Reintentar</a>
@endsection
