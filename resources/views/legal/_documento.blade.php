{{--
    Plantilla de los documentos legales: índice a la izquierda en escritorio y texto a 70 caracteres por línea.
    $secciones: [ancla => título]. El contenido llega en $slot.
--}}
<div class="grid gap-10 lg:grid-cols-[16rem_minmax(0,1fr)]">
    <nav aria-label="En esta página" class="lg:sticky lg:top-28 lg:self-start">
        <p class="font-rotulo text-sm font-bold uppercase tracking-[0.14em] text-tinta-suave">En esta página</p>
        <ol class="mt-3 space-y-1 border-l-2 border-linea">
            @foreach($secciones as $ancla => $titulo)
                <li><a href="#{{ $ancla }}" class="-ml-0.5 block border-l-2 border-transparent py-1.5 pl-4 text-[0.9375rem] font-semibold text-tinta-suave hover:border-tierra hover:text-tierra">{{ $titulo }}</a></li>
            @endforeach
        </ol>
        <p class="mt-6 text-sm text-tinta-suave">Última actualización: 27 de octubre de 2026</p>
    </nav>
    <article class="max-w-[70ch] space-y-4 text-[1.0625rem] leading-relaxed [&_h2]:titular [&_h2]:scroll-mt-28 [&_h2]:pt-6 [&_h2]:text-3xl [&_li]:ml-5 [&_li]:list-disc [&_strong]:text-tinta">
        {{ $slot }}
    </article>
</div>
