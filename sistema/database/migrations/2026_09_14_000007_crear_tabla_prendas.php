<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prendas', function (Blueprint $table) {
            $table->id()->comment('Identificador de la prenda');
            $table->unsignedBigInteger('orden_id')->comment('Orden a la que pertenece (RN-06)');
            $table->unsignedBigInteger('tipo_prenda_id')->comment('Tipo de prenda de la lista del mismo negocio (RN-10, RN-43)');
            $table->string('descripcion_arreglo', 255)->comment('Qué arreglo lleva, escrito al registrarla (RN-10)');
            $table->unsignedInteger('precio')->comment('Precio del arreglo en pesos colombianos, entero y mayor que cero (RN-11)');
            $table->enum('estado', ['pendiente', 'en_proceso', 'terminada', 'entregada', 'devuelta'])->default('pendiente')->comment('Estado de la prenda; toda prenda nueva empieza Pendiente (RN-12)');
            $table->dateTime('entregada_en')->nullable()->comment('Fecha y hora de entrega de la prenda; la entrega real de la orden es la de su última prenda (RN-20, RN-23)');
            $table->dateTime('devuelta_en')->nullable()->comment('Fecha y hora en que el cliente se la llevó sin arreglar; su precio deja de contar (RN-44)');
            $table->dateTime('creado_en')->useCurrent()->comment('Fecha y hora de registro');
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate()->comment('Fecha y hora del último cambio');
            $table->comment('Pieza de ropa de una orden, con su arreglo, su precio y su estado');

            $table->index(['orden_id', 'estado'], 'ix_prendas_orden_estado');
            $table->index('tipo_prenda_id', 'ix_prendas_tipo');
            $table->foreign('orden_id', 'fk_prendas_orden')->references('id')->on('ordenes');
            $table->foreign('tipo_prenda_id', 'fk_prendas_tipo')->references('id')->on('tipos_prenda');
        });

        DB::statement('ALTER TABLE prendas
            ADD CONSTRAINT ck_prendas_descripcion CHECK (CHAR_LENGTH(TRIM(descripcion_arreglo)) > 0),
            ADD CONSTRAINT ck_prendas_precio CHECK (precio > 0),
            ADD CONSTRAINT ck_prendas_entregada CHECK ((estado = \'entregada\') = (entregada_en IS NOT NULL)),
            ADD CONSTRAINT ck_prendas_devuelta CHECK ((estado = \'devuelta\') = (devuelta_en IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('prendas');
    }
};
