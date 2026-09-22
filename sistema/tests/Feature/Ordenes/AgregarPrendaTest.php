<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HU-11 · Agregar una prenda a una orden que ya existe. Los datos se crean antes de iniciar sesión,
 * porque al crear se asigna el negocio de la sesión.
 */
class AgregarPrendaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $pantalon;

    private TipoPrenda $falda;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('privado');
        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón']);
        $this->pantalon = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Pantalón']);
        $this->falda = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Falda']);
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_11_1_orden_lista(): void
    {
        // La #0042 quedó lista el 12 con un pantalón terminado de $15.000, y su aviso todavía no sale
        $orden = $this->orden(42, ['lista_en' => '2026-09-12 16:00:00']);
        $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 15000, 'terminada');
        $avisoSinEnviar = Aviso::factory()->create(['orden_id' => $orden->id, 'ciclo_lista_en' => '2026-09-12 16:00:00', 'estado' => 'pendiente_asistido', 'canal' => null, 'resuelto_en' => null]);
        $this->actingAs($this->duena);

        $this->get(route('ordenes.detalle', $orden))
            ->assertSee('<a class="btn btn-texto alinear-inicio" href="'.route('prendas.nueva', $orden).'">', false);
        $this->get(route('prendas.nueva', $orden))
            ->assertOk()
            ->assertSeeInOrder(['Agregar prenda', '#0042 · Marta Rincón', 'La prenda queda Pendiente', 'volverá a En proceso', 'Tipo de prenda', 'Falda', 'Qué arreglo lleva', 'Precio', 'Fotos', 'Guardar prenda'])
            // Una sola prenda: sin «Quitar prenda» ni «Agregar otra prenda», y con los campos sin prefijo que espera PrendaRequest
            ->assertDontSee('data-quitar', false)
            ->assertDontSee('Agregar otra prenda')
            ->assertSee('name="descripcion_arreglo"', false);

        $this->post(route('prendas.agregar', $orden), [
            'tipo_prenda_id' => (string) $this->falda->id,
            'descripcion_arreglo' => 'Subir ruedo',
            'precio' => '$10.000',
            'fotos' => [UploadedFile::fake()->image('falda.jpg', 900, 1200)],
        ])->assertSessionHasNoErrors()->assertRedirect(route('ordenes.detalle', $orden));

        // La falda queda Pendiente, con su foto
        $falda = Prenda::where('orden_id', $orden->id)->where('tipo_prenda_id', $this->falda->id)->sole();
        $this->assertSame('pendiente', $falda->estado->value);
        $this->assertSame([1], $falda->fotos()->pluck('posicion')->all());
        Storage::disk('privado')->assertExists($falda->fotos()->sole()->ruta);

        // La orden vuelve a En proceso, vale $10.000 más y se borra la fecha en que había quedado lista (RN-18, RN-22)
        $detalle = app(DetalleDeOrden::class)->obtener($orden->fresh());
        $this->assertSame(EstadoDeOrden::EnProceso, $detalle['estado']);
        $this->assertSame(25000, $detalle['valor']->valor());
        $this->assertNull($orden->fresh()->lista_en);

        // RN-39: ya no hay por qué llamar a Marta
        $this->assertSame('descartado', $avisoSinEnviar->fresh()->estado);

        $this->get(route('ordenes.detalle', $orden))
            ->assertSeeInOrder(['Listo: «Subir ruedo» quedó en la orden.', 'En proceso', 'Pantalón', 'Falda', 'Subir ruedo', 'Pendiente', '$10.000', 'Valor de la orden', '$25.000'])
            ->assertDontSee('Quedó lista');
    }

    public function test_ca_11_2_orden_entregada(): void
    {
        $orden = $this->orden(40);
        $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 15000, 'entregada', '2026-09-13 11:00:00');
        $this->actingAs($this->duena);

        $this->get(route('ordenes.detalle', $orden))->assertDontSee(route('prendas.nueva', $orden));
        $this->get(route('prendas.nueva', $orden))
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => 'Solo se pueden agregar prendas a una orden en proceso o lista para entregar.']);

        // Aunque el formulario llegue igual, la prenda no se guarda
        $this->post(route('prendas.agregar', $orden), $this->falda())
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => 'Solo se pueden agregar prendas a una orden en proceso o lista para entregar.']);
        $this->assertSame(1, $orden->prendas()->count());
    }

    public function test_ca_11_3_orden_cancelada(): void
    {
        $orden = $this->orden(41, ['cancelada_en' => '2026-09-13 09:00:00']);
        $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 15000, 'pendiente');
        $this->actingAs($this->duena);

        $this->get(route('ordenes.detalle', $orden))->assertDontSee(route('prendas.nueva', $orden));
        $this->get(route('prendas.nueva', $orden))
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => 'Esta orden está cancelada y no admite cambios.']);

        $this->post(route('prendas.agregar', $orden), $this->falda())
            ->assertSessionHasErrors(['prenda' => 'Esta orden está cancelada y no admite cambios.']);
        $this->assertSame(1, $orden->prendas()->count());
    }

    public function test_en_proceso_admite_otro_tipo_y_no_toca_la_orden_si_los_datos_fallan(): void
    {
        $orden = $this->orden(43);
        $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 15000, 'en_proceso');
        $this->actingAs($this->duena);
        $formulario = route('prendas.nueva', $orden);

        // RN-10 y RN-11: vuelve al formulario con los mensajes junto a cada campo y lo escrito
        $this->from($formulario)->post(route('prendas.agregar', $orden), ['tipo_prenda_id' => 'otro', 'descripcion_arreglo' => '', 'precio' => '15.000,50'])
            ->assertRedirect($formulario)
            ->assertSessionHasErrors([
                'tipo_otro' => 'Escribe qué tipo de prenda es.',
                'descripcion_arreglo' => 'Escribe qué arreglo lleva la prenda.',
                'precio' => 'Escribe el precio en pesos, sin centavos.',
            ]);
        $this->followingRedirects()->from($formulario)->post(route('prendas.agregar', $orden), ['tipo_prenda_id' => 'otro', 'descripcion_arreglo' => 'Entallar', 'precio' => '0'])
            ->assertSeeInOrder(['Agregar prenda', '¿Qué prenda es?', 'Escribe qué tipo de prenda es.', 'Entallar', 'El precio debe ser mayor que cero.']);
        $this->assertSame(1, $orden->prendas()->count());

        // RN-43: el tipo escrito con «Otro» queda en la lista para las próximas prendas
        $this->post(route('prendas.agregar', $orden), ['tipo_prenda_id' => 'otro', 'tipo_otro' => 'Chaleco', 'descripcion_arreglo' => 'Cambiar botones', 'precio' => '6000'])
            ->assertSessionHasNoErrors();
        $chaleco = TipoPrenda::where('nombre', 'Chaleco')->sole();
        $this->assertTrue($orden->prendas()->where('tipo_prenda_id', $chaleco->id)->where('estado', 'pendiente')->exists());
        $this->assertSame(EstadoDeOrden::EnProceso, app(DetalleDeOrden::class)->obtener($orden->fresh())['estado']);
    }

    public function test_rnf_25_no_se_agrega_a_una_orden_de_otro_negocio(): void
    {
        $deOtroTaller = Orden::factory()->create();
        $this->actingAs($this->duena);

        $this->get(route('prendas.nueva', $deOtroTaller))->assertNotFound();
        $this->post(route('prendas.agregar', $deOtroTaller), $this->falda())->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function orden(int $numero, array $atributos = []): Orden
    {
        return Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => $numero, 'recibida_en' => '2026-09-07 09:15:00', ...$atributos]);
    }

    private function prenda(Orden $orden, TipoPrenda $tipo, string $arreglo, int $precio, string $estado, ?string $entregadaEn = null): Prenda
    {
        return Prenda::factory()->create([
            'orden_id' => $orden->id,
            'tipo_prenda_id' => $tipo->id,
            'descripcion_arreglo' => $arreglo,
            'precio' => $precio,
            'estado' => $estado,
            'entregada_en' => $entregadaEn,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function falda(): array
    {
        return ['tipo_prenda_id' => (string) $this->falda->id, 'descripcion_arreglo' => 'Subir ruedo', 'precio' => '10000'];
    }
}
