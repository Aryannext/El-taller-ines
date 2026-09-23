<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HU-37 · El correo con el que la usuaria entra por Google. Lo registra quien instala el sistema, con un comando:
 * un correo desconocido no crea nada (RN-45). Es opcional, porque el acceso con usuario y contraseña sigue siendo válido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('correo', 255)->nullable()->after('usuario')
                ->comment('Correo de Google con el que entra, si lo tiene; único en todo el sistema (RN-45)');
            $table->unique('correo', 'uq_usuarios_correo');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropUnique('uq_usuarios_correo');
            $table->dropColumn('correo');
        });
    }
};
