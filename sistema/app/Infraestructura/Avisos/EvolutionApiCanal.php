<?php

declare(strict_types=1);

namespace App\Infraestructura\Avisos;

use App\Dominio\Avisos\CanalDeAviso;
use App\Dominio\Avisos\MensajeDeAviso;
use App\Dominio\Avisos\ResultadoDeEnvio;
use App\Dominio\Clientes\Celular;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * El aviso automático por Evolution API, que se conecta a WhatsApp como WhatsApp Web (ADR-007). Es la única clase que conoce su API.
 */
final class EvolutionApiCanal implements CanalDeAviso
{
    public const CANAL = 'evolution_api';

    public function __construct(
        private readonly ?string $url,
        private readonly ?string $claveApi,
        private readonly ?string $instancia,
    ) {}

    public function estaDisponible(): bool
    {
        return ($this->url ?? '') !== '' && ($this->claveApi ?? '') !== '' && ($this->instancia ?? '') !== '';
    }

    public function enviar(Celular $destino, MensajeDeAviso $mensaje): ResultadoDeEnvio
    {
        // Sin conexión o sin respuesta a tiempo, Http lanza ConnectionException y la cola reintenta (RNF-17)
        $respuesta = Http::withHeaders(['apikey' => (string) $this->claveApi])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->post(rtrim((string) $this->url, '/').'/message/sendText/'.rawurlencode((string) $this->instancia), [
                'number' => $destino->enFormatoInternacional(),
                'text' => $mensaje->texto(),
            ]);

        // Errores temporales: la cola reintenta. El mensaje no lleva la clave ni el celular
        if ($respuesta->serverError() || $respuesta->status() === 429) {
            throw new RuntimeException("Evolution API respondió {$respuesta->status()}; se reintentará.");
        }

        $idMensaje = $respuesta->json('key.id');
        if ($respuesta->successful() && is_string($idMensaje)) {
            return ResultadoDeEnvio::aceptado(self::CANAL, $idMensaje);
        }

        // Número sin WhatsApp, instancia inexistente o clave errada: reintentar no lo arregla (RN-40).
        // Solo el código: la respuesta de Evolution API repite el celular, que no se registra
        return ResultadoDeEnvio::rechazado(self::CANAL, 'HTTP '.$respuesta->status());
    }
}
