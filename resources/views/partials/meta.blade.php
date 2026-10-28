@php
    // Etiquetas para buscadores y para la vista previa al compartir un enlace (WhatsApp, Facebook).
    // Cada vista puede definir @section('descripcion'), @section('imagen_og') y @section('canonica').
    $titulo = trim($__env->yieldContent('titulo', 'Repuestos y maquinaria agrícola')) . ' · Inversiones Rialto 7';
    $descripcion = trim($__env->yieldContent('descripcion'))
        ?: 'Repuestos y maquinaria agrícola en Valle de la Pascua desde 1963. Existencia real, precios en USD y Bs., y cotización en línea. Agente exclusivo de Marchesan Tatu, Colombo Aemco, Vicon y Lumagi.';
    $imagen = trim($__env->yieldContent('imagen_og')) ?: asset('img/compartir.png');
    $canonica = trim($__env->yieldContent('canonica')) ?: url()->current();
    // Las páginas privadas o de un solo uso no deben aparecer en Google
    $privada = auth()->user()?->esPersonal()
        || request()->routeIs('carrito.*', 'cotizaciones.*', 'cuenta.*', 'login', 'registro', 'password.*', 'gestion.*', 'admin.*');
@endphp
<meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($descripcion), 160) }}">
@if($privada)
<meta name="robots" content="noindex, nofollow">
@else
<link rel="canonical" href="{{ $canonica }}">
@endif
<meta property="og:site_name" content="Inversiones Rialto 7">
<meta property="og:locale" content="es_VE">
<meta property="og:type" content="@yield('tipo_og', 'website')">
<meta property="og:title" content="{{ $titulo }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($descripcion), 200) }}">
<meta property="og:url" content="{{ $canonica }}">
<meta property="og:image" content="{{ $imagen }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('img/icono-192.png') }}">
@if(! $privada)
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Store',
    'name' => 'Inversiones Rialto 7 C.A.',
    'description' => 'Repuestos y maquinaria agrícola. Agente autorizado exclusivo en Venezuela de Marchesan Tatu, Colombo Aemco, Vicon y Lumagi.',
    'url' => url('/'),
    'logo' => asset('img/logo-original.jpg'),
    'image' => asset('img/compartir.png'),
    'foundingDate' => '1963',
    'taxID' => 'J-50668510-8',
    'telephone' => \App\Support\Contacto::numero() ? '+' . \App\Support\Contacto::numero() : null,
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => 'Av. Las Industrias, Edificio Campagna',
        'addressLocality' => 'Valle de la Pascua',
        'addressRegion' => 'Guárico',
        'addressCountry' => 'VE',
    ],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endif
@stack('meta')
