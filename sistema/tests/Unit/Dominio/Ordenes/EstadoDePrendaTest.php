<?php

namespace Tests\Unit\Dominio\Ordenes;

use App\Dominio\Ordenes\EstadoDePrenda;
use PHPUnit\Framework\TestCase;

/**
 * RN-12 · Estados de una prenda.
 */
class EstadoDePrendaTest extends TestCase
{
    public function test_rn_12_estados_de_una_prenda(): void
    {
        // Los mismos valores del ENUM de prendas.estado en esquema.sql
        $this->assertSame(
            ['pendiente', 'en_proceso', 'terminada', 'entregada', 'devuelta'],
            array_map(fn (EstadoDePrenda $estado) => $estado->value, EstadoDePrenda::cases()),
        );
        $this->assertSame(EstadoDePrenda::Pendiente, EstadoDePrenda::inicial(), 'Toda prenda nueva empieza Pendiente');
    }

    public function test_cada_estado_tiene_su_nombre_para_la_usuaria(): void
    {
        $this->assertSame(
            ['Pendiente', 'En proceso', 'Terminada', 'Entregada', 'Devuelta'],
            array_map(fn (EstadoDePrenda $estado) => $estado->etiqueta(), EstadoDePrenda::cases()),
        );
    }
}
