<?php

namespace Tests\Feature\Clientes;

use App\Dominio\Clientes\Celular;
use App\Modelos\Cliente;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * HU-06 · Corregir los datos de un cliente.
 */
class CorregirClienteTest extends TestCase
{
    use RefreshDatabase;

    private Cliente $marta;

    protected function setUp(): void
    {
        parent::setUp();

        $duena = Usuario::factory()->create();
        $this->marta = Cliente::factory()->create([
            'negocio_id' => $duena->negocio_id,
            'nombre' => 'Marta Rincón',
            'celular' => '3104567890',
        ]);
        $this->actingAs($duena);
    }

    public function test_ca_06_1_nuevo_celular(): void
    {
        $this->get(route('clientes.editar', $this->marta))
            ->assertOk()
            ->assertSee('value="Marta Rincón"', false)
            ->assertSee('value="3104567890"', false);

        $this->corregir('Marta Rincón', '3157654321')
            ->assertRedirect(route('clientes.ficha', $this->marta))
            ->assertSessionHasNoErrors();

        $this->assertSame('3157654321', $this->marta->fresh()->celular);
        // Es el número al que llegarán los avisos siguientes (M-03)
        $this->assertSame('573157654321', Celular::desde($this->marta->fresh()->celular)->enFormatoInternacional());
        $this->get(route('clientes.ficha', $this->marta))
            ->assertSee('315 765 4321')
            ->assertSee('Los datos de Marta Rincón quedaron actualizados.');
    }

    public function test_ca_06_2_nombre_vacio(): void
    {
        $this->corregir('', '3104567890')
            ->assertSessionHasErrors(['nombre' => 'Escribe el nombre del cliente.']);

        $this->assertSame('Marta Rincón', $this->marta->fresh()->nombre);
    }

    public function test_ca_06_3_celular_incompleto(): void
    {
        $this->corregir('Marta Rincón', '315765432')
            ->assertSessionHasErrors(['celular' => 'Escribe un celular colombiano de 10 dígitos que empiece por 3']);

        $this->assertSame('3104567890', $this->marta->fresh()->celular);
    }

    private function corregir(string $nombre, string $celular): TestResponse
    {
        return $this->from(route('clientes.editar', $this->marta))
            ->put(route('clientes.corregir', $this->marta), ['nombre' => $nombre, 'celular' => $celular]);
    }
}
