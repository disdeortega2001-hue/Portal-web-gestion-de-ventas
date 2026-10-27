@php
    // WhatsApp en la cabecera: solo en las páginas del cliente y si el número está configurado
    $whatsappBarra = request()->routeIs('catalogo.*', 'carrito.*', 'cotizaciones.*', 'nosotros')
        ? \App\Support\Contacto::whatsapp('Hola, quiero consultar un repuesto.')
        : null;
    $usuario = auth()->user();
    $personal = $usuario?->esPersonal() ?? false;

    // Menú del personal: [ruta, patrón activo, ícono, texto, solo administrador]
    $menuPersonal = [
        ['gestion.tablero', 'gestion.tablero', 'grafico', 'Tablero', false],
        ['gestion.cotizaciones.index', 'gestion.cotizaciones.*', 'cotizacion', 'Cotizaciones', false],
        ['gestion.pedidos.index', 'gestion.pedidos.*', 'camion', 'Pedidos', false],
        ['gestion.inventario.index', 'gestion.inventario.*', 'caja', 'Inventario', false],
        ['gestion.verificaciones.index', 'gestion.verificaciones.*', 'usuario-check', 'Verificar cuentas', false],
        ['gestion.reportes.index', 'gestion.reportes.*', 'reporte', 'Reportes', true],
    ];
    $menuAdmin = [
        ['admin.productos.index', 'admin.productos.*', 'etiqueta', 'Productos'],
        ['admin.categorias.index', 'admin.categorias.*', 'rejilla', 'Categorías'],
        ['admin.usuarios.index', 'admin.usuarios.*', 'usuarios', 'Usuarios'],
        ['admin.configuracion.edit', 'admin.configuracion.*', 'moneda', 'Tasa, IVA y contacto'],
        ['admin.respaldos.index', 'admin.respaldos.*', 'escudo', 'Respaldos'],
    ];
