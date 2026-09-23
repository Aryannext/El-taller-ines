<?php

namespace Tests\Feature\Configuracion;

use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-16 · Renombrar o dejar de usar un tipo de prenda (RN-01, RN-10, RN-43).
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class GestionarTiposDePrendaTest extends TestCase
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
        $this->fijarReloj('2026-09-15 09:00:00');
    }

    public function test_ca_16_1_renombrar(): void
    {
        // El tipo «Overol» tiene dos prendas registradas
        $overol = $this->tipo('Overol');
        $this->prenda($overol, 'Subir tirantes');
        $this->prenda($overol, 'Cambiar cremallera');
        $this->actingAs($this->duena);

        $this->get(route('ajustes'))->assertOk()->assertSeeInOrder(['Tipos de prenda', 'value="Overol"']);

        $this->put(route('ajustes.tipos.renombrar', $overol), ['nombre' => 'Enterizo'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ajustes'));

        // Las dos prendas muestran Enterizo, porque comparten el tipo
        $this->assertSame('Enterizo', $overol->fresh()->nombre);
        $this->get(route('ordenes.detalle', $this->orden))
            ->assertOk()
            ->assertSee('Enterizo')
            ->assertDontSee('Overol');
    }

    public function test_ca_16_2_desactivar(): void
    {
        $chaqueta = $this->tipo('Chaqueta');
        $camisa = $this->tipo('Camisa');
        $this->prenda($chaqueta, 'Cambiar forro');
        $this->actingAs($this->duena);

        $this->put(route('ajustes.tipos.activo', $chaqueta), ['activo' => '0'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ajustes'));

        // Ya no se ofrece al registrar prendas nuevas
        $this->assertFalse($chaqueta->fresh()->activo);
        $this->get(route('ordenes.nueva'))->assertOk()->assertSee('Camisa')->assertDontSee('Chaqueta');
        $this->get(route('prendas.nueva', $this->orden))->assertOk()->assertDontSee('Chaqueta');

        // Pero la prenda que ya era chaqueta lo conserva
        $this->get(route('ordenes.detalle', $this->orden))->assertSee('Chaqueta');

        // Y desde ajustes se puede volver a usar
        $this->get(route('ajustes'))->assertSee('No aparece al registrar prendas nuevas');
        $this->put(route('ajustes.tipos.activo', $chaqueta), ['activo' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($chaqueta->fresh()->activo);
        $this->get(route('ordenes.nueva'))->assertSee('Chaqueta');
        $this->assertTrue($camisa->fresh()->activo);
    }

    public function test_rn_43_dos_tipos_del_negocio_no_se_llaman_igual(): void
    {
        $overol = $this->tipo('Overol');
        $this->tipo('Camisa');
        $this->actingAs($this->duena);

        $this->from(route('ajustes'))->put(route('ajustes.tipos.renombrar', $overol), ['nombre' => 'Camisa'])
            ->assertRedirect(route('ajustes'))
            ->assertSessionHasErrors(['nombre' => 'Ya existe un tipo de prenda con ese nombre.']);
        $this->assertSame('Overol', $overol->fresh()->nombre);

        // Guardarlo con su propio nombre no choca consigo mismo
        $this->put(route('ajustes.tipos.renombrar', $overol), ['nombre' => 'Overol'])->assertSessionHasNoErrors();

        $this->from(route('ajustes'))->put(route('ajustes.tipos.renombrar', $overol), ['nombre' => ''])
            ->assertSessionHasErrors(['nombre' => 'Escribe el nombre del tipo de prenda.']);
    }

    public function test_rn_01_no_se_toca_un_tipo_de_otro_negocio(): void
    {
        $deOtroTaller = TipoPrenda::factory()->create(['nombre' => 'Overol']);
        $this->actingAs($this->duena);

        $this->put(route('ajustes.tipos.renombrar', $deOtroTaller), ['nombre' => 'Enterizo'])->assertNotFound();
        $this->put(route('ajustes.tipos.activo', $deOtroTaller), ['activo' => '0'])->assertNotFound();
        $this->assertSame('Overol', $deOtroTaller->fresh()->nombre);
        $this->assertTrue($deOtroTaller->fresh()->activo);
    }

    private function tipo(string $nombre): TipoPrenda
    {
        return TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => $nombre]);
    }

    private function prenda(TipoPrenda $tipo, string $arreglo): Prenda
    {
        return Prenda::factory()->create([
            'orden_id' => $this->orden->id,
            'tipo_prenda_id' => $tipo->id,
            'descripcion_arreglo' => $arreglo,
            'precio' => 20000,
            'estado' => 'pendiente',
        ]);
    }
}
