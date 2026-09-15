<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\FichaDeCliente;
use App\Modelos\Cliente;
use App\Modelos\MetodoPago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-05 · Consultar la ficha de un cliente. Las órdenes son las de los mockups (docs/03-diseno/mockups/README.md).
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class FichaDeClienteTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $camisa;

    private MetodoPago $efectivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón', 'celular' => '3104567890']);
        $this->camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);
        $this->efectivo = MetodoPago::factory()->create([...$negocio, 'nombre' => 'Efectivo']);
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_05_1_ordenes_y_deuda(): void
    {
        $this->ordenesDeMarta();
        // Lo que debe otro cliente no se suma a Marta
        $luis = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Luis Pardo']);
        $this->orden($luis, 44, '2026-09-08 09:00:00', [['pendiente', 23000]], pagado: 5000);
        $this->actingAs($this->duena);

        $ficha = app(FichaDeCliente::class)->obtener($this->marta);
        $this->assertSame(33000, $ficha['debe']->valor());
        $this->assertSame(['#0042', '#0040'], $ficha['ordenesQueDeben']);

        $this->get(route('clientes.ficha', $this->marta))
            ->assertOk()
            ->assertSeeInOrder([
                'Marta Rincón', '310 456 7890',
                'Debe en total', '$33.000', 'Órdenes #0042 y #0040',
                'Nueva orden para Marta Rincón',
                'Órdenes', '<span class="contador neutro">2</span>',
                route('ordenes.detalle', 42), '#0042', '7 sep 2026', 'En proceso', 'Por cobrar', '$21.000', 'saldo',
                route('ordenes.detalle', 40), '#0040', '2 sep 2026', 'Entregada', 'Por cobrar', '$12.000', 'saldo',
            ], false)
            ->assertDontSee('#0044');
    }

    public function test_ca_05_2_las_canceladas_no_suman(): void
    {
        $this->ordenesDeMarta();
        // La #0041 se canceló con $8.000 por cobrar
        $this->orden($this->marta, 41, '2026-09-04 11:00:00', [['pendiente', 8000]], cancelada: true);
        $this->actingAs($this->duena);

        $ficha = app(FichaDeCliente::class)->obtener($this->marta);
        $this->assertSame(33000, $ficha['debe']->valor());
        $this->assertSame(8000, $ficha['ordenes'][1]['saldo']->valor());

        $pagina = $this->get(route('clientes.ficha', $this->marta))
            ->assertOk()
            ->assertSeeInOrder(['$33.000', 'Órdenes #0042 y #0040', '<span class="contador neutro">3</span>', '#0042', '#0041', 'Cancelada', 'No suma a la deuda', '#0040'], false);

        // La fila de la cancelada no dice que esté por cobrar ni muestra su saldo
        $filaCancelada = str($pagina->getContent())->after('#0041')->before('#0040');
        $this->assertStringNotContainsString('Por cobrar', (string) $filaCancelada);
        $this->assertStringNotContainsString('$8.000', (string) $filaCancelada);
    }

    public function test_ca_05_3_cliente_sin_ordenes(): void
    {
        $laura = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Laura Rincón']);
        $this->ordenesDeMarta();
        $this->actingAs($this->duena);

        $this->get(route('clientes.ficha', $laura))
            ->assertOk()
            ->assertSee('Laura Rincón no tiene órdenes y no debe nada.')
            ->assertSee('Nueva orden para Laura Rincón')
            ->assertDontSee('Debe en total')
            ->assertDontSee('#0042');
    }

    public function test_rn_29_una_orden_pagada_no_suma_y_se_ve_pagada(): void
    {
        // Pagada del todo, aunque sigue en proceso: el estado de pago no depende del avance
        $this->orden($this->marta, 45, '2026-09-10 10:00:00', [['en_proceso', 20000]], pagado: 20000);
        $this->actingAs($this->duena);

        $pagina = $this->get(route('clientes.ficha', $this->marta))
            ->assertOk()
            ->assertSeeInOrder(['Debe en total', '$0', 'No debe nada', '#0045', 'En proceso', 'Pagada'])
            ->assertDontSee('Por cobrar');

        $this->assertStringNotContainsString('saldo</span>', $pagina->getContent());
    }

    /**
     * La #0040 Entregada con saldo de $12.000 y la #0042 En proceso con saldo de $21.000 (CA-05.1).
     */
    private function ordenesDeMarta(): void
    {
        $this->orden($this->marta, 40, '2026-09-02 15:30:00', [['entregada', 20000]], pagado: 8000);
        $this->orden($this->marta, 42, '2026-09-07 09:15:00', [['terminada', 15000], ['terminada', 8000], ['pendiente', 8000]], pagado: 10000);
    }

    /**
     * @param  list<array{0: string, 1: int}>  $prendas  estado y precio de cada prenda
     */
    private function orden(Cliente $cliente, int $numero, string $recibidaEn, array $prendas, int $pagado = 0, bool $cancelada = false): Orden
    {
        $orden = Orden::factory()->create([
            'cliente_id' => $cliente->id,
            'numero' => $numero,
            'recibida_en' => $recibidaEn,
            'cancelada_en' => $cancelada ? '2026-09-12 11:00:00' : null,
        ]);

        foreach ($prendas as [$estado, $precio]) {
            Prenda::factory()->create([
                'orden_id' => $orden->id,
                'tipo_prenda_id' => $this->camisa->id,
                'precio' => $precio,
                'estado' => $estado,
                'entregada_en' => $estado === 'entregada' ? '2026-09-10 11:20:00' : null,
            ]);
        }

        if ($pagado > 0) {
            Pago::factory()->create(['orden_id' => $orden->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => $pagado]);
        }

        return $orden;
    }
}
