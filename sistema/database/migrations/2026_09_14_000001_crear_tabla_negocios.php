<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negocios', function (Blueprint $table) {
            $table->id()->comment('Identificador del negocio');
            $table->string('nombre', 120)->comment('Nombre del taller');
            $table->unsignedSmallInteger('dias_sin_reclamar')->default(30)->comment('Días en Lista para entregar después de los cuales una orden queda sin reclamar, entre 1 y 365 (RN-35)');
            $table->dateTime('creado_en')->useCurrent()->comment('Fecha y hora de registro');
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate()->comment('Fecha y hora del último cambio');
            $table->comment('Taller que usa el sistema. En esta entrega hay uno solo (ADR-002)');
        });

        DB::statement('ALTER TABLE negocios
            ADD CONSTRAINT ck_negocios_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0),
            ADD CONSTRAINT ck_negocios_dias_sin_reclamar CHECK (dias_sin_reclamar BETWEEN 1 AND 365)');
    }

    public function down(): void
    {
        Schema::dropIfExists('negocios');
    }
};
