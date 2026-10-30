<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Producto del catálogo. El costo está oculto para que nunca llegue a una vista
 * pública ni a una respuesta JSON.
 */
class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'codigo_fabrica', 'nombre', 'descripcion', 'compatibilidad', 'marca',
        'precio', 'exento_iva', 'costo', 'id_categoria', 'imagen', 'stock', 'activo',
    ];

    protected $hidden = ['costo'];

    protected $casts = [
        'precio' => 'decimal:2',
        'exento_iva' => 'boolean',
        'costo' => 'decimal:2',
        'stock' => 'integer',
        'activo' => 'boolean',
    ];

    /** Limpia las listas del filtro del catálogo (marcas y categorías con su conteo) cuando cambia un producto. */
    protected static function booted(): void
    {
        $limpiar = function () {
            Cache::forget('catalogo.marcas');
            Cache::forget('catalogo.categorias');
        };
        static::saved(function (Producto $producto) use ($limpiar) {
            if ($producto->wasRecentlyCreated || $producto->wasChanged(['marca', 'activo', 'id_categoria'])) {
                $limpiar();
            }
        });
        static::deleted($limpiar);
        static::restored($limpiar);
    }

    /** El código de fábrica siempre se guarda en mayúsculas y sin espacios extremos. */
    public function setCodigoFabricaAttribute(string $valor): void
    {
        $this->attributes['codigo_fabrica'] = mb_strtoupper(trim($valor));
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'id_producto');
    }

    public function detallesPedido(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'id_producto');
    }

    /* ---------- Scopes del catálogo ---------- */

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', true);
    }

    /** Búsqueda libre por código, nombre, marca o compatibilidad. */
    public function scopeBuscar(Builder $consulta, ?string $texto): Builder
    {
        if (blank($texto)) {
            return $consulta;
        }

        $patron = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($texto)) . '%';

        return $consulta->where(function (Builder $q) use ($patron) {
            $q->where('codigo_fabrica', 'like', $patron)
              ->orWhere('nombre', 'like', $patron)
              ->orWhere('marca', 'like', $patron)
              ->orWhere('compatibilidad', 'like', $patron);
        });
    }

    /** Filtros del catálogo: categoría, fabricante (marca) y modelo de maquinaria. */
    public function scopeFiltrar(Builder $consulta, array $filtros): Builder
    {
        return $consulta
            ->when($filtros['categoria'] ?? null, fn ($q, $id) => $q->where('id_categoria', $id))
            ->when($filtros['marca'] ?? null, fn ($q, $marca) => $q->where('marca', $marca))
            ->when($filtros['modelo'] ?? null, fn ($q, $modelo) => $q->where('compatibilidad', 'like', '%' . $modelo . '%'))
            ->when(($filtros['disponibles'] ?? null) === '1', fn ($q) => $q->where('stock', '>', 0));
    }

    /* ---------- Reglas de stock ---------- */

    public function estaAgotado(): bool
    {
        return $this->stock <= 0;
    }

    /** disponible | bajo | agotado, según el umbral configurado. */
    public function estadoStock(): string
    {
        if ($this->estaAgotado()) {
            return 'agotado';
        }

        return $this->stock <= Configuracion::umbralStockBajo() ? 'bajo' : 'disponible';
    }

    /** Precio en Bs. con la tasa vigente (solo para mostrar; los totales los calcula el servicio). */
    public function precioBolivares(): string
    {
        $centavos = (int) round((float) $this->precio * 100);
        $tasa = Configuracion::tasaCambio();

        return number_format(round($centavos * $tasa) / 100, 2, '.', '');
    }

    /**
     * URL de la foto. Las fotos subidas se guardan en dos tamaños (ver ImagenProductoService):
     * "grande" (800 px) y "mini" (240 px). Sin foto, se usa la silueta de su categoría.
     */
    public function urlImagen(string $tamano = 'grande'): string
    {
        if ($this->imagen) {
            $ruta = $tamano === 'mini' ? str_replace('-800.', '-240.', $this->imagen) : $this->imagen;

            return asset('storage/' . $ruta);
        }

        return asset('img/categorias/' . $this->silueta() . '.svg');
    }

    /** srcset para que el celular descargue la versión pequeña; null si no hay dos tamaños. */
    public function srcsetImagen(): ?string
    {
        if (! $this->imagen || ! str_contains($this->imagen, '-800.')) {
            return null;
        }

        return $this->urlImagen('mini') . ' 240w, ' . $this->urlImagen('grande') . ' 800w';
    }

    /** Silueta genérica según la categoría (solo si la categoría ya está cargada: evita consultas N+1). */
    private function silueta(): string
    {
        $categoria = $this->relationLoaded('categoria') ? mb_strtolower((string) $this->categoria?->nombre) : '';

        return match (true) {
            str_contains($categoria, 'filtro') => 'filtro',
            str_contains($categoria, 'correa'), str_contains($categoria, 'embrague') => 'correa',
            str_contains($categoria, 'cuchilla'), str_contains($categoria, 'disco') => 'disco',
            str_contains($categoria, 'bomba') => 'bomba',
            str_contains($categoria, 'maquinaria') => 'maquinaria',
            default => 'generico',
        };
    }
}
