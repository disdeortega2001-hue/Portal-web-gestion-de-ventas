<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Categoria extends Model
{
    use SoftDeletes;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'descripcion'];

    /** Limpia la lista de categorías del filtro del catálogo cuando cambia. */
    protected static function booted(): void
    {
        $limpiar = fn () => Cache::forget('catalogo.categorias');
        static::saved($limpiar);
        static::deleted($limpiar);
        static::restored($limpiar);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'id_categoria');
    }
}
