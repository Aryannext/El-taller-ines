<?php

declare(strict_types=1);

namespace App\Dominio\Compartido;

use DomainException;

/**
 * Una regla de negocio impide la acción. Lleva el código de la regla, el mensaje en español para la usuaria
 * y el campo junto al que se muestra, si lo hay (RNF-09).
 */
final class ReglaIncumplida extends DomainException
{
    public function __construct(
        public readonly string $regla,
        public readonly string $mensajeParaUsuaria,
        public readonly ?string $campo = null,
    ) {
        parent::__construct($mensajeParaUsuaria);
    }
}
