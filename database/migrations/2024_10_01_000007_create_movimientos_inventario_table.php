<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de inventario: cada cambio de stock deja un registro con
 * tipo, cantidad, stock antes/después, fecha y usuario responsable.
 * No lleva soft deletes: la trazabilidad no se borra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_producto')->constrained('productos')->restrictOnDelete();
            $table->enum('tipo', ['entrada', 'salida', 'ajuste'])->index();
            $table->integer('cantidad');                             // Ajuste puede ser negativo
            $table->unsignedInteger('stock_anterior');
            $table->unsignedInteger('stock_nuevo');
            $table->string('motivo', 255);
            $table->foreignId('id_pedido')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->dateTime('fecha')->index();
            $table->foreignId('id_usuario')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
