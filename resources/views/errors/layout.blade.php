{{--
    Páginas de error: independientes de la base de datos y de la sesión, para que se muestren
    aunque lo que falló sea justamente eso. Cada error define $codigo, $titulo, $texto y su acción.
--}}
<!doctype html>
<html lang="es-VE">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0b4a3b">
    <title>@yield('titulo') · Inversiones Rialto 7</title>
    <link rel="icon" type="image/png" href="{{ asset('img/favicon.png') }}">
    @if(is_file(public_path('build/manifest.json')))@vite(['resources/css/app.css'])@endif
</head>
<body class="flex min-h-dvh flex-col bg-agua font-sans text-tinta">
    <header class="bg-white">
        <div class="mx-auto flex h-16 max-w-[1320px] items-center gap-3 px-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 no-underline">
                <span class="grid size-11 place-items-center bg-tierra"><img src="{{ asset('img/logo.png') }}" alt="" class="size-9 object-contain"></span>
                <span class="font-rotulo text-[1.375rem] font-extrabold uppercase text-tinta">Rialto 7</span>
            </a>
        </div>
        <div class="franja" aria-hidden="true"></div>
    </header>
    <main class="mx-auto grid w-full max-w-[1320px] flex-1 items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[auto_1fr] lg:px-8">
        <span class="ruedo size-40 text-6xl sm:size-52 sm:text-7xl" aria-hidden="true">@yield('codigo')</span>
        <div class="max-w-2xl">
            <h1 class="titular text-5xl sm:text-6xl">@yield('titulo')</h1>
            <p class="mt-4 text-lg text-tinta-suave">@yield('texto')</p>
            <div class="mt-7 flex flex-wrap gap-2">
                @yield('acciones')
            </div>
        </div>
    </main>
    <footer class="bg-tierra-oscura"><div class="franja franja-fina" aria-hidden="true"></div>
        <p class="mx-auto max-w-[1320px] px-4 py-5 text-sm text-white/70 sm:px-6 lg:px-8">Inversiones Rialto 7 C.A. · Valle de la Pascua desde 1963</p>
    </footer>
</body>
</html>
