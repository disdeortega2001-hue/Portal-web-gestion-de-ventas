{{--
    Logo oficial: el engranaje verde, sin fondo. Archivo: public/img/logo.png (también .jpg, .jpeg, .webp o .svg).
    conRif: versión con el RIF en blanco (public/img/logo-rif.png), para fondos oscuros donde el logo va grande.
    Mientras no exista se muestra solo el nombre: nunca un emblema inventado.
--}}
@props(['tamano' => 'size-14', 'conRif' => false])
@if($archivoLogo = ($conRif && is_file(public_path('img/logo-rif.png')) ? 'img/logo-rif.png' : \App\Support\Logo::ruta()))
    <img src="{{ asset($archivoLogo) }}" alt="Inversiones Rialto 7 C.A." {{ $attributes->merge(['class' => $tamano . ' shrink-0 object-contain']) }} width="112" height="112">
@endif
