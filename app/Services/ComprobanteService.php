<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\Pago;
use App\Models\Pedido;
use App\Support\Credito;
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

    /** Estado de cuenta: compras a crédito con saldo pendiente y sus abonos. */
    public function estadoCuenta(Cliente $cliente): \Barryvdh\DomPDF\PDF
    {
        $pedidos = Credito::pedidosConSaldo($cliente);

        return Pdf::loadView('pedidos.estado-cuenta', [
            'cliente' => $cliente,
            'pedidos' => $pedidos,
            'deuda' => $pedidos->sum(fn (Pedido $p) => (float) $p->saldoUsd()),
            'tasa' => Configuracion::tasaCambio(),
        ])->setPaper('letter');
    }

    /** Cuentas por cobrar de todos los clientes, por días de atraso. */
    public function cuentasPorCobrar(): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('gestion.creditos.cuentas-pdf', ['cuentas' => Credito::cuentasPorCobrar()])->setPaper('letter', 'landscape');
    }

    public function nombreArchivo(Pedido $pedido): string
    {
        return "Comprobante {$pedido->codigo}.pdf";
    }
}
