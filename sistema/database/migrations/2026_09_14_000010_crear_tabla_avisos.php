<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avisos', function (Blueprint $table) {
            $table->id()->comment('Identificador del aviso');
            $table->unsignedBigInteger('orden_id')->comment('Orden que quedó lista (RN-37)');
            $table->dateTime('ciclo_lista_en')->comment('Valor de ordenes.lista_en al generar el aviso: identifica cada vez que la orden quedó lista (RN-38)');
            $table->enum('estado', ['en_cola', 'enviado', 'pendiente_asistido', 'descartado'])->default('en_cola')->comment('Resultado del aviso (RN-39, RN-40, RN-41)');
            $table->enum('canal', ['api_oficial', 'asistido'])->nullable()->comment('Canal por el que salió el aviso (RN-40, ADR-003)');
            $table->text('mensaje')->nullable()->comment('Texto enviado, armado con los datos de la orden en el momento del envío (RN-42)');
            $table->unsignedTinyInteger('intentos')->default(0)->comment('Intentos de envío por la API oficial, máximo 3 (RNF-17)');
            $table->string('id_mensaje_whatsapp', 100)->nullable()->comment('Identificador que devuelve la API oficial al aceptar el mensaje');
            $table->dateTime('generado_en')->useCurrent()->comment('Fecha y hora en que se generó (RN-37)');
            $table->dateTime('resuelto_en')->nullable()->comment('Fecha y hora en que se envió o se descartó (RN-41)');
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate()->comment('Fecha y hora del último cambio');
            $table->comment('Mensaje al cliente para informarle que su orden está lista, con su canal y su resultado (RN-37 a RN-42)');

            // Un solo aviso por cada vez que la orden queda lista (RN-38)
            $table->unique(['orden_id', 'ciclo_lista_en'], 'uq_avisos_orden_ciclo');
            $table->index('estado', 'ix_avisos_estado');
            $table->foreign('orden_id', 'fk_avisos_orden')->references('id')->on('ordenes');
        });

        DB::statement('ALTER TABLE avisos
            ADD CONSTRAINT ck_avisos_intentos CHECK (intentos <= 3),
            ADD CONSTRAINT ck_avisos_enviado CHECK (estado <> \'enviado\' OR (canal IS NOT NULL AND mensaje IS NOT NULL AND resuelto_en IS NOT NULL)),
            ADD CONSTRAINT ck_avisos_descartado CHECK (estado <> \'descartado\' OR resuelto_en IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
