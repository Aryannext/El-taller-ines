<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos', function (Blueprint $table) {
            $table->id()->comment('Identificador de la foto');
            $table->unsignedBigInteger('prenda_id')->comment('Prenda de la foto; al eliminar la prenda se eliminan sus fotos (RN-17)');
            $table->unsignedTinyInteger('posicion')->comment('Lugar de la foto en la prenda, de 1 a 3: así una prenda no tiene más de tres (RN-17)');
            $table->string('ruta', 255)->comment('Ubicación del archivo en el almacenamiento privado, fuera de la carpeta pública (RNF-25)');
            $table->unsignedSmallInteger('ancho_px')->comment('Ancho de la imagen guardada, en píxeles (RNF-03)');
            $table->unsignedSmallInteger('alto_px')->comment('Alto de la imagen guardada, en píxeles (RNF-03)');
            $table->unsignedInteger('bytes')->comment('Tamaño del archivo guardado; no más de 400 KB (RNF-03)');
            $table->dateTime('creado_en')->useCurrent()->comment('Fecha y hora en que se tomó o se subió');
            $table->comment('Foto de una prenda para reconocerla entre las demás del rincón (RN-17, M-06.1). Guarda la ubicación del archivo, no la imagen');

            $table->unique(['prenda_id', 'posicion'], 'uq_fotos_prenda_posicion');
            $table->unique('ruta', 'uq_fotos_ruta');
            $table->foreign('prenda_id', 'fk_fotos_prenda')->references('id')->on('prendas')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE fotos
            ADD CONSTRAINT ck_fotos_posicion CHECK (posicion BETWEEN 1 AND 3),
            ADD CONSTRAINT ck_fotos_lado_mayor CHECK (GREATEST(ancho_px, alto_px) BETWEEN 1 AND 1600),
            ADD CONSTRAINT ck_fotos_bytes CHECK (bytes BETWEEN 1 AND 409600)');
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos');
    }
};
