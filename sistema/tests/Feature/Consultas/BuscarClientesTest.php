<?php

namespace Tests\Feature\Consultas;

use App\Modelos\Cliente;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-04 · Buscar un cliente.
 */
class BuscarClientesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        foreach ([['María Gómez', '3012223344'], ['Mariana López', '3174008821'], ['Marta Rincón', '3104567890']] as [$nombre, $celular]) {
            Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => $nombre, 'celular' => $celular]);
        }
        // Los datos del otro negocio se crean antes de iniciar sesión
        Cliente::factory()->create(['nombre' => 'Marta Pérez', 'celular' => '3159998877']);
        $this->actingAs($this->duena);
    }

    public function test_ca_04_1_por_nombre_sin_tildes(): void
    {
        $this->get(route('clientes.buscar', ['q' => 'maria']))
            ->assertOk()
            ->assertSee('María Gómez')
            ->assertSee('Mariana López')
            ->assertDontSee('Marta Rincón')
            ->assertSee('2 clientes encontrados');
    }

    public function test_ca_04_2_por_celular(): void
    {
        $this->get(route('clientes.buscar', ['q' => '3104567890']))
            ->assertSee('Marta Rincón')
            ->assertDontSee('María Gómez');

        // Con espacios, como se ve en la pantalla
        $this->get(route('clientes.buscar', ['q' => '310 456 7890']))
            ->assertSee('Marta Rincón')
            ->assertSee('1 cliente encontrado');
    }

    public function test_ca_04_3_sin_resultados(): void
    {
        $this->get(route('clientes.buscar', ['q' => 'pedro']))
            ->assertOk()
            ->assertSee('No hay clientes con «pedro».')
            ->assertSee(route('clientes.nuevo'))
            ->assertDontSee('María Gómez');
    }

    public function test_ca_04_4_solo_mi_negocio(): void
    {
        $this->get(route('clientes.buscar', ['q' => 'marta']))
            ->assertSee('Marta Rincón')
            ->assertDontSee('Marta Pérez');
    }

    public function test_rnf_23_una_busqueda_con_comodines_o_inyeccion_no_devuelve_de_mas(): void
    {
        foreach (['%', '_', "' OR 1=1 --"] as $busqueda) {
            $this->get(route('clientes.buscar', ['q' => $busqueda]))
                ->assertOk()
                ->assertDontSee('María Gómez')
                ->assertDontSee('Marta Rincón');
        }
    }

    public function test_sin_busqueda_muestra_todos_los_clientes_del_negocio_por_nombre(): void
    {
        $this->get(route('clientes.buscar'))
            ->assertSeeInOrder(['María Gómez', 'Mariana López', 'Marta Rincón'])
            ->assertSee('3 clientes')
            ->assertDontSee('Marta Pérez');
    }
}
