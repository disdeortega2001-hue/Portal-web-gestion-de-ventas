<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Pedido;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Comprobante de compra: se emite cuando el pago del pedido queda verificado. Lo descargan el cliente
 * (desde su pedido) y el personal, y viaja adjunto en el correo del aviso de pago verificado.
 * No sustituye la factura fiscal.
 */
class ComprobanteService
{
    public function pdf(Pedido $pedido): \Barryvdh\DomPDF\PDF
    {
        $pedido->loadMissing(['cliente', 'detalles.producto', 'entregadoPor:id,nombre']);
        $pagos = $pedido->pagos()->where('estado', Pago::VERIFICADO)->with('verificadoPor:id,nombre')->get();

        return Pdf::loadView('pedidos.comprobante', [
            'pedido' => $pedido,
            'pagos' => $pagos,
            'emitido' => $pagos->max('verificado_en') ?? now(),
        ])->setPaper('letter');
    }

    public function nombreArchivo(Pedido $pedido): string
    {
        return "Comprobante {$pedido->codigo}.pdf";
    }
}
