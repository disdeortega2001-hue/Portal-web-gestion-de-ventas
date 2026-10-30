<?php

namespace App\Http\Controllers\Gestion;

use App\Console\Commands\RecordatoriosCredito;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Services\AvisoClienteService;
use App\Support\Credito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Créditos (administrativo y directivo): quién debe, cuánto y cuándo toca recordarle el pago;
 * autorizar el crédito de un cliente y fijar su límite.
 */
class CreditoController extends Controller
{
    public function __construct(private readonly AvisoClienteService $avisos)
    {
    }

    public function index(Request $request): View
    {
        $estado = $request->query('estado', 'vigentes');

        $creditos = Pedido::with(['cliente:id,identificacion,nombre,telefono,correo,id_usuario', 'cliente.usuario:id,correo', 'pagos'])
            ->where('a_credito', true)
            ->where('estado', '!=', Pedido::ANULADO)
            ->when($estado === 'saldados', fn ($q) => $q->where('estado_pago', Pedido::PAGADO))
            ->when($estado !== 'saldados', fn ($q) => $q->where('estado_pago', '!=', Pedido::PAGADO))
            ->latest('fecha')
            ->get();

        // Los que ya cumplieron 30 días sin aviso van primero
        if ($estado === 'por_recordar') {
            $creditos = $creditos->filter->recordatorioVencido()->values();
        }

        $vigentes = Pedido::with('pagos')->where('a_credito', true)->where('estado', '!=', Pedido::ANULADO)->where('estado_pago', '!=', Pedido::PAGADO)->get();
        $conteos = [
            'vigentes' => $vigentes->count(),
            'por_recordar' => $vigentes->filter->recordatorioVencido()->count(),
            'saldados' => Pedido::where('a_credito', true)->where('estado', '!=', Pedido::ANULADO)->where('estado_pago', Pedido::PAGADO)->count(),
        ];
        $porCobrar = $vigentes->sum(fn (Pedido $p) => (float) $p->saldoUsd());

        return view('gestion.creditos.index', compact('creditos', 'estado', 'conteos', 'porCobrar'));
    }

    /**
     * Recordar el pago ahora: por correo si el cliente lo tiene; por WhatsApp, abre el mensaje ya
     * redactado. En ambos casos el próximo recordatorio queda para dentro de 30 días.
     */
    public function recordar(Request $request, Pedido $pedido): RedirectResponse
    {
        abort_unless($pedido->creditoVigente(), 404, 'Este pedido no tiene un crédito con saldo pendiente.');

        if ($request->input('via') === 'correo') {
            $envio = $this->avisos->enviar($pedido, AvisoClienteService::RECORDATORIO_CREDITO);
            if (! $envio['correo']) {
                return back()->with('error', 'No se pudo enviar el correo. Revisa la configuración del correo o envíalo por WhatsApp.');
            }
            $pedido->marcarRecordatorio();

            return back()->with('exito', "Recordatorio del pedido {$pedido->codigo} enviado por correo a {$this->avisos->correo($pedido)}.");
        }

        $whatsapp = $this->avisos->whatsapp($pedido, AvisoClienteService::RECORDATORIO_CREDITO);
        if (! $whatsapp) {
            return back()->with('error', "El teléfono de {$pedido->cliente->nombre} no es válido para WhatsApp.");
        }
        $pedido->marcarRecordatorio();

        return redirect()->away($whatsapp);
    }

    /** Autorizar o quitar el crédito de un cliente y fijar su límite (vacío: sin límite). */
    public function actualizarCliente(Request $request, Cliente $cliente): RedirectResponse
    {
        $datos = $request->validate([
            'credito_habilitado' => ['boolean'],
            'limite_credito' => ['nullable', 'numeric', 'min:0', 'max:9999999999', 'decimal:0,2'],
        ], [
            'limite_credito.numeric' => 'El límite debe ser un monto en dólares.',
            'limite_credito.decimal' => 'El límite admite máximo 2 decimales.',
        ]);

        $cliente->forceFill([
            'credito_habilitado' => (bool) ($datos['credito_habilitado'] ?? false),
            'limite_credito' => $datos['limite_credito'] ?? null,
        ])->save();

        return back()->with('exito', $cliente->credito_habilitado
            ? "Crédito autorizado para {$cliente->nombre}" . ($cliente->limite_credito !== null ? ' hasta USD ' . number_format((float) $cliente->limite_credito, 2, ',', '.') : ' sin límite') . '.'
            : "{$cliente->nombre} ya no puede comprar a crédito. Lo que debe sigue pendiente.");
    }

    /** Cuántos créditos esperan recordatorio (para el menú y el tablero). */
    public static function porRecordar(): int
    {
        return RecordatoriosCredito::porRecordar()->count();
    }

    /** Resumen del crédito de un cliente para su ficha. */
    public static function resumenCliente(Cliente $cliente): array
    {
        return ['deuda' => Credito::deudaUsd($cliente), 'disponible' => Credito::disponibleUsd($cliente)];
    }
}
