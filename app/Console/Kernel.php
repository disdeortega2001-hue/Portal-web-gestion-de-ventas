<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Respaldo nocturno. En el hosting basta una tarea Cron: * * * * * php artisan schedule:run
        $schedule->command('respaldo:crear')->dailyAt('02:00')->withoutOverlapping();

        // Tasa oficial del BCV: cada hora de 6:00 a 20:00, así no se pierde aunque la computadora
        // se encienda tarde (el BCV publica la del día siguiente en la tarde)
        $schedule->command('tasa:bcv')->hourly()->between('6:00', '20:00')->withoutOverlapping();

        // Recordatorio de pago a los clientes a crédito: cada 30 días desde la compra o el último aviso.
        // Corre cada hora en horario de oficina para no perderlo si la computadora se enciende tarde.
        $schedule->command('credito:recordatorios')->hourly()->between('8:00', '18:00')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
