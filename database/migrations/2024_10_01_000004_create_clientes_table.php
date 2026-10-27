<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clientes (productores). Puede estar asociado a una cuenta de usuario
 * (cliente registrado) o no (cliente invitado que cotizó sin cuenta).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->nullable()->unique()->constrained('usuarios')->nullOnDelete();
            $table->string('identificacion', 20)->unique();      // Cédula o RIF: V-12345678, J-30123456-7
            $table->string('nombre', 150);
            $table->string('telefono', 20);
            $table->string('correo', 150)->nullable()->index();
            $table->string('direccion', 255);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
