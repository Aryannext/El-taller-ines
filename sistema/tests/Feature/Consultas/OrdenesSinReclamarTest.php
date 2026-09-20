<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\OrdenesSinReclamar;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-34 · Las órdenes listas que nadie recoge (PT-21, RN-35, RN-36).
 * Hoy es 16 de septiembre de 2026 y el plazo del negocio son los 30 días por defecto.
 */
class OrdenesSinReclamarTest extends TestCase
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

    public function test_ca_34_1_orden_sin_reclamar(): void
    {
        // La #0030 quedó lista el 1 de agosto con 2 camisas Terminadas y debe $16.000
        $this->orden(30, 'Carmen Díaz', '2026-08-01 10:00:00', prendas: 2, precio: 8000);

        $filas = app(OrdenesSinReclamar::class)->listar($this->duena->negocio);
        $this->assertCount(1, $filas);
        $this->assertSame(46, $filas[0]['diasDeEspera']);
        $this->assertSame(2, $filas[0]['prendas']);
        $this->assertSame(16000, $filas[0]['saldo']->valor());

        $this->get(route('seguimiento.sin-reclamar'))
            ->assertOk()
            ->assertSeeInOrder([
                '46',
                'Carmen Díaz',
                '#0030',
                '2 prendas sin reclamar',
                'lista desde el 1 ago 2026',
                'debe',
                '$16.000',
                'En total: 1 orden y 2 prendas esperan en el taller.',
            ]);

        $this->assertSame(['ordenes' => 1, 'prendas' => 2], app(OrdenesSinReclamar::class)->contar($this->duena->negocio));
    }

    public function test_ca_34_2_dentro_del_plazo(): void
    {
        // La #0042 quedó lista el 1 de septiembre: lleva 15 días, menos que el plazo de 30
        $this->orden(42, 'Marta Rincón', '2026-09-01 10:00:00');

        $this->assertSame([], app(OrdenesSinReclamar::class)->listar($this->duena->negocio));
        $this->get(route('seguimiento.sin-reclamar'))
            ->assertOk()
            ->assertSee('No hay órdenes sin reclamar.')
            ->assertDontSee('Marta Rincón');
    }

    public function test_ca_34_3_mayor_espera_primero(): void
    {
        $this->orden(30, 'Carmen Díaz', '2026-08-01 10:00:00');
        $this->orden(25, 'Luis Pardo', '2026-07-18 10:00:00');

        $filas = app(OrdenesSinReclamar::class)->listar($this->duena->negocio);
        $this->assertSame([60, 46], array_column($filas, 'diasDeEspera'));
        $this->get(route('seguimiento.sin-reclamar'))->assertOk()->assertSeeInOrder(['Luis Pardo', 'Carmen Díaz']);
    }

    public function test_rn_35_justo_en_el_plazo_todavia_no_cuenta(): void
    {
        // Lista hace exactamente 30 días: hace falta pasar el plazo, no alcanzarlo
        $this->orden(31, 'Ana Beltrán', '2026-08-17 10:00:00');

        $this->assertSame([], app(OrdenesSinReclamar::class)->listar($this->duena->negocio));

        // Un día más y sí aparece
        $this->fijarReloj('2026-09-17 09:00:00');
        $this->assertCount(1, app(OrdenesSinReclamar::class)->listar($this->duena->negocio));
    }

    public function test_rn_35_el_plazo_lo_define_el_negocio(): void
    {
        $this->orden(30, 'Carmen Díaz', '2026-08-01 10:00:00');
        $negocio = $this->duena->negocio;

        $negocio->update(['dias_sin_reclamar' => 60]);
        $this->assertSame([], app(OrdenesSinReclamar::class)->listar($negocio->fresh()));
    }

    public function test_una_orden_entregada_deja_de_esperar(): void
    {
        $orden = $this->orden(30, 'Carmen Díaz', '2026-08-01 10:00:00');
        $orden->prendas()->update(['estado' => 'entregada', 'entregada_en' => '2026-09-15 11:00:00']);

        $this->assertSame([], app(OrdenesSinReclamar::class)->listar($this->duena->negocio));
    }

    /**
     * Una orden Lista para entregar con sus prendas Terminadas, sin pagos.
     */
    private function orden(int $numero, string $cliente, string $listaEn, int $prendas = 1, int $precio = 15000): Orden
    {
        $entrega = substr($listaEn, 0, 10);

        $orden = Orden::factory()->create([
            'cliente_id' => Cliente::factory()->create([...$this->negocio, 'nombre' => $cliente])->id,
            'numero' => $numero,
            'fecha_entrega_acordada' => $entrega,
            // La orden se recibió una semana antes de quedar lista
            'recibida_en' => date('Y-m-d', strtotime($entrega.' -7 days')).' 09:00:00',
            'lista_en' => $listaEn,
        ]);

        Prenda::factory()->count($prendas)->create([
            'orden_id' => $orden->id,
            'tipo_prenda_id' => $this->camisa->id,
            'estado' => 'terminada',
            'precio' => $precio,
        ]);

        return $orden;
    }
}
