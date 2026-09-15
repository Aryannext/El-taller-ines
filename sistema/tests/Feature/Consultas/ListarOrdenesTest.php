<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\ListarOrdenes;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-15 · Listar y buscar órdenes. Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class ListarOrdenesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $camisa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $this->marta = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Marta Rincón']);
        $this->camisa = TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Camisa']);
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_15_1_filtrar_por_estado(): void
    {
        $this->orden(41, ['pendiente']);
        $this->orden(42, ['terminada'], ['lista_en' => '2026-09-10 16:00:00']);
        $this->orden(43, ['entregada']);
        $this->orden(44, ['terminada', 'terminada'], ['lista_en' => '2026-09-13 09:40:00']);
        $this->orden(45, ['pendiente'], ['cancelada_en' => '2026-09-12 11:00:00']);
        $this->actingAs($this->duena);

        $pagina = $this->get(route('ordenes.listar', ['estado' => 'lista']))->assertOk();

        // Solo las listas, la más reciente primero
        $pagina->assertSeeInOrder(['#0044', 'Camisa y Camisa', '· lista desde 13 sep 2026', '#0042', '· lista desde 10 sep 2026']);
        foreach (['#0041', '#0043', '#0045'] as $otra) {
            $pagina->assertDontSee($otra);
        }
        // La pestaña elegida es Listas, y los contadores cuentan En proceso y Listas
        $this->assertMatchesRegularExpression('/aria-current="page"\s*>\s*Listas\s*<span class="contador neutro">2<\/span>/', $pagina->getContent());
        $this->assertMatchesRegularExpression('/>\s*En proceso\s*<span class="contador neutro">1<\/span>/', $pagina->getContent());

        $this->get(route('ordenes.listar', ['estado' => 'cancelada']))->assertSee('#0045')->assertDontSee('#0041');
        $this->get(route('ordenes.listar', ['estado' => 'entregada']))->assertSee('#0043')->assertDontSee('#0042');
    }

    public function test_ca_15_2_buscar_por_numero(): void
    {
        $this->orden(42, ['pendiente']);
        $this->actingAs($this->duena);

        foreach (['42', '#0042', ' # 0042 '] as $busqueda) {
            $this->get(route('ordenes.listar', ['numero' => $busqueda]))->assertRedirect(route('ordenes.detalle', 42));
        }
    }

    public function test_ca_15_3_numero_inexistente(): void
    {
        $this->orden(42, ['pendiente']);
        // La #0050 existe, pero en otro taller (RN-01)
        Orden::factory()->create(['numero' => 50]);
        $this->actingAs($this->duena);

        $this->get(route('ordenes.listar', ['numero' => '#9999']))
            ->assertOk()
            ->assertSee('No hay una orden con el número #9999.')
            ->assertSee('value="#9999"', false)
            ->assertSee('#0042');

        $this->get(route('ordenes.listar', ['numero' => '50']))->assertOk()->assertSee('No hay una orden con el número #0050.');
        $this->get(route('ordenes.listar', ['numero' => 'marta']))->assertOk()->assertSee('Escribe solo el número de la bolsa, por ejemplo 42.');
    }

    public function test_rn_18_el_filtro_de_la_lista_coincide_con_el_estado_calculado(): void
    {
        // Combinaciones de prendas, incluidas las Devueltas, que no cuentan (RN-44)
        $combinaciones = [
            ['pendiente'], ['en_proceso', 'terminada'], ['pendiente', 'devuelta'],
            ['terminada'], ['terminada', 'entregada'], ['terminada', 'devuelta'],
            ['entregada'], ['entregada', 'devuelta'],
        ];
        foreach ($combinaciones as $i => $estados) {
            $this->orden($i + 1, $estados);
        }
        $this->orden(20, ['terminada'], ['cancelada_en' => '2026-09-12 11:00:00']);
        $this->actingAs($this->duena);

        foreach (EstadoDeOrden::cases() as $estado) {
            $filtradas = app(ListarOrdenes::class)->conEstado(Orden::query(), $estado)->orderBy('numero')->pluck('numero')->all();
            $calculadas = Orden::with('prendas')->orderBy('numero')->get()
                ->filter(fn (Orden $orden) => EstadoDeOrden::calcular($orden->prendas->pluck('estado'), $orden->cancelada_en !== null) === $estado)
                ->pluck('numero')->values()->all();

            $this->assertNotSame([], $calculadas, "Faltan datos de prueba para {$estado->value}");
            $this->assertSame($calculadas, $filtradas, "El filtro {$estado->value} no coincide con EstadoDeOrden::calcular");
        }
    }

    public function test_sin_filtro_muestra_en_proceso_de_la_entrega_mas_cercana_a_la_mas_lejana(): void
    {
        $this->orden(41, ['pendiente'], ['fecha_entrega_acordada' => '2026-09-19']);
        // Atrasada: se recibió el 5 y la entrega era el 12 (la entrega no puede ser antes de la recepción, RN-07)
        $this->orden(44, ['en_proceso'], ['recibida_en' => '2026-09-05 10:00:00', 'fecha_entrega_acordada' => '2026-09-12']);
        $this->orden(46, ['terminada']);
        // Una orden de otro taller no aparece
        Prenda::factory()->create(['orden_id' => Orden::factory()->create(['numero' => 47])->id]);
        $this->actingAs($this->duena);

        $this->get(route('ordenes.listar'))
            ->assertOk()
            ->assertSeeInOrder(['#0044', 'entrega 12 sep 2026', '#0041', 'entrega 19 sep 2026'])
            ->assertDontSee('#0046')
            ->assertDontSee('#0047');

        $this->get(route('ordenes.listar', ['estado' => 'cancelada']))->assertSee('No hay órdenes canceladas.');
    }

    /**
     * @param  list<string>  $estados
     * @param  array<string, mixed>  $atributos
     */
    private function orden(int $numero, array $estados, array $atributos = []): Orden
    {
        $orden = Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => $numero, ...$atributos]);

        foreach ($estados as $estado) {
            Prenda::factory()->create([
                'orden_id' => $orden->id,
                'tipo_prenda_id' => $this->camisa->id,
                'precio' => 8000,
                'estado' => $estado,
                'entregada_en' => $estado === 'entregada' ? '2026-09-12 17:45:00' : null,
                'devuelta_en' => $estado === 'devuelta' ? '2026-09-11 10:00:00' : null,
            ]);
        }

        return $orden;
    }
}
