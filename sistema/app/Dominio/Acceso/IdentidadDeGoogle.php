<?php

declare(strict_types=1);

namespace App\Dominio\Acceso;

/**
 * Quién dice Google que es quien está entrando (HU-37, ADR-005). El dominio no sabe de OAuth ni de HTTP:
 * solo necesita una dirección a la que mandar a la usuaria y, a la vuelta, un correo verificado.
 */
interface IdentidadDeGoogle
{
    /**
     * Si el sistema se instaló sin las llaves de Google, no hay por dónde entrar y el botón no se muestra (CA-37.4).
     */
    public function estaConfigurada(): bool;

    /**
     * La dirección de Google a la que se manda a la usuaria. El «estado» vuelve tal cual y sirve para
     * comprobar que la vuelta corresponde a esta misma sesión.
     */
    public function direccionDeAutorizacion(string $estado): string;

    /**
     * El correo verificado de quien entró, a cambio del código que Google devolvió.
     * Devuelve null si Google no responde, si el código no sirve o si el correo no está verificado.
     */
    public function correoVerificado(string $codigo): ?string;
}
