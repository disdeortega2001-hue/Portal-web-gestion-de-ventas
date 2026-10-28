<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perfiles del panel y pagos de los pedidos.
 *
 * - Perfiles: el panel lo usan solo vendedor, administrativo y directivo. El antiguo
 *   «administrador» pasa a directivo (conserva el acceso total). El cliente compra en el portal
 *   pero no entra al panel.
 * - Pedidos: pueden nacer en el portal (compra del cliente, sin vendedor asignado todavía),
 *   llevan el método de pago elegido y el estado de su pago.
 * - Pagos: lo que el cliente reporta (referencia y comprobante) o lo que el personal registra
 *   en la tienda. El personal administrativo verifica los reportados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', fn (Blueprint $t) => $t->string('rol', 20)->default('cliente')->change());
        DB::table('usuarios')->where('rol', 'administrador')->update(['rol' => 'directivo']);
        Schema::table('usuarios', fn (Blueprint $t) => $t
            ->enum('rol', ['cliente', 'vendedor', 'administrativo', 'directivo'])->default('cliente')->change());

        Schema::table('pedidos', function (Blueprint $t) {
            $t->foreignId('id_usuario')->nullable()->change();   // Compra del portal: aún sin vendedor
            $t->enum('origen', ['mostrador', 'cotizacion', 'portal'])->default('mostrador')->after('id_cotizacion');
            $t->string('metodo_pago', 20)->nullable()->after('estado');
            $t->enum('estado_pago', ['sin_pago', 'por_verificar', 'pagado'])->default('sin_pago')->after('metodo_pago')->index();
            $t->enum('entrega', ['retiro', 'envio'])->nullable()->after('estado_pago');
            $t->text('observaciones')->nullable()->after('entrega');
        });

        DB::table('pedidos')->whereNotNull('id_cotizacion')->update(['origen' => 'cotizacion']);
        // Las ventas confirmadas antes de este cambio se cobraron en la tienda
        DB::table('pedidos')->where('estado', 'confirmado')->update(['estado_pago' => 'pagado']);

        Schema::create('pagos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('id_pedido')->constrained('pedidos')->cascadeOnDelete();
            $t->string('metodo', 20);
            $t->enum('moneda', ['USD', 'VES']);
            $t->decimal('monto', 16, 2);
            $t->string('referencia', 60)->nullable();
            $t->date('fecha_pago');
            $t->string('comprobante')->nullable();                 // Ruta en el disco privado (storage/app)
            $t->enum('estado', ['por_verificar', 'verificado', 'rechazado'])->default('por_verificar')->index();
            $t->string('motivo_rechazo', 255)->nullable();
            $t->foreignId('id_usuario_registro')->nullable()->constrained('usuarios')->nullOnDelete();   // Personal que lo registró en tienda
            $t->foreignId('id_usuario_verifica')->nullable()->constrained('usuarios')->nullOnDelete();
            $t->dateTime('verificado_en')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');

        Schema::table('pedidos', function (Blueprint $t) {
            $t->dropIndex(['estado_pago']);
            $t->dropColumn(['origen', 'metodo_pago', 'estado_pago', 'entrega', 'observaciones']);
        });

        Schema::table('usuarios', fn (Blueprint $t) => $t->string('rol', 20)->default('cliente')->change());
        DB::table('usuarios')->whereIn('rol', ['directivo', 'administrativo'])->update(['rol' => 'administrador']);
        Schema::table('usuarios', fn (Blueprint $t) => $t
            ->enum('rol', ['cliente', 'vendedor', 'administrador'])->default('cliente')->change());
    }
};