@endphp
<!doctype html>
<html lang="es-VE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b4a3b">
    <title>@yield('titulo', 'Catálogo') · Inversiones Rialto 7</title>
    {{-- El logo del sitio es blanco sobre transparente; la pestaña usa la versión con fondo verde --}}
    @if(is_file(public_path('img/favicon.png')))<link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">@elseif($icono = \App\Support\Logo::ruta())<link rel="icon" href="{{ asset($icono) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('estilos')
</head>
<body data-url-cotizacion="{{ route('carrito.index') }}" @class(['flex min-h-dvh flex-col', 'lg:pl-64' => $personal])>
<a href="#contenido" class="btn btn-sol sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[70]">Saltar al contenido</a>

{{-- Forma de lóbulo para recortar imágenes de producto (coordenadas relativas a la caja) --}}
<svg width="0" height="0" class="absolute" aria-hidden="true" focusable="false">
    <clipPath id="forma-lobulo" clipPathUnits="objectBoundingBox">
        <path d="M.08 .28C.1 .1 .27 .02 .45 .05C.6 .08 .72 .01 .86 .08C.98 .15 1 .32 .97 .48C.94 .62 1 .78 .9 .9C.79 1.02 .6 .96 .45 .97C.3 .99 .12 1 .05 .86C-.02 .72 .06 .58 .05 .45C.04 .38 .07 .33 .08 .28Z"/>
    </clipPath>
</svg>

@if($personal)
    {{-- ======== Personal: riel verde a la izquierda; su borde derecho es una costa ======== --}}
    <div x-data="{ abierto: false }" @keydown.escape.window="abierto = false">
        <header class="sticky top-0 z-40 flex h-16 items-center gap-3 bg-tierra px-4 text-white lg:hidden">
            <button type="button" class="grid size-11 place-items-center rounded-full hover:bg-white/10" @click="abierto = true" aria-label="Abrir menú" aria-controls="riel" :aria-expanded="abierto">
                <x-icono nombre="menu" class="size-6" />
            </button>
            <x-logo tamano="size-10" />
            <span class="font-extrabold tracking-tight">Rialto 7 · Gestión</span>
        </header>

        <div x-show="abierto" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-tinta/50 lg:hidden" @click="abierto = false"></div>

        <nav id="riel" aria-label="Menú del personal"
             class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-tierra text-white transition-transform duration-300 ease-[var(--ease-salida)] lg:translate-x-0"
             :class="abierto && 'translate-x-0'">
            <svg class="pointer-events-none absolute inset-y-0 -right-5 hidden h-full w-6 text-tierra lg:block" viewBox="0 0 24 800" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 0H8C20 60 2 120 12 190C22 260 4 330 14 400C24 470 6 540 16 610C24 680 4 740 10 800H0Z"/></svg>
            <div class="relative flex items-center gap-3 px-5 pb-4 pt-5">
                <x-logo tamano="size-12" />
                <div class="leading-tight"><div class="text-lg font-extrabold tracking-tight">Rialto 7</div><div class="text-xs font-semibold text-white/75">Gestión de ventas</div></div>
                <button type="button" class="ml-auto grid size-10 place-items-center rounded-full hover:bg-white/10 lg:hidden" @click="abierto = false" aria-label="Cerrar menú"><x-icono nombre="x" /></button>
            </div>
            <div class="relative flex-1 overflow-y-auto px-3 pb-4">
                <ul class="space-y-1">
                    @foreach($menuPersonal as [$ruta, $patron, $icono, $texto, $soloAdmin])
                        @continue($soloAdmin && ! $usuario->esAdministrador())
                        @php($activo = request()->routeIs($patron))
                        <li><a href="{{ route($ruta) }}" @if($activo) aria-current="page" @endif
                               @class(['flex min-h-11 items-center gap-3 rounded-full px-4 font-semibold transition-colors', 'bg-sol text-tinta' => $activo, 'text-white/90 hover:bg-white/10' => ! $activo])>
                            <x-icono :nombre="$icono" /> {{ $texto }}</a></li>
                    @endforeach
                </ul>
                @if($usuario->esAdministrador())
                    <p class="mb-1 mt-6 px-4 text-xs font-bold uppercase tracking-[0.12em] text-sabana">Administración</p>
                    <ul class="space-y-1">
                        @foreach($menuAdmin as [$ruta, $patron, $icono, $texto])
                            @php($activo = request()->routeIs($patron))
                            <li><a href="{{ route($ruta) }}" @if($activo) aria-current="page" @endif
                                   @class(['flex min-h-11 items-center gap-3 rounded-full px-4 font-semibold transition-colors', 'bg-sol text-tinta' => $activo, 'text-white/90 hover:bg-white/10' => ! $activo])>
                                <x-icono :nombre="$icono" /> {{ $texto }}</a></li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('catalogo.index') }}" class="mt-6 flex min-h-11 items-center gap-3 rounded-full px-4 font-semibold text-white/90 hover:bg-white/10"><x-icono nombre="rejilla" /> Ver catálogo público</a>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="relative border-t border-white/15 p-4" data-sin-bloqueo>
                @csrf
                <div class="mb-2 truncate px-1 text-sm text-white/80">{{ $usuario->nombre }}</div>
                <button class="btn btn-linea-clara btn-mini w-full" aria-label="Salir ({{ $usuario->nombre }})"><x-icono nombre="salir" class="size-4" /> Salir</button>
            </form>
        </nav>
    </div>
