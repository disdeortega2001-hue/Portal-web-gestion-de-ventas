<?php

namespace App\Providers;

use App\Services\CarritoService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Una sola instancia por petición para que todas las vistas lean el mismo carrito
        $this->app->scoped(CarritoService::class);
    }

    public function boot(): void
    {
        // En producción todos los enlaces se generan con https://
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('partials.paginacion');
        Paginator::defaultSimpleView('partials.paginacion');

        // Formato venezolano de montos: @usd($monto) → USD 1.234,56 · @bs($monto) → Bs. 1.234,56
        Blade::directive('usd', fn ($monto) => "<?php echo 'USD ' . number_format((float) ($monto), 2, ',', '.'); ?>");
        Blade::directive('bs', fn ($monto) => "<?php echo 'Bs. ' . number_format((float) ($monto), 2, ',', '.'); ?>");

        // Contador del carrito en la barra de navegación
        View::composer('layouts.app', function ($vista) {
            $vista->with('itemsCarrito', app(CarritoService::class)->cantidadItems());
        });
    }
}
