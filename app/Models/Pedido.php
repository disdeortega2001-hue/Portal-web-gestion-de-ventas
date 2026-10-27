<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use SoftDeletes;

    public const PENDIENTE = 'pendiente';
    public const CONFIRMADO = 'confirmado';
    public const ANULADO = 'anulado';

    protected $table = 'pedidos';

    protected $fillable = [
        'codigo', 'id_cliente', 'id_usuario', 'id_cotizacion', 'fecha', 'subtotal', 'descuento',
        'porcentaje_iva', 'impuesto', 'total', 'tasa_cambio', 'total_bs', 'estado', 'fecha_confirmacion',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_confirmacion' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'porcentaje_iva' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'tasa_cambio' => 'decimal:4',
        'total_bs' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'id_pedido');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'id_pedido');
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::PENDIENTE;
    }
}
