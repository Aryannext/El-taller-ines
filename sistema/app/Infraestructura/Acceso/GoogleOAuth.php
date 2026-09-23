<?php

declare(strict_types=1);

namespace App\Infraestructura\Acceso;

use App\Dominio\Acceso\IdentidadDeGoogle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * El intercambio con Google, por el flujo de código de autorización de OpenID Connect.
 *
 * El código que vuelve en la dirección no prueba nada por sí solo: se cambia por un identificador firmado
 * hablando directamente con Google, servidor a servidor y sobre HTTPS, con el secreto que solo tiene el sistema.
 * Ese identificador es el que dice quién entró. Solo se piden el correo y el nombre: ningún otro dato (RNF-26).
 */
final class GoogleOAuth implements IdentidadDeGoogle
{
    private const AUTORIZACION = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const INTERCAMBIO = 'https://oauth2.googleapis.com/token';

    private const EMISORES = ['accounts.google.com', 'https://accounts.google.com'];

    public function __construct(
        private readonly ?string $identificador,
        private readonly ?string $secreto,
        private readonly string $direccionDeVuelta,
    ) {}

    public function estaConfigurada(): bool
    {
        return $this->identificador !== null && $this->identificador !== ''
            && $this->secreto !== null && $this->secreto !== '';
    }

    public function direccionDeAutorizacion(string $estado): string
    {
        return self::AUTORIZACION.'?'.http_build_query([
            'client_id' => $this->identificador,
            'redirect_uri' => $this->direccionDeVuelta,
            'response_type' => 'code',
            'scope' => 'openid email',
            'state' => $estado,
            // La usuaria elige la cuenta cada vez: el celular puede tener varias, o ser prestado
            'prompt' => 'select_account',
        ]);
    }

    public function correoVerificado(string $codigo): ?string
    {
        $respuesta = Http::asForm()->timeout(10)->post(self::INTERCAMBIO, [
            'code' => $codigo,
            'client_id' => $this->identificador,
            'client_secret' => $this->secreto,
            'redirect_uri' => $this->direccionDeVuelta,
            'grant_type' => 'authorization_code',
        ]);

        if ($respuesta->failed()) {
            Log::warning('Google no aceptó el código de acceso.', ['estado' => $respuesta->status()]);

            return null;
        }

        $identidad = $this->contenidoDelIdentificador((string) $respuesta->json('id_token', ''));
        if ($identidad === null) {
            return null;
        }

        // Que el identificador sea para este sistema y no para otro, que lo firme Google y que no esté vencido
        $paraEsteSistema = ($identidad['aud'] ?? null) === $this->identificador;
        $loFirmoGoogle = in_array($identidad['iss'] ?? '', self::EMISORES, true);
        $vigente = (int) ($identidad['exp'] ?? 0) > time();
        $correoVerificado = ($identidad['email_verified'] ?? false) === true || ($identidad['email_verified'] ?? '') === 'true';
        $correo = is_string($identidad['email'] ?? null) ? $identidad['email'] : null;

        if (! $paraEsteSistema || ! $loFirmoGoogle || ! $vigente || ! $correoVerificado || $correo === null) {
            Log::warning('Google devolvió una identidad que no se puede aceptar.');

            return null;
        }

        return mb_strtolower(trim($correo));
    }

    /**
     * El contenido del identificador firmado. No se comprueba la firma porque no hizo falta confiar en el
     * mensajero: el identificador vino de Google en la respuesta a una petición nuestra, autenticada y por HTTPS.
     *
     * @return array<string, mixed>|null
     */
    private function contenidoDelIdentificador(string $identificador): ?array
    {
        $partes = explode('.', $identificador);
        if (count($partes) !== 3) {
            return null;
        }

        $json = base64_decode(strtr($partes[1], '-_', '+/'), true);
        if ($json === false) {
            return null;
        }

        $contenido = json_decode($json, true);

        return is_array($contenido) ? $contenido : null;
    }
}
