<?php

declare(strict_types=1);

namespace App\Infraestructura\Avisos;

use App\Dominio\Avisos\CanalDeAviso;
use App\Dominio\Avisos\MensajeDeAviso;
use App\Dominio\Avisos\ResultadoDeEnvio;
use App\Dominio\Clientes\Celular;

/**
 * El respaldo de ADR-003: la dueña envía el aviso desde su propio WhatsApp, con el mensaje ya escrito (RN-40, HU-29).
 */
final class WhatsAppAsistidoCanal implements CanalDeAviso
{
    public const CANAL = 'asistido';

    /**
     * Siempre: solo necesita el WhatsApp del celular de la usuaria.
     */
    public function estaDisponible(): bool
    {
        return true;
    }

    /**
     * La dirección que abre el chat del cliente con el mensaje escrito.
     */
    public function enlace(Celular $destino, MensajeDeAviso $mensaje): string
    {
        return 'https://wa.me/'.$destino->enFormatoInternacional().'?text='.rawurlencode($mensaje->texto());
    }

    /**
     * El sistema no envía por este canal: deja constancia cuando la usuaria confirma que lo mandó (RN-41).
     */
    public function enviar(Celular $destino, MensajeDeAviso $mensaje): ResultadoDeEnvio
    {
        return ResultadoDeEnvio::aceptado(self::CANAL);
    }
}
