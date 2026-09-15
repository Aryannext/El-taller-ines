<?php

namespace Tests\Feature\Http;

use App\Http\Solicitudes\PagoRequest;
use App\Modelos\MetodoPago;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * RN-25 · Datos de un pago: valor entero mayor que cero y un método activo del negocio.
 */
class PagoRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_rn_25_datos_de_un_pago(): void
    {
        $duena = Usuario::factory()->create();
        $efectivo = MetodoPago::factory()->create(['negocio_id' => $duena->negocio_id, 'nombre' => 'Efectivo']);
        $desactivado = MetodoPago::factory()->create(['negocio_id' => $duena->negocio_id, 'nombre' => 'Daviplata', 'activo' => false]);
        // Los datos del otro negocio se crean antes de iniciar sesión
        $deOtroNegocio = MetodoPago::factory()->create(['nombre' => 'Nequi']);
        $this->actingAs($duena);

        // Abono de $10.000 en efectivo: se acepta
        $completo = ['token_formulario' => (string) Str::uuid(), 'valor' => '10000', 'metodo_pago_id' => (string) $efectivo->id];
        $this->assertSame([], $this->errores($completo));

        // Un pago de $0 se rechaza, igual que uno sin valor o con centavos
        $this->assertSame(['valor' => ['El valor debe ser mayor que cero.']], $this->errores([...$completo, 'valor' => '0']));
        $this->assertSame(['valor' => ['Escribe el valor del pago.']], $this->errores([...$completo, 'valor' => '']));
        $this->assertSame(['valor' => ['Escribe el valor en pesos, sin centavos.']], $this->errores([...$completo, 'valor' => '10000,50']));

        // El método es obligatorio y tiene que ser uno activo de este negocio (RN-01)
        $this->assertSame(['metodo_pago_id' => ['Elige cómo pagó.']], $this->errores([...$completo, 'metodo_pago_id' => '']));
        foreach ([$desactivado->id, $deOtroNegocio->id, 'efectivo'] as $metodo) {
            $this->assertSame(['metodo_pago_id' => ['Elige cómo pagó.']], $this->errores([...$completo, 'metodo_pago_id' => (string) $metodo]));
        }

        $this->assertSame(
            ['token_formulario' => ['La página se desactualizó. Vuelve a abrir el formulario.']],
            $this->errores([...$completo, 'token_formulario' => 'no-es-un-uuid']),
        );
    }

    /**
     * @param  array<string, string>  $datos
     * @return array<string, list<string>>
     */
    private function errores(array $datos): array
    {
        $solicitud = new PagoRequest;

        return Validator::make($datos, $solicitud->rules(), $solicitud->messages())->errors()->toArray();
    }
}
