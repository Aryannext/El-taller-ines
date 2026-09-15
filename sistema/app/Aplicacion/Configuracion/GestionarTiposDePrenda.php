<?php

declare(strict_types=1);

namespace App\Aplicacion\Configuracion;

use App\Modelos\TipoPrenda;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lista de tipos de prenda del negocio. Renombrar y desactivar llegan con HU-16.
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
}
