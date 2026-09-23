<?php

declare(strict_types=1);

namespace App\Aplicacion\Configuracion;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Modelos\Negocio;

/**
 * HU-35 · El plazo tras el cual una orden lista se considera sin reclamar. Es de cada negocio y empieza en 30
 * días (RN-35); la base solo admite entre 1 y 365, y aquí se exige lo mismo antes de llegar a ella.
 */
class CambiarPlazoSinReclamar
{
    public const MINIMO = 1;

    public const MAXIMO = 365;

    public function ejecutar(Negocio $negocio, int $dias): void
    {
        if ($dias < self::MINIMO || $dias > self::MAXIMO) {
            throw new ReglaIncumplida('RN-35', 'El plazo debe estar entre 1 y 365 días.', 'dias_sin_reclamar');
        }

        $negocio->update(['dias_sin_reclamar' => $dias]);
    }
}
