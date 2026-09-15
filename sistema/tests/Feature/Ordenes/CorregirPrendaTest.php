<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Consultas\DetalleDeOrden;
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
 * HU-12 · Corregir la descripción o el precio de una prenda. Los datos se crean antes de iniciar sesión,
 * porque al crear se asigna el negocio de la sesión.
 */
class CorregirPrendaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $camisa;

    private MetodoPago $efectivo;

    private Orden $orden42;

    private Prenda $pantalon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón']);
        $pantalon = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Pantalón']);
        $this->camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);
        $this->efectivo = MetodoPago::factory()->create([...$negocio, 'nombre' => 'Efectivo']);

        // La #0042 vale $31.000: pantalón de $15.000 y dos camisas de $8.000
        $this->orden42 = Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => 42, 'recibida_en' => '2026-09-07 09:15:00']);
        $this->pantalon = $this->prenda($this->orden42, $pantalon->id, 'Subir basta 3 cm', 15000, 'terminada');
        $this->prenda($this->orden42, $this->camisa->id, 'Entallar los costados', 8000, 'en_proceso');
        $this->prenda($this->orden42, $this->camisa->id, 'Entallar y acortar mangas', 8000, 'pendiente');
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_12_1_subir_el_precio(): void
    {
        $this->actingAs($this->duena);

        $this->get(route('prendas.editar', [$this->orden42, $this->pantalon]))
            ->assertOk()
            ->assertSeeInOrder(['Corregir prenda', '#0042 · Marta Rincón', 'Pantalón', 'Terminada', 'Subir basta 3 cm'])
            ->assertSee('value="15.000"', false)
            ->assertDontSee('La orden no puede valer menos de lo ya pagado');

        $this->put(route('prendas.corregir', [$this->orden42, $this->pantalon]), [
            'descripcion_arreglo' => 'Subir basta 3 cm y cambiar el botón',
            'precio' => '$18.000',
        ])->assertSessionHasNoErrors()->assertRedirect(route('ordenes.detalle', $this->orden42));

        $this->assertSame(18000, $this->pantalon->fresh()->precio);
        $this->assertSame('Subir basta 3 cm y cambiar el botón', $this->pantalon->fresh()->descripcion_arreglo);

        $detalle = app(DetalleDeOrden::class)->obtener($this->orden42->fresh());
        $this->assertSame(34000, $detalle['valor']->valor());
        $this->assertSame(34000, $detalle['saldo']->valor());

        $this->get(route('ordenes.detalle', $this->orden42))
            ->assertSee('Los cambios de la prenda quedaron guardados.')
            ->assertSeeInOrder(['Subir basta 3 cm y cambiar el botón', '$18.000', 'Valor de la orden', '$34.000', 'Saldo', '$34.000']);
    }

    public function test_ca_12_2_por_debajo_de_lo_pagado(): void
    {
        // $25.000 pagados; el pago anulado no cuenta (RN-27, RN-31)
        Pago::factory()->create(['orden_id' => $this->orden42->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => 25000]);
        Pago::factory()->anulado()->create(['orden_id' => $this->orden42->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => 5000]);
        $this->actingAs($this->duena);
        $editar = route('prendas.editar', [$this->orden42, $this->pantalon]);
        $corregir = route('prendas.corregir', [$this->orden42, $this->pantalon]);

        $this->get($editar)->assertSee('La orden no puede valer menos de lo ya pagado ($25.000).');

        $this->from($editar)->put($corregir, ['descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => '5000'])
            ->assertRedirect($editar)
            ->assertSessionHasErrors(['precio' => 'La orden quedaría valiendo $21.000 y ya tiene $25.000 pagados. Primero anula el pago que sobra.']);
        $this->assertSame(15000, $this->pantalon->fresh()->precio);

        // Bajarlo sin quedar por debajo de lo pagado sí se permite: $31.000 − $6.000 = $25.000
        $this->from($editar)->put($corregir, ['descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => '9000'])->assertSessionHasNoErrors();
        $this->assertSame(9000, $this->pantalon->fresh()->precio);
    }

    public function test_ca_12_3_prenda_entregada(): void
    {
        $orden40 = Orden::factory()->create([
            'cliente_id' => $this->marta->id,
            'numero' => 40,
            'recibida_en' => '2026-09-01 10:00:00',
            'fecha_entrega_acordada' => '2026-09-10',
        ]);
        $camisaEntregada = $this->prenda($orden40, $this->camisa->id, 'Entallar', 8000, 'entregada', '2026-09-10 11:20:00');
        $camisaPendiente = $this->prenda($orden40, $this->camisa->id, 'Acortar mangas', 8000, 'pendiente');
        $this->actingAs($this->duena);
        $motivo = 'Esta prenda ya fue entregada y no se puede modificar.';

        $this->get(route('prendas.editar', [$orden40, $camisaEntregada]))
            ->assertRedirect(route('ordenes.detalle', $orden40))
            ->assertSessionHasErrors(['prenda' => $motivo]);
        $this->put(route('prendas.corregir', [$orden40, $camisaEntregada]), ['descripcion_arreglo' => 'Otro arreglo', 'precio' => '1000'])
            ->assertRedirect(route('ordenes.detalle', $orden40))
            ->assertSessionHasErrors(['prenda' => $motivo]);
        $this->assertSame(8000, $camisaEntregada->fresh()->precio);
        $this->assertSame('Entallar', $camisaEntregada->fresh()->descripcion_arreglo);

        // El detalle explica el motivo y solo ofrece corregir la prenda que todavía se puede
        $this->followingRedirects()->get(route('prendas.editar', [$orden40, $camisaEntregada]))
            ->assertSee($motivo)
            ->assertDontSee(route('prendas.editar', [$orden40, $camisaEntregada]))
            ->assertSee(route('prendas.editar', [$orden40, $camisaPendiente]));
    }

    public function test_rn_24_una_orden_cancelada_no_admite_cambios(): void
    {
        $this->orden42->update(['cancelada_en' => '2026-09-12 11:00:00']);
        $this->actingAs($this->duena);

        $this->put(route('prendas.corregir', [$this->orden42, $this->pantalon]), ['descripcion_arreglo' => 'Otro arreglo', 'precio' => '20000'])
            ->assertRedirect(route('ordenes.detalle', $this->orden42))
            ->assertSessionHasErrors(['prenda' => 'Esta orden está cancelada y no admite cambios.']);

        $this->assertSame(15000, $this->pantalon->fresh()->precio);
        $this->get(route('ordenes.detalle', $this->orden42))->assertDontSee(route('prendas.editar', [$this->orden42, $this->pantalon]));
    }

    public function test_rn_11_el_precio_corregido_es_un_entero_mayor_que_cero(): void
    {
        $this->actingAs($this->duena);
        $corregir = route('prendas.corregir', [$this->orden42, $this->pantalon]);

        $invalidos = [
            ['0', 'El precio debe ser mayor que cero.'],
            ['15.000,50', 'Escribe el precio en pesos, sin centavos.'],
            ['', 'Escribe el precio del arreglo.'],
        ];
        foreach ($invalidos as [$precio, $mensaje]) {
            $this->put($corregir, ['descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => $precio])->assertSessionHasErrors(['precio' => $mensaje]);
        }
        $this->put($corregir, ['descripcion_arreglo' => '', 'precio' => '15000'])
            ->assertSessionHasErrors(['descripcion_arreglo' => 'Escribe qué arreglo lleva la prenda.']);

        $this->assertSame(15000, $this->pantalon->fresh()->precio);
    }

    public function test_la_prenda_se_busca_dentro_de_la_orden_de_la_direccion(): void
    {
        $orden43 = Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => 43]);
        $this->actingAs($this->duena);

        $this->get(route('prendas.editar', [$orden43, $this->pantalon]))->assertNotFound();
        $this->put(route('prendas.corregir', [$orden43, $this->pantalon]), ['descripcion_arreglo' => 'Otro arreglo', 'precio' => '1000'])->assertNotFound();
        $this->assertSame(15000, $this->pantalon->fresh()->precio);
    }

    private function prenda(Orden $orden, int $tipoPrendaId, string $arreglo, int $precio, string $estado, ?string $entregadaEn = null): Prenda
    {
        return Prenda::factory()->create([
            'orden_id' => $orden->id,
            'tipo_prenda_id' => $tipoPrendaId,
            'descripcion_arreglo' => $arreglo,
            'precio' => $precio,
            'estado' => $estado,
            'entregada_en' => $entregadaEn,
        ]);
    }
}
