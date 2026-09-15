<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Ordenes\CambiarEstadoDePrenda;
use App\Aplicacion\Ordenes\SincronizarEstadoDeOrden;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\OrdenQuedoLista;
use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

/**
 * RN-22 · Fecha en que la orden quedó lista. Los datos se crean antes de iniciar sesión.
 */
class SincronizarEstadoDeOrdenTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Orden $orden;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $marta = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Marta Rincón']);
        $this->orden = Orden::factory()->create(['cliente_id' => $marta->id, 'numero' => 42, 'recibida_en' => '2026-09-07 09:15:00']);
    }

    public function test_rn_22_fecha_en_que_la_orden_quedo_lista(): void
    {
        Event::fake([OrdenQuedoLista::class]);
        $pantalon = Prenda::factory()->create(['orden_id' => $this->orden->id, 'estado' => 'terminada']);
        $camisa = Prenda::factory()->create(['orden_id' => $this->orden->id, 'estado' => 'en_proceso']);
        $this->actingAs($this->duena);

        // La orden queda lista el 10 de septiembre a las 4:00 p. m.
        $this->cambiarEl('2026-09-10 16:00:00', $camisa, EstadoDePrenda::Terminada);
        $this->assertSame('2026-09-10 16:00', $this->orden->fresh()->lista_en?->format('Y-m-d H:i'));

        // El 12 una prenda vuelve a En proceso: la fecha se borra
        $this->cambiarEl('2026-09-12 09:00:00', $pantalon, EstadoDePrenda::EnProceso);
        $this->assertNull($this->orden->fresh()->lista_en);

        // El 13 vuelve a quedar lista: la nueva fecha es el 13
        $this->cambiarEl('2026-09-13 11:30:00', $pantalon, EstadoDePrenda::Terminada);
        $this->assertSame('2026-09-13 11:30', $this->orden->fresh()->lista_en?->format('Y-m-d H:i'));
        Event::assertDispatchedTimes(OrdenQuedoLista::class, 2);

        // Si se entrega, se conserva
        $this->orden->prendas()->update(['estado' => 'entregada', 'entregada_en' => '2026-09-14 10:00:00']);
        $this->fijarReloj('2026-09-14 10:00:00');
        $this->assertSame(EstadoDeOrden::Entregada, app(SincronizarEstadoDeOrden::class)->sincronizar($this->orden->fresh()));
        $this->assertSame('2026-09-13 11:30', $this->orden->fresh()->lista_en?->format('Y-m-d H:i'));
        Event::assertDispatchedTimes(OrdenQuedoLista::class, 2);
    }

    public function test_rn_37_al_quedar_lista_la_orden_se_genera_su_aviso(): void
    {
        Prenda::factory()->create(['orden_id' => $this->orden->id, 'estado' => 'terminada']);
        $pantalon = Prenda::factory()->create(['orden_id' => $this->orden->id, 'estado' => 'en_proceso']);
        $this->actingAs($this->duena);

        // Se marca Terminado el último pantalón: sin ninguna otra acción, el aviso a Marta queda en la cola
        $this->cambiarEl('2026-09-14 16:00:00', $pantalon, EstadoDePrenda::Terminada);

        $aviso = Aviso::sole();
        $this->assertSame(
            [$this->orden->id, 'en_cola', '2026-09-14 16:00', '2026-09-14 16:00'],
            [(int) $aviso->orden_id, $aviso->estado, $aviso->ciclo_lista_en->format('Y-m-d H:i'), $aviso->generado_en?->format('Y-m-d H:i')],
        );
        $this->assertSame(1, DB::table('jobs')->where('queue', 'avisos')->count());
    }

    public function test_el_evento_de_orden_lista_solo_sale_si_el_cambio_se_confirma(): void
    {
        Event::fake([OrdenQuedoLista::class]);
        Prenda::factory()->create(['orden_id' => $this->orden->id, 'estado' => 'terminada']);
        $this->actingAs($this->duena);
        $this->fijarReloj('2026-09-14 16:00:00');

        try {
            DB::transaction(function (): void {
                app(SincronizarEstadoDeOrden::class)->sincronizar($this->orden);
                throw new RuntimeException('Falla después de sincronizar');
            });
        } catch (RuntimeException) {
        }

        $this->assertNull($this->orden->fresh()->lista_en);
        Event::assertNotDispatched(OrdenQuedoLista::class);
    }

    private function cambiarEl(string $momento, Prenda $prenda, EstadoDePrenda $hacia): void
    {
        $this->fijarReloj($momento);
        // Se resuelve de nuevo para que tome el reloj de ese momento
        app(CambiarEstadoDePrenda::class)->ejecutar($prenda, $hacia);
    }
}
