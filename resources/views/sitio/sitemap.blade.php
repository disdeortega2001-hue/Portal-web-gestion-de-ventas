{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($paginas as $pagina)
    <url>
        <loc>{{ $pagina['url'] }}</loc>
@isset($pagina['modificado'])
        <lastmod>{{ $pagina['modificado'] }}</lastmod>
@endisset
        <changefreq>{{ $pagina['frecuencia'] }}</changefreq>
        <priority>{{ $pagina['prioridad'] }}</priority>
    </url>
@endforeach
</urlset>
