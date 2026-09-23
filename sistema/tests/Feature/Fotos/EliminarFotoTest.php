<?php

namespace Tests\Feature\Fotos;

use App\Modelos\Cliente;
use App\Modelos\Foto;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HU-19 · Eliminar una foto borrosa o equivocada (RN-17, RNF-10). El ejemplo es el vestido de Marta.
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class EliminarFotoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Orden $orden;

    private Prenda $vestido;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('privado');
        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón']);
        $tipo = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Vestido']);
        $this->orden = Orden::factory()->create(['cliente_id' => $marta->id, 'numero' => 42, 'recibida_en' => '2026-09-07 09:15:00']);
        $this->vestido = Prenda::factory()->create([
            'orden_id' => $this->orden->id,
            'tipo_prenda_id' => $tipo->id,
            'descripcion_arreglo' => 'Entallar el talle',
            'precio' => 25000,
            'estado' => 'pendiente',
        ]);
        $this->fijarReloj('2026-09-15 11:20:00');
    }

    public function test_ca_19_1_libera_espacio_para_otra(): void
    {
        $fotos = collect([1, 2, 3])->map(fn (int $posicion) => $this->foto($posicion));
        $this->actingAs($this->duena);

        // Con las 3 fotos, PT-13 no deja agregar más (RN-17)
        $this->get(route('prendas.editar', [$this->orden, $this->vestido]))
            ->assertOk()
            ->assertSee('Ya tiene las 3 fotos que caben')
            ->assertSee('Eliminar la foto 2');

        $this->delete(route('fotos.eliminar', $fotos[1]), ['confirmacion' => 'si'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('prendas.editar', [$this->orden, $this->vestido]));

        // Quedan 2, el archivo se borró y la posición 2 queda libre para otra
        $this->assertSame([1, 3], $this->vestido->fotos()->orderBy('posicion')->pluck('posicion')->map(fn ($p) => (int) $p)->all());
        Storage::disk('privado')->assertMissing($fotos[1]->ruta);

        $this->get(route('prendas.editar', [$this->orden, $this->vestido]))
            ->assertSee('La foto se eliminó.')
            ->assertSee('· 2 de 3')
            ->assertDontSee('Ya tiene las 3 fotos que caben');

        $this->post(route('fotos.agregar', [$this->orden, $this->vestido]), ['fotos' => [UploadedFile::fake()->image('vestido.jpg', 900, 1200)]])
            ->assertSessionHasNoErrors();
        $this->assertSame([1, 2, 3], $this->vestido->fotos()->orderBy('posicion')->pluck('posicion')->map(fn ($p) => (int) $p)->all());
    }

    public function test_ca_19_2_la_prenda_se_conserva(): void
    {
        $unica = $this->foto(1);
        $this->actingAs($this->duena);

        $this->delete(route('fotos.eliminar', $unica), ['confirmacion' => 'si'])->assertSessionHasNoErrors();

        // El vestido sigue registrado, sin fotos, y la pantalla sugiere tomarle una (CA-17.4)
        $this->assertNotNull(Prenda::find($this->vestido->id));
        $this->assertSame(0, $this->vestido->fotos()->count());
        $this->assertNull(Foto::find($unica->id));
        $this->get(route('prendas.editar', [$this->orden, $this->vestido]))
            ->assertSee('Sin foto: tómale una para reconocerla después.');
    }

    public function test_rnf_10_sin_confirmacion_la_foto_no_se_borra(): void
    {
        $foto = $this->foto(1);
        $this->actingAs($this->duena);

        $this->delete(route('fotos.eliminar', $foto))
            ->assertRedirect(route('prendas.editar', [$this->orden, $this->vestido]))
            ->assertSessionHasErrors(['fotos' => 'Para eliminar la foto hay que confirmarlo en el cuadro que aparece.']);

        $this->assertNotNull(Foto::find($foto->id));
        Storage::disk('privado')->assertExists($foto->ruta);
    }

    public function test_rn_15_una_prenda_entregada_conserva_sus_fotos(): void
    {
        $foto = $this->foto(1);
        $this->vestido->update(['estado' => 'entregada', 'entregada_en' => '2026-09-14 16:00:00']);
        $this->actingAs($this->duena);

        $this->delete(route('fotos.eliminar', $foto), ['confirmacion' => 'si'])
            ->assertSessionHasErrors(['fotos' => 'Esta prenda ya fue entregada y no se puede modificar.']);
        $this->assertNotNull(Foto::find($foto->id));
    }

    public function test_rnf_25_no_se_elimina_una_foto_de_otro_negocio(): void
    {
        $deOtroTaller = Foto::factory()->create();
        $this->actingAs($this->duena);

        $this->delete(route('fotos.eliminar', $deOtroTaller), ['confirmacion' => 'si'])->assertNotFound();
        $this->assertNotNull(Foto::find($deOtroTaller->id));
    }

    private function foto(int $posicion): Foto
    {
        $foto = Foto::factory()->create(['prenda_id' => $this->vestido->id, 'posicion' => $posicion]);
        Storage::disk('privado')->put($foto->ruta, 'una foto');

        return $foto;
    }
}
