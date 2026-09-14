<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_prenda', function (Blueprint $table) {
            $table->id()->comment('Identificador del tipo');
            $table->unsignedBigInteger('negocio_id')->comment('Negocio dueño de la lista de tipos (ADR-002)');
            $table->string('nombre', 60)->comment('Nombre del tipo; no se repite en el negocio sin distinguir mayúsculas ni tildes (RN-43)');
            $table->boolean('activo')->default(true)->comment('Si aparece al registrar prendas nuevas; desactivar no cambia las prendas existentes (RF-17)');
            $table->dateTime('creado_en')->useCurrent()->comment('Fecha y hora de registro');
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate()->comment('Fecha y hora del último cambio');
            $table->comment('Lista de tipos de prenda de cada negocio; empieza con seis y crece con «Otro» (RF-16, RN-43)');

            $table->unique(['negocio_id', 'nombre'], 'uq_tipos_prenda_negocio_nombre');
            $table->foreign('negocio_id', 'fk_tipos_prenda_negocio')->references('id')->on('negocios');
        });

        DB::statement('ALTER TABLE tipos_prenda
            ADD CONSTRAINT ck_tipos_prenda_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_prenda');
    }
};
