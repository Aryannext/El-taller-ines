<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id()->comment('Identificador de la orden');
            $table->unsignedBigInteger('negocio_id')->comment('Negocio de la orden; siempre coincide con el de su cliente por la llave foránea compuesta (ADR-004)');
            $table->unsignedBigInteger('cliente_id')->comment('Cliente que dejó las prendas en una misma visita (RN-05)');
            $table->unsignedInteger('numero')->comment('Número consecutivo dentro del negocio; se muestra como #0042 y nunca se reutiliza (RN-08)');
            $table->date('fecha_entrega_acordada')->comment('Fecha de entrega acordada con el cliente; puede ser el mismo día de la recepción, no antes (RN-07)');
            $table->dateTime('recibida_en')->useCurrent()->comment('Fecha y hora en que se registró la orden (RN-09)');
            $table->dateTime('lista_en')->nullable()->comment('Fecha y hora en que la orden quedó Lista para entregar; se borra si vuelve a En proceso y se conserva al entregar (RN-22)');
            $table->dateTime('cancelada_en')->nullable()->comment('Fecha y hora de la cancelación; si tiene valor la orden está Cancelada y no se reabre (RN-24)');
            $table->char('token_formulario', 36)->nullable()->comment('Identificador del envío del formulario; impide registrar la misma orden dos veces (RNF-14)');
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate()->comment('Fecha y hora del último cambio');
            $table->comment('Prendas que un cliente deja en una misma visita; lo que va en una bolsa. Su estado, valor y saldo se calculan (RN-18, RN-26, RN-27)');

            $table->unique(['negocio_id', 'numero'], 'uq_ordenes_negocio_numero');
            $table->unique('token_formulario', 'uq_ordenes_token_formulario');
            $table->index(['negocio_id', 'cliente_id'], 'ix_ordenes_negocio_cliente');
            $table->index('cliente_id', 'ix_ordenes_cliente');
            $table->index(['negocio_id', 'fecha_entrega_acordada'], 'ix_ordenes_negocio_entrega');
            $table->index(['negocio_id', 'lista_en'], 'ix_ordenes_negocio_lista');
            $table->foreign('negocio_id', 'fk_ordenes_negocio')->references('id')->on('negocios');
            // El cliente debe ser del mismo negocio que la orden (ADR-004)
            $table->foreign(['negocio_id', 'cliente_id'], 'fk_ordenes_cliente')->references(['negocio_id', 'id'])->on('clientes');
        });

        DB::statement('ALTER TABLE ordenes
            ADD CONSTRAINT ck_ordenes_numero CHECK (numero > 0),
            ADD CONSTRAINT ck_ordenes_entrega CHECK (fecha_entrega_acordada >= CAST(recibida_en AS DATE))');
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes');
    }
};
