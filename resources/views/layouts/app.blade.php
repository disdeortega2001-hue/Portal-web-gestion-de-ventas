@php
    // WhatsApp en la cabecera: solo en las páginas del cliente y si el número está configurado
    $whatsappBarra = request()->routeIs('inicio', 'catalogo.*', 'carrito.*', 'cotizaciones.*', 'nosotros', 'contacto', 'legal.*')
        ? \App\Support\Contacto::whatsapp('Hola, quiero consultar un repuesto.')
        : null;
    $usuario = auth()->user();
    $personal = $usuario?->esPersonal() ?? false;

    // Foto tenue detrás del contenido de las páginas públicas (distinta de la de la cabecera)
    $fotoContenido = match (true) {
        request()->routeIs('catalogo.*') => 'campo-4.webp',
        request()->routeIs('nosotros', 'contacto') => 'campo-2.webp',
        request()->routeIs('carrito.*', 'compra.*', 'cotizaciones.*') => 'campo-1.webp',
        default => 'campo-3.webp',
    };

    // Foto de fondo de la cabecera de cada página pública: [foto grande, foto para celular]
    $fotoCabecera = match (true) {
        request()->routeIs('catalogo.*') => ['campo-2.webp', 'campo-2-movil.webp'],
        request()->routeIs('nosotros', 'contacto') => ['sede-fachada.jpg', 'sede-fachada-movil.jpg'],
        request()->routeIs('carrito.*', 'compra.*', 'cotizaciones.*') => ['campo-3.webp', 'campo-3-movil.webp'],
        request()->routeIs('cuenta.*') => ['campo-4.webp', 'campo-4-movil.webp'],
        default => ['campo-1.webp', 'campo-1-movil.webp'],
    };

    // Menú del personal: [ruta, patrón activo, ícono, texto, solo administrativo y directivo]
    $menuPersonal = [
        ['gestion.tablero', 'gestion.tablero', 'grafico', 'Tablero', false],
        ['gestion.cotizaciones.index', 'gestion.cotizaciones.*', 'cotizacion', 'Cotizaciones', false],
        ['gestion.pedidos.index', 'gestion.pedidos.*', 'camion', 'Pedidos', false],
        ['gestion.clientes.index', 'gestion.clientes.*', 'usuarios', 'Clientes', false],
        ['gestion.inventario.index', 'gestion.inventario.*', 'caja', 'Inventario', false],
        ['gestion.verificaciones.index', 'gestion.verificaciones.*', 'usuario-check', 'Verificar cuentas', false],
        ['gestion.pagos.index', 'gestion.pagos.*', 'moneda', 'Pagos', true],
        ['gestion.reportes.index', 'gestion.reportes.*', 'reporte', 'Reportes', true],
    ];
    // Lo que espera atención, a la vista en el menú (conteos sobre columnas indexadas)
    $pendientesMenu = $personal ? array_filter([
        'gestion.cotizaciones.index' => \App\Models\Cotizacion::where('estado', \App\Models\Cotizacion::PENDIENTE)->count(),
        'gestion.pedidos.index' => \App\Models\Pedido::where('estado', \App\Models\Pedido::PENDIENTE)->count()
            + \App\Models\Pedido::whereNotNull('devolucion_solicitada_en')->where('estado', '!=', \App\Models\Pedido::ANULADO)->count(),
        'gestion.verificaciones.index' => \App\Models\Cliente::whereNotNull('id_usuario_solicitante')->count(),
        'gestion.pagos.index' => $usuario->puedeAdministrar() ? \App\Models\Pago::where('estado', \App\Models\Pago::POR_VERIFICAR)->count() : 0,
    ]) : [];
    // Administración: [ruta, patrón activo, ícono, texto, solo directivo]
    $menuAdmin = [
        ['admin.productos.index', 'admin.productos.*', 'etiqueta', 'Productos', false],
        ['admin.categorias.index', 'admin.categorias.*', 'rejilla', 'Categorías', false],
        ['admin.tasa.edit', 'admin.tasa.*', 'moneda', 'Tasa del día', false],
        ['admin.configuracion.edit', 'admin.configuracion.*', 'ajustes', 'Configuración del portal', true],
        ['admin.usuarios.index', 'admin.usuarios.*', 'usuarios', 'Usuarios', true],
        ['admin.respaldos.index', 'admin.respaldos.*', 'escudo', 'Respaldos', true],
    ];
