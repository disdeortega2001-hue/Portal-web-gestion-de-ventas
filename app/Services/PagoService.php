<?php

namespace App\Services;

use App\Exceptions\StockInsuficienteException;
use App\Models\Configuracion;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Support\MetodosPago;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

/**
 * Pagos de los pedidos.
 *
 * - El cliente reporta un pago a distancia (Pago Móvil, transferencia o Zelle) con su referencia
 *   y el comprobante: el pedido queda «pago por verificar».
 * - El personal administrativo lo verifica: el pedido queda pagado y, si estaba pendiente, la venta
 *   se confirma en el mismo paso (descuenta el stock). Si falta stock, el pago queda verificado y
 *   la venta se confirma después.
 * - Si lo rechaza (la referencia no aparece en el banco), el cliente puede reportarlo de nuevo.
 * - En la tienda (efectivo o punto de venta) el personal registra el pago ya verificado.
 * - A crédito, cada pago es un abono: el pedido queda «abonado» hasta que el saldo llega a cero.
 *   Cada abono guarda la tasa del día y su equivalente en USD, que es lo que se descuenta del saldo.
 *
 * Los comprobantes se guardan en el disco privado (storage/app/comprobantes): solo el personal los ve.
 */
class PagoService
{
    public const CARPETA = 'comprobantes';

    public function __construct(private readonly PedidoService $pedidos)
    {
    }

    /**
     * A crédito el cliente indica con qué método abonó ($datos['metodo']); de contado, es el del pedido.
     *
     * @param  array{referencia: string, fecha_pago: string, monto: string, metodo?: string}  $datos
     */
    public function reportar(Pedido $pedido, array $datos, ?UploadedFile $comprobante): Pago
    {
        if (! $pedido->aceptaReporteDePago()) {
            throw new LogicException("El pedido {$pedido->codigo} no espera un pago en este momento.");
        }

        $metodo = $pedido->a_credito ? $datos['metodo'] : $pedido->metodo_pago;
        $moneda = MetodosPago::moneda($metodo);
        $this->validarAbono($pedido, $moneda, $datos['monto']);
        $ruta = $comprobante?->store(self::CARPETA, 'local');

        return DB::transaction(function () use ($pedido, $datos, $ruta, $metodo, $moneda) {
            $pago = $pedido->pagos()->create($this->equivalencia($moneda, $datos['monto']) + [
                'metodo' => $metodo,
                'moneda' => $moneda,
                'monto' => $datos['monto'],
                'referencia' => $datos['referencia'],
                'fecha_pago' => $datos['fecha_pago'],
                'comprobante' => $ruta,
                'estado' => Pago::POR_VERIFICAR,
            ]);
            $pedido->update(['estado_pago' => Pedido::PAGO_POR_VERIFICAR]);

            return $pago;
        });
    }

    /**
     * Verifica un pago reportado. Devuelve un aviso si la venta no se pudo confirmar todavía.
     */
    public function verificar(Pago $pago, Usuario $usuario): ?string
    {
        DB::transaction(function () use ($pago, $usuario) {
            $pago = Pago::lockForUpdate()->findOrFail($pago->id);
            if ($pago->estado !== Pago::POR_VERIFICAR) {
                throw new LogicException('Este pago ya fue ' . mb_strtolower($pago->etiquetaEstado()) . '.');
            }

            $pago->update(['estado' => Pago::VERIFICADO, 'id_usuario_verifica' => $usuario->id, 'verificado_en' => now()]);
            $pedido = $pago->pedido;
            $pedido->update(['estado_pago' => $pedido->a_credito ? $this->estadoCredito($pedido) : Pedido::PAGADO]);
        });

        return $this->confirmarSiPendiente($pago->pedido->fresh(), $usuario);
    }

    public function rechazar(Pago $pago, Usuario $usuario, string $motivo): void
    {
        DB::transaction(function () use ($pago, $usuario, $motivo) {
            $pago = Pago::lockForUpdate()->findOrFail($pago->id);
            if ($pago->estado !== Pago::POR_VERIFICAR) {
                throw new LogicException('Este pago ya fue ' . mb_strtolower($pago->etiquetaEstado()) . '.');
            }

            $pago->update([
                'estado' => Pago::RECHAZADO, 'motivo_rechazo' => $motivo,
                'id_usuario_verifica' => $usuario->id, 'verificado_en' => now(),
            ]);

            // Sin otro pago en revisión, el cliente puede reportarlo de nuevo
            $pedido = $pago->pedido;
            if (! $pedido->pagos()->where('estado', Pago::POR_VERIFICAR)->exists()) {
                $pedido->update(['estado_pago' => $pedido->a_credito ? $this->estadoCredito($pedido) : Pedido::SIN_PAGO]);
            }
        });
    }

