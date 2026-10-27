<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo con el enlace para crear una nueva contraseña.
 * Reemplaza la notificación inglesa de Laravel y usa el parámetro "correo".
 */
class RestablecerContrasena extends Notification
{
    public function __construct(public readonly string $token)
    {
    }

    public function via(object $notificable): array
    {
        return ['mail'];
    }

    public function toMail(object $notificable): MailMessage
    {
        $minutos = config('auth.passwords.users.expire', 60);
        $enlace = route('password.reset', ['token' => $this->token, 'correo' => $notificable->correo]);

        return (new MailMessage())
            ->subject('Recupera tu contraseña · Inversiones Rialto 7')
            ->greeting("Hola, {$notificable->nombre}")
            ->line('Recibimos una solicitud para cambiar la contraseña de tu cuenta en el portal de Inversiones Rialto 7.')
            ->action('Crear nueva contraseña', $enlace)
            ->line("Este enlace vence en {$minutos} minutos y solo se puede usar una vez.")
            ->line('Si no lo pediste, ignora este correo: tu contraseña actual sigue funcionando.')
            ->salutation('Inversiones Rialto 7 C.A. · Valle de la Pascua, Guárico');
    }
}
