<?php

namespace Tests\Feature\Avisos;

use App\Aplicacion\Avisos\EnviarAviso;
use App\Aplicacion\Avisos\GenerarAviso;
use App\Dominio\Avisos\CanalDeAviso;
use App\Dominio\Ordenes\OrdenQuedoLista;
use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\Soporte\CanalDeAvisoFalso;
use Tests\TestCase;

/**
 * HU-28 · Recibir un aviso cuando mi ropa está lista. Hoy es el martes 15 de septiembre de 2026 a las 4:00 p. m.
 * La cola es la de base de datos, como en el VPS (phpunit.xml): el envío lo hace el trabajador, no la solicitud.
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class GenerarAvisoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Orden $orden42;

    private Prenda $mangas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón', 'celular' => '3104567890']);
        $pantalon = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Pantalón']);
        $camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);

        // A la #0042 le falta una camisa; vale $31.000 y tiene un abono de $10.000
        $this->orden42 = Orden::factory()->create(['cliente_id' => $marta->id, 'numero' => 42, 'recibida_en' => '2026-09-07 09:15:00']);
        $prenda = ['orden_id' => $this->orden42->id];
        Prenda::factory()->create([...$prenda, 'tipo_prenda_id' => $pantalon->id, 'descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => 15000, 'estado' => 'terminada']);
        Prenda::factory()->create([...$prenda, 'tipo_prenda_id' => $camisa->id, 'descripcion_arreglo' => 'Entallar los costados', 'precio' => 8000, 'estado' => 'terminada']);
        $this->mangas = Prenda::factory()->create([...$prenda, 'tipo_prenda_id' => $camisa->id, 'descripcion_arreglo' => 'Entallar y acortar mangas', 'precio' => 8000, 'estado' => 'en_proceso']);
        Pago::factory()->create(['orden_id' => $this->orden42->id, 'valor' => 10000, 'pagado_en' => '2026-09-07 09:20:00']);
        $this->fijarReloj('2026-09-15 16:00:00');
    }

    public function test_ca_28_1_sale_solo(): void
    {
        $canal = $this->usarCanal(CanalDeAvisoFalso::queAcepta());
        $this->actingAs($this->duena);

        $this->marcarTerminadaLaUltimaPrenda()->assertRedirect(route('ordenes.detalle', $this->orden42));
        // Sin ninguna otra acción: el trabajador de la cola lo envía
        $this->procesarLaCola();

        $this->assertSame([[
            'destino' => '573104567890',
            'texto' => 'Hola Marta, tu orden #0042 del taller está lista para recoger. Prendas listas: 3. Saldo pendiente: $21.000. Te esperamos.',
        ]], $canal->enviados);
        $aviso = Aviso::sole();
        $this->assertSame(
            ['enviado', 'api_oficial', 'wamid.falso.1', 1, '2026-09-15 16:00'],
            [$aviso->estado, $aviso->canal, $aviso->id_mensaje_whatsapp, $aviso->intentos, $aviso->resuelto_en?->format('Y-m-d H:i')],
        );
        $this->get(route('ordenes.detalle', $this->orden42))
            ->assertSeeInOrder(['Avisos al cliente', '15 sep 2026 · 4:00 p. m.', 'API oficial', 'Hola Marta, tu orden #0042 del taller está lista para recoger.', 'Enviado']);
    }

    public function test_ca_28_2_no_hace_esperar(): void
    {
        $canal = $this->usarCanal(CanalDeAvisoFalso::queTarda(10));
        $this->actingAs($this->duena);
        // La primera solicitud de la prueba carga el sistema; se mide la de marcar la prenda
        $this->get(route('ordenes.detalle', $this->orden42))->assertOk();

        $inicio = hrtime(true);
        $this->marcarTerminadaLaUltimaPrenda()->assertRedirect(route('ordenes.detalle', $this->orden42));
        $milisegundos = (hrtime(true) - $inicio) / 1_000_000;

        $this->assertLessThan(1000, $milisegundos);
        // WhatsApp no se llamó: el aviso espera en la cola de la base de datos
        $this->assertSame(0, $canal->llamadas);
        $this->assertSame('en_cola', Aviso::sole()->estado);
        $this->assertSame(1, DB::table('jobs')->where('queue', 'avisos')->count());
    }

    public function test_rn_38_un_solo_aviso_por_cada_vez_que_la_orden_queda_lista(): void
    {
        Queue::fake();
        $this->actingAs($this->duena);
        $generar = app(GenerarAviso::class);

        // El mismo evento dos veces: un solo aviso y un solo envío
        $generar->handle(new OrdenQuedoLista($this->orden42->id, new DateTimeImmutable('2026-09-15 16:00:00')));
        $generar->handle(new OrdenQuedoLista($this->orden42->id, new DateTimeImmutable('2026-09-15 16:00:00')));
        $this->assertSame(1, Aviso::count());
        Queue::assertPushedTimes(EnviarAviso::class, 1);

        // Volvió a En proceso y quedó lista otra vez: sí se genera un aviso nuevo
        $generar->handle(new OrdenQuedoLista($this->orden42->id, new DateTimeImmutable('2026-09-15 17:30:00')));
        $this->assertSame(
            ['2026-09-15 16:00', '2026-09-15 17:30'],
            Aviso::orderBy('id')->get()->map(fn (Aviso $aviso) => $aviso->ciclo_lista_en->format('Y-m-d H:i'))->all(),
        );
        Queue::assertPushedTimes(EnviarAviso::class, 2);
        Queue::assertPushedOn('avisos', EnviarAviso::class);
    }

    private function usarCanal(CanalDeAvisoFalso $canal): CanalDeAvisoFalso
    {
        $this->app->instance(CanalDeAviso::class, $canal);

        return $canal;
    }

    private function marcarTerminadaLaUltimaPrenda(): TestResponse
    {
        return $this->post(route('prendas.cambiar-estado', [$this->orden42, $this->mangas]), ['estado' => 'terminada']);
    }

    private function procesarLaCola(): void
    {
        Artisan::call('queue:work', ['--queue' => 'avisos', '--stop-when-empty' => true, '--sleep' => 0, '--memory' => 1024]);
    }
}
