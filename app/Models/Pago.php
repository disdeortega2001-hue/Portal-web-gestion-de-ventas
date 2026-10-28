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
        'id_pedido', 'metodo', 'moneda', 'monto', 'referencia', 'fecha_pago', 'comprobante', 'estado',
        'motivo_rechazo', 'id_usuario_registro', 'id_usuario_verifica', 'verificado_en',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
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
