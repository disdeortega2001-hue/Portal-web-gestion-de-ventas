<?php

namespace App\Console\Commands;

use App\Models\Pedido;
use App\Services\AvisoClienteService;
use Illuminate\Console\Command;

/**
 * Recordatorio de pago a los clientes con compras a crédito: a los 30 días de la venta (y luego
 * cada 30 días desde el último aviso) se le envía por correo el saldo pendiente.
 *
 * Quien no tiene correo queda en Gestión → Créditos como «por recordar»: el personal le envía el
 * recordatorio por WhatsApp con un toque (el portal no tiene la API de WhatsApp Business).
 */
class RecordatoriosCredito extends Command
{
    protected $signature = 'credito:recordatorios';

    protected $description = 'Envía el recordatorio de pago a los clientes a crédito que cumplen 30 días sin saldar';

    public function handle(AvisoClienteService $avisos): int
    {
        $enviados = 0;
        $porWhatsapp = 0;

        foreach (self::porRecordar() as $pedido) {
            if (! $avisos->correo($pedido)) {
                $porWhatsapp++;
                continue;
            }
            if ($avisos->enviar($pedido, AvisoClienteService::RECORDATORIO_CREDITO)['correo']) {
                $pedido->marcarRecordatorio();
                $enviados++;
            }
        }

        $this->info("Recordatorios enviados por correo: {$enviados}. Por enviar por WhatsApp desde el panel: {$porWhatsapp}.");

        return self::SUCCESS;
    }

    /** Créditos confirmados con saldo cuyo recordatorio ya venció. */
    public static function porRecordar()
    {
        return Pedido::with(['cliente.usuario', 'pagos'])
            ->where('a_credito', true)
            ->where('estado', Pedido::CONFIRMADO)
            ->whereNotIn('estado_pago', [Pedido::PAGADO, Pedido::REEMBOLSADO])
            ->get()
            ->filter(fn (Pedido $p) => $p->recordatorioVencido())
            ->values();
    }
}
