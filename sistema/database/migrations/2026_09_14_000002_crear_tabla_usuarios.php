<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id()->comment('Identificador de la usuaria');
            $table->unsignedBigInteger('negocio_id')->comment('Negocio al que pertenece; solo ve la información de ese negocio (RN-01)');
            $table->string('nombre', 120)->comment('Nombre de la persona');
            $table->string('usuario', 60)->comment('Nombre con el que inicia sesión; único en todo el sistema');
            $table->string('contrasena', 255)->comment('Hash de la contraseña, nunca el texto plano (RNF-19)');
            $table->string('token_recordar', 100)->nullable()->comment('Token de la sesión recordada en el dispositivo');
            $table->dateTime('creado_en')->useCurrent()->comment('Fecha y hora de registro');
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate()->comment('Fecha y hora del último cambio');
            $table->comment('Persona del negocio que usa el sistema; hoy, la dueña del taller');

            $table->unique('usuario', 'uq_usuarios_usuario');
            $table->index('negocio_id', 'ix_usuarios_negocio');
            $table->foreign('negocio_id', 'fk_usuarios_negocio')->references('id')->on('negocios');
        });

        DB::statement('ALTER TABLE usuarios
            ADD CONSTRAINT ck_usuarios_usuario CHECK (CHAR_LENGTH(TRIM(usuario)) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
