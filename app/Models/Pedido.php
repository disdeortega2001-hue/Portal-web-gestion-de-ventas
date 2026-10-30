<?php

namespace App\Models;

use App\Support\Credito;
use App\Support\MetodosPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

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
    public const PAGO_PARCIAL = 'parcial';   // crédito con abonos y saldo pendiente
    public const PAGADO = 'pagado';
    public const REEMBOLSADO = 'reembolsado';

    protected $table = 'pedidos';

    protected $fillable = [
        'codigo', 'id_cliente', 'id_usuario', 'id_cotizacion', 'fecha', 'subtotal', 'monto_exento', 'descuento',
        'moneda_pago', 'tipo_impuesto', 'porcentaje_impuesto', 'impuesto', 'total', 'tasa_cambio', 'total_bs', 'estado', 'fecha_confirmacion',
        'origen', 'metodo_pago', 'a_credito', 'ultimo_recordatorio_en', 'recordatorios_enviados', 'estado_pago', 'entrega', 'observaciones',
        'devolucion_motivo', 'devolucion_solicitada_en', 'motivo_anulacion',
        'entregado_en', 'recibido_por', 'recibido_por_identificacion', 'id_usuario_entrega',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'fecha_confirmacion' => 'datetime',
        'devolucion_solicitada_en' => 'datetime',
        'entregado_en' => 'datetime',
        'ultimo_recordatorio_en' => 'datetime',
        'a_credito' => 'boolean',
        'recordatorios_enviados' => 'integer',
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

    public function entregadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_entrega');
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

    public function estaEntregado(): bool
    {
        return $this->entregado_en !== null;
    }

    /** Venta confirmada y pagada (o a crédito) que aún no se entrega: el personal registra el retiro (o el despacho). */
    public function puedeEntregarse(): bool
    {
        return $this->estado === self::CONFIRMADO && ($this->estaPagado() || $this->a_credito) && ! $this->estaEntregado();
    }

    /* ---------- Crédito ---------- */

    /** Lo abonado en USD: los pagos verificados (los de bolívares, a la tasa del día en que se pagaron). */
    public function abonadoUsd(): string
    {
        $centavos = $this->pagos->where('estado', Pago::VERIFICADO)->sum(fn (Pago $p) => (int) round($p->montoEnUsd() * 100));

        return number_format($centavos / 100, 2, '.', '');
    }

    /** Lo que falta por pagar, en USD (nunca negativo). */
    public function saldoUsd(): string
    {
        $centavos = (int) round((float) $this->total * 100) - (int) round((float) $this->abonadoUsd() * 100);

        return number_format(max(0, $centavos) / 100, 2, '.', '');
    }

    /** El saldo en bolívares a la tasa de hoy (la deuda está en dólares). */
    public function saldoBs(): string
    {
        return number_format(round((float) $this->saldoUsd() * Configuracion::tasaCambio(), 2), 2, '.', '');
    }

    /** Crédito confirmado que todavía debe algo: es el que recibe recordatorios. */
    public function creditoVigente(): bool
    {
        return $this->a_credito && $this->estado === self::CONFIRMADO && ! $this->estaPagado() && (float) $this->saldoUsd() > 0;
    }

    /** Fecha del próximo recordatorio: 30 días después de la venta o del último aviso. */
    public function proximoRecordatorio(): ?Carbon
    {
        if (! $this->creditoVigente()) {
            return null;
        }

        return ($this->ultimo_recordatorio_en ?? $this->fecha_confirmacion ?? $this->fecha)->copy()->addDays(Credito::DIAS_RECORDATORIO);
    }

    public function recordatorioVencido(): bool
    {
        return (bool) $this->proximoRecordatorio()?->isPast();
    }

    /** Se le recordó el saldo al cliente: el próximo aviso toca en 30 días. */
    public function marcarRecordatorio(): void
    {
        $this->forceFill(['ultimo_recordatorio_en' => now(), 'recordatorios_enviados' => $this->recordatorios_enviados + 1])->save();
    }

    /** Días desde que se confirmó la venta a crédito. */
    public function diasDeCredito(): int
    {
        return (int) ($this->fecha_confirmacion ?? $this->fecha)->diffInDays(now());
    }

    /** Con el pago verificado el cliente descarga su comprobante (también después de entregado). */
    public function tieneComprobante(): bool
    {
        return $this->estado !== self::ANULADO && $this->estaPagado();
    }

    /** El cliente puede reportar un pago: pedido vigente, sin pagar y con un método a distancia. */
    public function aceptaReporteDePago(): bool
    {
        // A crédito abona las veces que quiera, con el método que prefiera, hasta saldar
        if ($this->a_credito) {
            return $this->estado !== self::ANULADO
                && in_array($this->estado_pago, [self::SIN_PAGO, self::PAGO_PARCIAL], true)
                && (float) $this->saldoUsd() > 0;
        }

        return $this->estado !== self::ANULADO
            && $this->estado_pago === self::SIN_PAGO
            && MetodosPago::enLinea($this->metodo_pago);
    }

    public function nombreMetodoPago(): string
    {
        if ($this->a_credito) {
            return 'Crédito en ' . ($this->moneda_pago === 'VES' ? 'bolívares' : 'dólares');
        }

        return MetodosPago::nombre($this->metodo_pago);
    }

    public function etiquetaPago(): string
    {
        return match ($this->estado_pago) {
            self::PAGADO => 'Pagado',
            self::PAGO_PARCIAL => 'Abonado',
            self::PAGO_POR_VERIFICAR => 'Pago por verificar',
            self::REEMBOLSADO => 'Reembolsado',
            default => $this->a_credito ? 'Crédito por pagar' : 'Sin pago',
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
        if ($this->a_credito) {
            return $this->moneda_pago === 'VES'
                ? 'Bs. ' . number_format((float) $this->saldoBs(), 2, ',', '.')
                : 'USD ' . number_format((float) $this->saldoUsd(), 2, ',', '.');
        }

        $moneda = $this->metodo_pago ? MetodosPago::moneda($this->metodo_pago) : 'USD';
        $monto = number_format((float) MetodosPago::montoEnMoneda($moneda, $this->total, $this->total_bs), 2, ',', '.');

        return $moneda === 'USD' ? "USD {$monto}" : "Bs. {$monto}";
    }
}