@endphp
<!doctype html>
<html lang="es-VE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d5192">
    {{-- Con JavaScript, las secciones entran animadas; sin él, todo se ve desde el principio --}}
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">document.documentElement.classList.add('js')</script>
    <title>@yield('titulo', 'Catálogo') · Inversiones Rialto 7</title>
    @include('partials.meta')
    {{-- El logo del sitio es blanco sobre transparente; la pestaña usa la versión con fondo verde --}}
    @if(is_file(public_path('img/favicon.png')))<link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">@elseif($icono = \App\Support\Logo::ruta())<link rel="icon" href="{{ asset($icono) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('estilos')
<body data-url-cotizacion="{{ route('carrito.index') }}" @class(['flex min-h-dvh flex-col', 'lg:pl-64' => $personal])>
<a href="#contenido" class="btn btn-sol sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[70]">Saltar al contenido</a>

@if($personal)
    {{-- ======== Personal: riel azul a la izquierda, con una línea amarilla en su borde ======== --}}
    <div x-data="{ abierto: false }" @keydown.escape.window="abierto = false">
        <header class="sticky top-0 z-40 flex h-16 items-center gap-3 bg-tierra px-4 text-white lg:hidden">
            <button type="button" class="grid size-11 place-items-center hover:bg-white/10" @click="abierto = true" aria-label="Abrir menú" aria-controls="riel" :aria-expanded="abierto">
                <x-icono nombre="menu" class="size-6" />
            </button>
            <x-logo tamano="size-10" />
            <span class="text-lg font-black uppercase">Rialto 7 · Gestión</span>
        </header>

        <div x-show="abierto" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-tinta/50 lg:hidden" @click="abierto = false"></div>

        <nav id="riel" aria-label="Menú del personal"
             class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-tierra text-white transition-transform duration-300 ease-[var(--ease-salida)] lg:translate-x-0"
             :class="abierto && 'translate-x-0'">
            <span class="franja-v pointer-events-none absolute inset-y-0 right-0" aria-hidden="true"></span>
            <div class="relative flex items-center gap-3 px-5 pb-4 pt-5">
                <x-logo tamano="size-12" />
                <div class="leading-tight"><div class="text-2xl font-black uppercase">Rialto 7</div><div class="text-sm font-semibold text-white/75">Gestión de ventas</div></div>
                <button type="button" class="ml-auto mr-3 grid size-10 place-items-center hover:bg-white/10 lg:hidden" @click="abierto = false" aria-label="Cerrar menú"><x-icono nombre="x" /></button>
            </div>
            <div class="relative flex-1 overflow-y-auto pb-4 pl-3 pr-6">
                <ul class="space-y-0.5">
                    @foreach($menuPersonal as [$ruta, $patron, $icono, $texto, $soloAdmin])
                        @continue($soloAdmin && ! $usuario->puedeAdministrar())
                        @php($activo = request()->routeIs($patron))
                        <li><a href="{{ route($ruta) }}" @if($activo) aria-current="page" @endif
                               @class(['flex min-h-11 items-center gap-3 rounded-[2px] px-3.5 font-semibold transition-colors', 'bg-sol text-tinta' => $activo, 'text-white/90 hover:bg-white/10' => ! $activo])>
                            <x-icono :nombre="$icono" /> {{ $texto }}
                            @isset($pendientesMenu[$ruta])<span class="ruedo ml-auto size-7 !border-2 text-sm" aria-label="{{ $pendientesMenu[$ruta] }} por atender">{{ $pendientesMenu[$ruta] }}</span>@endisset
                        </a></li>
                    @endforeach
                </ul>
                @if($usuario->puedeAdministrar())
                    <p class="mb-1.5 mt-6 px-3.5 text-xs font-extrabold uppercase tracking-[0.14em] text-sol">Administración</p>
                    <ul class="space-y-0.5">
                        @foreach($menuAdmin as [$ruta, $patron, $icono, $texto, $soloDirectivo])
                            @continue($soloDirectivo && ! $usuario->esDirectivo())
                            @php($activo = request()->routeIs($patron))
                            <li><a href="{{ route($ruta) }}" @if($activo) aria-current="page" @endif
                                   @class(['flex min-h-11 items-center gap-3 rounded-[2px] px-3.5 font-semibold transition-colors', 'bg-sol text-tinta' => $activo, 'text-white/90 hover:bg-white/10' => ! $activo])>
                                <x-icono :nombre="$icono" /> {{ $texto }}</a></li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('inicio') }}" class="mt-6 flex min-h-11 items-center gap-3 rounded-[2px] px-3.5 font-semibold text-white/90 hover:bg-white/10"><x-icono nombre="rejilla" /> Ver portal público</a>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="relative border-t border-white/15 py-4 pl-4 pr-7" data-sin-bloqueo>
                @csrf
                <div class="mb-2 px-1 text-sm text-white/80"><span class="block truncate">{{ $usuario->nombre }}</span><span class="text-xs font-extrabold uppercase tracking-[0.08em] text-sol">{{ $usuario->nombreRol() }}</span></div>
                <button class="btn btn-linea-clara btn-mini w-full" aria-label="Salir ({{ $usuario->nombre }})"><x-icono nombre="salir" class="size-4" /> Salir</button>
            </form>
        </nav>
    </div>
