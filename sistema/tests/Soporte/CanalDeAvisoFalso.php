<?php

namespace Tests\Soporte;

use App\Dominio\Avisos\CanalDeAviso;
use App\Dominio\Avisos\MensajeDeAviso;
use App\Dominio\Avisos\ResultadoDeEnvio;
use App\Dominio\Clientes\Celular;
use RuntimeException;

/**
 * Doble de prueba de WhatsAppCloudApiCanal (plan de pruebas): anota los mensajes, tarda, falla N veces y luego acepta, siempre falla
 * o rechaza. Devuelve el mismo ResultadoDeEnvio que el canal real.
 */
final class CanalDeAvisoFalso implements CanalDeAviso
{
    /** @var list<array{destino: string, texto: string}> */
    public array $enviados = [];

    public int $llamadas = 0;

    private function __construct(
        private readonly bool $disponible = true,
        private readonly int $fallasAntesDeAceptar = 0,
        private readonly int $segundosDeEspera = 0,
        private readonly ?string $rechazo = null,
    ) {}

    public static function queAcepta(): self
    {
        return new self;
    }

    public static function sinConfigurar(): self
    {
        return new self(disponible: false);
    }

    public static function queFalla(int $veces): self
    {
        return new self(fallasAntesDeAceptar: $veces);
    }

    public static function queSiempreFalla(): self
    {
        return new self(fallasAntesDeAceptar: PHP_INT_MAX);
    }

    public static function queTarda(int $segundos): self
    {
        return new self(segundosDeEspera: $segundos);
    }

    public static function queRechaza(string $error): self
    {
        return new self(rechazo: $error);
    }

    public function estaDisponible(): bool
    {
        return $this->disponible;
    }

    public function enviar(Celular $destino, MensajeDeAviso $mensaje): ResultadoDeEnvio
    {
        $this->llamadas++;
        sleep($this->segundosDeEspera);

        if ($this->llamadas <= $this->fallasAntesDeAceptar) {
            throw new RuntimeException('WhatsApp respondió 503; se reintentará.');
        }
        if ($this->rechazo !== null) {
            return ResultadoDeEnvio::rechazado('api_oficial', $this->rechazo);
        }

        $this->enviados[] = ['destino' => $destino->enFormatoInternacional(), 'texto' => $mensaje->texto()];

        return ResultadoDeEnvio::aceptado('api_oficial', 'wamid.falso.'.count($this->enviados));
    }
}
