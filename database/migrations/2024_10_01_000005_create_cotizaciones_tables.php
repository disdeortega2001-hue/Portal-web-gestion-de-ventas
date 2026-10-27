<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cotizaciones y su detalle.
 * Se guardan precio unitario, tasa e IVA del momento para que la cotización
 * no cambie si después se modifica el precio del producto o la tasa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->nullable()->unique();      // COT-000001
            $table->foreignId('id_cliente')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('id_usuario')->nullable()->constrained('usuarios')->nullOnDelete(); // Vendedor que la gestiona
            $table->dateTime('fecha')->index();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('porcentaje_iva', 5, 2);
            $table->decimal('impuesto', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('tasa_cambio', 12, 4);
            $table->decimal('total_bs', 16, 2);
            $table->enum('estado', ['pendiente', 'convertida', 'rechazada'])->default('pendiente')->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('detalle_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cotizacion')->constrained('cotizaciones')->cascadeOnDelete();
            $table->foreignId('id_producto')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_cotizaciones');
        Schema::dropIfExists('cotizaciones');
    }
};
