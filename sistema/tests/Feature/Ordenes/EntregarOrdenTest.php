<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Ordenes\EntregarOrden;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Pagos\EstadoDePago;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * HU-21 · Entregar la orden al cliente. Hoy es el martes 15 de septiembre de 2026 a las 11:20 a. m.
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class EntregarOrdenTest extends TestCase
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
        $this->fijarReloj('2026-09-15 11:20:00');
    }

    public function test_ca_21_1_entrega_completa(): void
    {
        [$orden, $prendas] = $this->orden42(pagado: 31000);
        $this->actingAs($this->duena);

        $this->get(route('ordenes.detalle', $orden))->assertSee(route('ordenes.confirmar-entrega', $orden));
        $this->get(route('ordenes.confirmar-entrega', $orden))
            ->assertOk()
            ->assertSeeInOrder(['Entregar la', '#0042', 'Se entregan', 'Pantalón · subir basta 3 cm', 'Camisa · entallar los costados', 'Camisa · entallar y acortar mangas', 'Entregar', 'No entregar'])
            ->assertDontSee('Se queda en el taller')
            ->assertDontSee('de todos modos');

        // Sin saldo no hace falta confirmar nada más (RN-21)
        $this->post(route('ordenes.entregar', $orden))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHas('exito', 'La orden quedó entregada.');

        foreach ($prendas as $prenda) {
            $this->assertSame([EstadoDePrenda::Entregada, '2026-09-15 11:20'], [$prenda->fresh()->estado, $prenda->fresh()->entregada_en?->format('Y-m-d H:i')]);
        }
        $detalle = app(DetalleDeOrden::class)->obtener($orden->fresh());
        $this->assertSame(EstadoDeOrden::Entregada, $detalle['estado']);
        $this->assertSame('2026-09-15 11:20', $detalle['entregadaEn']?->format('Y-m-d H:i'));

        $this->get(route('ordenes.detalle', $orden))
            ->assertSeeInOrder(['La orden quedó entregada.', 'Entregada', 'Pagada', 'Entregada', '15 sep 2026 · 11:20 a. m.'])
            ->assertDontSee(route('ordenes.confirmar-entrega', $orden));
    }

    public function test_ca_21_2_entrega_parcial(): void
    {
        [$orden, [$camisa, $vestido]] = $this->orden(43, [['Camisa', 'Entallar los costados', 'terminada', 8000], ['Vestido', 'Ajustar la cintura', 'en_proceso', 25000]], pagado: 33000);
        $this->actingAs($this->duena);

        $this->get(route('ordenes.confirmar-entrega', $orden))
            ->assertOk()
            ->assertSeeInOrder(['Se entregan', 'Camisa · entallar los costados', 'Terminada', 'Se queda en el taller', 'Vestido · ajustar la cintura', 'En proceso', 'Es una entrega parcial: la orden sigue En proceso.']);

        $this->post(route('ordenes.entregar', $orden), ['confirmacion' => 'si'])
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHas('exito', 'Se entregó 1 prenda. La orden sigue en proceso.');

        $this->assertSame([EstadoDePrenda::Entregada, EstadoDePrenda::EnProceso], [$camisa->fresh()->estado, $vestido->fresh()->estado]);
        $this->assertSame(EstadoDeOrden::EnProceso, app(DetalleDeOrden::class)->obtener($orden->fresh())['estado']);
        // Ya no queda nada Terminado: el detalle no ofrece entregar otra vez
        $this->get(route('ordenes.detalle', $orden))->assertDontSee(route('ordenes.confirmar-entrega', $orden));
    }

    public function test_ca_21_3_aviso_de_saldo(): void
    {
        [$orden] = $this->orden42(pagado: 19000);
        $this->actingAs($this->duena);

        $this->get(route('ordenes.confirmar-entrega', $orden))
            ->assertOk()
            ->assertSeeInOrder(['Se entregan', 'Marta debe $12.000. ¿Entregar de todos modos?', 'Sí, entregar', 'Registrar un pago primero', 'No entregar'])
            ->assertSee(route('pagos.nuevo', $orden));

        // Tocar Entregar sin haber visto el aviso lleva al aviso
        $this->followingRedirects()->post(route('ordenes.entregar', $orden))
            ->assertOk()
            ->assertSee('Marta debe $12.000. ¿Entregar de todos modos?');
    }

    public function test_ca_21_4_entrego_debiendo(): void
    {
        [$orden, $prendas] = $this->orden42(pagado: 19000);
        $this->actingAs($this->duena);

        $this->post(route('ordenes.entregar', $orden), ['confirmacion' => 'si'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHas('exito', 'La orden quedó entregada.');

        $this->assertSame(EstadoDePrenda::Entregada, $prendas[2]->fresh()->estado);
        $detalle = app(DetalleDeOrden::class)->obtener($orden->fresh());
        $this->assertSame([EstadoDeOrden::Entregada, EstadoDePago::PorCobrar, 12000], [$detalle['estado'], $detalle['estadoDePago'], $detalle['saldo']->valor()]);
        $this->get(route('ordenes.detalle', $orden))->assertSeeInOrder(['Entregada', 'Por cobrar', 'Saldo', '$12.000']);
    }

    public function test_ca_21_5_no_entrego(): void
    {
        [$orden, $prendas] = $this->orden42(pagado: 19000);
        $this->actingAs($this->duena);

        $this->get(route('ordenes.confirmar-entrega', $orden))->assertSee('href="'.route('ordenes.detalle', $orden).'"', false);
        foreach ([[], ['confirmacion' => 'no']] as $datos) {
            $this->post(route('ordenes.entregar', $orden), $datos)->assertRedirect(route('ordenes.confirmar-entrega', $orden));
        }

        foreach ($prendas as $prenda) {
            $this->assertSame([EstadoDePrenda::Terminada, null], [$prenda->fresh()->estado, $prenda->fresh()->entregada_en]);
        }
        $this->assertSame(EstadoDeOrden::ListaParaEntregar, app(DetalleDeOrden::class)->obtener($orden->fresh())['estado']);
    }

    public function test_rn_20_entregar_la_orden(): void
    {
        [$orden, [$camisa, $vestido]] = $this->orden(43, [['Camisa', 'Entallar los costados', 'terminada', 8000], ['Vestido', 'Ajustar la cintura', 'en_proceso', 25000]]);
        [$sinTerminar] = $this->orden(44, [['Vestido', 'Subir el ruedo', 'pendiente', 20000]]);
        $this->actingAs($this->duena);
        $entregar = app(EntregarOrden::class);

        $this->assertSame(['estado' => EstadoDeOrden::EnProceso, 'entregadas' => 1], $entregar->ejecutar($orden, true));
        $this->assertSame([EstadoDePrenda::Entregada, EstadoDePrenda::EnProceso], [$camisa->fresh()->estado, $vestido->fresh()->estado]);

        // Sin nada Terminado no hay qué entregar, ni desde la pantalla ni desde el caso de uso
        $motivo = 'Esta orden no tiene prendas terminadas para entregar.';
        $this->assertReglaIncumplida('RN-20', $motivo, fn () => $entregar->ejecutar($sinTerminar, true));
        $this->get(route('ordenes.confirmar-entrega', $sinTerminar))
            ->assertRedirect(route('ordenes.detalle', $sinTerminar))
            ->assertSessionHasErrors(['orden' => $motivo]);
    }

    public function test_rn_21_entregar_con_saldo_pendiente(): void
    {
        [$orden, $prendas] = $this->orden42(pagado: 19000);
        $this->actingAs($this->duena);
        $entregar = app(EntregarOrden::class);

        $this->assertReglaIncumplida('RN-21', 'Marta debe $12.000. ¿Entregar de todos modos?', fn () => $entregar->ejecutar($orden, false));
        $this->assertSame(EstadoDePrenda::Terminada, $prendas[0]->fresh()->estado);

        $this->assertSame(EstadoDeOrden::Entregada, $entregar->ejecutar($orden, true)['estado']);
    }

    public function test_rn_23_fecha_de_entrega_real(): void
    {
        [$orden, [$camisa, $vestido]] = $this->orden(43, [['Camisa', 'Entallar los costados', 'terminada', 8000], ['Vestido', 'Ajustar la cintura', 'en_proceso', 25000]], pagado: 33000);
        $this->actingAs($this->duena);

        // El 10 de septiembre se lleva la camisa; el 15 a las 11:20 a. m., el vestido
        $this->fijarReloj('2026-09-10 17:05:00');
        app(EntregarOrden::class)->ejecutar($orden, false);
        $this->assertNull(app(DetalleDeOrden::class)->obtener($orden->fresh())['entregadaEn']);

        $vestido->update(['estado' => EstadoDePrenda::Terminada]);
        $this->fijarReloj('2026-09-15 11:20:00');
        app(EntregarOrden::class)->ejecutar($orden->fresh(), false);

        $this->assertSame(['2026-09-10 17:05', '2026-09-15 11:20'], [$camisa->fresh()->entregada_en?->format('Y-m-d H:i'), $vestido->fresh()->entregada_en?->format('Y-m-d H:i')]);
        $this->assertSame('2026-09-15 11:20', app(DetalleDeOrden::class)->obtener($orden->fresh())['entregadaEn']?->format('Y-m-d H:i'));
    }

    public function test_rnf_13_una_entrega_se_hace_completa_o_no_se_hace(): void
    {
        [$orden, $prendas] = $this->orden42(pagado: 31000);
        $this->actingAs($this->duena);

        // Falla al guardar la segunda prenda, cuando la primera ya quedó Entregada
        $guardadas = 0;
        Prenda::updated(function () use (&$guardadas) {
            if (++$guardadas === 2) {
                throw new RuntimeException('Falla forzada a mitad de la entrega');
            }
        });

        try {
            app(EntregarOrden::class)->ejecutar($orden, true);
            $this->fail('La falla forzada no ocurrió.');
        } catch (RuntimeException $falla) {
            $this->assertSame('Falla forzada a mitad de la entrega', $falla->getMessage());
        }

        foreach ($prendas as $prenda) {
            $this->assertSame([EstadoDePrenda::Terminada, null], [$prenda->fresh()->estado, $prenda->fresh()->entregada_en]);
        }
    }

    public function test_una_orden_cancelada_no_se_entrega(): void
    {
        [$orden, $prendas] = $this->orden42(pagado: 31000, atributos: ['cancelada_en' => '2026-09-14 09:00:00']);
        $this->actingAs($this->duena);
        $motivo = 'Esta orden está cancelada y no admite cambios.';

        $this->get(route('ordenes.detalle', $orden))->assertDontSee(route('ordenes.confirmar-entrega', $orden));
        $this->get(route('ordenes.confirmar-entrega', $orden))->assertRedirect(route('ordenes.detalle', $orden))->assertSessionHasErrors(['orden' => $motivo]);
        $this->post(route('ordenes.entregar', $orden), ['confirmacion' => 'si'])->assertRedirect(route('ordenes.detalle', $orden))->assertSessionHasErrors(['orden' => $motivo]);
        $this->assertSame(EstadoDePrenda::Terminada, $prendas[0]->fresh()->estado);
    }

    /**
     * La #0042 de los criterios: 3 prendas Terminadas por $31.000.
     *
     * @param  array<string, mixed>  $atributos
     * @return array{0: Orden, 1: list<Prenda>}
     */
    private function orden42(int $pagado, array $atributos = []): array
    {
        return $this->orden(42, [
            ['Pantalón', 'Subir basta 3 cm', 'terminada', 15000],
            ['Camisa', 'Entallar los costados', 'terminada', 8000],
            ['Camisa', 'Entallar y acortar mangas', 'terminada', 8000],
        ], $pagado, $atributos);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string, 3: int}>  $prendas
     * @param  array<string, mixed>  $atributos
     * @return array{0: Orden, 1: list<Prenda>}
     */
    private function orden(int $numero, array $prendas, int $pagado = 0, array $atributos = []): array
    {
        $orden = Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => $numero, 'recibida_en' => '2026-09-07 09:15:00', ...$atributos]);

        $creadas = [];
        foreach ($prendas as [$tipo, $arreglo, $estado, $precio]) {
            $this->tipos[$tipo] ??= TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => $tipo])->id;
            $creadas[] = Prenda::factory()->create(['orden_id' => $orden->id, 'tipo_prenda_id' => $this->tipos[$tipo], 'descripcion_arreglo' => $arreglo, 'estado' => $estado, 'precio' => $precio]);
        }
        if ($pagado > 0) {
            Pago::factory()->create(['orden_id' => $orden->id, 'valor' => $pagado, 'pagado_en' => '2026-09-07 09:20:00']);
        }

        return [$orden, $creadas];
    }

    private function assertReglaIncumplida(string $regla, string $mensaje, callable $accion): void
    {
        try {
            $accion();
            $this->fail("Se esperaba {$regla}.");
        } catch (ReglaIncumplida $incumplida) {
            $this->assertSame([$regla, $mensaje], [$incumplida->regla, $incumplida->mensajeParaUsuaria]);
        }
    }
}
