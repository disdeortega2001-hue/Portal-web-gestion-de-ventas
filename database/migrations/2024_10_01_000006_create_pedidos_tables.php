<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pedidos y su detalle.
 * Estados: pendiente (creado, sin afectar stock) → confirmado (venta procesada,
 * stock descontado) | anulado (si estaba confirmado, el stock se repone).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->nullable()->unique();      // PED-000001
            $table->foreignId('id_cliente')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('id_usuario')->constrained('usuarios')->restrictOnDelete(); // Vendedor responsable
            $table->foreignId('id_cotizacion')->nullable()->unique()->constrained('cotizaciones')->nullOnDelete();
            $table->dateTime('fecha')->index();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('porcentaje_iva', 5, 2);
            $table->decimal('impuesto', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('tasa_cambio', 12, 4);
            $table->decimal('total_bs', 16, 2);
            $table->enum('estado', ['pendiente', 'confirmado', 'anulado'])->default('pendiente')->index();
            $table->dateTime('fecha_confirmacion')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'fecha_confirmacion']);         // Reportes de ventas
        });

        Schema::create('detalle_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pedido')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('id_producto')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
        Schema::dropIfExists('pedidos');
    }
};
