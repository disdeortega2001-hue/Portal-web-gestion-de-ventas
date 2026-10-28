@extends('errors.layout')
@section('codigo', '404')
@section('titulo', 'Esta página no existe')
@section('texto', 'Puede que el enlace esté mal escrito o que el producto ya no esté publicado. Búscalo por su código de fábrica en el catálogo.')
@section('acciones')
<a href="{{ url('/catalogo') }}" class="btn btn-sol btn-grande">Ir al catálogo</a>
<a href="{{ url('/') }}" class="btn btn-linea btn-grande">Volver al inicio</a>
@endsection
