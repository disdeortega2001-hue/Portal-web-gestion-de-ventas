<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de usuarios del sistema con su perfil (rol).
 * Reemplaza la tabla "users" por defecto de Laravel para usar nombres en español.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('correo', 150)->unique();
            $table->string('contrasena');                       // Hash bcrypt, nunca texto plano
            $table->enum('rol', ['cliente', 'vendedor', 'administrador'])->default('cliente')->index();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo')->index();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
