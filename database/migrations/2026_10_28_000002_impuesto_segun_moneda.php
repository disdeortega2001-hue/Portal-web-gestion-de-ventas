<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Impuesto según la moneda de pago:
 * - pago en dólares → IGTF (3 %), sin IVA;
 * - pago en bolívares → IVA (16 %).
 *
 * Cada cotización y pedido guarda la moneda de pago, el impuesto aplicado y su porcentaje,
 * así un documento emitido nunca cambia aunque cambien las reglas o los porcentajes.
 * El descuento por volumen se eliminó: la columna se conserva en 0 por los documentos anteriores.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['cotizaciones', 'pedidos'] as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->renameColumn('porcentaje_iva', 'porcentaje_impuesto');
            });
            Schema::table($tabla, function (Blueprint $t) {
                $t->enum('moneda_pago', ['USD', 'VES'])->default('VES')->after('descuento');
                $t->enum('tipo_impuesto', ['IVA', 'IGTF'])->default('IVA')->after('moneda_pago');
            });
        }

        // Los documentos anteriores se calcularon con IVA: quedan como pago en bolívares con IVA
        DB::table('configuraciones')->whereIn('clave', ['descuento_porcentaje', 'descuento_monto_minimo'])->delete();
    }

    public function down(): void
    {
        foreach (['cotizaciones', 'pedidos'] as $tabla) {
            Schema::table($tabla, function (Blueprint $t) {
                $t->dropColumn(['moneda_pago', 'tipo_impuesto']);
            });
            Schema::table($tabla, function (Blueprint $t) {
                $t->renameColumn('porcentaje_impuesto', 'porcentaje_iva');
            });
        }
    }
};
