<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Modelos\TipoPrenda;

/**
 * HU-09 · El tipo de prenda sale de la lista del negocio o se escribe con «Otro» (RN-43).
 */
class ResolverTipoDePrenda
{
    public function resolver(int $negocioId, int|string $tipoPrendaId, ?string $otro): TipoPrenda
    {
        if ($tipoPrendaId !== 'otro') {
            return TipoPrenda::where('negocio_id', $negocioId)->where('activo', true)->findOrFail((int) $tipoPrendaId);
        }

        $nombre = trim((string) $otro);
        if ($nombre === '') {
            throw new ReglaIncumplida('RN-43', 'Escribe qué tipo de prenda es.', 'tipo_otro');
        }

        // La intercalación compara sin tildes ni mayúsculas: «overol» encuentra el «Overol» que ya existe
        $existente = TipoPrenda::where('negocio_id', $negocioId)->where('nombre', $nombre)->first();
        if ($existente !== null) {
            return $existente;
        }

        $tipo = new TipoPrenda(['nombre' => mb_strtoupper(mb_substr($nombre, 0, 1)).mb_substr($nombre, 1), 'activo' => true]);
        $tipo->negocio_id = $negocioId;
        $tipo->save();

        return $tipo;
    }
}
