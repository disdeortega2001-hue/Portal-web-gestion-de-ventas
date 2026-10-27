<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro inmutable de cada cambio de stock (trazabilidad completa).
 */
class MovimientoInventario extends Model
{
    public const ENTRADA = 'entrada';
    public const SALIDA = 'salida';
    public const AJUSTE = 'ajuste';

    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'id_producto', 'tipo', 'cantidad', 'stock_anterior', 'stock_nuevo',
        'motivo', 'id_pedido', 'fecha', 'id_usuario',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'cantidad' => 'integer',
        'stock_anterior' => 'integer',
        'stock_nuevo' => 'integer',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id_producto')->withTrashed();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario')->withTrashed();
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }
}
