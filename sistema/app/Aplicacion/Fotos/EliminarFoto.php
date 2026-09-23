<?php

declare(strict_types=1);

namespace App\Aplicacion\Fotos;

use App\Aplicacion\Ordenes\CorregirPrenda;
use App\Dominio\Fotos\AlmacenDeFotos;
use App\Modelos\Foto;
use App\Modelos\Orden;
use Illuminate\Support\Facades\DB;

/**
 * HU-19 · Borrar una foto borrosa o equivocada. La prenda se conserva, aunque quede sin fotos (RN-17, CA-19.2),
 * y su posición queda libre para otra (CA-19.1). El archivo se borra después de confirmar la transacción.
 */
class EliminarFoto
{
    public function __construct(
        private readonly AlmacenDeFotos $almacen,
        private readonly CorregirPrenda $corregirPrenda,
    ) {}

    public function ejecutar(Foto $foto): void
    {
        $ruta = DB::transaction(function () use ($foto): string {
            // Bloquear la orden, como los demás cambios de la prenda
            $orden = Orden::whereKey($foto->prenda->orden_id)->lockForUpdate()->firstOrFail();
            $foto->refresh();
            // Una prenda Entregada o Devuelta, o de una orden cancelada, no cambia (RN-15, RN-24)
            $this->corregirPrenda->exigirQueSePuedaCorregir($foto->prenda, $orden);

            $ruta = $foto->ruta;
            $foto->delete();

            return $ruta;
        });

        $this->almacen->eliminar($ruta);
    }
}
