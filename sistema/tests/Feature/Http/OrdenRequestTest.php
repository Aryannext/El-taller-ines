<?php

namespace Tests\Feature\Http;

use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * RN-07 · La entrega no puede ser antes de la recepción.
 */
class OrdenRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_rn_07_la_entrega_no_puede_ser_antes_de_la_recepcion(): void
    {
        $duena = Usuario::factory()->create();
        $marta = Cliente::factory()->create(['negocio_id' => $duena->negocio_id]);
        $pantalon = TipoPrenda::factory()->create(['negocio_id' => $duena->negocio_id, 'nombre' => 'Pantalón']);
        $this->actingAs($duena);
        // Orden recibida el 14 de septiembre
        $this->fijarReloj('2026-09-14 18:30:00');

        $orden = fn (string $entrega) => $this->post(route('ordenes.guardar'), [
            'token_formulario' => (string) Str::uuid(),
            'cliente_id' => $marta->id,
            'fecha_entrega_acordada' => $entrega,
            'prendas' => [['tipo_prenda_id' => $pantalon->id, 'descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => '15000']],
        ]);

        $orden('2026-09-13')->assertSessionHasErrors(['fecha_entrega_acordada' => 'La entrega no puede ser antes de la fecha de recepción.']);
        $orden('2026-09-14')->assertSessionHasNoErrors();
        $orden('2026-09-20')->assertSessionHasNoErrors();

        $this->assertSame(2, Orden::count());
    }
}
