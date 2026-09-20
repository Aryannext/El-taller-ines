<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\OrdenesAtrasadas;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-33 · Las órdenes En proceso cuya fecha de entrega ya pasó (PT-20, RN-34).
 * Hoy es miércoles 16 de septiembre de 2026, como en los criterios.
 */
class OrdenesAtrasadasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    /** @var array<string, int> */
    private array $negocio;

    private TipoPrenda $vestido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $this->negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->vestido = TipoPrenda::factory()->create([...$this->negocio, 'nombre' => 'Vestido']);

        $this->fijarReloj('2026-09-16 09:00:00');
        $this->actingAs($this->duena);
    }

    public function test_ca_33_1_mas_atrasada_primero(): void
    {
        $this->orden(44, 'Luis Pardo', '2026-09-12', 'en_proceso');
        $this->orden(42, 'Sandra Ruiz', '2026-09-15', 'en_proceso');

        $this->assertSame(2, app(OrdenesAtrasadas::class)->contar());

        $this->get(route('seguimiento.atrasadas'))
            ->assertOk()
            ->assertSeeInOrder([
                'Luis Pardo',
                '#0044',
                'entrega era el 12 sep 2026',
                'Sandra Ruiz',
                '#0042',
                'entrega era el 15 sep 2026',
            ]);

        $atrasadas = app(OrdenesAtrasadas::class)->listar();
        $this->assertSame([4, 1], array_column($atrasadas, 'diasDeAtraso'));
    }

    public function test_ca_33_2_lista_no_es_atrasada(): void
    {
        // La #0043 tiene el trabajo hecho: lo que falta es que el cliente la recoja
        $this->orden(43, 'Carmen Díaz', '2026-09-15', 'terminada');

        $this->assertSame(0, app(OrdenesAtrasadas::class)->contar());
        $this->get(route('seguimiento.atrasadas'))
            ->assertOk()
            ->assertSee('No hay órdenes atrasadas.')
            ->assertDontSee('Carmen Díaz');
    }

    public function test_ca_33_3_hora_de_colombia(): void
    {
        // Son las 10:00 p. m. del 14 de septiembre: la entrega del 15 todavía no se ha vencido
        $this->fijarReloj('2026-09-14 22:00:00');
        $this->orden(45, 'Ana Beltrán', '2026-09-15', 'en_proceso');

        $this->assertSame(0, app(OrdenesAtrasadas::class)->contar());
        $this->get(route('seguimiento.atrasadas'))->assertOk()->assertDontSee('Ana Beltrán');
    }

    public function test_una_orden_cancelada_no_esta_atrasada(): void
    {
        $orden = $this->orden(46, 'Pedro Gil', '2026-09-10', 'en_proceso');
        $orden->update(['cancelada_en' => '2026-09-11 08:00:00']);

        $this->assertSame(0, app(OrdenesAtrasadas::class)->contar());
    }

    public function test_rnf_22_las_atrasadas_de_otro_negocio_no_se_ven(): void
    {
        $otra = Usuario::factory()->create();
        $ajena = Cliente::factory()->create(['negocio_id' => $otra->negocio_id, 'nombre' => 'Clienta Ajena']);
        $orden = Orden::factory()->create([
            'cliente_id' => $ajena->id,
            'numero' => 77,
            'fecha_entrega_acordada' => '2026-09-01',
            'recibida_en' => '2026-08-25 09:00:00',
        ]);
        Prenda::factory()->create([
            'orden_id' => $orden->id,
            'tipo_prenda_id' => TipoPrenda::factory()->create(['negocio_id' => $otra->negocio_id])->id,
            'estado' => 'en_proceso',
        ]);

        $this->assertSame(0, app(OrdenesAtrasadas::class)->contar());
        $this->get(route('seguimiento.atrasadas'))->assertOk()->assertDontSee('Clienta Ajena');
    }

    private function orden(int $numero, string $cliente, string $entrega, string $estadoDePrenda): Orden
    {
        $orden = Orden::factory()->create([
            'cliente_id' => Cliente::factory()->create([...$this->negocio, 'nombre' => $cliente])->id,
            'numero' => $numero,
            'fecha_entrega_acordada' => $entrega,
            // La orden se recibió una semana antes: la entrega acordada nunca es anterior a la recepción
            'recibida_en' => date('Y-m-d', strtotime($entrega.' -7 days')).' 09:00:00',
            'lista_en' => $estadoDePrenda === 'terminada' ? '2026-09-15 16:00:00' : null,
        ]);

        Prenda::factory()->create(['orden_id' => $orden->id, 'tipo_prenda_id' => $this->vestido->id, 'estado' => $estadoDePrenda]);

        return $orden;
    }
}
