<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Ordenes\TransicionesDePrenda;
use App\Modelos\Foto;
use App\Modelos\Orden;
use App\Modelos\Prenda;

/**
 * CU-19 · Las fotos de una orden agrupadas por prenda (HU-18, PT-10), y la búsqueda de una foto privada (RNF-25, diagrama 11).
 */
class FotosDeOrden
{
    public function __construct(private readonly TransicionesDePrenda $transiciones) {}

    /**
     * @return array{orden: Orden, numero: string, prendasCorregibles: list<int>}
     */
    public function obtener(Orden $orden): array
    {
        // RNF-01: una consulta por relación, no una por prenda
        $orden->loadMissing([
            'cliente',
            'prendas' => fn ($consulta) => $consulta->orderBy('id'),
            'prendas.tipoPrenda',
            'prendas.fotos' => fn ($consulta) => $consulta->orderBy('posicion'),
        ]);

        return [
            'orden' => $orden,
            'numero' => NumeroDeOrden::desde($orden->numero)->formato(),
            // Una prenda sin fotos ofrece tomarle una si todavía se puede cambiar (RN-15, RN-24)
            'prendasCorregibles' => $orden->cancelada_en !== null ? [] : $orden->prendas
                ->filter(fn (Prenda $prenda) => $this->transiciones->puedeModificarse($prenda->estado))
                ->map(fn (Prenda $prenda) => $prenda->id)
                ->values()
                ->all(),
        ];
    }

    /**
     * La foto con ese identificador, si es del negocio de la sesión. La foto no guarda su negocio:
     * se busca a través de su prenda y su orden, que sí lo filtra (RN-01, RNF-25). Sin esto, /fotos/{id} entregaría la de otro taller.
     */
    public function foto(string $id): ?Foto
    {
        if (! ctype_digit($id)) {
            return null;
        }

        return Foto::whereKey((int) $id)->whereHas('prenda.orden')->first();
    }
}
