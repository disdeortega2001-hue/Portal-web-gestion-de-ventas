<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entrega del pedido: cuándo se retiró en la tienda (o se despachó), quién lo recibió
 * (el titular u otra persona autorizada, con su cédula) y qué empleado lo entregó.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            $t->dateTime('entregado_en')->nullable()->after('motivo_anulacion');
            $t->string('recibido_por', 120)->nullable()->after('entregado_en');
            $t->string('recibido_por_identificacion', 20)->nullable()->after('recibido_por');
            $t->foreignId('id_usuario_entrega')->nullable()->after('recibido_por_identificacion')
                ->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $t) {
            $t->dropConstrainedForeignId('id_usuario_entrega');
            $t->dropColumn(['entregado_en', 'recibido_por', 'recibido_por_identificacion']);
        });
    }
};
