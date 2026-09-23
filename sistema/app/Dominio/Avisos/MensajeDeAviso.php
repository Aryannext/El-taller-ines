<?php

declare(strict_types=1);

namespace App\Dominio\Avisos;

use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\Dinero;

/**
 * El aviso de orden lista, armado con los datos del momento en que se envía (RN-42).
 *
 * Qué dice y cómo lo dice es RN-46: saluda de usted, nombra el taller de la dueña —no un genérico «el taller»— y
 * habla del dinero solo si hay saldo. Sus cuatro valores son los de la plantilla de WhatsApp, y el texto completo
 * es el del envío asistido (docs/04-especificacion-tecnica/05-avisos-fotos-y-reloj.md).
 */
final class MensajeDeAviso
{
    private function __construct(
        private readonly string $nombre,
        private readonly string $taller,
        private readonly NumeroDeOrden $numero,
        private readonly int $prendasListas,
        private readonly Dinero $saldo,
    ) {}

    public static function construir(string $cliente, string $taller, NumeroDeOrden $numero, int $prendasListas, Dinero $saldo): self
    {
        // {{1}} es el primer nombre: la primera palabra del nombre registrado
        $nombre = explode(' ', trim((string) preg_replace('/\s+/u', ' ', $cliente)))[0];

        return new self($nombre, trim($taller), $numero, $prendasListas, $saldo);
    }

    /**
     * Los valores de {{1}} a {{4}} de la plantilla, en ese orden. El cuarto es la frase de las prendas y el
     * dinero: va entera en una variable porque una plantilla aprobada no puede cambiar de texto según el caso.
     *
     * @return list<string>
     */
    public function parametros(): array
    {
        return [$this->nombre, $this->taller, $this->numero->formato(), $this->fraseDeLasPrendas()];
    }

    public function texto(): string
    {
        [$nombre, $taller, $numero, $frase] = $this->parametros();

        return "Hola {$nombre}, le escribimos de {$taller}. Su orden {$numero} ya está lista 🧵 {$frase} La esperamos cuando pueda pasar.";
    }

    /**
     * RN-46: cuántas prendas están listas y, solo si lo hay, cuánto queda por pagar.
     */
    private function fraseDeLasPrendas(): string
    {
        $prendas = $this->prendasListas === 1 ? 'Es 1 prenda' : "Son {$this->prendasListas} prendas";

        return $this->saldo->esMayorQue(Dinero::pesos(0))
            ? "{$prendas}, con un saldo de {$this->saldo->formato()}."
            : "{$prendas} y ya está pagada: solo pasar a recogerla.";
    }
}
