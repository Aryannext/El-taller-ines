<?php

declare(strict_types=1);

namespace App\Dominio\Ordenes;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Las reglas de las dos listas de seguimiento: qué orden está atrasada (RN-34), cuál está sin reclamar (RN-35) y cuántos
 * días lleva esperando (RN-36). Los días son de calendario: la hora no cuenta. «Hoy» lo da el reloj de Colombia (RN-09).
 *
 * OrdenesAtrasadas y OrdenesSinReclamar filtran en SQL para no traer todas las órdenes a memoria (RNF-02), pero sacan de
 * aquí la fecha de corte y los días: la regla está escrita una sola vez.
 */
final class ReglasDeSeguimiento
{
    /**
     * RN-34: En proceso y con la fecha acordada anterior a hoy. Una orden Lista para entregar no está atrasada:
     * el trabajo está hecho y lo que falta es que el cliente la recoja.
     */
    public function estaAtrasada(EstadoDeOrden $estado, DateTimeInterface $entregaAcordada, DateTimeImmutable $hoy): bool
    {
        return $estado === EstadoDeOrden::EnProceso && $this->dia($entregaAcordada) < $this->dia($hoy);
    }

    /**
     * Los días que pasaron desde la fecha acordada hasta hoy.
     */
    public function diasDeAtraso(DateTimeInterface $entregaAcordada, DateTimeImmutable $hoy): int
    {
        return $this->diasEntre($entregaAcordada, $hoy);
    }

    /**
     * RN-35: Lista para entregar por más días que el plazo del negocio. Con el plazo en 30, una orden lista hace
     * 30 días todavía no está sin reclamar: hace falta pasarlo.
     */
    public function estaSinReclamar(EstadoDeOrden $estado, ?DateTimeInterface $listaEn, DateTimeImmutable $hoy, int $plazo): bool
    {
        return $estado === EstadoDeOrden::ListaParaEntregar && $listaEn !== null && $this->diasDeEspera($listaEn, $hoy) > $plazo;
    }

    /**
     * RN-35 dicho como fecha de corte, para filtrar en SQL: está sin reclamar si quedó lista antes de este día.
     */
    public function sinReclamarSiQuedoListaAntesDe(DateTimeImmutable $hoy, int $plazo): DateTimeImmutable
    {
        return $this->dia($hoy)->modify("-{$plazo} days");
    }

    /**
     * RN-35: las prendas sin reclamar de una orden son sus Terminadas; las entregadas ya salieron del taller.
     *
     * @param  iterable<EstadoDePrenda>  $prendas
     */
    public function prendasSinReclamar(iterable $prendas): int
    {
        $terminadas = 0;
        foreach ($prendas as $estado) {
            $terminadas += $estado === EstadoDePrenda::Terminada ? 1 : 0;
        }

        return $terminadas;
    }

    /**
     * RN-36: los días calendario desde que la orden quedó lista hasta hoy. Una orden lista ayer a las 11 p. m.
     * lleva un día de espera, no cero.
     */
    public function diasDeEspera(DateTimeInterface $listaEn, DateTimeImmutable $hoy): int
    {
        return $this->diasEntre($listaEn, $hoy);
    }

    private function diasEntre(DateTimeInterface $desde, DateTimeImmutable $hasta): int
    {
        return (int) $this->dia($desde)->diff($this->dia($hasta))->days;
    }

    private function dia(DateTimeInterface $momento): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($momento)->setTime(0, 0);
    }
}
