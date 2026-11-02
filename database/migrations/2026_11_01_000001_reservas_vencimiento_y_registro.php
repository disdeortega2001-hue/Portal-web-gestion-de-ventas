<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crédito con plazo y fecha de vencimiento, y registro de quién cambió precios, configuración y créditos.
 * Las existencias reservadas no necesitan columnas: se calculan de los pedidos pendientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedSmallInteger('plazo_credito_dias')->default(30)->after('limite_credito');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->date('credito_vence_en')->nullable()->after('a_credito');
        });

        Schema::create('registros_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('entidad', 40);        // Producto, Configuración o Cliente
            $table->string('referencia', 150);    // Código, clave o nombre al momento del cambio
            $table->string('campo', 60);
            $table->text('antes')->nullable();
            $table->text('despues')->nullable();
            $table->timestamp('creado_en')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_cambios');
        Schema::table('pedidos', fn (Blueprint $table) => $table->dropColumn('credito_vence_en'));
        Schema::table('clientes', fn (Blueprint $table) => $table->dropColumn('plazo_credito_dias'));
    }
};
