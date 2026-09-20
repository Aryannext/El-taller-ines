<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\PanelDelDia;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-32 · Lo que necesita atención al entrar (PT-02). Hoy es 16 de septiembre de 2026 y el taller tiene
 * $33.000 por cobrar, 2 órdenes atrasadas, 1 sin reclamar con 2 prendas y 1 aviso pendiente (CA-32.1).
 */
class PanelDelDiaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    /** @var array<string, int> */
    private array $negocio;

    private TipoPrenda $camisa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $this->negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->camisa = TipoPrenda::factory()->create([...$this->negocio, 'nombre' => 'Camisa']);

        $this->fijarReloj('2026-09-16 09:00:00');
        $this->actingAs($this->duena);
    }

    public function test_ca_32_1_cifras_del_dia(): void
    {
        $this->prepararElDia();

        $panel = app(PanelDelDia::class)->obtener($this->duena->negocio);
        $this->assertSame(33000, $panel['porCobrar']->valor());
        $this->assertSame(2, $panel['atrasadas']);
        $this->assertSame(1, $panel['ordenesSinReclamar']);
        $this->assertSame(2, $panel['prendasSinReclamar']);
        $this->assertSame(1, $panel['avisosPorEnviar']);

        $this->get(route('panel'))
            ->assertOk()
            ->assertSeeInOrder([
                'Por cobrar', '$33.000',
                'Atrasadas', '>2<',
                'Sin reclamar', '>1<', '2 prendas · más de 30 días',
                'Avisos por enviar', '>1<',
            ], false);
    }

    public function test_ca_32_2_ir_al_detalle(): void
    {
        $this->prepararElDia();

        // Las cifras llevan a su lista
        $panel = $this->get(route('panel'))->assertOk();
        $panel->assertSee(route('seguimiento.atrasadas'));
        $panel->assertSee(route('seguimiento.sin-reclamar'));
        $panel->assertSee(route('avisos.pendientes'));

        $this->get(route('seguimiento.atrasadas'))->assertOk()->assertSee('#0042')->assertSee('#0044');
        $this->get(route('seguimiento.sin-reclamar'))->assertOk()->assertSee('#0030');
    }

    public function test_ca_32_3_todo_al_dia(): void
    {
        // Sin nada pendiente las cuatro cifras quedan en cero, no desaparecen
        $panel = app(PanelDelDia::class)->obtener($this->duena->negocio);
        $this->assertSame(0, $panel['porCobrar']->valor());
        $this->assertSame([0, 0, 0, 0], [$panel['atrasadas'], $panel['ordenesSinReclamar'], $panel['prendasSinReclamar'], $panel['avisosPorEnviar']]);

        $this->get(route('panel'))
            ->assertOk()
            ->assertSee('Por cobrar')
            ->assertSee('$0')
            ->assertSee('Atrasadas')
            ->assertSee('Sin reclamar')
            ->assertSee('Avisos por enviar');
    }

    /**
     * RN-32 se calcula sumando prendas y pagos por separado, no orden por orden. Este es el control
     * de que ese atajo da el mismo total que CalculadoraDeSaldo, que es donde vive la regla.
     */
    public function test_rn_32_el_total_por_cobrar_coincide_con_el_dominio(): void
    {
        $this->prepararElDia();

        $calculadora = app(CalculadoraDeSaldo::class);
        $saldos = Orden::with(['prendas', 'pagos'])->get()->map(function (Orden $orden) use ($calculadora) {
            $valor = $calculadora->valor($orden->prendas->map(fn (Prenda $prenda) => [$prenda->precio, $prenda->estado]));

            return [
                $calculadora->saldo($valor, $orden->pagos->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null])),
                $orden->cancelada_en !== null,
            ];
        });

        $this->assertSame(
            $calculadora->porCobrar($saldos)->valor(),
            app(PanelDelDia::class)->obtener($this->duena->negocio)['porCobrar']->valor(),
        );
    }

    public function test_rnf_22_el_panel_solo_cuenta_lo_del_negocio_de_la_sesion(): void
    {
        $this->prepararElDia();

        $otra = Usuario::factory()->create();
        $this->actingAs($otra);

        $panel = app(PanelDelDia::class)->obtener($otra->negocio);
        $this->assertSame(0, $panel['porCobrar']->valor());
        $this->assertSame(0, $panel['atrasadas']);
        $this->assertSame(0, $panel['ordenesSinReclamar']);
    }

    /**
     * El día del criterio: #0042 y #0044 atrasadas, #0040 entregada con saldo, #0041 cancelada,
     * y la #0030 lista desde el 1 de agosto con su aviso sin enviar.
     */
    private function prepararElDia(): void
    {
        // Atrasada y por cobrar: $21.000
        $this->orden(42, 'Sandra Ruiz', '2026-09-15', 'en_proceso', 21000);

        // Atrasada pero ya pagada: no suma al por cobrar
        $cuarentaYCuatro = $this->orden(44, 'Luis Pardo', '2026-09-12', 'en_proceso', 5000);
        Pago::factory()->create(['orden_id' => $cuarentaYCuatro->id, 'valor' => 5000, 'pagado_en' => '2026-09-10 10:00:00']);

        // Entregada con saldo: RN-32 la cuenta igual
        $this->orden(40, 'Marta Rincón', '2026-09-05', 'entregada', 12000);

        // Cancelada: no se cobra (RN-32)
        $cancelada = $this->orden(41, 'Pedro Gil', '2026-09-08', 'en_proceso', 8000);
        $cancelada->update(['cancelada_en' => '2026-09-09 08:00:00']);

        // Sin reclamar desde el 1 de agosto, con 2 prendas y su aviso pendiente, ya pagada
        $treinta = Orden::factory()->create([
            'cliente_id' => Cliente::factory()->create([...$this->negocio, 'nombre' => 'Carmen Díaz'])->id,
            'numero' => 30,
            'fecha_entrega_acordada' => '2026-07-30',
            'recibida_en' => '2026-07-23 09:00:00',
            'lista_en' => '2026-08-01 10:00:00',
        ]);
        Prenda::factory()->count(2)->create(['orden_id' => $treinta->id, 'tipo_prenda_id' => $this->camisa->id, 'estado' => 'terminada', 'precio' => 8000]);
        Pago::factory()->create(['orden_id' => $treinta->id, 'valor' => 16000, 'pagado_en' => '2026-08-01 10:30:00']);
        Aviso::factory()->create([
            'orden_id' => $treinta->id,
            'ciclo_lista_en' => '2026-08-01 10:00:00',
            'estado' => 'pendiente_asistido',
            'canal' => null,
            'mensaje' => null,
            'generado_en' => '2026-08-01 10:00:05',
            'resuelto_en' => null,
        ]);
    }

    private function orden(int $numero, string $cliente, string $entrega, string $estadoDePrenda, int $precio): Orden
    {
        $orden = Orden::factory()->create([
            'cliente_id' => Cliente::factory()->create([...$this->negocio, 'nombre' => $cliente])->id,
            'numero' => $numero,
            'fecha_entrega_acordada' => $entrega,
            // La orden se recibió una semana antes: la entrega acordada nunca es anterior a la recepción
            'recibida_en' => date('Y-m-d', strtotime($entrega.' -7 days')).' 09:00:00',
        ]);

        Prenda::factory()->create([
            'orden_id' => $orden->id,
            'tipo_prenda_id' => $this->camisa->id,
            'estado' => $estadoDePrenda,
            'precio' => $precio,
            'entregada_en' => $estadoDePrenda === 'entregada' ? '2026-09-05 11:00:00' : null,
        ]);

        return $orden;
    }
}
