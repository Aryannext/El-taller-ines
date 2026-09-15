<?php

namespace Tests\Feature\Aislamiento;

use App\Modelos\Cliente;
use App\Modelos\Foto;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\Usuario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Ningún negocio ve ni modifica los datos de otro (RN-01, RNF-22, HT-03).
 * Los datos de los dos negocios se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class AislamientoEntreNegociosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rutas con parámetros que no entregan datos de un negocio, con el motivo por el que no necesitan caso.
     *
     * @var array<string, string>
     */
    private const RUTAS_SIN_DATOS_DE_NEGOCIO = [];

    private Usuario $usuariaDelNegocioA;

    private Usuario $usuariaDelNegocioB;

    private Cliente $martaDelNegocioA;

    private Orden $ordenDelNegocioA;

    private Foto $fotoDelNegocioA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->martaDelNegocioA = Cliente::factory()->create(['nombre' => 'Marta Rincón', 'celular' => '3104567890']);
        $this->ordenDelNegocioA = Orden::factory()->create(['cliente_id' => $this->martaDelNegocioA->id, 'numero' => 42]);
        $this->fotoDelNegocioA = Foto::factory()->create(['prenda_id' => Prenda::factory()->create(['orden_id' => $this->ordenDelNegocioA->id])->id]);
        $this->usuariaDelNegocioA = Usuario::factory()->create(['negocio_id' => $this->martaDelNegocioA->negocio_id]);
        $this->usuariaDelNegocioB = Usuario::factory()->create();
        Cliente::factory()->create(['negocio_id' => $this->usuariaDelNegocioB->negocio_id, 'nombre' => 'Luis Pardo']);
    }

    public function test_rn_01_la_informacion_pertenece_a_un_negocio(): void
    {
        $this->actingAs($this->usuariaDelNegocioB);
        $this->assertCount(0, Cliente::where('nombre', 'like', '%Marta%')->get());

        $this->actingAs($this->usuariaDelNegocioA);
        $this->assertCount(1, Cliente::where('nombre', 'like', '%Marta%')->get());
    }

    public function test_rn_01_un_registro_de_otro_negocio_responde_como_si_no_existiera(): void
    {
        $this->actingAs($this->usuariaDelNegocioB);

        $this->assertNull(Cliente::find($this->martaDelNegocioA->id));
        $this->expectException(ModelNotFoundException::class);
        Cliente::findOrFail($this->martaDelNegocioA->id);
    }

    public function test_rn_01_al_crear_se_asigna_el_negocio_de_la_sesion(): void
    {
        $this->actingAs($this->usuariaDelNegocioB);

        // negocio_id no se puede asignar desde afuera: se descarta y manda la sesión
        $laura = Cliente::create([
            'nombre' => 'Laura Rincón',
            'celular' => '3104567890',
            'negocio_id' => $this->usuariaDelNegocioA->negocio_id,
        ]);

        $this->assertSame($this->usuariaDelNegocioB->negocio_id, $laura->negocio_id);
    }

    public function test_rn_01_una_ruta_web_sin_sesion_no_ve_datos(): void
    {
        Route::get('/prueba-de-aislamiento', fn () => ['clientes' => Cliente::count()]);

        $this->get('/prueba-de-aislamiento')->assertExactJson(['clientes' => 0]);
    }

    public function test_rn_01_la_cola_y_la_consola_no_filtran(): void
    {
        $this->assertSame(2, Cliente::count());
    }

    public function test_rnf_22_toda_ruta_con_parametros_tiene_su_caso_de_aislamiento(): void
    {
        $sinCaso = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta) => $ruta->parameterNames() !== [])
            ->map(fn ($ruta) => $ruta->getName() ?? $ruta->uri())
            ->reject(fn ($nombre) => array_key_exists($nombre, $this->casos())
                || array_key_exists($nombre, self::RUTAS_SIN_DATOS_DE_NEGOCIO))
            ->values()
            ->all();

        $this->assertSame([], $sinCaso, 'Agrega a casos() cómo pedir estas rutas con un dato de otro negocio.');
    }

    public function test_rnf_22_ninguna_ruta_entrega_datos_de_otro_negocio(): void
    {
        $this->actingAs($this->usuariaDelNegocioB);
        $obtenidos = [];

        foreach ($this->casos() as $nombre => [$metodo, $direccion]) {
            $obtenidos[$nombre] = $this->call($metodo, $direccion)->getStatusCode();
        }

        $this->assertSame(array_fill_keys(array_keys($this->casos()), 404), $obtenidos);
        // Pedir una ruta que modifica tampoco cambia el dato del otro negocio
        $this->assertSame('Marta Rincón', $this->martaDelNegocioA->fresh()->nombre);
    }

    /**
     * Cómo pedir cada ruta con parámetros usando datos del negocio A, con la sesión del negocio B.
     * Cada historia que agrega una ruta con parámetros suma aquí su caso (HT-03).
     *
     * @return array<string, array{0: string, 1: string}>
     */
    private function casos(): array
    {
        return [
            'clientes.ficha' => ['GET', route('clientes.ficha', $this->martaDelNegocioA)],
            'clientes.editar' => ['GET', route('clientes.editar', $this->martaDelNegocioA)],
            'clientes.corregir' => ['PUT', route('clientes.corregir', $this->martaDelNegocioA)],
            'ordenes.guardada' => ['GET', route('ordenes.guardada', $this->ordenDelNegocioA)],
            'ordenes.detalle' => ['GET', route('ordenes.detalle', $this->ordenDelNegocioA)],
            // RNF-25: la foto no guarda su negocio; se busca a través de su orden
            'fotos.mostrar' => ['GET', route('fotos.mostrar', $this->fotoDelNegocioA)],
        ];
    }
}
