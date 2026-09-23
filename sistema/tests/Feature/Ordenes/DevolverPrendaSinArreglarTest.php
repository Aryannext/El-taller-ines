<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-36 · El cliente se lleva una prenda sin arreglar (RN-44). El ejemplo es el de la regla: la #0042 de Marta,
 * con un pantalón Terminado de $15.000 y dos camisas Pendientes de $8.000. Los datos se crean antes de iniciar
 * sesión, porque al crear se asigna el negocio de la sesión.
 */
class DevolverPrendaSinArreglarTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $pantalon;

    private TipoPrenda $camisa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón']);
        $this->pantalon = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Pantalón']);
        $this->camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);
        $this->fijarReloj('2026-09-15 11:20:00');
    }

    public function test_ca_36_1_devolucion(): void
    {
        [$orden, , $primeraCamisa] = $this->ordenDeMarta();
        $this->actingAs($this->duena);

        // La pantalla pregunta antes, con el valor que quedaría (RNF-10)
        $this->get(route('prendas.confirmar-devolucion', [$orden, $primeraCamisa]))
            ->assertOk()
            ->assertSeeInOrder([
                '¿Devolver camisa sin arreglar?',
                'Marta se lleva camisa sin el arreglo «Entallar los costados»',
                'Valor de la orden', '$31.000', '$23.000',
                'Saldo', '$23.000',
                'No se puede deshacer',
                'Sí, devolver sin arreglar',
            ]);

        $this->post(route('prendas.devolver', [$orden, $primeraCamisa]), ['confirmacion' => 'si'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.detalle', $orden));

        // La camisa queda Devuelta con su fecha (RN-44)
        $primeraCamisa->refresh();
        $this->assertSame('devuelta', $primeraCamisa->estado->value);
        $this->assertSame('2026-09-15 11:20:00', $primeraCamisa->devuelta_en->format('Y-m-d H:i:s'));

        // El valor y el saldo bajan a $23.000 y la orden sigue En proceso (RN-26, RN-18)
        $detalle = app(DetalleDeOrden::class)->obtener($orden->fresh());
        $this->assertSame(23000, $detalle['valor']->valor());
        $this->assertSame(23000, $detalle['saldo']->valor());
        $this->assertSame(EstadoDeOrden::EnProceso, $detalle['estado']);

        $this->get(route('ordenes.detalle', $orden))
            ->assertSeeInOrder(['quedó devuelta sin arreglar y ya no se cobra', 'En proceso', 'Devuelta', 'Valor de la orden', '$23.000']);
    }

    public function test_ca_36_2_me_arrepiento(): void
    {
        [$orden, , $primeraCamisa] = $this->ordenDeMarta();
        $this->actingAs($this->duena);

        // Responder que no es no enviar la confirmación: el servidor vuelve a preguntar y nada cambia
        $this->post(route('prendas.devolver', [$orden, $primeraCamisa]))
            ->assertRedirect(route('prendas.confirmar-devolucion', [$orden, $primeraCamisa]));

        $this->assertSame('pendiente', $primeraCamisa->fresh()->estado->value);
        $this->assertNull($primeraCamisa->fresh()->devuelta_en);
        $this->assertSame(31000, app(DetalleDeOrden::class)->obtener($orden->fresh())['valor']->valor());
    }

    public function test_ca_36_3_prenda_terminada(): void
    {
        [$orden, $pantalon, $primeraCamisa] = $this->ordenDeMarta();
        $this->actingAs($this->duena);

        // En la camisa Pendiente la opción está; en el pantalón Terminado, no (RN-44)
        $this->get(route('prendas.acciones', [$orden, $primeraCamisa]))
            ->assertOk()
            ->assertSee(route('prendas.confirmar-devolucion', [$orden, $primeraCamisa]), false);

        $this->get(route('prendas.acciones', [$orden, $pantalon]))
            ->assertOk()
            ->assertDontSee(route('prendas.confirmar-devolucion', [$orden, $pantalon]), false);

        $motivo = 'Solo se devuelve sin arreglar una prenda que todavía está pendiente o en proceso.';
        $this->get(route('prendas.confirmar-devolucion', [$orden, $pantalon]))
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => $motivo]);

        // Aunque el formulario llegue igual, el pantalón no se devuelve
        $this->post(route('prendas.devolver', [$orden, $pantalon]), ['confirmacion' => 'si'])
            ->assertSessionHasErrors(['prenda' => $motivo]);
        $this->assertSame('terminada', $pantalon->fresh()->estado->value);
    }

    public function test_ca_36_4_unica_prenda_por_resolver(): void
    {
        // La #0043 tiene una sola prenda, Pendiente: devolverla dejaría la orden entera devuelta (RN-44, RN-24)
        $orden = $this->orden(43);
        $unica = $this->prenda($orden, $this->camisa, 'Cambiar cremallera', 12000, 'pendiente');
        $this->actingAs($this->duena);

        $motivo = 'Es la única prenda que queda por resolver: lo que corresponde es cancelar la orden.';
        $this->get(route('prendas.confirmar-devolucion', [$orden, $unica]))
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => $motivo]);

        $this->post(route('prendas.devolver', [$orden, $unica]), ['confirmacion' => 'si'])
            ->assertSessionHasErrors(['prenda' => $motivo]);
        $this->assertSame('pendiente', $unica->fresh()->estado->value);

        // Y la salida que sí corresponde sigue disponible
        $this->get(route('ordenes.detalle', $orden))->assertSee(route('ordenes.confirmar-cancelacion', $orden), false);
    }

    public function test_ca_36_5_lo_pagado_supera_el_valor(): void
    {
        [$orden, , $primeraCamisa] = $this->ordenDeMarta();
        Pago::factory()->create(['orden_id' => $orden->id, 'valor' => 30000, 'pagado_en' => '2026-09-14 10:00:00']);
        $this->actingAs($this->duena);

        // Devolver la camisa dejaría el valor en $23.000, por debajo de los $30.000 pagados (RN-16)
        $motivo = 'La orden quedaría valiendo $23.000 y ya tiene $30.000 pagados. Primero anula el pago que sobra.';
        $this->get(route('prendas.confirmar-devolucion', [$orden, $primeraCamisa]))
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => $motivo]);

        $this->post(route('prendas.devolver', [$orden, $primeraCamisa]), ['confirmacion' => 'si'])
            ->assertSessionHasErrors(['prenda' => $motivo]);
        $this->assertSame('pendiente', $primeraCamisa->fresh()->estado->value);
        $this->assertSame(31000, app(DetalleDeOrden::class)->obtener($orden->fresh())['valor']->valor());
    }

    public function test_rn_44_devolver_una_prenda_sin_arreglar(): void
    {
        [$orden, $pantalon, $primeraCamisa, $segundaCamisa] = $this->ordenDeMarta();
        $this->actingAs($this->duena);

        // El ejemplo de la regla: Marta prefiere llevarse una camisa sin arreglar
        $this->post(route('prendas.devolver', [$orden, $primeraCamisa]), ['confirmacion' => 'si'])->assertSessionHasNoErrors();

        $this->assertSame('devuelta', $primeraCamisa->fresh()->estado->value);
        $this->assertSame(23000, app(DetalleDeOrden::class)->obtener($orden->fresh())['valor']->valor());

        // El pantalón Terminado no se puede devolver sin arreglar
        $this->post(route('prendas.devolver', [$orden, $pantalon]), ['confirmacion' => 'si'])->assertSessionHasErrors('prenda');
        $this->assertSame('terminada', $pantalon->fresh()->estado->value);

        // Una prenda Devuelta ya no se modifica ni se vuelve a devolver (RN-15)
        $this->get(route('prendas.acciones', [$orden, $primeraCamisa]))
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => 'Esta prenda ya fue devuelta y no se puede modificar.']);
        $this->post(route('prendas.devolver', [$orden, $primeraCamisa]), ['confirmacion' => 'si'])->assertSessionHasErrors('prenda');

        // Con el pantalón terminado y la otra camisa terminada, la orden queda lista: la devuelta no cuenta (RN-19)
        $this->post(route('prendas.cambiar-estado', [$orden, $segundaCamisa]), ['estado' => 'terminada'])->assertSessionHasNoErrors();
        $detalle = app(DetalleDeOrden::class)->obtener($orden->fresh());
        $this->assertSame(EstadoDeOrden::ListaParaEntregar, $detalle['estado']);
        $this->assertSame(23000, $detalle['valor']->valor());
    }

    public function test_rnf_25_no_se_devuelve_una_prenda_de_otro_negocio(): void
    {
        $deOtroTaller = Prenda::factory()->create();
        $this->actingAs($this->duena);

        $this->get(route('prendas.confirmar-devolucion', [$deOtroTaller->orden_id, $deOtroTaller]))->assertNotFound();
        $this->post(route('prendas.devolver', [$deOtroTaller->orden_id, $deOtroTaller]), ['confirmacion' => 'si'])->assertNotFound();
    }

    /**
     * La #0042 del ejemplo de RN-44: pantalón Terminado de $15.000 y dos camisas Pendientes de $8.000.
     *
     * @return array{0: Orden, 1: Prenda, 2: Prenda, 3: Prenda}
     */
    private function ordenDeMarta(): array
    {
        $orden = $this->orden(42);

        return [
            $orden,
            $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 15000, 'terminada'),
            $this->prenda($orden, $this->camisa, 'Entallar los costados', 8000, 'pendiente'),
            $this->prenda($orden, $this->camisa, 'Entallar y acortar mangas', 8000, 'pendiente'),
        ];
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function orden(int $numero, array $atributos = []): Orden
    {
        return Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => $numero, 'recibida_en' => '2026-09-07 09:15:00', ...$atributos]);
    }

    private function prenda(Orden $orden, TipoPrenda $tipo, string $arreglo, int $precio, string $estado): Prenda
    {
        return Prenda::factory()->create([
            'orden_id' => $orden->id,
            'tipo_prenda_id' => $tipo->id,
            'descripcion_arreglo' => $arreglo,
            'precio' => $precio,
            'estado' => $estado,
        ]);
    }
}
