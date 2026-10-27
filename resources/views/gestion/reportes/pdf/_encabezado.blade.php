<div class="encabezado">
    <span class="marca">Inversiones Rialto 7 C.A.</span>
    <p class="titulo">{{ $titulo }}</p>
    <span class="muted">
        @foreach($filtros as $etiqueta => $valor){{ $etiqueta }}: {{ $valor }}@if(! $loop->last) · @endif @endforeach
        · Generado el {{ now()->format('d/m/Y h:i a') }} por {{ auth()->user()->nombre }}
    </span>
</div>
<div class="pie">Documento de uso interno · Inversiones Rialto 7 C.A. · Valle de la Pascua, Guárico</div>
