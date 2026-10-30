<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleCotizacion extends Model
{
    protected $table = 'detalle_cotizaciones';

    protected $fillable = ['id_cotizacion', 'id_producto', 'cantidad', 'precio_unitario', 'exento_iva', 'subtotal'];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'exento_iva' => 'boolean',
        'subtotal' => 'decimal:2',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    /** withTrashed: una cotización vieja sigue mostrando un producto ya retirado. */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto')->withTrashed();
    }
}
