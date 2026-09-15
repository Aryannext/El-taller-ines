<?php

namespace Tests\Feature\Avisos;

use App\Aplicacion\Avisos\EnviarAviso;
use App\Aplicacion\Pagos\RegistrarPago;
use App\Dominio\Avisos\CanalDeAviso;
use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\MetodoPago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Soporte\CanalDeAvisoFalso;
use Tests\TestCase;

/**
 * HU-28 · El envío del aviso en la cola (RN-39 a RN-42, RNF-17). La #0042 de Marta quedó lista hoy, martes 15 de septiembre,
 * a las 4:00 p. m., con saldo de $21.000; el trabajador la procesa a las 4:01 p. m. Los datos se crean antes de iniciar sesión.
 */
class EnviarAvisoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private MetodoPago $efectivo;

    private Orden $orden42;

    private Prenda $mangas;

    private Aviso $aviso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón', 'celular' => '3104567890']);
        $camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);
        $this->efectivo = MetodoPago::factory()->create([...$negocio, 'nombre' => 'Efectivo']);

        $this->orden42 = Orden::factory()->create(['cliente_id' => $marta->id, 'numero' => 42, 'recibida_en' => '2026-09-07 09:15:00', 'lista_en' => '2026-09-15 16:00:00']);
        $prenda = ['orden_id' => $this->orden42->id, 'tipo_prenda_id' => $camisa->id, 'estado' => 'terminada'];
        Prenda::factory()->create([...$prenda, 'descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => 15000]);
        Prenda::factory()->create([...$prenda, 'descripcion_arreglo' => 'Entallar los costados', 'precio' => 8000]);
        $this->mangas = Prenda::factory()->create([...$prenda, 'descripcion_arreglo' => 'Entallar y acortar mangas', 'precio' => 8000]);
        Pago::factory()->create(['orden_id' => $this->orden42->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => 10000, 'pagado_en' => '2026-09-07 09:20:00']);
        $this->aviso = $this->avisoEnCola('2026-09-15 16:00:00');

        $this->fijarReloj('2026-09-15 16:01:00');
        $this->actingAs($this->duena);
    }

    public function test_ca_28_3_datos_del_momento(): void
    {
        $canal = $this->usarCanal(CanalDeAvisoFalso::queAcepta());
        EnviarAviso::dispatch($this->aviso->id);

        // Antes de que salga, Marta abona $10.000
        app(RegistrarPago::class)->ejecutar($this->orden42, 10000, $this->efectivo->id, (string) Str::uuid());
        $this->procesarLaCola();

        $this->assertCount(1, $canal->enviados);
        $this->assertStringContainsString('Saldo pendiente: $11.000.', $canal->enviados[0]['texto']);
        $this->assertSame($canal->enviados[0]['texto'], $this->aviso->fresh()->mensaje);
    }

    public function test_ca_28_4_reintentos_sin_duplicar(): void
    {
        $canal = $this->usarCanal(CanalDeAvisoFalso::queFalla(2));
        EnviarAviso::dispatch($this->aviso->id);

        $this->procesarLaCola();
        $this->assertSame([1, 'en_cola', 1], [$canal->llamadas, $this->aviso->fresh()->estado, $this->aviso->fresh()->intentos]);

        // Espera creciente: 30 segundos antes del segundo intento y 2 minutos antes del tercero (RNF-17)
        $this->travel(29)->seconds();
        $this->procesarLaCola();
        $this->assertSame(1, $canal->llamadas);
        $this->travel(2)->seconds();
        $this->procesarLaCola();
        $this->assertSame(2, $canal->llamadas);
        $this->travel(121)->seconds();
        $this->procesarLaCola();

        $this->assertSame(3, $canal->llamadas);
        $this->assertCount(1, $canal->enviados);
        $aviso = $this->aviso->fresh();
        $this->assertSame(['enviado', 3, 'wamid.falso.1'], [$aviso->estado, $aviso->intentos, $aviso->id_mensaje_whatsapp]);

        // No queda nada en la cola: Marta recibe un solo mensaje
        $this->travel(10)->minutes();
        $this->procesarLaCola();
        $this->assertSame([3, 0, 0], [$canal->llamadas, DB::table('jobs')->count(), DB::table('failed_jobs')->count()]);
    }

    public function test_ca_28_5_falla_persistente(): void
    {
        $canal = $this->usarCanal(CanalDeAvisoFalso::queSiempreFalla());
        EnviarAviso::dispatch($this->aviso->id);

        $this->procesarLaCola();
        $this->travel(31)->seconds();
        $this->procesarLaCola();
        $this->travel(121)->seconds();
        $this->procesarLaCola();

        $aviso = $this->aviso->fresh();
        $this->assertSame([3, 'pendiente_asistido', 3, null, null], [$canal->llamadas, $aviso->estado, $aviso->intentos, $aviso->canal, $aviso->mensaje]);
        $this->assertSame([0, 1], [DB::table('jobs')->count(), DB::table('failed_jobs')->count()]);
    }

    public function test_rn_39_no_se_avisa_una_orden_que_ya_no_esta_lista(): void
    {
        $canal = $this->usarCanal(CanalDeAvisoFalso::queAcepta());
        EnviarAviso::dispatch($this->aviso->id);

        // Antes de que salga, Marta se mide una camisa y vuelve a En proceso
        $this->mangas->update(['estado' => 'en_proceso']);
        $this->orden42->update(['lista_en' => null]);
        $this->procesarLaCola();

        $this->assertSame(0, $canal->llamadas);
        $this->assertSame(['descartado', '2026-09-15 16:01'], [$this->aviso->fresh()->estado, $this->aviso->fresh()->resuelto_en?->format('Y-m-d H:i')]);

        // Si la orden volvió a quedar lista, el aviso de una vez anterior tampoco sale: sale el de la nueva vez
        $this->mangas->update(['estado' => 'terminada']);
        $this->orden42->update(['lista_en' => '2026-09-15 16:30:00']);
        $anterior = $this->avisoEnCola('2026-09-15 16:10:00');
        EnviarAviso::dispatch($anterior->id);
        $this->procesarLaCola();

        $this->assertSame([0, 'descartado'], [$canal->llamadas, $anterior->fresh()->estado]);
    }

    public function test_rn_40_canal_del_aviso(): void
    {
        // Sin la API configurada queda para el envío asistido, sin intentos ni mensaje: se arma al abrir WhatsApp (RN-42)
        $sinApi = $this->usarCanal(CanalDeAvisoFalso::sinConfigurar());
        EnviarAviso::dispatch($this->aviso->id);
        $this->procesarLaCola();

        $aviso = $this->aviso->fresh();
        $this->assertSame([0, 'pendiente_asistido', 0, null, null], [$sinApi->llamadas, $aviso->estado, $aviso->intentos, $aviso->canal, $aviso->mensaje]);

        // Con la API configurada, un rechazo que no se arregla reintentando, como un número sin WhatsApp, no se reintenta
        // Con fresh(): el modelo de setUp todavía dice en_cola y update() no vería el cambio
        $this->aviso->fresh()->update(['estado' => 'en_cola']);
        $rechaza = $this->usarCanal(CanalDeAvisoFalso::queRechaza('131026'));
        EnviarAviso::dispatch($this->aviso->id);
        $this->procesarLaCola();
        $this->travel(5)->minutes();
        $this->procesarLaCola();

        $aviso = $this->aviso->fresh();
        $this->assertSame([1, 'pendiente_asistido', 1], [$rechaza->llamadas, $aviso->estado, $aviso->intentos]);
        $this->assertSame([0, 0], [DB::table('jobs')->count(), DB::table('failed_jobs')->count()]);
    }

    public function test_rn_41_constancia_de_cada_aviso(): void
    {
        $this->usarCanal(CanalDeAvisoFalso::queAcepta());
        EnviarAviso::dispatch($this->aviso->id);
        $this->procesarLaCola();

        $aviso = $this->aviso->fresh();
        $this->assertSame(
            ['2026-09-15 16:00', 'api_oficial', 'Hola Marta, tu orden #0042 del taller está lista para recoger. Prendas listas: 3. Saldo pendiente: $21.000. Te esperamos.', 'enviado', '2026-09-15 16:01'],
            [$aviso->generado_en?->format('Y-m-d H:i'), $aviso->canal, $aviso->mensaje, $aviso->estado, $aviso->resuelto_en?->format('Y-m-d H:i')],
        );
        $this->get(route('ordenes.detalle', $this->orden42))
            ->assertSeeInOrder(['Avisos al cliente', '15 sep 2026 · 4:01 p. m.', 'API oficial', 'Hola Marta, tu orden #0042', 'Enviado']);
    }

    private function avisoEnCola(string $cicloListaEn): Aviso
    {
        return Aviso::factory()->create([
            'orden_id' => $this->orden42->id,
            'ciclo_lista_en' => $cicloListaEn,
            'estado' => 'en_cola',
            'canal' => null,
            'mensaje' => null,
            'generado_en' => $cicloListaEn,
            'resuelto_en' => null,
        ]);
    }

    private function usarCanal(CanalDeAvisoFalso $canal): CanalDeAvisoFalso
    {
        $this->app->instance(CanalDeAviso::class, $canal);

        return $canal;
    }

    private function procesarLaCola(): void
    {
        Artisan::call('queue:work', ['--queue' => 'avisos', '--stop-when-empty' => true, '--sleep' => 0, '--memory' => 1024]);
    }
}
