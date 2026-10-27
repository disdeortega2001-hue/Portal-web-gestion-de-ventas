<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitud de vinculación de cuenta.
 *
 * La cédula no prueba identidad. Si alguien crea una cuenta con una cédula que ya tiene
 * cotizaciones o pedidos como invitado, la cuenta NO se asocia de inmediato: queda como
 * solicitud hasta que un vendedor llame al teléfono de la ficha y confirme que es la
 * misma persona. Así nadie accede al historial ajeno ni bloquea a su dueño.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('id_usuario_solicitante')->nullable()->unique()->after('id_usuario')
                ->constrained('usuarios')->nullOnDelete();
            $table->dateTime('solicitud_vinculacion_en')->nullable()->after('id_usuario_solicitante');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_usuario_solicitante');
            $table->dropColumn('solicitud_vinculacion_en');
        });
    }
};
