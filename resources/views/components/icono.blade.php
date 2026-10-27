{{--
    Íconos de trazo (24×24, trazo 2, redondeado). Uso: <x-icono nombre="buscar" class="size-5" />
    Decorativos por defecto (aria-hidden); el texto del control lleva el significado.
--}}
@props(['nombre'])
@php
    $trazos = [
        'buscar' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'cotizacion' => '<path d="M5 3h14v18l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
        'camion' => '<path d="M3 6h11v10H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'caja' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z"/><path d="m4 7.5 8 4.5 8-4.5M12 12v9"/>',
        'usuario-check' => '<circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6s7 2 7 6"/><path d="m16 11 2 2 4-4"/>',
        'grafico' => '<path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 6-7"/>',
        'reporte' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M10 18v-4M14 18v-6M18 18v-2"/>',
        'ajustes' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14 3h-4l-.5 2.6a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2 1.2L10 21h4l.5-2.6a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2z"/>',
        'salir' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'whatsapp' => '<path d="M3 21l1.6-4.7A8.5 8.5 0 1 1 7.8 19.6z"/><path d="M9 8.5c0 3.5 3 6.5 6.5 6.5l1.2-1.6-2.2-1-1 1a5 5 0 0 1-2.9-2.9l1-1-1-2.2z"/>',
        'telefono' => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
        'check' => '<path d="m5 12 5 5L20 7"/>',
        'check-circulo' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'alerta' => '<path d="M12 3 2 20h20z"/><path d="M12 10v4M12 17.5v.5"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.5v.5"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'mas' => '<path d="M12 5v14M5 12h14"/>',
        'menos' => '<path d="M5 12h14"/>',
        'papelera' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'flecha' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'flecha-izq' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'deslizadores' => '<path d="M4 7h10M18 7h2M4 17h4M12 17h8"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="17" r="2"/>',
        'descarga' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
        'pdf' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h1.5a1.5 1.5 0 0 1 0 3H9v-3zm0 3v2"/>',
        'excel' => '<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 12l5 6M14 12l-5 6"/>',
        'tabla' => '<path d="M3 5h18v14H3zM3 10h18M3 15h18M9 5v14"/>',
        'ojo' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'lapiz' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'restaurar' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        'rejilla' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'enviar' => '<path d="M21 3 3 10l7 3 3 7z"/><path d="m10 13 4-4"/>',
        'abajo' => '<path d="m6 9 6 6 6-6"/>',
        'usuarios' => '<circle cx="9" cy="8" r="4"/><path d="M2 21c0-4 3-6 7-6s7 2 7 6M16 4a4 4 0 0 1 0 8M22 21c0-3-1.5-5-4-5.7"/>',
        'etiqueta' => '<path d="M3 3h8l10 10-8 8L3 11z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'moneda' => '<circle cx="12" cy="12" r="9"/><path d="M15 9.5c-.5-1-1.6-1.5-3-1.5-1.7 0-3 .9-3 2s1.3 1.7 3 2 3 .9 3 2-1.3 2-3 2c-1.4 0-2.5-.5-3-1.5M12 6v2M12 16v2"/>',
        'escudo' => '<path d="M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'ubicacion' =>'<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $trazos[$nombre] ?? '' !!}</svg>
