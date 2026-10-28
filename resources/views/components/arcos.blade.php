{{-- Arcos curvos de las páginas de las marcas: dos bandas concéntricas que barren la imagen. Decorativo. --}}
@props(['tono' => 'claro'])
<svg {{ $attributes->merge(['class' => 'pointer-events-none']) }} viewBox="0 0 600 800" fill="none" aria-hidden="true" preserveAspectRatio="xMaxYMid slice">
    <path d="M600 -40C340 60 210 250 210 400s130 340 390 440" stroke="{{ $tono === 'claro' ? 'rgba(255,255,255,0.16)' : 'rgba(13,81,146,0.10)' }}" stroke-width="70"/>
    <path d="M600 40C410 130 310 270 310 400s100 270 290 360" stroke="{{ $tono === 'claro' ? 'rgba(255,255,255,0.10)' : 'rgba(13,81,146,0.07)' }}" stroke-width="44"/>
</svg>
