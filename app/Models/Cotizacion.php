<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cotizacion extends Model
{
    use SoftDeletes;

    public const PENDIENTE = 'pendiente';
    public const CONVERTIDA = 'convertida';
    public const RECHAZADA = 'rechazada';

    protected $table = 'cotizaciones';

    protected $fillable = [
        'codigo', 'id_cliente', 'id_usuario', 'fecha', 'subtotal', 'monto_exento', 'descuento',
        'moneda_pago', 'tipo_impuesto', 'porcentaje_impuesto', 'impuesto', 'total', 'tasa_cambio', 'total_bs', 'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'subtotal' => 'decimal:2',
        'monto_exento' => 'decimal:2',
        'descuento' => 'decimal:2',
        'porcentaje_impuesto' => 'decimal:2',
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

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCotizacion::class, 'id_cotizacion');
    }

    public function pedido(): HasOne
    {
        return $this->hasOne(Pedido::class, 'id_cotizacion');
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::PENDIENTE;
    }
}
