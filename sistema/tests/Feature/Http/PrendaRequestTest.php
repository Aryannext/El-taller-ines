<?php

namespace Tests\Feature\Http;

use App\Http\Solicitudes\PrendaRequest;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * RN-10 · Datos obligatorios de una prenda. Las mismas reglas valida cada prenda de una orden.
 */
class PrendaRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_rn_10_datos_obligatorios_de_una_prenda(): void
    {
        $duena = Usuario::factory()->create();
        $pantalon = TipoPrenda::factory()->create(['negocio_id' => $duena->negocio_id, 'nombre' => 'Pantalón']);
        $this->actingAs($duena);

        $completa = ['tipo_prenda_id' => $pantalon->id, 'descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => '15000'];
        $this->assertSame([], $this->errores($completa));

        $this->assertSame(['descripcion_arreglo' => ['Escribe qué arreglo lleva la prenda.']], $this->errores([...$completa, 'descripcion_arreglo' => '']));
        $this->assertSame(['tipo_prenda_id' => ['Elige el tipo de prenda.']], $this->errores([...$completa, 'tipo_prenda_id' => '']));
        $this->assertSame(['precio' => ['Escribe el precio del arreglo.']], $this->errores([...$completa, 'precio' => '']));
    }

    public function test_rn_11_el_precio_con_centavos_no_es_un_entero(): void
    {
        $duena = Usuario::factory()->create();
        $pantalon = TipoPrenda::factory()->create(['negocio_id' => $duena->negocio_id]);
        $this->actingAs($duena);

        $prenda = PrendaRequest::normalizar(['tipo_prenda_id' => $pantalon->id, 'descripcion_arreglo' => 'Subir basta', 'precio' => '$15.000,50']);

        $this->assertSame(['precio' => ['Escribe el precio en pesos, sin centavos.']], $this->errores($prenda));
        $this->assertSame('15000', PrendaRequest::normalizar(['precio' => '$15.000'])['precio']);
    }

    public function test_rn_43_un_tipo_desactivado_o_de_otro_negocio_no_se_puede_elegir(): void
    {
        $duena = Usuario::factory()->create();
        $chaqueta = TipoPrenda::factory()->create(['negocio_id' => $duena->negocio_id, 'activo' => false]);
        $deOtroNegocio = TipoPrenda::factory()->create();
        $this->actingAs($duena);

        foreach ([$chaqueta->id, $deOtroNegocio->id, 'no-es-un-tipo'] as $tipo) {
            $this->assertSame(
                ['tipo_prenda_id' => ['Elige un tipo de prenda de la lista.']],
                $this->errores(['tipo_prenda_id' => $tipo, 'descripcion_arreglo' => 'Ajustar', 'precio' => '10000']),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, list<string>>
     */
    private function errores(array $datos): array
    {
        return Validator::make($datos, PrendaRequest::reglas(), PrendaRequest::mensajes())->errors()->toArray();
    }
}
