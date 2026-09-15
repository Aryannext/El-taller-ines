<?php

namespace Tests\Feature\Clientes;

use App\Modelos\Cliente;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-03 · Registrar un cliente.
 */
class RegistrarClienteTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
    }

    public function test_ca_03_1_registro_correcto(): void
    {
        $respuesta = $this->actingAs($this->duena)
            ->post(route('clientes.guardar'), ['nombre' => 'Marta Rincón', 'celular' => '3104567890']);

        $marta = Cliente::where('nombre', 'Marta Rincón')->sole();
        $this->assertSame($this->duena->negocio_id, $marta->negocio_id);
        $this->assertSame('3104567890', $marta->celular);

        $respuesta->assertRedirect(route('clientes.ficha', $marta));
        $this->get(route('clientes.ficha', $marta))
            ->assertOk()
            ->assertSee('Marta Rincón')
            ->assertSee('310 456 7890');
    }

    public function test_ca_03_2_sin_celular(): void
    {
        $this->actingAs($this->duena)
            ->post(route('clientes.guardar'), ['nombre' => 'Marta Rincón'])
            ->assertSessionHasErrors(['celular' => 'Escribe el celular del cliente.']);

        $this->assertSame(0, Cliente::count());
    }

    public function test_ca_03_3_numero_fijo(): void
    {
        $this->actingAs($this->duena)
            ->post(route('clientes.guardar'), ['nombre' => 'Marta Rincón', 'celular' => '6014567890'])
            ->assertSessionHasErrors(['celular' => 'Escribe un celular colombiano de 10 dígitos que empiece por 3']);

        $this->assertSame(0, Cliente::count());
    }

    public function test_ca_03_4_celular_compartido(): void
    {
        $marta = Cliente::factory()->create([
            'negocio_id' => $this->duena->negocio_id,
            'nombre' => 'Marta Rincón',
            'celular' => '3104567890',
        ]);

        $this->actingAs($this->duena)
            ->post(route('clientes.guardar'), ['nombre' => 'Laura Rincón', 'celular' => '3104567890'])
            ->assertSessionHasNoErrors();

        $conEseCelular = Cliente::where('celular', '3104567890')->pluck('nombre', 'id');
        $this->assertCount(2, $conEseCelular);
        $this->assertSame('Marta Rincón', $conEseCelular[$marta->id]);
        $this->assertContains('Laura Rincón', $conEseCelular);
    }
}