@else
    {{-- ======== Cliente: cabecera de tierra verde cerrada por una costa ======== --}}
    <header class="relative z-30 bg-tierra text-white">
        <div class="mx-auto flex max-w-[1320px] items-center gap-3 px-4 pt-3 sm:px-6 lg:gap-6 lg:px-8 lg:pt-4">
            <a href="{{ route('catalogo.index') }}" class="flex items-center gap-3 rounded-2xl no-underline" aria-label="Inversiones Rialto 7, ir al catálogo">
                <x-logo tamano="size-12 lg:size-16" />
                <span class="leading-tight">
                    <span class="block text-lg font-extrabold tracking-tight lg:text-xl">Rialto 7</span>
                    <span class="hidden text-xs font-semibold text-white/75 sm:block">Repuestos y maquinaria agrícola</span>
                </span>
            </a>
            <nav class="ml-auto hidden items-center gap-1 md:flex" aria-label="Principal">
                <a href="{{ route('catalogo.index') }}" @if(request()->routeIs('catalogo.*')) aria-current="page" @endif class="rounded-full px-4 py-2.5 font-semibold text-white/90 hover:bg-white/10 aria-[current]:bg-white/15 aria-[current]:text-white">Catálogo</a>
                <a href="{{ route('nosotros') }}" @if(request()->routeIs('nosotros')) aria-current="page" @endif class="rounded-full px-4 py-2.5 font-semibold text-white/90 hover:bg-white/10 aria-[current]:bg-white/15 aria-[current]:text-white">Nosotros</a>
                @if($usuario?->rol === 'cliente')
                    <a href="{{ route('cuenta.cotizaciones') }}" @if(request()->routeIs('cuenta.cotizaciones')) aria-current="page" @endif class="rounded-full px-4 py-2.5 font-semibold text-white/90 hover:bg-white/10 aria-[current]:bg-white/15">Mis cotizaciones</a>
                    <a href="{{ route('cuenta.pedidos') }}" @if(request()->routeIs('cuenta.pedidos')) aria-current="page" @endif class="rounded-full px-4 py-2.5 font-semibold text-white/90 hover:bg-white/10 aria-[current]:bg-white/15">Mis pedidos</a>
                @endif
            </nav>
            <div class="ml-auto flex items-center gap-2 md:ml-2">
                @if($whatsappBarra)
                    <a href="{{ $whatsappBarra }}" class="btn btn-linea-clara btn-mini !min-h-11 !px-3 lg:!px-4" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp" title="Escríbenos por WhatsApp">
                        <x-icono nombre="whatsapp" /><span class="hidden lg:inline">WhatsApp</span>
                    </a>
                @endif
                @auth
                    <div x-data="{ m: false }" class="relative" @click.outside="m = false">
                        <button type="button" class="btn btn-linea-clara btn-mini !min-h-11" @click="m = !m" :aria-expanded="m" aria-haspopup="true">
                            <span class="max-w-28 truncate">{{ strtok($usuario->nombre, ' ') }}</span><x-icono nombre="abajo" class="size-4" />
                        </button>
                        <div x-show="m" x-cloak x-transition.origin.top.right class="absolute right-0 top-full mt-2 w-56 rounded-2xl border border-linea bg-white p-2 text-tinta">
                            @if($usuario->rol === 'cliente')
                                <a href="{{ route('cuenta.cotizaciones') }}" class="block rounded-xl px-3 py-2.5 font-semibold hover:bg-agua md:hidden">Mis cotizaciones</a>
                                <a href="{{ route('cuenta.pedidos') }}" class="block rounded-xl px-3 py-2.5 font-semibold hover:bg-agua md:hidden">Mis pedidos</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" data-sin-bloqueo>
                                @csrf
                                <button class="flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-left font-semibold hover:bg-agua" aria-label="Salir ({{ $usuario->nombre }})"><x-icono nombre="salir" class="size-4" /> Salir</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-linea-clara btn-mini !min-h-11">Ingresar</a>
                @endauth
            </div>
        </div>

        @hasSection('cabecera')
            <div class="mx-auto max-w-[1320px] px-4 pb-12 pt-6 sm:px-6 sm:pb-8 lg:px-8 lg:pb-10 lg:pt-8">@yield('cabecera')</div>
        @else
            <div class="h-4 lg:h-6"></div>
        @endif

        {{-- La costa: la tierra verde termina en una orilla, no en una línea recta --}}
        {{-- Costa asimétrica: lóbulos desiguales y un cabo a la derecha, bajo la cápsula «Mi cotización» --}}
        <svg class="absolute inset-x-0 top-full h-9 w-full text-tierra lg:h-14" viewBox="0 0 1440 56" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 0H1440V44C1412 58 1350 58 1296 50C1236 41 1214 16 1146 14C1054 11 1004 32 912 30C810 28 776 6 664 6C540 6 504 26 386 26C280 26 226 10 148 12C86 14 40 26 0 22Z"/></svg>

        {{-- «Mi cotización»: la cápsula sol clavada en el cabo; el anillo crema la recorta de la tierra, sin sombra --}}
        <a href="{{ route('carrito.index') }}" data-carrito-enlace aria-label="Mi cotización, {{ $itemsCarrito }} {{ $itemsCarrito === 1 ? 'producto' : 'productos' }}"
           class="btn btn-sol btn-grande absolute right-4 top-full z-10 -translate-y-1/3 ring-4 ring-agua sm:right-6 lg:right-[max(2rem,calc((100vw-1320px)/2+2rem))]">
            <x-icono nombre="cotizacion" /> <span>Mi cotización</span>
            <span class="grid min-w-7 h-7 place-items-center rounded-full bg-tinta px-1.5 text-sm text-white cifra" data-carrito-contador @if(! $itemsCarrito) hidden @endif>{{ $itemsCarrito }}</span>
        </a>
    </header>
