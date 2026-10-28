@extends('errors.layout')
@section('codigo', '419')
@section('titulo', 'La página venció')
@section('texto', 'Pasó mucho tiempo desde que abriste el formulario y, por seguridad, hay que cargarlo de nuevo. Tu cotización sigue guardada.')
@section('acciones')
<a href="{{ url()->previous() }}" class="btn btn-sol btn-grande">Volver e intentar de nuevo</a>
@endsection
