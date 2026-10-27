<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Productos: maquinaria agrícola y repuestos.
 * - codigo_fabrica es obligatorio, único e indexado (estandariza los pedidos).
 * - precio está en USD; el monto en Bs. se calcula con la tasa de "configuraciones".
 * - costo es interno y nunca se muestra en el catálogo (oculto en el modelo).
 * - compatibilidad guarda los modelos de maquinaria compatibles (filtro "modelo").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_fabrica', 50)->unique();     // Índice único
            $table->string('nombre', 150)->index();
            $table->text('descripcion')->nullable();
            $table->text('compatibilidad')->nullable();          // Ej.: "John Deere 5075E, 5090E"
            $table->string('marca', 80)->index();                // Fabricante
            $table->decimal('precio', 12, 2);                    // Precio de venta en USD
            $table->decimal('costo', 12, 2)->nullable();         // Uso interno, nunca público
            $table->foreignId('id_categoria')->constrained('categorias')->restrictOnDelete();
            $table->string('imagen')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('activo')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_categoria', 'marca']);            // Filtro combinado del catálogo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
