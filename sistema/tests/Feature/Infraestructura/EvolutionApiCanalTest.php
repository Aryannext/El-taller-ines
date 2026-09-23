<?php

namespace Tests\Feature\Infraestructura;

use App\Dominio\Avisos\CanalDeAviso;
use App\Dominio\Avisos\MensajeDeAviso;
use App\Dominio\Clientes\Celular;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\Dinero;
use App\Infraestructura\Avisos\EvolutionApiCanal;
use App\Infraestructura\Avisos\WhatsAppCloudApiCanal;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * RNF-06 y ADR-007 · El aviso automático por Evolution API, con respuestas simuladas: las pruebas nunca llaman al servidor real.
 */
class EvolutionApiCanalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_rnf_06_solo_el_adaptador_conoce_evolution_api(): void
    {
        Http::fake(['http://evolution:8080/*' => Http::response(['key' => ['id' => '3EB0A1B2C3'], 'status' => 'PENDING'], 201)]);

        $resultado = $this->canal()->enviar(Celular::desde('3104567890'), $this->mensaje());

        $this->assertSame([true, 'evolution_api', '3EB0A1B2C3', null], [$resultado->aceptado, $resultado->canal, $resultado->idMensaje, $resultado->error]);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $solicitud) => $solicitud->url() === 'http://evolution:8080/message/sendText/taller'
            && $solicitud->hasHeader('apikey', 'clave-de-prueba')
            && $solicitud->data() === [
                'number' => '573104567890',
                'text' => 'Hola Marta, le escribimos de Modistería Inés. Su orden #0042 ya está lista 🧵 Son 3 prendas, con un saldo de $21.000. La esperamos cuando pueda pasar.',
            ]);

        // Ninguna otra clase del sistema conoce el punto de envío de Evolution API
        $conocen = collect(File::allFiles(app_path()))
            ->filter(fn ($archivo) => str_contains($archivo->getContents(), '/message/sendText/'))
            ->map(fn ($archivo) => str_replace('\\', '/', $archivo->getRelativePathname()))
            ->values()
            ->all();
        $this->assertSame(['Infraestructura/Avisos/EvolutionApiCanal.php'], $conocen);
    }

    public function test_un_error_temporal_lanza_una_excepcion_para_que_la_cola_reintente(): void
    {
        Http::fakeSequence('http://evolution:8080/*')->push([], 500)->push([], 429);

        foreach ([500, 429] as $codigo) {
            try {
                $this->canal()->enviar(Celular::desde('3104567890'), $this->mensaje());
                $this->fail("Con {$codigo} se esperaba una excepción.");
            } catch (RuntimeException $error) {
                $this->assertSame("Evolution API respondió {$codigo}; se reintentará.", $error->getMessage());
            }
        }
    }

    public function test_sin_conexion_lanza_una_excepcion_para_que_la_cola_reintente(): void
    {
        Http::fakeSequence('http://evolution:8080/*')->pushFailedConnection();

        $this->expectException(ConnectionException::class);
        $this->canal()->enviar(Celular::desde('3104567890'), $this->mensaje());
    }

    public function test_un_numero_sin_whatsapp_se_rechaza_sin_registrar_el_celular(): void
    {
        Http::fake(['http://evolution:8080/*' => Http::response(['status' => 400, 'response' => ['message' => [['exists' => false, 'number' => '573104567890']]]], 400)]);

        $resultado = $this->canal()->enviar(Celular::desde('3104567890'), $this->mensaje());

        $this->assertSame([false, null, 'HTTP 400'], [$resultado->aceptado, $resultado->idMensaje, $resultado->error]);
        $this->assertStringNotContainsString('3104567890', (string) $resultado->error);
    }

    public function test_se_usa_evolution_api_solo_si_esta_configurada(): void
    {
        $this->assertFalse((new EvolutionApiCanal('', 'clave-de-prueba', 'taller'))->estaDisponible());
        $this->assertFalse((new EvolutionApiCanal('http://evolution:8080', null, 'taller'))->estaDisponible());
        $this->assertFalse((new EvolutionApiCanal('http://evolution:8080', 'clave-de-prueba', ''))->estaDisponible());

        // Sin Evolution API, el sistema usa la API oficial
        $this->assertInstanceOf(WhatsAppCloudApiCanal::class, app(CanalDeAviso::class));

        config(['services.evolution.url' => 'http://evolution:8080', 'services.evolution.clave_api' => 'clave-de-prueba', 'services.evolution.instancia' => 'taller']);
        $this->assertInstanceOf(EvolutionApiCanal::class, app(CanalDeAviso::class));
        Http::assertNothingSent();
    }

    private function canal(): EvolutionApiCanal
    {
        return new EvolutionApiCanal('http://evolution:8080', 'clave-de-prueba', 'taller');
    }

    private function mensaje(): MensajeDeAviso
    {
        return MensajeDeAviso::construir('Marta Rincón', 'Modistería Inés', NumeroDeOrden::desde(42), 3, Dinero::pesos(21000));
    }
}
