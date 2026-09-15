<?php

declare(strict_types=1);

namespace App\Aplicacion\Avisos;

use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\OrdenQuedoLista;
use App\Modelos\Orden;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Oyente de OrdenQuedoLista: crea el aviso en cola y encola su envío. No llama a WhatsApp, así la pantalla no espera (RN-37, RNF-04).
 */
class GenerarAviso
{
    public function __construct(private readonly Reloj $reloj) {}

    public function handle(OrdenQuedoLista $evento): void
    {
        $orden = Orden::find($evento->ordenId);
        if ($orden === null) {
            return;
        }

        try {
            $aviso = $orden->avisos()->create([
                'ciclo_lista_en' => $evento->listaEn,
                'estado' => 'en_cola',
                'generado_en' => $this->reloj->ahora(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // RN-38: ya hay un aviso para esta vez que la orden quedó lista
            return;
        }

        EnviarAviso::dispatch($aviso->id);
    }
}
