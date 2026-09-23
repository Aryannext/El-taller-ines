<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Modelos\Cliente;
use App\Modelos\Foto;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HU-13 · Eliminar una prenda registrada por error (RN-06, RN-15, RN-16). El ejemplo es la #0042 de Marta:
 * un pantalón de $15.000 y dos camisas de $8.000, sin pagos. Los datos se crean antes de iniciar sesión,
 * porque al crear se asigna el negocio de la sesión.
 */
class EliminarPrendaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $pantalon;

    private TipoPrenda $camisa;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('privado');
        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón']);
        $this->pantalon = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Pantalón']);
        $this->camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);
        $this->fijarReloj('2026-09-15 11:20:00');
    }

    public function test_ca_13_1_eliminacion_correcta(): void
    {
        [$orden, , $primeraCamisa] = $this->ordenDeMarta();
        $foto = Foto::factory()->create(['prenda_id' => $primeraCamisa->id, 'posicion' => 1]);
        Storage::disk('privado')->put($foto->ruta, 'una foto');
        $this->actingAs($this->duena);

        // PT-13 ofrece eliminarla, y dice en cuánto quedaría la orden antes de confirmar (RNF-10)
        $this->get(route('prendas.editar', [$orden, $primeraCamisa]))
            ->assertOk()
            ->assertSeeInOrder([
                '¿Registraste esta prenda por error?',
                'Se eliminará «Entallar los costados» y sus fotos. La orden quedará valiendo $23.000.',
                'Eliminar esta prenda',
            ]);

        $this->delete(route('prendas.eliminar', [$orden, $primeraCamisa]), ['confirmacion' => 'si'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.detalle', $orden));

        // La orden queda con dos prendas y un valor de $23.000
        $this->assertSame(2, $orden->prendas()->count());
        $this->assertNull(Prenda::find($primeraCamisa->id));
        $this->assertSame(23000, app(DetalleDeOrden::class)->obtener($orden->fresh())['valor']->valor());

        // La foto se va con la prenda, en la base y en el disco (05-avisos-fotos-y-reloj)
        $this->assertNull(Foto::find($foto->id));
        Storage::disk('privado')->assertMissing($foto->ruta);
    }

    public function test_ca_13_2_me_arrepiento(): void
    {
        [$orden, , $primeraCamisa] = $this->ordenDeMarta();
        $this->actingAs($this->duena);

        // Responder que no es no mandar la confirmación: el cuadro la agrega solo al aceptar
        $this->delete(route('prendas.eliminar', [$orden, $primeraCamisa]))
            ->assertRedirect(route('prendas.editar', [$orden, $primeraCamisa]))
            ->assertSessionHasErrors(['prenda' => 'Para eliminar la prenda hay que confirmarlo en el cuadro que aparece.']);

        $this->assertSame(3, $orden->prendas()->count());
        $this->assertSame(31000, app(DetalleDeOrden::class)->obtener($orden->fresh())['valor']->valor());
    }

    public function test_ca_13_3_unica_prenda(): void
    {
        // RN-06: una orden sin prendas no existe
        $orden = $this->orden(43);
        $unica = $this->prenda($orden, $this->camisa, 'Cambiar cremallera', 12000, 'pendiente');
        $this->actingAs($this->duena);

        $motivo = 'No puedes eliminar la única prenda de la orden. Si el cliente ya no quiere el arreglo, cancela la orden.';
        $this->get(route('prendas.editar', [$orden, $unica]))
            ->assertOk()
            ->assertDontSee('Eliminar esta prenda');

        $this->delete(route('prendas.eliminar', [$orden, $unica]), ['confirmacion' => 'si'])
            ->assertRedirect(route('prendas.editar', [$orden, $unica]))
            ->assertSessionHasErrors(['prenda' => $motivo]);
        $this->assertSame(1, $orden->prendas()->count());

        // Y la salida que sí corresponde sigue disponible
        $this->get(route('ordenes.detalle', $orden))->assertSee(route('ordenes.confirmar-cancelacion', $orden), false);
    }

    public function test_ca_13_4_prenda_entregada(): void
    {
        // RN-15: lo que ya salió del taller es historia del negocio y no se borra
        $orden = $this->orden(40);
        $entregada = $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 15000, 'entregada');
        $this->prenda($orden, $this->camisa, 'Entallar los costados', 8000, 'terminada');
        $this->actingAs($this->duena);

        $this->delete(route('prendas.eliminar', [$orden, $entregada]), ['confirmacion' => 'si'])
            ->assertSessionHasErrors(['prenda' => 'Esta prenda ya fue entregada y no se puede modificar.']);
        $this->assertSame(2, $orden->prendas()->count());
    }

    public function test_rn_16_no_deja_la_orden_valiendo_menos_de_lo_pagado(): void
    {
        [$orden, , $primeraCamisa] = $this->ordenDeMarta();
        Pago::factory()->create(['orden_id' => $orden->id, 'valor' => 30000, 'pagado_en' => '2026-09-14 10:00:00']);
        $this->actingAs($this->duena);

        $motivo = 'La orden quedaría valiendo $23.000 y ya tiene $30.000 pagados. Primero anula el pago que sobra.';
        $this->get(route('prendas.editar', [$orden, $primeraCamisa]))->assertDontSee('Eliminar esta prenda');
        $this->delete(route('prendas.eliminar', [$orden, $primeraCamisa]), ['confirmacion' => 'si'])
            ->assertSessionHasErrors(['prenda' => $motivo]);
        $this->assertSame(3, $orden->prendas()->count());
    }

    public function test_al_eliminar_la_ultima_pendiente_la_orden_queda_lista(): void
    {
        // RN-18: el estado se recalcula con las prendas que quedan
        $orden = $this->orden(44);
        $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 15000, 'terminada');
        $pendiente = $this->prenda($orden, $this->camisa, 'Entallar los costados', 8000, 'pendiente');
        $this->actingAs($this->duena);

        $this->delete(route('prendas.eliminar', [$orden, $pendiente]), ['confirmacion' => 'si'])->assertSessionHasNoErrors();

        $detalle = app(DetalleDeOrden::class)->obtener($orden->fresh());
        $this->assertSame(EstadoDeOrden::ListaParaEntregar, $detalle['estado']);
        $this->assertSame('2026-09-15 11:20:00', $orden->fresh()->lista_en->format('Y-m-d H:i:s'));
    }

    public function test_rnf_25_no_se_elimina_una_prenda_de_otro_negocio(): void
    {
        $deOtroTaller = Prenda::factory()->create();
        $this->actingAs($this->duena);

        $this->delete(route('prendas.eliminar', [$deOtroTaller->orden_id, $deOtroTaller]), ['confirmacion' => 'si'])->assertNotFound();
        $this->assertNotNull(Prenda::find($deOtroTaller->id));
    }

    /**
     * La #0042: un pantalón de $15.000 y dos camisas de $8.000, sin pagos.
     *
     * @return array{0: Orden, 1: Prenda, 2: Prenda, 3: Prenda}
     */
    private function ordenDeMarta(): array
    {
        $orden = $this->orden(42);

        return [
            $orden,
            $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 15000, 'pendiente'),
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
            'entregada_en' => $estado === 'entregada' ? '2026-09-13 11:00:00' : null,
        ]);
    }
}
