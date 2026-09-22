<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\DineroRecibido;
use App\Modelos\Cliente;
use App\Modelos\MetodoPago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Usuario;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-27 · Ver cuánto dinero he recibido (PT-22). Los datos se crean antes de iniciar sesión,
 * porque al crear se asigna el negocio de la sesión.
 */
class DineroRecibidoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Orden $orden;

    private MetodoPago $efectivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $marta = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Marta Rincón']);
        $this->orden = Orden::factory()->create(['cliente_id' => $marta->id, 'numero' => 42]);
        $this->efectivo = MetodoPago::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Efectivo']);
        // Martes 22 de septiembre de 2026
        $this->fijarReloj('2026-09-22 10:00:00');
    }

    public function test_ca_27_1_mes(): void
    {
        $this->pago(10000, '2026-09-03 09:00:00');
        $this->pago(21000, '2026-09-11 16:20:00');
        $this->pago(15000, '2026-09-18 12:00:00', anulado: true);
        // Agosto no es septiembre
        $this->pago(7000, '2026-08-31 18:00:00');
        $this->actingAs($this->duena);

        $recibido = app(DineroRecibido::class)->entre(new DateTimeImmutable('2026-09-01'), new DateTimeImmutable('2026-09-30'));
        $this->assertSame(31000, $recibido['recibido']->valor());
        $this->assertSame(2, $recibido['pagos']);
        $this->assertSame(1, $recibido['anulados']);

        // El mes es lo que se ve al abrir Dinero
        $pagina = $this->get(route('dinero'))
            ->assertOk()
            ->assertSeeInOrder(['Recibido', 'Hoy', 'Semana', 'Mes', 'Fechas', 'Septiembre de 2026', '$31.000', '2 pagos · no incluye 1 pago anulado']);
        $this->assertMatchesRegularExpression('/periodo=mes"\s+aria-current="page"\s*>Mes</', (string) $pagina->getContent());
    }

    public function test_rn_33_dinero_recibido_en_un_periodo(): void
    {
        // En septiembre hay pagos de $10.000, $21.000 y $15.000 (anulado): lo recibido en septiembre es $31.000
        $this->pago(10000, '2026-09-02 10:00:00');
        $this->pago(21000, '2026-09-20 15:00:00');
        $this->pago(15000, '2026-09-21 09:00:00', anulado: true);
        $this->actingAs($this->duena);

        $septiembre = app(DineroRecibido::class)->periodo('mes');
        $this->assertSame(31000, app(DineroRecibido::class)->entre($septiembre['desde'], $septiembre['hasta'])['recibido']->valor());
    }

    public function test_ca_27_2_pago_de_noche(): void
    {
        // El 14 a las 11:30 p. m., hora de Colombia, sigue siendo el 14 (RN-09)
        $this->pago(8000, '2026-09-14 23:30:00');
        $this->pago(3000, '2026-09-15 00:10:00');
        $this->actingAs($this->duena);

        $catorce = new DateTimeImmutable('2026-09-14');
        $this->assertSame(8000, app(DineroRecibido::class)->entre($catorce, $catorce)['recibido']->valor());

        $this->get(route('dinero', ['periodo' => 'fechas', 'desde' => '2026-09-14', 'hasta' => '2026-09-14']))
            ->assertSeeInOrder(['Lunes 14 sep 2026', '$8.000', '1 pago'])
            ->assertDontSee('no incluye');
    }

    public function test_ca_27_3_rango(): void
    {
        $this->pago(10000, '2026-09-01 08:00:00');
        $this->pago(20000, '2026-09-15 19:00:00');
        $this->pago(40000, '2026-09-16 09:00:00');
        $this->actingAs($this->duena);

        $this->get(route('dinero', ['periodo' => 'fechas', 'desde' => '2026-09-01', 'hasta' => '2026-09-15']))
            ->assertOk()
            ->assertSee('value="2026-09-01"', false)
            ->assertSeeInOrder(['Del 1 sep 2026 al 15 sep 2026', '$30.000', '2 pagos']);

        // La fecha final antes de la inicial: se explica y no se suma nada
        $this->get(route('dinero', ['periodo' => 'fechas', 'desde' => '2026-09-15', 'hasta' => '2026-09-01']))
            ->assertOk()
            ->assertSee('La fecha final no puede ser antes de la inicial.')
            ->assertDontSee('$30.000');

        // Al tocar Fechas todavía no hay fechas: se piden, sin mostrar un error
        $this->get(route('dinero', ['periodo' => 'fechas']))
            ->assertOk()
            ->assertSee('Ver lo recibido')
            ->assertDontSee('Elige las dos fechas.');
    }

    public function test_hoy_y_la_semana_van_de_lunes_a_domingo(): void
    {
        $this->pago(5000, '2026-09-20 17:00:00'); // domingo de la semana anterior
        $this->pago(6000, '2026-09-21 09:00:00'); // lunes
        $this->pago(7000, '2026-09-22 08:00:00'); // hoy, martes
        $this->actingAs($this->duena);

        $semana = app(DineroRecibido::class)->periodo('semana');
        $this->assertSame(['2026-09-21', '2026-09-27'], [$semana['desde']->format('Y-m-d'), $semana['hasta']->format('Y-m-d')]);

        $this->get(route('dinero', ['periodo' => 'semana']))->assertSeeInOrder(['Del 21 sep 2026 al 27 sep 2026', '$13.000']);
        $this->get(route('dinero', ['periodo' => 'hoy']))->assertSeeInOrder(['Hoy, Martes 22 sep 2026', '$7.000', '1 pago']);
        // Un período que no existe se ignora y se ve el mes
        $this->get(route('dinero', ['periodo' => 'año']))->assertSee('Septiembre de 2026');
    }

    public function test_rnf_22_no_suma_los_pagos_de_otro_negocio(): void
    {
        Pago::factory()->create(['valor' => 99000, 'pagado_en' => '2026-09-10 10:00:00']);
        $this->pago(1000, '2026-09-10 10:00:00');
        $this->actingAs($this->duena);

        $this->assertSame(1000, app(DineroRecibido::class)->entre(new DateTimeImmutable('2026-09-01'), new DateTimeImmutable('2026-09-30'))['recibido']->valor());
    }

    private function pago(int $valor, string $pagadoEn, bool $anulado = false): Pago
    {
        $fabrica = Pago::factory();

        return ($anulado ? $fabrica->anulado() : $fabrica)->create([
            'orden_id' => $this->orden->id,
            'metodo_pago_id' => $this->efectivo->id,
            'valor' => $valor,
            'pagado_en' => $pagadoEn,
        ]);
    }
}
