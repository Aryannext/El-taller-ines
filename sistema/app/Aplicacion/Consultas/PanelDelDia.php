<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Pagos\Dinero;
use App\Modelos\Negocio;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use Illuminate\Database\Eloquent\Builder;

/**
 * CU-33 · Lo que necesita la atención de la dueña al entrar (HU-32, PT-02, RF-37): lo que falta por cobrar
 * (RN-32), las órdenes atrasadas (RN-34), las que nadie recoge (RN-35) y los avisos que esperan un toque (RN-40).
 * Sin nada pendiente, las cuatro cifras quedan en cero (CA-32.3).
 */
class PanelDelDia
{
    public function __construct(
        private readonly OrdenesAtrasadas $atrasadas,
        private readonly OrdenesSinReclamar $sinReclamar,
        private readonly AvisosPorEnviar $avisos,
    ) {}

    /**
     * @return array{porCobrar: Dinero, atrasadas: int, ordenesSinReclamar: int, prendasSinReclamar: int, avisosPorEnviar: int, plazoSinReclamar: int}
     */
    public function obtener(Negocio $negocio): array
    {
        $sinReclamar = $this->sinReclamar->contar($negocio);

        return [
            'porCobrar' => $this->porCobrar(),
            'atrasadas' => $this->atrasadas->contar(),
            'ordenesSinReclamar' => $sinReclamar['ordenes'],
            'prendasSinReclamar' => $sinReclamar['prendas'],
            'avisosPorEnviar' => $this->avisos->contar(),
            'plazoSinReclamar' => $negocio->dias_sin_reclamar,
        ];
    }

    /**
     * RN-32 en SQL: la suma de los saldos de las órdenes no canceladas, incluidas las entregadas.
     * Sumar por separado lo que valen las prendas y lo que se ha pagado da el mismo total que sumar
     * orden por orden con CalculadoraDeSaldo —ningún saldo queda negativo (RN-16, RN-28)— y no trae
     * las 750 órdenes del volumen de referencia a memoria (RNF-01, RNF-02). PanelDelDiaTest comprueba
     * que los dos caminos coinciden.
     *
     * Las prendas y los pagos no guardan su negocio: el filtro llega por la orden (RNF-22).
     */
    private function porCobrar(): Dinero
    {
        $noCancelada = fn (Builder $orden) => $orden->whereNull('cancelada_en');

        // Las prendas Devueltas no se cobran (RN-44)
        $valor = Prenda::whereHas('orden', $noCancelada)
            ->where('estado', '!=', EstadoDePrenda::Devuelta->value)
            ->sum('precio');

        // Los pagos anulados no cuentan (RN-31)
        $pagado = Pago::whereHas('orden', $noCancelada)
            ->whereNull('anulado_en')
            ->sum('valor');

        return Dinero::pesos((int) $valor)->restar(Dinero::pesos((int) $pagado));
    }
}
