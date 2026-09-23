<?php

namespace Tests\Feature\Infraestructura;

use App\Dominio\Avisos\MensajeDeAviso;
use App\Dominio\Clientes\Celular;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\Dinero;
use App\Infraestructura\Avisos\WhatsAppCloudApiCanal;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * RNF-06 · El aviso sale por la API oficial de WhatsApp, con respuestas simuladas: nunca se llama a Meta desde las pruebas.
 * La versión v99.0 y el identificador son de prueba; los reales viven en .env (RNF-24).
 */
class WhatsAppCloudApiCanalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_rnf_06_solo_la_api_oficial_envia_el_aviso(): void
    {
        Http::fake(['https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.HBgM']]])]);

        $resultado = $this->canal()->enviar(Celular::desde('3104567890'), $this->mensaje());

        $this->assertSame([true, 'api_oficial', 'wamid.HBgM', null], [$resultado->aceptado, $resultado->canal, $resultado->idMensaje, $resultado->error]);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $solicitud) => $solicitud->url() === 'https://graph.facebook.com/v99.0/1234567890/messages'
            && $solicitud->hasHeader('Authorization', 'Bearer token-de-prueba')
            && $solicitud->data() === [
                'messaging_product' => 'whatsapp',
                'to' => '573104567890',
                'type' => 'template',
                'template' => [
                    'name' => 'orden_lista',
                    'language' => ['code' => 'es'],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => 'Marta'],
                            ['type' => 'text', 'text' => 'Modistería Inés'],
                            ['type' => 'text', 'text' => '#0042'],
                            ['type' => 'text', 'text' => 'Son 3 prendas, con un saldo de $21.000.'],
                        ],
                    ]],
                ],
            ]);

        // Ninguna otra clase del sistema conoce la dirección de la API
        $conocenLaApi = collect(File::allFiles(app_path()))
            ->filter(fn ($archivo) => str_contains($archivo->getContents(), 'graph.facebook.com'))
            ->map(fn ($archivo) => str_replace('\\', '/', $archivo->getRelativePathname()))
            ->values()
            ->all();
        $this->assertSame(['Infraestructura/Avisos/WhatsAppCloudApiCanal.php'], $conocenLaApi);
    }

    public function test_un_error_temporal_lanza_una_excepcion_para_que_la_cola_reintente(): void
    {
        Http::fakeSequence('https://graph.facebook.com/*')->push([], 500)->push([], 503)->push([], 429);

        foreach ([500, 503, 429] as $codigo) {
            try {
                $this->canal()->enviar(Celular::desde('3104567890'), $this->mensaje());
                $this->fail("Con {$codigo} se esperaba una excepción.");
            } catch (RuntimeException $error) {
                $this->assertSame("WhatsApp respondió {$codigo}; se reintentará.", $error->getMessage());
                $this->assertStringNotContainsString('token-de-prueba', $error->getMessage());
            }
        }
    }

    public function test_sin_conexion_lanza_una_excepcion_para_que_la_cola_reintente(): void
    {
        Http::fakeSequence('https://graph.facebook.com/*')->pushFailedConnection();

        $this->expectException(ConnectionException::class);
        $this->canal()->enviar(Celular::desde('3104567890'), $this->mensaje());
    }

    public function test_un_error_que_no_se_arregla_reintentando_se_rechaza(): void
    {
        Http::fakeSequence('https://graph.facebook.com/*')
            ->push(['error' => ['code' => 131026, 'message' => 'Message undeliverable']], 400)
            ->push('', 401);

        $numeroSinWhatsApp = $this->canal()->enviar(Celular::desde('3104567890'), $this->mensaje());
        $tokenVencido = $this->canal()->enviar(Celular::desde('3104567890'), $this->mensaje());

        $this->assertSame([false, null, '131026'], [$numeroSinWhatsApp->aceptado, $numeroSinWhatsApp->idMensaje, $numeroSinWhatsApp->error]);
        $this->assertSame([false, 'HTTP 401'], [$tokenVencido->aceptado, $tokenVencido->error]);
    }

    public function test_sin_token_no_esta_disponible(): void
    {
        $this->assertTrue($this->canal()->estaDisponible());
        $this->assertFalse($this->canal(token: null)->estaDisponible());
        $this->assertFalse($this->canal(token: '')->estaDisponible());
        $this->assertFalse((new WhatsAppCloudApiCanal('token-de-prueba', '', 'v99.0', 'orden_lista', 'es'))->estaDisponible());
        $this->assertFalse((new WhatsAppCloudApiCanal('token-de-prueba', '1234567890', null, 'orden_lista', 'es'))->estaDisponible());
        Http::assertNothingSent();
    }

    private function canal(?string $token = 'token-de-prueba'): WhatsAppCloudApiCanal
    {
        return new WhatsAppCloudApiCanal($token, '1234567890', 'v99.0', 'orden_lista', 'es');
    }

    private function mensaje(): MensajeDeAviso
    {
        return MensajeDeAviso::construir('Marta Rincón', 'Modistería Inés', NumeroDeOrden::desde(42), 3, Dinero::pesos(21000));
    }
}
