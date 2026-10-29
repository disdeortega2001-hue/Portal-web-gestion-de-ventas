<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envía un correo de prueba para comprobar que el portal puede escribir a los clientes
 * (avisos de pago verificado y de entrega, y recuperación de contraseña).
 */
class CorreoPrueba extends Command
{
    protected $signature = 'correo:prueba {destino : Correo que recibe la prueba}';

    protected $description = 'Envía un correo de prueba con la configuración de correo actual';

    public function handle(): int
    {
        if (config('mail.default') === 'log') {
            $this->error('El correo no está configurado: los mensajes solo se guardan en el registro del portal.');

            return self::FAILURE;
        }

        try {
            Mail::raw("Este es un correo de prueba del portal de Inversiones Rialto 7.\n\nSi lo recibiste, los avisos a los clientes ya salen por correo.", function ($mensaje) {
                $mensaje->to($this->argument('destino'))->subject('Prueba de correo · Inversiones Rialto 7');
            });
        } catch (Throwable $e) {
            $this->error('No se pudo enviar: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Correo de prueba enviado a ' . $this->argument('destino') . '.');

        return self::SUCCESS;
    }
}