@endif

{{-- Recorta en el borde de la pantalla los lóbulos de color que se salen del contenido, sin barra horizontal --}}
<div class="flex-1 overflow-x-clip">
<main id="contenido" tabindex="-1" @class([
    'mx-auto px-4 sm:px-6 lg:px-8 outline-none',
    'max-w-[1320px] pt-14 pb-16 lg:pt-16' => ! $personal,
    'max-w-[1400px] py-6 lg:py-8' => $personal,
])>
    {{-- En el área del personal, la cabecera de las páginas públicas es una zona de tierra dentro del contenido --}}
    @if($personal && $__env->hasSection('cabecera'))
        <div class="lobulo-tierra mb-8 px-6 py-7 sm:px-8 lg:px-10 lg:py-9">@yield('cabecera')</div>
    @endif

    <div class="space-y-3 empty:hidden">
        @if(session('exito'))
            <div class="aviso aviso-exito" role="status"><x-icono nombre="check-circulo" class="mt-0.5" /><div>{{ session('exito') }}</div></div>
        @endif
        @if(session('error'))
            <div class="aviso aviso-error" role="alert"><x-icono nombre="alerta" class="mt-0.5" /><div>{{ session('error') }}</div></div>
        @endif
        @if($errors->any() && ! isset($ocultarErroresGlobales))
            <div class="aviso aviso-error" role="alert"><x-icono nombre="alerta" class="mt-0.5" />
                <div><strong>Revisa lo siguiente:</strong><ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </div>
        @endif
    </div>

    @yield('contenido')
</main>
</div>

@unless($personal)
    <footer class="relative mt-10 bg-tierra-oscura text-white/85">
        <svg class="absolute inset-x-0 bottom-full h-6 w-full text-tierra-oscura lg:h-8" viewBox="0 0 1440 40" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 40H1440V22C1320 0 1220 30 1080 16C930 2 850 30 700 18C540 6 450 34 300 20C180 9 90 26 0 12Z"/></svg>
        <div class="mx-auto grid max-w-[1320px] gap-6 px-4 py-10 text-sm sm:px-6 md:grid-cols-[auto_1fr_auto] md:items-center lg:px-8">
            <x-logo tamano="size-16" />
            <div class="space-y-1">
                <p class="text-base font-extrabold text-white">Inversiones Rialto 7 C.A.</p>
                <p class="flex items-center gap-1.5"><x-icono nombre="ubicacion" class="size-4" /> Av. Las Industrias, Edificio Campagna, Valle de la Pascua, Guárico</p>
                <p>RIF J-50668510-8 · Precios en USD más IVA; en Bs. a la tasa oficial del día.</p>
                <p>Agente autorizado exclusivo en Venezuela de Marchesan Tatu, Colombo Aemco, Vicon y Lumagi · <a href="{{ route('nosotros') }}" class="font-bold text-white underline decoration-sol decoration-2 underline-offset-4 hover:text-sol">Nuestra historia</a></p>
            </div>
            @if($telefono = \App\Support\Contacto::telefonoVisible())
                <div class="flex flex-wrap gap-2">
                    <a href="{{ \App\Support\Contacto::llamada() }}" class="btn btn-linea-clara btn-mini"><x-icono nombre="telefono" class="size-4" /> {{ $telefono }}</a>
                    <a href="{{ \App\Support\Contacto::whatsapp() }}" class="btn btn-sol btn-mini" target="_blank" rel="noopener"><x-icono nombre="whatsapp" class="size-4" /> WhatsApp</a>
                </div>
            @endif
        </div>
    </footer>
@endunless

{{-- Aviso breve para acciones sin recarga (agregar a la cotización) --}}
<div id="aviso" role="status" aria-live="polite"
     class="toast flex w-[min(92vw,30rem)] items-center gap-3 rounded-full bg-tinta py-2 pl-5 pr-2 text-[0.9375rem] text-white ring-4 ring-agua data-[tipo=error]:bg-coral-tinta">
    <span data-texto class="flex-1 [&_a]:text-sol"></span>
    <button type="button" class="grid size-9 place-items-center rounded-full hover:bg-white/10" onclick="this.parentElement.removeAttribute('data-visible')" aria-label="Cerrar aviso"><x-icono nombre="x" class="size-4" /></button>
</div>

@stack('scripts')
</body>
</html>
