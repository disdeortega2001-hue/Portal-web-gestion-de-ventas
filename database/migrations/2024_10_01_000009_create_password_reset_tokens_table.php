<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tokens de recuperación de contraseña.
 * El nombre de la tabla y la columna "email" los impone el gestor de contraseñas
 * de Laravel (DatabaseTokenRepository); aquí "email" guarda el valor de usuarios.correo.
 * El token se guarda con hash: quien lea la tabla no puede usarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 150)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
