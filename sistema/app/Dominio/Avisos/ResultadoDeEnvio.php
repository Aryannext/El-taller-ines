<?php

declare(strict_types=1);

namespace App\Dominio\Avisos;

/**
 * Lo que responde un canal al enviar un aviso. Todos los canales, también el falso de las pruebas, devuelven este mismo resultado.
 * Los errores temporales no llegan aquí: el canal lanza una excepción para que la cola reintente (RNF-17).
 */
final class ResultadoDeEnvio
{
    private function __construct(
        public readonly bool $aceptado,
        public readonly string $canal,
        public readonly ?string $idMensaje,
        public readonly ?string $error,
    ) {}

    /**
     * El envío asistido no devuelve identificador: lo manda la usuaria desde su WhatsApp (RN-40).
     */
    public static function aceptado(string $canal, ?string $idMensaje = null): self
    {
        return new self(true, $canal, $idMensaje, null);
    }

    /**
     * El canal respondió, pero no aceptó el mensaje y reintentar no lo arregla (RN-40).
     */
    public static function rechazado(string $canal, string $error): self
    {
        return new self(false, $canal, null, $error);
    }
}
