{{--
    Logo oficial (emblema blanco sobre verde). Archivo: public/img/logo.png (también .jpg, .jpeg, .webp o .svg).
    Mientras no exista se muestra solo el nombre: nunca un emblema inventado.
--}}
@props(['tamano' => 'size-14'])
@if($archivoLogo = \App\Support\Logo::ruta())
    <img src="{{ asset($archivoLogo) }}" alt="Inversiones Rialto 7 C.A." {{ $attributes->merge(['class' => $tamano . ' shrink-0 rounded-xl object-contain']) }} width="112" height="112">
@endif