@else
    {{-- ======== Cliente: barra blanca fija; el logo conserva su verde ======== --}}
    @php($enlacesPublicos = [['inicio', 'inicio', 'Inicio'], ['catalogo.index', 'catalogo.*', 'Catálogo'], ['nosotros', 'nosotros', 'Nuestra historia'], ['contacto', 'contacto', 'Contacto']])
    <header x-data="{ menu: false, bajo: false }" @keydown.escape.window="menu = false" @scroll.window.throttle.100ms="bajo = window.scrollY > 8"
            class="sticky top-0 z-40 bg-white transition-shadow duration-300" :class="bajo && 'shadow-[0_8px_24px_-16px_rgb(5_43_112/0.45)]'">
        <div class="mx-auto flex h-16 max-w-[1320px] items-center gap-3 px-4 sm:px-6 lg:h-[4.5rem] lg:gap-6 2xl:gap-8 lg:px-8">
            <a href="{{ route('inicio') }}" class="flex shrink-0 items-center gap-2.5 no-underline" aria-label="Inversiones Rialto 7, ir al inicio">
                <x-logo tamano="size-11 lg:size-12" />
                <span class="leading-none">
                    <span class="block whitespace-nowrap text-[1.375rem] font-black uppercase leading-none text-tierra-oscura lg:text-2xl">Rialto 7</span>
                    <span class="hidden whitespace-nowrap text-[0.8125rem] font-semibold text-tinta-suave xl:block">Repuestos y maquinaria agrícola</span>
                </span>
            </a>
            <nav class="hidden items-stretch self-stretch lg:flex" aria-label="Principal">
                @foreach($enlacesPublicos as [$ruta, $patron, $texto])
                    <a href="{{ route($ruta) }}" @if(request()->routeIs($patron)) aria-current="page" @endif
                       class="flex items-center whitespace-nowrap border-b-4 border-transparent px-2.5 pt-1 text-[0.875rem] font-extrabold uppercase tracking-[0.04em] text-tierra-oscura/75 transition-colors hover:text-tierra aria-[current]:border-sol aria-[current]:text-tierra-oscura xl:px-4">{{ $texto }}</a>
                @endforeach
            </nav>
            <div class="ml-auto flex items-center gap-2">
                @if($whatsappBarra)
                    <a href="{{ $whatsappBarra }}" class="btn btn-linea btn-mini !min-h-11 !px-3 lg:!px-4" target="_blank" rel="noopener" aria-label="Escríbenos por WhatsApp" title="Escríbenos por WhatsApp">
                        <x-icono nombre="whatsapp" /><span @class(['hidden', '2xl:inline' => auth()->check(), 'xl:inline' => auth()->guest()])>WhatsApp</span>
                    </a>
                @endif
                @auth
                    <div x-data="{ m: false }" class="relative hidden sm:block" @click.outside="m = false">
                        <button type="button" class="btn btn-linea btn-mini !min-h-11" @click="m = !m" :aria-expanded="m" aria-haspopup="true">
                            <span class="max-w-28 truncate normal-case tracking-normal">{{ strtok($usuario->nombre, ' ') }}</span><x-icono nombre="abajo" class="size-4" />
                        </button>
                        <div x-show="m" x-cloak x-transition.origin.top.right class="absolute right-0 top-full mt-2 w-56 border border-linea bg-white p-1.5 text-tinta">
                            @if($usuario->rol === 'cliente')
                                <a href="{{ route('cuenta.cotizaciones') }}" class="block px-3 py-2.5 font-semibold hover:bg-agua">Mis cotizaciones</a>
                                <a href="{{ route('cuenta.pedidos') }}" class="block px-3 py-2.5 font-semibold hover:bg-agua">Mis pedidos</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" data-sin-bloqueo>
                                @csrf
                                <button class="flex w-full items-center gap-2 px-3 py-2.5 text-left font-semibold hover:bg-agua" aria-label="Salir ({{ $usuario->nombre }})"><x-icono nombre="salir" class="size-4" /> Salir</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-linea btn-mini !min-h-11 hidden sm:inline-flex" title="Clientes y personal de Rialto 7">Ingresar</a>
                @endauth
                <a href="{{ route('carrito.index') }}" data-carrito-enlace aria-label="Mi cotización, {{ $itemsCarrito }} {{ $itemsCarrito === 1 ? 'producto' : 'productos' }}"
                   class="btn btn-sol !min-h-11 !pr-2 sm:!pr-2.5">
                    <x-icono nombre="cotizacion" /> <span class="hidden sm:inline lg:hidden xl:inline">Mi cotización</span>
                    <span class="ruedo size-8 !border-2 text-sm" data-carrito-contador @if(! $itemsCarrito) hidden @endif>{{ $itemsCarrito }}</span>
                </a>
                <button type="button" class="grid size-11 place-items-center border-[1.5px] border-tinta lg:hidden" @click="menu = !menu" :aria-expanded="menu" aria-controls="menu-movil" aria-label="Menú">
                    <x-icono nombre="menu" />
                </button>
            </div>
        </div>
        <div class="h-px bg-linea" aria-hidden="true"></div>

        {{-- Menú del celular: cae bajo la barra como una persiana --}}
        <nav id="menu-movil" x-show="menu" x-cloak x-transition.origin.top class="absolute inset-x-0 top-full border-b border-linea bg-white lg:hidden" aria-label="Principal">
            <ul class="px-4 py-2">
                @foreach($enlacesPublicos as [$ruta, $patron, $texto])
                    <li><a href="{{ route($ruta) }}" @if(request()->routeIs($patron)) aria-current="page" @endif class="flex min-h-12 items-center border-b border-linea text-base font-extrabold uppercase tracking-[0.04em] aria-[current]:text-tierra">{{ $texto }}</a></li>
                @endforeach
                @auth
                    @if($usuario->rol === 'cliente')
                        <li><a href="{{ route('cuenta.cotizaciones') }}" class="flex min-h-12 items-center border-b border-linea font-semibold">Mis cotizaciones</a></li>
                        <li><a href="{{ route('cuenta.pedidos') }}" class="flex min-h-12 items-center border-b border-linea font-semibold">Mis pedidos</a></li>
                    @endif
                    <li><form method="POST" action="{{ route('logout') }}" data-sin-bloqueo>@csrf<button class="flex min-h-12 w-full items-center gap-2 font-semibold"><x-icono nombre="salir" class="size-4" /> Salir</button></form></li>
                @else
                    <li><a href="{{ route('login') }}" class="flex min-h-12 items-center border-b border-linea font-semibold">Ingresar o crear cuenta</a></li>
                    <li><a href="{{ route('panel') }}" class="flex min-h-12 items-center gap-2 font-semibold text-tierra"><x-icono nombre="usuario-check" class="size-4" /> Acceso del personal</a></li>
                @endauth
            </ul>
        </nav>
    </header>

    {{-- Cabecera de página: banda azul con los arcos de las marcas y la línea amarilla abajo --}}
    @hasSection('cabecera')
        <div class="sobre-azul relative isolate overflow-hidden bg-tierra-oscura text-white">
            {{-- Siempre una foto de fondo (campo o sede), con el degradado azul y verde encima para leer el texto --}}
            <picture class="absolute inset-0 -z-10" aria-hidden="true">
                <source media="(max-width: 767px)" srcset="{{ asset('img/portada/' . $fotoCabecera[1]) }}">
                <img src="{{ asset('img/portada/' . $fotoCabecera[0]) }}" alt="" class="size-full object-cover" fetchpriority="high">
            </picture>
            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-noche/90 via-tierra-oscura/65 to-rialto/30" aria-hidden="true"></div>
            <x-arcos class="absolute inset-y-0 right-0 h-full w-[42rem] max-w-[70%]" />
            <div class="relative mx-auto max-w-[1320px] px-4 pb-10 pt-9 sm:px-6 lg:px-8 lg:pb-14 lg:pt-12" data-entrar>@yield('cabecera')</div>
            <div class="franja franja-fina" aria-hidden="true"></div>
        </div>
    @endif
