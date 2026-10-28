<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El cliente entra con su cédula o RIF y su contraseña: el correo pasa a ser opcional
 * (solo sirve para recuperar la contraseña). El personal sigue entrando con su correo.
 * La columna conserva el índice único: varios NULL no chocan entre sí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', fn (Blueprint $t) => $t->string('correo', 150)->nullable()->change());
    }

    public function down(): void
    {
        Schema::table('usuarios', fn (Blueprint $t) => $t->string('correo', 150)->nullable(false)->change());
    }
};
