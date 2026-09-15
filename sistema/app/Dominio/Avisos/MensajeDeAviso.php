<?php

declare(strict_types=1);

namespace App\Dominio\Avisos;

use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\Dinero;

/**
 * El aviso de orden lista, armado con los datos del momento en que se envía (RN-42). Sus cuatro valores son los de la plantilla
 * `orden_lista` de WhatsApp, y el texto completo es el que se usa en el envío asistido (docs/04-especificacion-tecnica/05-avisos-fotos-y-reloj.md).
 */
final class MensajeDeAviso
{
    private function __construct(
        private readonly string $nombre,
        private readonly NumeroDeOrden $numero,
        private readonly int $prendasListas,
        private readonly Dinero $saldo,
    ) {}

    public static function construir(string $cliente, NumeroDeOrden $numero, int $prendasListas, Dinero $saldo): self
    {
        // {{1}} es el primer nombre: la primera palabra del nombre registrado
        $nombre = explode(' ', trim((string) preg_replace('/\s+/u', ' ', $cliente)))[0];

        return new self($nombre, $numero, $prendasListas, $saldo);
    }

    /**
     * Los valores de {{1}} a {{4}} de la plantilla, en ese orden.
     *
     * @return list<string>
     */
    public function parametros(): array
    {
        return [$this->nombre, $this->numero->formato(), (string) $this->prendasListas, $this->saldo->formato()];
    }

    public function texto(): string
    {
        [$nombre, $numero, $prendas, $saldo] = $this->parametros();

        return "Hola {$nombre}, tu orden {$numero} del taller está lista para recoger. Prendas listas: {$prendas}. Saldo pendiente: {$saldo}. Te esperamos.";
    }
}