@endif

{{-- Páginas públicas: el contenido va sobre una foto de campo tenue en lugar del gris liso --}}
<div @class(['flex-1 overflow-x-clip', 'fondo-foto fondo-foto-fijo' => ! $personal && ! $__env->hasSection('ancho_completo')])
     @if(! $personal && ! $__env->hasSection('ancho_completo')) style="--foto: url('{{ asset('img/portada/' . $fotoContenido) }}')" @endif>
<main id="contenido" tabindex="-1" @class([
    'outline-none',
    'mx-auto px-4 sm:px-6 lg:px-8' => $personal || ! $__env->hasSection('ancho_completo'),
    'max-w-[1320px] pt-10 pb-16 lg:pt-12' => ! $personal && ! $__env->hasSection('ancho_completo'),
    'pb-16' => ! $personal && $__env->hasSection('ancho_completo'),
    'max-w-[1400px] py-6 lg:py-8' => $personal,
])>
    {{-- En el área del personal, la cabecera de las páginas públicas es un panel verde dentro del contenido --}}
    @if($personal && $__env->hasSection('cabecera'))
        <div class="lobulo-tierra panel-tapa mb-8 px-6 pb-7 pt-9 sm:px-8 lg:px-10 lg:pb-9 lg:pt-11">@yield('cabecera')</div>
    @endif

    <div @class(['space-y-3 empty:hidden', 'mx-auto max-w-[1320px] px-4 has-[>*]:pt-6 sm:px-6 lg:px-8' => ! $personal && $__env->hasSection('ancho_completo')])>
        @if(session('exito'))
            <div class="aviso aviso-exito" role="status"><x-icono nombre="check-circulo" class="mt-0.5" /><div>{{ session('exito') }}</div></div>
        @endif
        @if($avisoCliente = session('aviso_cliente'))
            <div class="aviso aviso-info flex-col sm:flex-row sm:items-center" role="status"><x-icono nombre="whatsapp" class="size-6 shrink-0" />
                <div class="flex-1"><strong>{{ $avisoCliente['texto'] }}.</strong>
                    {{ $avisoCliente['correo'] ? 'Ya le enviamos un correo; también puedes avisarle por WhatsApp.' : 'El cliente no tiene correo registrado: avísale por WhatsApp.' }}</div>
                @if($avisoCliente['whatsapp'])
                    <a href="{{ $avisoCliente['whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-tierra btn-mini"><x-icono nombre="whatsapp" class="size-4" /> Enviar por WhatsApp</a>
                @else
                    <span class="text-sm font-semibold">El teléfono del cliente no es válido para WhatsApp.</span>
                @endif
            </div>
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
    <footer class="mt-6 bg-gradient-to-br from-noche via-rialto-oscuro to-rialto-oscuro text-white/85">
        <div class="franja" aria-hidden="true"></div>
        <div class="mx-auto grid max-w-[1320px] gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.4fr_1fr_1fr] lg:px-8">
            <div>
                <div class="flex items-center gap-3">
                    <x-logo tamano="size-24" con-rif />
                    <div>
                        <p class="text-xl font-black uppercase text-white">Inversiones Rialto 7 C.A.</p>
                        <p class="text-sm">RIF J-50668510-8 · Valle de la Pascua desde 1963</p>
                    </div>
                </div>
                <p class="mt-5 max-w-md text-[0.9375rem]">Agente autorizado exclusivo en Venezuela de Marchesan Tatu, Colombo Aemco, Vicon y Lumagi. Precios en USD; en bolívares a la tasa oficial del BCV. Pagos en dólares con IGTF y en bolívares con IVA.</p>
            </div>
            <nav aria-label="Pie de página">
                <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-sol">El portal</p>
                <ul class="mt-3 space-y-2 text-[0.9375rem]">
                    <li><a href="{{ route('catalogo.index') }}" class="hover:text-white hover:underline">Catálogo</a></li>
                    <li><a href="{{ route('nosotros') }}" class="hover:text-white hover:underline">Nuestra historia</a></li>
                    <li><a href="{{ route('contacto') }}" class="hover:text-white hover:underline">Contacto</a></li>
                    <li><a href="{{ route('legal.privacidad') }}" class="hover:text-white hover:underline">Privacidad</a></li>
                    <li><a href="{{ route('legal.terminos') }}" class="hover:text-white hover:underline">Términos de venta</a></li>
                    @guest<li class="pt-2"><a href="{{ route('panel') }}" class="inline-flex items-center gap-2 font-bold text-sol hover:underline"><x-icono nombre="usuario-check" class="size-4" /> Acceso del personal</a></li>@endguest
                </ul>
            </nav>
            <div>
                <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-sol">Dónde estamos</p>
                <p class="mt-3 flex items-start gap-2 text-[0.9375rem]"><x-icono nombre="ubicacion" class="mt-0.5 size-5" /> Av. Las Industrias, Edificio Campagna, Valle de la Pascua, Guárico</p>
                @if($telefono = \App\Support\Contacto::telefonoVisible())
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ \App\Support\Contacto::llamada() }}" class="btn btn-linea-clara btn-mini"><x-icono nombre="telefono" class="size-4" /> {{ $telefono }}</a>
                        <a href="{{ \App\Support\Contacto::whatsapp() }}" class="btn btn-sol btn-mini" target="_blank" rel="noopener"><x-icono nombre="whatsapp" class="size-4" /> WhatsApp</a>
                    </div>
                @endif
            </div>
        </div>
        <div class="border-t border-white/10">
            <p class="mx-auto max-w-[1320px] px-4 py-4 text-sm text-white/60 sm:px-6 lg:px-8">© {{ now()->year }} Inversiones Rialto 7 C.A. Todos los derechos reservados.</p>
        </div>
    </footer>
@endunless

{{-- Aviso breve para acciones sin recarga (agregar a la cotización) --}}
<div id="aviso" role="status" aria-live="polite"
     class="toast flex w-[min(92vw,30rem)] items-center gap-3 bg-tinta py-2 pl-5 pr-2 text-[0.9375rem] text-white data-[tipo=error]:bg-coral-tinta">
    <span data-texto class="flex-1 [&_a]:text-sol"></span>
    <button type="button" class="grid size-9 place-items-center hover:bg-white/10" x-data @click="$el.parentElement.removeAttribute('data-visible')" aria-label="Cerrar aviso"><x-icono nombre="x" class="size-4" /></button>
</div>

@stack('scripts')
</body>
</html>
