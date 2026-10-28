<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Devoluciones y anulaciones con motivo.
 * - El cliente puede pedir la cancelación o devolución de su pedido (queda la solicitud con su motivo).
 * - El personal anula con un motivo obligatorio; si el pedido ya estaba pagado, el pago queda «reembolsado».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            $t->enum('estado_pago', ['sin_pago', 'por_verificar', 'pagado', 'reembolsado'])->default('sin_pago')->change();
            $t->text('devolucion_motivo')->nullable()->after('observaciones');
            $t->dateTime('devolucion_solicitada_en')->nullable()->index()->after('devolucion_motivo');
            $t->text('motivo_anulacion')->nullable()->after('devolucion_solicitada_en');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            $t->dropIndex(['devolucion_solicitada_en']);
            $t->dropColumn(['devolucion_motivo', 'devolucion_solicitada_en', 'motivo_anulacion']);
            $t->enum('estado_pago', ['sin_pago', 'por_verificar', 'pagado'])->default('sin_pago')->change();
        });
    }
};
