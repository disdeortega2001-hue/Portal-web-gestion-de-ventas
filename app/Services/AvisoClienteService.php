<?php

namespace App\Services;

use App\Http\Controllers\CompraController;
use App\Models\Pedido;
use App\Notifications\AvisoPedido;
use App\Support\Contacto;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Avisos al cliente cuando su pago queda verificado, cuando su pedido se entrega y, en las compras
 * a crédito, cuando se registra un abono o se cumplen 30 días sin saldar (recordatorio de pago).
 *
 * - Si el cliente dejó correo, se le envía en el momento (con el comprobante adjunto si es el pago).
 * - Para WhatsApp se prepara el mensaje: el personal lo envía con un toque desde el panel
 *   (el portal no tiene la API de WhatsApp Business para enviarlo solo).
 *
 * Un fallo del correo nunca deshace el pago ni la entrega: se registra en el log y sigue.
 */
class AvisoClienteService
{
    public const PAGO_VERIFICADO = 'pago_verificado';
    public const ENTREGADO = 'entregado';
    public const ABONO = 'abono';
    public const RECORDATORIO_CREDITO = 'recordatorio_credito';

    public function __construct(private readonly ComprobanteService $comprobantes)
    {
    }

    /** El correo de la ficha (opcional al comprar sin cuenta) o el de la cuenta del cliente. */
    public function correo(Pedido $pedido): ?string
    {
        $pedido->loadMissing('cliente.usuario');

        return $pedido->cliente->correo ?: $pedido->cliente->usuario?->correo;
    }

    /** @return array{correo: bool, whatsapp: ?string} */
    public function enviar(Pedido $pedido, string $tipo): array
    {
        $correo = $this->correo($pedido);
        $correoEnviado = false;

        if ($correo) {
            try {
                $pdf = $tipo === self::PAGO_VERIFICADO ? $this->comprobantes->pdf($pedido)->output() : null;
                Notification::route('mail', $correo)->notify(new AvisoPedido(
                    match ($tipo) {
                        self::PAGO_VERIFICADO => "Pago verificado del pedido {$pedido->codigo}",
                        self::ABONO => "Recibimos tu abono al pedido {$pedido->codigo}",
                        self::RECORDATORIO_CREDITO => "Recordatorio de pago del pedido {$pedido->codigo}",
                        default => "Pedido {$pedido->codigo} entregado",
                    },
                    'Hola, ' . $pedido->cliente->nombre,
                    $this->lineas($pedido, $tipo),
                    $this->enlacePedido($pedido),
                    $pdf,
                    $pdf ? $this->comprobantes->nombreArchivo($pedido) : null,
                ));
                $correoEnviado = true;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return ['correo' => $correoEnviado, 'whatsapp' => $this->whatsapp($pedido, $tipo)];
    }

    /** Enlace de WhatsApp al teléfono del cliente con el aviso ya redactado (null si el teléfono no es válido). */
    public function whatsapp(Pedido $pedido, string $tipo): ?string
    {
        $pedido->loadMissing('cliente');
        $texto = 'Hola ' . strtok($pedido->cliente->nombre, ' ') . ', te escribimos de Inversiones Rialto 7. '
            . implode(' ', $this->lineas($pedido, $tipo))
            . ' Puedes ver tu pedido aquí: ' . $this->enlacePedido($pedido);

        return Contacto::whatsappA($pedido->cliente->telefono, $texto);
    }

    /** @return list<string> */
    private function lineas(Pedido $pedido, string $tipo): array
    {
        if ($tipo === self::RECORDATORIO_CREDITO || $tipo === self::ABONO) {
            $pedido->loadMissing('pagos');
            $saldo = 'USD ' . number_format((float) $pedido->saldoUsd(), 2, ',', '.')
                . ' (Bs. ' . number_format((float) $pedido->saldoBs(), 2, ',', '.') . ' a la tasa de hoy)';
            $abono = $pedido->pagos->firstWhere('estado', \App\Models\Pago::VERIFICADO);

            return $tipo === self::ABONO
                ? array_values(array_filter([
                    $abono ? "Recibimos tu abono de {$abono->montoFormateado()} al pedido {$pedido->codigo}. ¡Gracias!" : null,
                    $pedido->estaPagado() ? 'Con este pago tu crédito quedó saldado.' : "Tu saldo pendiente es {$saldo}.",
                ]))
                : [
                    "Te recordamos que tu compra a crédito {$pedido->codigo} del {$pedido->fecha->format('d/m/Y')} tiene un saldo pendiente de {$saldo}.",
                    'Puedes abonar por Pago Móvil, transferencia o Zelle y reportarlo desde tu pedido, o pagar en la tienda.',
                ];
        }

        if ($tipo === self::PAGO_VERIFICADO) {
            $siguiente = match (true) {
                $pedido->estaEntregado() => 'Tu pedido ya fue entregado.',
                $pedido->estado !== Pedido::CONFIRMADO => 'Estamos preparando tu pedido y te avisamos cuando esté listo.',
                $pedido->entrega === 'envio' => 'Tu pedido está confirmado; te contactamos para coordinar el envío.',
                default => 'Tu pedido está confirmado y listo para retirar en la tienda: Av. Las Industrias, Edificio Campagna, Valle de la Pascua.',
            };

            return [
                "Verificamos el pago de tu pedido {$pedido->codigo} por {$pedido->montoAPagar()}.",
                $siguiente,
                'Tu comprobante de pago está disponible en tu pedido.',
            ];
        }

        $quien = $pedido->recibido_por . ($pedido->recibido_por_identificacion ? " (C.I. {$pedido->recibido_por_identificacion})" : '');

        return [
            "Tu pedido {$pedido->codigo} fue " . ($pedido->entrega === 'envio' ? 'entregado' : 'retirado en la tienda')
                . " el {$pedido->entregado_en->format('d/m/Y')} a las {$pedido->entregado_en->format('h:i a')}. Lo recibió {$quien}.",
            'Si no reconoces esta entrega, comunícate con nosotros de inmediato.',
        ];
    }

    /** Con cuenta, a «Mis pedidos»; sin cuenta, al enlace firmado del pedido. */
    private function enlacePedido(Pedido $pedido): string
    {
        return $pedido->cliente->id_usuario
            ? route('cuenta.pedidos.show', $pedido)
            : CompraController::enlaceSeguimiento($pedido);
    }
}