    /**
     * Pago recibido en la tienda (o confirmado por el personal): queda verificado al registrarlo.
     *
     * @param  array{metodo: string, monto: string, referencia: ?string}  $datos
     */
    public function registrarEnTienda(Pedido $pedido, array $datos, Usuario $usuario): ?string
    {
        if ($pedido->estado === Pedido::ANULADO || $pedido->estaPagado()) {
            throw new LogicException("El pedido {$pedido->codigo} no admite más pagos.");
        }
        $moneda = MetodosPago::moneda($datos['metodo']);
        if ($pedido->a_credito) {
            $this->validarAbono($pedido, $moneda, $datos['monto']);
        }

        DB::transaction(function () use ($pedido, $datos, $usuario, $moneda) {
            $pedido->pagos()->create($this->equivalencia($moneda, $datos['monto']) + [
                'metodo' => $datos['metodo'],
                'moneda' => MetodosPago::moneda($datos['metodo']),
                'monto' => $datos['monto'],
                'referencia' => $datos['referencia'] ?? null,
                'fecha_pago' => now()->toDateString(),
                'estado' => Pago::VERIFICADO,
                'id_usuario_registro' => $usuario->id,
                'id_usuario_verifica' => $usuario->id,
                'verificado_en' => now(),
            ]);
            $pedido->update($pedido->a_credito
                ? ['estado_pago' => $this->estadoCredito($pedido)]
                : ['estado_pago' => Pedido::PAGADO, 'metodo_pago' => $pedido->metodo_pago ?? $datos['metodo']]);
        });

        return $this->confirmarSiPendiente($pedido->fresh(), $usuario);
    }

    public function rutaComprobante(Pago $pago): ?string
    {
        return $pago->comprobante && Storage::disk('local')->exists($pago->comprobante)
            ? Storage::disk('local')->path($pago->comprobante)
            : null;
    }

    /**
     * Estado del pago de un crédito según sus abonos: en revisión, saldado, abonado o sin abonos.
     */
    private function estadoCredito(Pedido $pedido): string
    {
        $pedido->load('pagos');

        return match (true) {
            $pedido->pagos->contains('estado', Pago::POR_VERIFICAR) => Pedido::PAGO_POR_VERIFICAR,
            (float) $pedido->saldoUsd() <= 0 => Pedido::PAGADO,
            $pedido->pagos->contains('estado', Pago::VERIFICADO) => Pedido::PAGO_PARCIAL,
            default => Pedido::SIN_PAGO,
        };
    }

    /** Un abono no puede pasar del saldo (con un margen de un centavo por el redondeo de la tasa). */
    private function validarAbono(Pedido $pedido, string $moneda, string|float $monto): void
    {
        if (! $pedido->a_credito) {
            return;
        }
        $pedido->load('pagos');
        $usd = $this->equivalencia($moneda, $monto)['monto_usd'];
        if ((float) $usd > (float) $pedido->saldoUsd() + 0.01) {
            $saldo = $moneda === 'USD' ? 'USD ' . number_format((float) $pedido->saldoUsd(), 2, ',', '.') : 'Bs. ' . number_format((float) $pedido->saldoBs(), 2, ',', '.');
            throw new LogicException("El abono pasa del saldo pendiente del pedido {$pedido->codigo}: {$saldo}.");
        }
    }

    /** @return array{tasa_cambio: float, monto_usd: string} tasa del día y equivalente en USD del pago */
    private function equivalencia(string $moneda, string|float $monto): array
    {
        $tasa = Configuracion::tasaCambio();
        $usd = $moneda === 'USD' ? (float) $monto : ($tasa > 0 ? (float) $monto / $tasa : 0);

        return ['tasa_cambio' => $tasa, 'monto_usd' => number_format(round($usd, 2), 2, '.', '')];
    }

    private function confirmarSiPendiente(Pedido $pedido, Usuario $usuario): ?string
    {
        if (! $pedido->estaPendiente()) {
            return null;
        }

        try {
            $this->pedidos->confirmarVenta($pedido, $usuario);

            return null;
        } catch (StockInsuficienteException $e) {
            return "El pago quedó registrado, pero la venta no se confirmó: {$e->getMessage()}";
        }
    }
}
