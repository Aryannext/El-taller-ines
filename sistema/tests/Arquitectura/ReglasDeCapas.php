<?php

namespace Tests\Arquitectura;

use PHPat\Selector\Selector;
use PHPat\Test\Attributes\TestRule;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * La regla de dependencias de ADR-005: las flechas van hacia adentro.
 * PHPStan las revisa con `vendor/bin/phpstan analyse` (RNF-27).
 */
final class ReglasDeCapas
{
    #[TestRule]
    public function el_dominio_no_depende_de_nada_externo(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Dominio'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('Illuminate'),
                Selector::inNamespace('Carbon'),
                Selector::inNamespace('App\Aplicacion'),
                Selector::inNamespace('App\Modelos'),
                Selector::inNamespace('App\Http'),
                Selector::inNamespace('App\Infraestructura'),
                Selector::inNamespace('App\Providers'),
            )
            ->because('El dominio es PHP puro: si se borrara Laravel, sus reglas seguirían funcionando.');
    }

    #[TestRule]
    public function la_aplicacion_no_depende_de_la_web_ni_de_las_implementaciones(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Aplicacion'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('App\Http'),
                Selector::inNamespace('App\Infraestructura'),
            )
            ->because('Los casos de uso dependen de las interfaces del dominio, no de sus implementaciones.');
    }

    #[TestRule]
    public function los_modelos_no_dependen_de_las_capas_externas(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Modelos'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('App\Aplicacion'),
                Selector::inNamespace('App\Http'),
                Selector::inNamespace('App\Infraestructura'),
            )
            ->because('Los modelos representan tablas y no deciden nada.');
    }

    #[TestRule]
    public function la_infraestructura_no_depende_de_la_aplicacion_ni_de_la_web(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Infraestructura'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('App\Aplicacion'),
                Selector::inNamespace('App\Http'),
                Selector::inNamespace('App\Modelos'),
            )
            ->because('La infraestructura solo implementa las interfaces del dominio.');
    }
}
