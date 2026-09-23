<?php

namespace Tests\Feature\Clientes;

use App\Http\Controladores\ClienteController;
use App\Modelos\Cliente;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_ca_10_1_cliente_nuevo(): void
    {
        // HU-10: la orden ya tiene dos prendas escritas y el cliente todavía no existe
        $tipo = TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Pantalón']);
        $this->actingAs($this->duena);
        $borrador = [
            'token_formulario' => (string) Str::uuid(),
            'fecha_entrega_acordada' => '2026-09-30',
            'prendas' => [
                ['tipo_prenda_id' => (string) $tipo->id, 'descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => '15.000'],
                ['tipo_prenda_id' => 'otro', 'tipo_otro' => 'Chaleco', 'descripcion_arreglo' => 'Cambiar botones', 'precio' => '6.000'],
            ],
        ];

        $this->post(route('clientes.desde-orden'), $borrador)->assertRedirect(route('clientes.nuevo'));

        // PT-04 avisa que lo escrito no se perdió
        $this->get(route('clientes.nuevo'))
            ->assertOk()
            ->assertSee('Lo que escribiste en la orden se conserva.')
            ->assertSee('name="desde" value="orden"', false);

        $this->post(route('clientes.guardar'), ['nombre' => 'Luis Pardo', 'celular' => '3001112233', 'desde' => 'orden'])
            ->assertSessionHasNoErrors();

        $luis = Cliente::where('nombre', 'Luis Pardo')->sole();
        $this->get(route('ordenes.nueva', ['cliente' => $luis->id]))
            ->assertOk()
            // Vuelve con Luis seleccionado y las dos prendas escritas (CA-10.1)
            ->assertSee('value="'.$luis->id.'" selected', false)
            ->assertSee('Subir basta 3 cm')
            ->assertSee('Cambiar botones')
            ->assertSee('2026-09-30')
            ->assertSee('Las fotos sí hay que volver a tomarlas');
    }

    public function test_ca_10_2_datos_invalidos(): void
    {
        $this->actingAs($this->duena);
        $borrador = [
            'token_formulario' => (string) Str::uuid(),
            'fecha_entrega_acordada' => '2026-09-30',
            'prendas' => [['tipo_prenda_id' => 'otro', 'tipo_otro' => 'Chaleco', 'descripcion_arreglo' => 'Cambiar botones', 'precio' => '6.000']],
        ];
        $this->post(route('clientes.desde-orden'), $borrador);

        // RN-03: el celular no sirve, así que el cliente no se registra
        $this->from(route('clientes.nuevo'))
            ->post(route('clientes.guardar'), ['nombre' => 'Luis Pardo', 'celular' => '123', 'desde' => 'orden'])
            ->assertRedirect(route('clientes.nuevo'))
            ->assertSessionHasErrors('celular');
        $this->assertSame(0, Cliente::count());

        // Y lo escrito en la orden sigue esperando
        $this->get(route('clientes.nuevo'))->assertSee('Lo que escribiste en la orden se conserva.');
        $this->get(route('ordenes.nueva'))->assertSee('Cambiar botones');
    }

    public function test_hu_10_lo_escrito_se_olvida_al_guardar_la_orden(): void
    {
        $tipo = TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Pantalón']);
        $this->actingAs($this->duena);
        $this->post(route('clientes.desde-orden'), ['fecha_entrega_acordada' => '2026-09-30']);
        $luis = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Luis Pardo']);

        $this->post(route('ordenes.guardar'), [
            'token_formulario' => (string) Str::uuid(),
            'cliente_id' => $luis->id,
            'fecha_entrega_acordada' => now()->addDays(3)->format('Y-m-d'),
            'prendas' => [['tipo_prenda_id' => (string) $tipo->id, 'descripcion_arreglo' => 'Subir basta', 'precio' => '15000']],
        ])->assertSessionHasNoErrors();

        $this->assertNull(session(ClienteController::ORDEN_EN_CURSO));
        $this->get(route('ordenes.nueva'))->assertDontSee('Las fotos sí hay que volver a tomarlas');
    }
}
