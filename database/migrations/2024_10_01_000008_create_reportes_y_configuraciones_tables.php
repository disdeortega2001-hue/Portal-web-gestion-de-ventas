<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - reportes: auditoría de cada reporte generado (quién, qué, con qué filtros, formato).
 * - configuraciones: parámetros de negocio editables por el personal administrativo
 *   (tasa_cambio, porcentaje_iva, umbral_stock_bajo, descuentos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuarios')->restrictOnDelete();
            $table->enum('tipo', ['ventas', 'inventario'])->index();
            $table->enum('formato', ['pdf', 'excel', 'pantalla']);
            $table->json('filtros')->nullable();
            $table->unsignedInteger('registros')->default(0);
            $table->dateTime('fecha')->index();
            $table->timestamps();
        });

        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('valor', 255);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuraciones');
        Schema::dropIfExists('reportes');
    }
};
