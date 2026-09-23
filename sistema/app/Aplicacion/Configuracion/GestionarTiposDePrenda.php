<?php

declare(strict_types=1);

namespace App\Aplicacion\Configuracion;

use App\Modelos\TipoPrenda;
use Illuminate\Database\Eloquent\Collection;

/**
 * HU-16 · La lista de tipos de prenda del negocio: los que se ofrecen al registrar, y renombrar o dejar de usar uno.
 */
class GestionarTiposDePrenda
{
    /**
     * Los que aparecen al registrar prendas nuevas, en el orden en que se agregaron (RF-16).
     *
     * @return Collection<int, TipoPrenda>
     */
    public function activos(): Collection
    {
        return TipoPrenda::where('activo', true)->orderBy('id')->get();
    }

    /**
     * Todos los del negocio, activos o no, para la pantalla de ajustes (PT-23).
     *
     * @return Collection<int, TipoPrenda>
     */
    public function todos(): Collection
    {
        return TipoPrenda::orderBy('id')->get();
    }

    /**
     * CA-16.1: renombrarlo cambia el nombre que muestran las prendas que ya lo usan, porque lo comparten.
     */
    public function renombrar(TipoPrenda $tipo, string $nombre): void
    {
        $tipo->update(['nombre' => $nombre]);
    }

    /**
     * CA-16.2: desactivarlo lo saca de la lista al registrar prendas nuevas; las que ya lo tienen lo conservan.
     */
    public function cambiarActivo(TipoPrenda $tipo, bool $activo): void
    {
        $tipo->update(['activo' => $activo]);
    }
}
