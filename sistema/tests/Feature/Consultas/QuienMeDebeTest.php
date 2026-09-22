<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\PanelDelDia;
use App\Aplicacion\Consultas\QuienMeDebe;
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
 * HU-26 · Ver quién me debe (PT-22). Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class QuienMeDebeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $pantalon;

    private MetodoPago $efectivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón']);
        $this->pantalon = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Pantalón']);
        $this->efectivo = MetodoPago::factory()->create([...$negocio, 'nombre' => 'Efectivo']);
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_26_1_lista_y_total(): void
    {
        // #0040 Entregada con saldo de $12.000, #0042 En proceso con saldo de $21.000 y #0041 Cancelada con saldo de $8.000
        $orden40 = $this->orden(40, [['entregada', 22000]], pagado: 10000);
        $orden42 = $this->orden(42, [['terminada', 15000], ['en_proceso', 8000], ['pendiente', 8000]], pagado: 10000);
        $this->orden(41, [['pendiente', 8000]], cancelada: true);
        // Una prenda devuelta sin arreglar no se cobra (RN-44): la #0043 no debe nada
        $this->orden(43, [['devuelta', 9000], ['entregada', 5000]], pagado: 5000);
        $this->actingAs($this->duena);

        $debe = app(QuienMeDebe::class)->obtener();
        $this->assertSame([$orden42->id, $orden40->id], array_map(fn (array $fila) => $fila['orden']->id, $debe['ordenes']));
        $this->assertSame([21000, 12000], array_map(fn (array $fila) => $fila['saldo']->valor(), $debe['ordenes']));
        $this->assertSame(33000, $debe['total']->valor());
        // RN-32: es el mismo total por cobrar del panel del día
        $this->assertSame($debe['total']->valor(), app(PanelDelDia::class)->obtener($this->duena->negocio)['porCobrar']->valor());

        $this->get(route('dinero'))
            ->assertOk()
            ->assertSeeInOrder([
                'Quién me debe', 'Total por cobrar', '$33.000', '2 órdenes · de la mayor deuda a la menor',
                'Marta Rincón', '#0042', 'En proceso', '$21.000',
                'Marta Rincón', '#0040', 'Entregada', '$12.000',
            ])
            ->assertDontSee('#0041')
            ->assertSee('href="'.route('ordenes.detalle', $orden42).'"', false);

        // La tarjeta del panel y la barra de navegación ya llevan a Dinero
        $this->get(route('panel'))->assertSee('<a class="cifra principal" href="'.route('dinero').'">', false);
    }

    public function test_ca_26_2_las_pagadas_no_aparecen(): void
    {
        $this->orden(45, [['en_proceso', 30000]], pagado: 30000);
        $this->actingAs($this->duena);

        $this->assertSame([], app(QuienMeDebe::class)->obtener()['ordenes']);
        $this->get(route('dinero'))
            ->assertSee('Nadie te debe')
            ->assertDontSee('#0045');
    }

    /**
     * @param  list<array{0: string, 1: int}>  $prendas  estado y precio de cada prenda
     */
    private function orden(int $numero, array $prendas, int $pagado = 0, bool $cancelada = false): Orden
    {
        $orden = Orden::factory()->create([
            'cliente_id' => $this->marta->id,
            'numero' => $numero,
            'cancelada_en' => $cancelada ? '2026-09-10 09:00:00' : null,
        ]);

        foreach ($prendas as [$estado, $precio]) {
            Prenda::factory()->create([
                'orden_id' => $orden->id,
                'tipo_prenda_id' => $this->pantalon->id,
                'estado' => $estado,
                'precio' => $precio,
                // La base exige la fecha de la entrega y de la devolución (ck_prendas_entregada, ck_prendas_devuelta)
                'entregada_en' => $estado === 'entregada' ? '2026-09-12 17:00:00' : null,
                'devuelta_en' => $estado === 'devuelta' ? '2026-09-12 17:00:00' : null,
            ]);
        }

        if ($pagado > 0) {
            Pago::factory()->create(['orden_id' => $orden->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => $pagado]);
        }

        return $orden;
    }
}
