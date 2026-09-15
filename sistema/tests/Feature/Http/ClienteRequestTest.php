<?php

namespace Tests\Feature\Http;

use App\Modelos\Cliente;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RN-02 · Datos mínimos de un cliente.
 */
class ClienteRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Usuario::factory()->create());
    }

    public function test_rn_02_datos_minimos_de_un_cliente(): void
    {
        $this->post(route('clientes.guardar'), ['nombre' => 'Luis Pardo'])
            ->assertSessionHasErrors(['celular' => 'Escribe el celular del cliente.']);

        // Solo espacios no es un nombre
        $this->post(route('clientes.guardar'), ['nombre' => '   ', 'celular' => '3001112233'])
            ->assertSessionHasErrors(['nombre' => 'Escribe el nombre del cliente.']);

        $this->assertSame(0, Cliente::count());
    }

    public function test_rn_02_el_nombre_cabe_en_su_columna(): void
    {
        $this->post(route('clientes.guardar'), ['nombre' => str_repeat('a', 121), 'celular' => '3001112233'])
            ->assertSessionHasErrors(['nombre' => 'El nombre puede tener hasta 120 caracteres.']);

        $this->assertSame(0, Cliente::count());
    }
}
