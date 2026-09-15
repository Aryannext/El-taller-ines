<?php

declare(strict_types=1);

namespace App\Dominio\Clientes;

use App\Dominio\Compartido\ReglaIncumplida;

/**
 * Objeto de valor: celular colombiano de 10 dígitos que empieza por 3, el número al que llegan los avisos (RN-03).
 * No se puede crear con un valor inválido, así la regla se comprueba en un solo lugar.
 */
final class Celular
{
    public const MENSAJE = 'Escribe un celular colombiano de 10 dígitos que empiece por 3';

    private function __construct(private readonly string $numero) {}

    public static function desde(string $texto): self
    {
        $numero = preg_replace('/\s+/', '', $texto) ?? '';

        if (preg_match('/^3\d{9}$/', $numero) !== 1) {
            throw new ReglaIncumplida('RN-03', self::MENSAJE, 'celular');
        }

        return new self($numero);
    }

    public function valor(): string
    {
        return $this->numero;
    }

    /**
     * Con el indicativo de Colombia y sin el signo +, como lo pide la API de WhatsApp.
     */
    public function enFormatoInternacional(): string
    {
        return '57'.$this->numero;
    }
}
