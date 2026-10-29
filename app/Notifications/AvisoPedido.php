<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso por correo al cliente sobre su pedido: pago verificado (con el comprobante adjunto)
 * o pedido entregado (quién lo retiró, por si lo buscó otra persona).
 */
class AvisoPedido extends Notification
{
    public function __construct(
        public readonly string $asunto,
        public readonly string $saludo,
        public readonly array $lineas,
        public readonly string $enlace,
        public readonly ?string $pdf = null,
        public readonly ?string $nombrePdf = null,
    ) {
    }

    public function via(object $notificable): array
    {
        return ['mail'];
    }

    public function toMail(object $notificable): MailMessage
    {
        $correo = (new MailMessage())->subject($this->asunto . ' · Inversiones Rialto 7')->greeting($this->saludo);
        foreach ($this->lineas as $linea) {
            $correo->line($linea);
        }
        $correo->action('Ver mi pedido', $this->enlace)
            ->salutation('Inversiones Rialto 7 C.A. · Valle de la Pascua, Guárico');

        return $this->pdf ? $correo->attachData($this->pdf, $this->nombrePdf, ['mime' => 'application/pdf']) : $correo;
    }
}
