<?php

namespace App\Models;

use App\Support\MetodosPago;
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

    // Origen del pedido
    public const MOSTRADOR = 'mostrador';
    public const DESDE_COTIZACION = 'cotizacion';
    public const PORTAL = 'portal';

    // Estado del pago
    public const SIN_PAGO = 'sin_pago';
    public const PAGO_POR_VERIFICAR = 'por_verificar';
    public const PAGADO = 'pagado';
    public const REEMBOLSADO = 'reembolsado';

    protected $table = 'pedidos';

    protected $fillable = [
        'codigo', 'id_cliente', 'id_usuario', 'id_cotizacion', 'fecha', 'subtotal', 'descuento',
        'moneda_pago', 'tipo_impuesto', 'porcentaje_impuesto', 'impuesto', 'total', 'tasa_cambio', 'total_bs', 'estado', 'fecha_confirmacion',
        'origen', 'metodo_pago', 'estado_pago', 'entrega', 'observaciones',
        'devolucion_motivo', 'devolucion_solicitada_en', 'motivo_anulacion',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_confirmacion' => 'datetime',
        'devolucion_solicitada_en' => 'datetime',
        'subtotal' => 'decimal:2',
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

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class, 'id_pedido');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_pedido')->latest();
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'id_pedido');
    }

    public function estaPendiente(): bool
    {
        return $this->estado === self::PENDIENTE;
    }

    public function estaPagado(): bool
    {
        return $this->estado_pago === self::PAGADO;
    }

    /** El cliente puede reportar un pago: pedido vigente, sin pagar y con un método a distancia. */
    public function aceptaReporteDePago(): bool
    {
        return $this->estado !== self::ANULADO
            && $this->estado_pago === self::SIN_PAGO
            && MetodosPago::enLinea($this->metodo_pago);
    }

    public function nombreMetodoPago(): string
    {
        return MetodosPago::nombre($this->metodo_pago);
    }

    public function etiquetaPago(): string
    {
        return match ($this->estado_pago) {
            self::PAGADO => 'Pagado',
            self::PAGO_POR_VERIFICAR => 'Pago por verificar',
            self::REEMBOLSADO => 'Reembolsado',
            default => 'Sin pago',
        };
    }

    /** El cliente pidió cancelar o devolver y el personal aún no lo atiende. */
    public function devolucionPendiente(): bool
    {
        return $this->devolucion_solicitada_en !== null && $this->estado !== self::ANULADO;
    }

    /** Pendiente y sin pagar: el cliente puede cancelarlo él mismo, sin intervención del personal. */
    public function clientePuedeCancelar(): bool
    {
        return $this->estado === self::PENDIENTE && $this->estado_pago === self::SIN_PAGO;
    }

    public function etiquetaOrigen(): string
    {
        return match ($this->origen) {
            self::PORTAL => 'Compra en el portal',
            self::DESDE_COTIZACION => 'Desde cotización',
            default => 'Mostrador',
        };
    }

    /** Monto a pagar en la moneda del método elegido (USD o Bs. a la tasa del pedido). */
    public function montoAPagar(): string
    {
        $moneda = $this->metodo_pago ? MetodosPago::moneda($this->metodo_pago) : 'USD';
        $monto = number_format((float) MetodosPago::montoEnMoneda($moneda, $this->total, $this->total_bs), 2, ',', '.');

        return $moneda === 'USD' ? "USD {$monto}" : "Bs. {$monto}";
    }
}
