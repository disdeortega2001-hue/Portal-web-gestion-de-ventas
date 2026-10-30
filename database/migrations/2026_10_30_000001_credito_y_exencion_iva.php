<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas a crédito y productos exentos de IVA.
 *
 * - clientes: el personal administrativo autoriza el crédito y fija el límite (en USD).
 * - pedidos: a_credito marca la compra a crédito; el saldo se lleva en USD y se cobra con abonos.
 *   Cada 30 días se le recuerda al cliente lo que debe (ultimo_recordatorio_en).
 * - pagos: tasa del día y equivalente en USD de cada abono, para descontarlo del saldo.
 * - productos y detalles: exento_iva (los implementos Marchesan Tatu no pagan IVA).
 *   El detalle guarda la condición del momento de la venta; monto_exento, la parte sin IVA.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('credito_habilitado')->default(false)->after('direccion');
            $table->decimal('limite_credito', 12, 2)->nullable()->after('credito_habilitado');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->boolean('a_credito')->default(false)->index()->after('metodo_pago');
            $table->timestamp('ultimo_recordatorio_en')->nullable()->after('a_credito');
            $table->unsignedSmallInteger('recordatorios_enviados')->default(0)->after('ultimo_recordatorio_en');
            $table->decimal('monto_exento', 12, 2)->default(0)->after('subtotal');
            // «parcial»: crédito con abonos y saldo pendiente
            $table->enum('estado_pago', ['sin_pago', 'por_verificar', 'parcial', 'pagado', 'reembolsado'])->default('sin_pago')->change();
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->decimal('monto_exento', 12, 2)->default(0)->after('subtotal');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->decimal('tasa_cambio', 12, 4)->nullable()->after('monto');
            $table->decimal('monto_usd', 12, 2)->nullable()->after('tasa_cambio');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->boolean('exento_iva')->default(false)->after('precio');
        });
        foreach (['detalle_pedidos', 'detalle_cotizaciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->boolean('exento_iva')->default(false)->after('precio_unitario');
            });
        }

        DB::table('productos')->where('marca', 'like', '%Tatu%')->update(['exento_iva' => true]);
    }

    public function down(): void
    {
        foreach (['detalle_pedidos', 'detalle_cotizaciones', 'productos'] as $tabla) {
            Schema::table($tabla, fn (Blueprint $table) => $table->dropColumn('exento_iva'));
        }
        Schema::table('pagos', fn (Blueprint $table) => $table->dropColumn(['tasa_cambio', 'monto_usd']));
        Schema::table('cotizaciones', fn (Blueprint $table) => $table->dropColumn('monto_exento'));
        DB::table('pedidos')->where('estado_pago', 'parcial')->update(['estado_pago' => 'sin_pago']);
        Schema::table('pedidos', function (Blueprint $table) {
            $table->enum('estado_pago', ['sin_pago', 'por_verificar', 'pagado', 'reembolsado'])->default('sin_pago')->change();
            $table->dropIndex(['a_credito']);
            $table->dropColumn(['a_credito', 'ultimo_recordatorio_en', 'recordatorios_enviados', 'monto_exento']);
        });
        Schema::table('clientes', fn (Blueprint $table) => $table->dropColumn(['credito_habilitado', 'limite_credito']));
    }
};
