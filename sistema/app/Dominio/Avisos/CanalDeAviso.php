<?php

declare(strict_types=1);

namespace App\Dominio\Avisos;

use App\Dominio\Clientes\Celular;

/**
 * Por dónde sale el aviso al cliente (ADR-003). Un canal nuevo es una clase que implementa esta interfaz, sin cambiar EnviarAviso.
 */
interface CanalDeAviso
{
    /**
     * Si el negocio tiene configurado este canal. Si no, el aviso queda para el envío asistido (RN-40).
     */
    public function estaDisponible(): bool;

    /**
     * Devuelve el resultado si el canal respondió. Si el error es temporal (sin conexión, error del servidor o demasiadas solicitudes),
     * lanza una excepción para que la cola reintente (RNF-17).
     */
    public function enviar(Celular $destino, MensajeDeAviso $mensaje): ResultadoDeEnvio;
}
