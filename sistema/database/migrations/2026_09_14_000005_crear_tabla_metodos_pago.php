<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->id()->comment('Identificador del método');
            $table->unsignedBigInteger('negocio_id')->comment('Negocio que usa el método (RN-25)');
            $table->string('nombre', 40)->comment('Nombre del método; el taller usa Efectivo y Nequi (RN-25)');
            $table->boolean('activo')->default(true)->comment('Si se ofrece al registrar pagos nuevos');
            $table->dateTime('creado_en')->useCurrent()->comment('Fecha y hora de registro');
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate()->comment('Fecha y hora del último cambio');
            $table->comment('Métodos de pago que acepta cada negocio. Solo se registra el método: no hay conexión con Nequi');

            $table->unique(['negocio_id', 'nombre'], 'uq_metodos_pago_negocio_nombre');
            $table->foreign('negocio_id', 'fk_metodos_pago_negocio')->references('id')->on('negocios');
        });

        DB::statement('ALTER TABLE metodos_pago
            ADD CONSTRAINT ck_metodos_pago_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
};
