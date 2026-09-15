<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Ordenes\CambiarEstadoDePrenda;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\OrdenQuedoLista;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * HU-20 · Actualizar el estado de una prenda. Hoy es el 14 de septiembre de 2026 a las 4:00 p. m.
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class CambiarEstadoDePrendaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    /** @var array<string, int> */
    private array $tipos = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $this->marta = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Marta Rincón']);
        $this->fijarReloj('2026-09-14 16:00:00');
    }

    public function test_ca_20_1_avanzar(): void
    {
        [$orden, [$pantalon]] = $this->orden(42, [['Pantalón', 'Subir basta 3 cm', 'pendiente']]);
        $this->actingAs($this->duena);

        $this->get(route('prendas.acciones', [$orden, $pantalon]))
            ->assertOk()
            ->assertSeeInOrder(['Pantalón', 'Subir basta 3 cm', '¿En qué va?', 'Pendiente', 'Estado actual', 'value="en_proceso"', 'En proceso', 'value="terminada"', 'Terminada', 'Corregir arreglo, precio o fotos'], false);

        $this->cambiar($orden, $pantalon, 'en_proceso')
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHas('exito', 'Listo: «Subir basta 3 cm» ahora está en proceso.');

        $this->assertSame(EstadoDePrenda::EnProceso, $pantalon->fresh()->estado);
        $this->get(route('ordenes.detalle', $orden))->assertSeeInOrder(['Listo: «Subir basta 3 cm» ahora está en proceso.', 'Pantalón', 'En proceso']);
    }

    public function test_ca_20_2_cambio_no_permitido(): void
    {
        [$orden, [$vestido]] = $this->orden(42, [['Vestido', 'Ajustar la cintura', 'en_proceso']]);
        $this->actingAs($this->duena);

        // Las opciones: solo Terminada. Entregada no aparece
        $this->get(route('prendas.acciones', [$orden, $vestido]))
            ->assertOk()
            ->assertSee('value="terminada"', false)
            ->assertDontSee('value="entregada"', false)
            ->assertDontSee('value="pendiente"', false)
            ->assertSee('Las prendas terminadas se entregan con el botón Entregar de la orden.');

        // Aunque alguien arme el envío a mano, no pasa
        $this->cambiar($orden, $vestido, 'entregada')
            ->assertRedirect(route('prendas.acciones', [$orden, $vestido]))
            ->assertSessionHasErrors(['estado' => 'Elige uno de los estados que se muestran.']);
        $this->cambiar($orden, $vestido, 'pendiente')
            ->assertSessionHasErrors(['estado' => 'Elige uno de los estados que se muestran.']);
        $this->assertSame(EstadoDePrenda::EnProceso, $vestido->fresh()->estado);

        // Ni llamando directamente al caso de uso
        $this->assertRegla('RN-13', fn () => app(CambiarEstadoDePrenda::class)->ejecutar($vestido, EstadoDePrenda::Entregada));
    }

    public function test_ca_20_3_la_orden_queda_lista_sola(): void
    {
        Event::fake([OrdenQuedoLista::class]);
        [$orden, [, , $tercera]] = $this->orden(42, [
            ['Pantalón', 'Subir basta 3 cm', 'terminada'],
            ['Camisa', 'Entallar los costados', 'terminada'],
            ['Camisa', 'Entallar y acortar mangas', 'en_proceso'],
        ]);
        $this->actingAs($this->duena);

        $this->cambiar($orden, $tercera, 'terminada')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito', 'La orden quedó lista para entregar.');

        $orden->refresh();
        $this->assertSame(EstadoDeOrden::ListaParaEntregar, app(DetalleDeOrden::class)->obtener($orden)['estado']);
        $this->assertSame('2026-09-14 16:00', $orden->lista_en?->format('Y-m-d H:i'));
        // Sale una sola vez el evento del que dependerá el aviso al cliente (HU-28)
        Event::assertDispatchedTimes(OrdenQuedoLista::class, 1);
        Event::assertDispatched(OrdenQuedoLista::class, fn (OrdenQuedoLista $evento) => $evento->ordenId === $orden->id
            && $evento->listaEn->format('Y-m-d H:i') === '2026-09-14 16:00');

        $this->get(route('ordenes.detalle', $orden))
            ->assertSeeInOrder(['La orden quedó lista para entregar.', 'Lista para entregar', 'Quedó lista', '14 sep 2026 · 4:00 p. m.']);
    }

    public function test_ca_20_4_retoque(): void
    {
        Event::fake([OrdenQuedoLista::class]);
        [$orden, [$pantalon]] = $this->orden(42, [
            ['Pantalón', 'Subir basta 3 cm', 'terminada'],
            ['Camisa', 'Entallar los costados', 'terminada'],
        ], ['lista_en' => '2026-09-13 10:00:00']);
        $this->actingAs($this->duena);

        // A Marta le quedó larga la basta al medírselo: la opción explica el retoque
        $this->get(route('prendas.acciones', [$orden, $pantalon]))
            ->assertSeeInOrder(['Terminada', 'Estado actual', 'value="en_proceso"', 'Al medírsela le falta algo: vuelve al arreglo'], false)
            ->assertDontSee('value="terminada"', false);

        $this->cambiar($orden, $pantalon, 'en_proceso')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito', 'Listo: «Subir basta 3 cm» ahora está en proceso.');

        $orden->refresh();
        $this->assertSame(EstadoDeOrden::EnProceso, app(DetalleDeOrden::class)->obtener($orden)['estado']);
        $this->assertNull($orden->lista_en);
        Event::assertNotDispatched(OrdenQuedoLista::class);
        $this->get(route('ordenes.detalle', $orden))->assertDontSee('Quedó lista');
    }

    public function test_ca_20_5_sin_cambio_manual(): void
    {
        [$orden] = $this->orden(42, [['Pantalón', 'Subir basta 3 cm', 'en_proceso']]);
        $this->actingAs($this->duena);

        // En la orden no hay cómo marcarla lista, entregada o en proceso: solo cada prenda
        $detalle = $this->get(route('ordenes.detalle', $orden))->assertOk();
        $this->assertStringNotContainsString('/ordenes/42/estado', $detalle->getContent());
        $this->assertDoesNotMatchRegularExpression('/name="estado"/', $detalle->getContent());

        foreach (['post', 'put', 'patch'] as $metodo) {
            $this->assertContains($this->{$metodo}('/ordenes/42/estado', ['estado' => 'lista'])->getStatusCode(), [404, 405]);
        }
        $this->assertNull($orden->fresh()->lista_en);
    }

    public function test_ca_20_6_orden_cancelada(): void
    {
        [$orden, [$camisa]] = $this->orden(41, [['Camisa', 'Acortar mangas', 'pendiente']], ['cancelada_en' => '2026-09-12 11:00:00']);
        $this->actingAs($this->duena);
        $motivo = 'Esta orden está cancelada y no admite cambios.';

        $this->get(route('prendas.acciones', [$orden, $camisa]))
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => $motivo]);
        $this->cambiar($orden, $camisa, 'en_proceso')
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => $motivo]);

        $this->assertSame(EstadoDePrenda::Pendiente, $camisa->fresh()->estado);
        $this->get(route('ordenes.detalle', $orden))->assertDontSee(route('prendas.acciones', [$orden, $camisa]));
    }

    public function test_rn_15_una_prenda_entregada_no_cambia_de_estado(): void
    {
        [$orden, [$entregada, $pendiente]] = $this->orden(40, [
            ['Camisa', 'Entallar', 'entregada'],
            ['Falda', 'Subir ruedo', 'pendiente'],
        ]);
        $this->actingAs($this->duena);

        $this->cambiar($orden, $entregada, 'en_proceso')
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => 'Esta prenda ya fue entregada y no se puede modificar.']);

        $this->assertSame(EstadoDePrenda::Entregada, $entregada->fresh()->estado);
        // El detalle solo ofrece cambiar la que todavía se puede
        $this->get(route('ordenes.detalle', $orden))
            ->assertDontSee(route('prendas.acciones', [$orden, $entregada]))
            ->assertSee(route('prendas.acciones', [$orden, $pendiente]));
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $prendas  tipo, arreglo y estado de cada prenda
     * @param  array<string, mixed>  $atributos
     * @return array{0: Orden, 1: list<Prenda>}
     */
    private function orden(int $numero, array $prendas, array $atributos = []): array
    {
        $orden = Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => $numero, 'recibida_en' => '2026-09-07 09:15:00', ...$atributos]);

        $creadas = [];
        foreach ($prendas as [$tipo, $arreglo, $estado]) {
            $this->tipos[$tipo] ??= TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => $tipo])->id;
            $creadas[] = Prenda::factory()->create([
                'orden_id' => $orden->id,
                'tipo_prenda_id' => $this->tipos[$tipo],
                'descripcion_arreglo' => $arreglo,
                'estado' => $estado,
                'entregada_en' => $estado === 'entregada' ? '2026-09-10 11:20:00' : null,
            ]);
        }

        return [$orden, $creadas];
    }

    private function cambiar(Orden $orden, Prenda $prenda, string $estado): TestResponse
    {
        return $this->from(route('prendas.acciones', [$orden, $prenda]))
            ->post(route('prendas.cambiar-estado', [$orden, $prenda]), ['estado' => $estado]);
    }

    private function assertRegla(string $regla, callable $accion): void
    {
        try {
            $accion();
            $this->fail("Se esperaba {$regla}");
        } catch (ReglaIncumplida $error) {
            $this->assertSame($regla, $error->regla);
        }
    }
}
