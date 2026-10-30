<?php

namespace App\Models;

use App\Support\MetodosPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago de un pedido: reportado por el cliente (referencia y comprobante) o registrado por el
 * personal en la tienda. El personal administrativo verifica los reportados.
 */
class Pago extends Model
{
    public const POR_VERIFICAR = 'por_verificar';
    public const VERIFICADO = 'verificado';
    public const RECHAZADO = 'rechazado';

    protected $table = 'pagos';

    protected $fillable = [
        'id_pedido', 'metodo', 'moneda', 'monto', 'tasa_cambio', 'monto_usd', 'referencia', 'fecha_pago', 'comprobante', 'estado',
        'motivo_rechazo', 'id_usuario_registro', 'id_usuario_verifica', 'verificado_en',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'tasa_cambio' => 'decimal:4',
        'monto_usd' => 'decimal:2',
        'fecha_pago' => 'date',
        'verificado_en' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'id_pedido');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_registro');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_verifica');
    }

    public function nombreMetodo(): string
    {
        return MetodosPago::nombre($this->metodo);
    }

    /** Equivalente en USD (los pagos anteriores al crédito no lo guardaban: se usa la tasa del pedido). */
    public function montoEnUsd(): float
    {
        if ($this->monto_usd !== null) {
            return (float) $this->monto_usd;
        }
        if ($this->moneda === 'USD') {
            return (float) $this->monto;
        }
        $tasa = (float) ($this->tasa_cambio ?: $this->pedido?->tasa_cambio);

        return $tasa > 0 ? round((float) $this->monto / $tasa, 2) : 0.0;
    }

    public function montoFormateado(): string
    {
        $monto = number_format((float) $this->monto, 2, ',', '.');

        return $this->moneda === 'USD' ? "USD {$monto}" : "Bs. {$monto}";
    }

    public function etiquetaEstado(): string
    {
        return match ($this->estado) {
            self::POR_VERIFICAR => 'Por verificar',
            self::VERIFICADO => 'Verificado',
            self::RECHAZADO => 'Rechazado',
            default => ucfirst((string) $this->estado),
        };
    }
}
