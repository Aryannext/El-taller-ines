<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Ordenes\ResolverTipoDePrenda;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Database\Seeders\NegocioInicialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * HU-09 · Escribir un tipo de prenda que no está en la lista (RN-43).
 */
class ResolverTipoDePrendaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $this->marta = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Marta Rincón']);
        foreach (NegocioInicialSeeder::TIPOS_DE_PRENDA as $nombre) {
            TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => $nombre]);
        }

        $this->actingAs($this->duena);
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_09_1_tipo_nuevo(): void
    {
        $this->registrarConOtro('Overol')->assertSessionHasNoErrors();

        $this->assertSame('Overol', Prenda::sole()->tipoPrenda->nombre);
        // La siguiente prenda que se registre ya lo tiene en la lista, antes de «Otro»
        $this->get(route('ordenes.nueva'))
            ->assertOk()
            ->assertSeeInOrder(['Chaqueta', 'Overol', 'Otro…']);
    }

    public function test_ca_09_2_tipo_repetido(): void
    {
        $overol = TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Overol']);

        $this->registrarConOtro('overol')->assertSessionHasNoErrors();

        $this->assertSame($overol->id, Prenda::sole()->tipo_prenda_id);
        $this->assertSame(1, TipoPrenda::where('nombre', 'Overol')->count());
        // La página tiene la lista de la prenda y la de la plantilla «Agregar otra prenda»: Overol sale tantas veces como Pantalón
        $pagina = $this->get(route('ordenes.nueva'))->getContent();
        $this->assertSame(substr_count($pagina, '>Pantalón</option>'), substr_count($pagina, '>Overol</option>'));
    }

    public function test_ca_09_3_otro_vacio(): void
    {
        $this->registrarConOtro('')
            ->assertSessionHasErrors(['prendas.0.tipo_otro' => 'Escribe qué tipo de prenda es.']);

        $this->assertSame(0, Orden::count());
        $this->assertSame(count(NegocioInicialSeeder::TIPOS_DE_PRENDA), TipoPrenda::count());

        // Al volver al formulario, «Otro» sigue elegido y el campo con el mensaje está a la vista
        $formulario = $this->followingRedirects()->registrarConOtro('   ')->assertOk();
        $formulario->assertSee('Escribe qué tipo de prenda es.');
        $formulario->assertSee('<option value="otro" selected', false);
        // Solo el campo de la prenda 0: el de la plantilla «Agregar otra prenda» siempre empieza oculto
        $this->assertMatchesRegularExpression('/data-otro\s*>\s*<label for="prenda-0-tipo_otro"/', $formulario->getContent());
    }

    public function test_rn_43_tipo_de_prenda_escrito_por_la_usuaria(): void
    {
        $resolver = app(ResolverTipoDePrenda::class);
        $negocio = $this->duena->negocio_id;

        $overol = $resolver->resolver($negocio, 'otro', 'overol');
        $this->assertSame('Overol', $overol->nombre);
        $this->assertTrue($overol->activo);

        // Otro día escribe lo mismo con otras mayúsculas o tildes: se usa el que ya existe
        $this->assertSame($overol->id, $resolver->resolver($negocio, 'otro', '  OVERÓL ')->id);
        $this->assertSame(TipoPrenda::where('nombre', 'Pantalón')->sole()->id, $resolver->resolver($negocio, 'otro', 'pantalon')->id);

        $this->assertSame(count(NegocioInicialSeeder::TIPOS_DE_PRENDA) + 1, TipoPrenda::count());

        try {
            $resolver->resolver($negocio, 'otro', ' ');
            $this->fail('Se aceptó «Otro» sin escribir el tipo');
        } catch (ReglaIncumplida $error) {
            $this->assertSame('RN-43', $error->regla);
        }
    }

    private function registrarConOtro(string $tipo): TestResponse
    {
        return $this->from(route('ordenes.nueva'))->post(route('ordenes.guardar'), [
            'token_formulario' => (string) Str::uuid(),
            'cliente_id' => $this->marta->id,
            'fecha_entrega_acordada' => '2026-09-20',
            'prendas' => [
                ['tipo_prenda_id' => 'otro', 'tipo_otro' => $tipo, 'descripcion_arreglo' => 'Cambiar la cremallera', 'precio' => '18.000'],
            ],
        ]);
    }
}
