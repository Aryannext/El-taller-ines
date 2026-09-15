<?php

declare(strict_types=1);

namespace App\Dominio\Fotos;

/**
 * Dónde y cómo se guardan las fotos de las prendas (ADR-005). Solo guarda, entrega y elimina: no decide cuántas caben (RN-17).
 */
interface AlmacenDeFotos
{
    /**
     * Endereza, reduce y guarda la imagen (RNF-03). Devuelve dónde quedó y cómo quedó, para registrarla.
     *
     * @return array{ruta: string, ancho_px: int, alto_px: int, bytes: int}
     */
    public function guardar(string $rutaTemporal, string $carpeta): array;

    /**
     * La ubicación del archivo, para entregarlo solo con sesión (RNF-25).
     */
    public function entregar(string $ruta): string;

    public function eliminar(string $ruta): void;
}
