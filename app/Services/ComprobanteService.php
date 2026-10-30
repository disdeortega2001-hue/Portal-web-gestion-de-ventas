<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Pedido;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Comprobante de compra: se emite cuando el pago del pedido queda verificado. Lo descargan el cliente
 * (desde su pedido) y el personal; el personal lo revisa y lo envía por correo (adjunto) o por WhatsApp.
 * No sustituye la factura fiscal.
 */
class ComprobanteService
{
    /**
     * Con $porVerificar el personal ve cómo quedará el comprobante si verifica ese pago
     * (sale marcado «VISTA PREVIA» y no es válido para el cliente).
     */
    public function pdf(Pedido $pedido, ?Pago $porVerificar = null): \Barryvdh\DomPDF\PDF
    {
        $pedido->loadMissing(['cliente', 'detalles.producto', 'entregadoPor:id,nombre']);
        $pagos = $pedido->pagos()->where('estado', Pago::VERIFICADO)->with('verificadoPor:id,nombre')->get();
        if ($porVerificar) {
            $pagos->push($porVerificar);
        }

        return Pdf::loadView('pedidos.comprobante', [
            'pedido' => $pedido,
            'pagos' => $pagos,
            'emitido' => $porVerificar ? now() : ($pagos->max('verificado_en') ?? now()),
            'vistaPrevia' => $porVerificar !== null,
        ])->setPaper('letter');
    }

    public function nombreArchivo(Pedido $pedido): string
    {
        return "Comprobante {$pedido->codigo}.pdf";
    }
}
