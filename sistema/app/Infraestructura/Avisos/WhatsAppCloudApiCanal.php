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
 * El aviso por la API oficial de WhatsApp (ADR-003, RNF-06). Es la única clase que conoce la API: un cambio de versión se hace solo aquí.
 */
final class WhatsAppCloudApiCanal implements CanalDeAviso
{
    public const CANAL = 'api_oficial';

    public function __construct(
        private readonly ?string $token,
        private readonly ?string $idNumero,
        private readonly ?string $versionApi,
        private readonly string $plantilla,
        private readonly string $idioma,
    ) {}

    public function estaDisponible(): bool
    {
        // Sin token el negocio usa el envío asistido (RN-40). La versión también hace falta para armar la dirección
        return ($this->token ?? '') !== '' && ($this->idNumero ?? '') !== '' && ($this->versionApi ?? '') !== '';
    }

    public function enviar(Celular $destino, MensajeDeAviso $mensaje): ResultadoDeEnvio
    {
        // Sin conexión o sin respuesta a tiempo, Http lanza ConnectionException y la cola reintenta (RNF-17)
        $respuesta = Http::withToken((string) $this->token)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->post("https://graph.facebook.com/{$this->versionApi}/{$this->idNumero}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $destino->enFormatoInternacional(),
                'type' => 'template',
                'template' => [
                    'name' => $this->plantilla,
                    'language' => ['code' => $this->idioma],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => array_map(fn (string $valor) => ['type' => 'text', 'text' => $valor], $mensaje->parametros()),
                    ]],
                ],
            ]);

        // Errores temporales: la cola reintenta. El mensaje de la excepción no lleva el token ni el celular
        if ($respuesta->serverError() || $respuesta->status() === 429) {
            throw new RuntimeException("WhatsApp respondió {$respuesta->status()}; se reintentará.");
        }

        $idMensaje = $respuesta->json('messages.0.id');
        if ($respuesta->successful() && is_string($idMensaje)) {
            return ResultadoDeEnvio::aceptado(self::CANAL, $idMensaje);
        }

        // Token vencido, número sin WhatsApp o plantilla no aprobada: reintentar no lo arregla (RN-40)
        $codigo = $respuesta->json('error.code');

        return ResultadoDeEnvio::rechazado(self::CANAL, is_scalar($codigo) ? (string) $codigo : 'HTTP '.$respuesta->status());
    }
}
