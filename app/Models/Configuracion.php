<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Parámetros de negocio clave/valor. Se guardan en caché y la caché
 * se limpia al modificarlos, para no consultar la BD en cada tarjeta del catálogo.
 */
class Configuracion extends Model
{
    use SoftDeletes;

    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor', 'descripcion'];

    private const CLAVE_CACHE = 'configuraciones.todas';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CLAVE_CACHE));
        static::deleted(fn () => Cache::forget(self::CLAVE_CACHE));
    }

    public static function valor(string $clave, mixed $porDefecto = null): mixed
    {
        $todas = Cache::rememberForever(
            self::CLAVE_CACHE,
            fn () => static::query()->pluck('valor', 'clave')->all()
        );

        return $todas[$clave] ?? $porDefecto;
    }

    public static function tasaCambio(): float
    {
        return (float) static::valor('tasa_cambio', 1);
    }

    public static function porcentajeIva(): float
    {
        return (float) static::valor('porcentaje_iva', 16);
    }

    public static function umbralStockBajo(): int
    {
        return (int) static::valor('umbral_stock_bajo', 5);
    }
}
