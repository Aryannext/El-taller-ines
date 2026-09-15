<?php

declare(strict_types=1);

namespace App\Dominio\Ordenes;

/**
 * RN-18: el estado de avance de una orden no se escribe, se calcula de sus prendas.
 * Los valores son los del filtro de la lista de órdenes (?estado=).
 */
enum EstadoDeOrden: string
{
    case EnProceso = 'en-proceso';
    case ListaParaEntregar = 'lista';
    case Entregada = 'entregada';
    case Cancelada = 'cancelada';

    /**
     * @param  iterable<EstadoDePrenda>  $estadosDePrendas
     */
    public static function calcular(iterable $estadosDePrendas, bool $cancelada): self
    {
        if ($cancelada) {
            return self::Cancelada;
        }

        // Las prendas Devueltas no hacen que la orden esté en proceso ni lista (RN-44)
        $cuentan = [];
        foreach ($estadosDePrendas as $estado) {
            if ($estado !== EstadoDePrenda::Devuelta) {
                $cuentan[] = $estado;
            }
        }

        if (in_array(EstadoDePrenda::Pendiente, $cuentan, true) || in_array(EstadoDePrenda::EnProceso, $cuentan, true)) {
            return self::EnProceso;
        }
        if (in_array(EstadoDePrenda::Terminada, $cuentan, true)) {
            return self::ListaParaEntregar;
        }
        if (in_array(EstadoDePrenda::Entregada, $cuentan, true)) {
            return self::Entregada;
        }

        // Sin prendas que cuenten no hay nada que entregar; RN-06 y RN-44 impiden llegar aquí
        return self::EnProceso;
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::EnProceso => 'En proceso',
            self::ListaParaEntregar => 'Lista para entregar',
            self::Entregada => 'Entregada',
            self::Cancelada => 'Cancelada',
        };
    }
}
