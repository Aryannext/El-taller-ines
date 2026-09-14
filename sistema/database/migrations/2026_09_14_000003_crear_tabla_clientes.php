<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id()->comment('Identificador del cliente');
            $table->unsignedBigInteger('negocio_id')->comment('Negocio que atiende al cliente (RN-01)');
            $table->string('nombre', 120)->comment('Nombre del cliente; se busca sin distinguir mayúsculas ni tildes (RN-02, RF-05)');
            $table->char('celular', 10)->comment('Celular colombiano de 10 dígitos que empieza por 3, destino de los avisos (RN-03); puede repetirse (RN-04)');
            $table->dateTime('creado_en')->useCurrent()->comment('Fecha y hora de registro');
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate()->comment('Fecha y hora del último cambio');
            $table->comment('Persona que lleva prendas a arreglar. No usa el sistema; recibe los avisos');

            // Destino de la llave foránea compuesta de ordenes (ADR-004)
            $table->unique(['negocio_id', 'id'], 'uq_clientes_negocio_id');
            $table->index(['negocio_id', 'nombre'], 'ix_clientes_negocio_nombre');
            $table->index(['negocio_id', 'celular'], 'ix_clientes_negocio_celular');
            $table->foreign('negocio_id', 'fk_clientes_negocio')->references('id')->on('negocios');
        });

        DB::statement('ALTER TABLE clientes
            ADD CONSTRAINT ck_clientes_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0),
            ADD CONSTRAINT ck_clientes_celular CHECK (REGEXP_LIKE(celular, \'^3[0-9]{9}$\'))');
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
