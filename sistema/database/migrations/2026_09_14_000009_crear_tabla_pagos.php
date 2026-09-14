<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id()->comment('Identificador del pago');
            $table->unsignedBigInteger('orden_id')->comment('Orden que se paga (RN-25)');
            $table->unsignedBigInteger('metodo_pago_id')->comment('Método de pago de la lista del mismo negocio (RN-25)');
            $table->unsignedInteger('valor')->comment('Valor en pesos, entero y mayor que cero; al registrarlo no supera el saldo (RN-25, RN-28)');
            $table->dateTime('pagado_en')->useCurrent()->comment('Fecha y hora del pago, que es la del registro (RF-26, RN-33)');
            $table->dateTime('anulado_en')->nullable()->comment('Fecha y hora de la anulación; un pago anulado no cuenta en el saldo y no se borra (RN-31)');
            $table->string('motivo_anulacion', 255)->nullable()->comment('Motivo de la anulación, obligatorio al anular (RN-31)');
            $table->char('token_formulario', 36)->nullable()->comment('Identificador del envío del formulario; impide registrar el mismo pago dos veces (RNF-14)');
            $table->dateTime('actualizado_en')->useCurrent()->useCurrentOnUpdate()->comment('Fecha y hora del último cambio');
            $table->comment('Dinero que el cliente entrega por una orden, sea el total o un abono (RN-25)');

            $table->unique('token_formulario', 'uq_pagos_token_formulario');
            $table->index('orden_id', 'ix_pagos_orden');
            $table->index('metodo_pago_id', 'ix_pagos_metodo');
            $table->index('pagado_en', 'ix_pagos_pagado_en');
            $table->foreign('orden_id', 'fk_pagos_orden')->references('id')->on('ordenes');
            $table->foreign('metodo_pago_id', 'fk_pagos_metodo')->references('id')->on('metodos_pago');
        });

        DB::statement('ALTER TABLE pagos
            ADD CONSTRAINT ck_pagos_valor CHECK (valor > 0),
            ADD CONSTRAINT ck_pagos_anulacion CHECK ((anulado_en IS NULL) = (motivo_anulacion IS NULL)),
            ADD CONSTRAINT ck_pagos_motivo CHECK (motivo_anulacion IS NULL OR CHAR_LENGTH(TRIM(motivo_anulacion)) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
