<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Consultas\FichaDeCliente;
use App\Aplicacion\Consultas\ListarOrdenes;
use App\Aplicacion\Ordenes\CambiarEstadoDePrenda;
use App\Aplicacion\Ordenes\CancelarOrden;
use App\Aplicacion\Pagos\RegistrarPago;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Modelos\Cliente;
use App\Modelos\MetodoPago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * HU-22 · Cancelar una orden. Hoy es el martes 15 de septiembre de 2026 a las 10:00 a. m.
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class CancelarOrdenTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $luis;

    private MetodoPago $efectivo;

    /** @var array<string, int> */
    private array $tipos = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $this->luis = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Luis Pardo']);
        $this->efectivo = MetodoPago::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Efectivo']);
        $this->fijarReloj('2026-09-15 10:00:00');
    }

    public function test_ca_22_1_cancelacion(): void
    {
        [$orden43] = $this->orden43ConAbono();
        $this->actingAs($this->duena);

        $this->get(route('ordenes.detalle', $orden43))->assertSee(route('ordenes.confirmar-cancelacion', $orden43));
        $this->get(route('ordenes.confirmar-cancelacion', $orden43))
            ->assertOk()
            ->assertSeeInOrder(['¿Cancelar la orden', '#0043', 'Cancélala solo si Luis desistió del arreglo.', 'Deja de contar como trabajo pendiente y como deuda.', 'El abono de', '$5.000', 'sigue registrado.', 'No admite prendas, pagos ni cambios de estado.', 'No se puede reabrir.', 'Sí, cancelar la orden', 'No cancelar']);
        $this->assertSame(1, app(ListarOrdenes::class)->contar(EstadoDeOrden::EnProceso));

        $this->post(route('ordenes.cancelar', $orden43), ['confirmacion' => 'si'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.detalle', $orden43))
            ->assertSessionHas('exito', 'La orden #0043 quedó cancelada.');

        $this->assertSame('2026-09-15 10:00', $orden43->fresh()->cancelada_en?->format('Y-m-d H:i'));
        // El abono sigue registrado
        $this->assertSame([5000], Pago::whereNull('anulado_en')->pluck('valor')->all());
        // No cuenta como trabajo pendiente ni como deuda (RN-32 para Luis)
        $this->assertSame([0, 1], [app(ListarOrdenes::class)->contar(EstadoDeOrden::EnProceso), app(ListarOrdenes::class)->contar(EstadoDeOrden::Cancelada)]);
        $this->assertSame(0, app(FichaDeCliente::class)->obtener($this->luis->fresh())['debe']->valor());
        $detalle = app(DetalleDeOrden::class)->obtener($orden43->fresh());
        $this->assertSame([EstadoDeOrden::Cancelada, null], [$detalle['estado'], $detalle['estadoDePago']]);

        $this->get(route('ordenes.detalle', $orden43))
            ->assertSeeInOrder(['La orden #0043 quedó cancelada.', 'Cancelada', 'Cancelada', '15 sep 2026 · 10:00 a. m.', '− $5.000'])
            ->assertDontSee(route('ordenes.confirmar-cancelacion', $orden43));
    }

    public function test_ca_22_2_orden_entregada(): void
    {
        $orden40 = $this->orden(40, [['Pantalón', 'Subir basta 3 cm', 'entregada']]);
        $this->actingAs($this->duena);
        $motivo = 'Una orden entregada no se puede cancelar.';

        $this->get(route('ordenes.detalle', $orden40))->assertOk()->assertDontSee(route('ordenes.confirmar-cancelacion', $orden40));
        $this->get(route('ordenes.confirmar-cancelacion', $orden40))
            ->assertRedirect(route('ordenes.detalle', $orden40))
            ->assertSessionHasErrors(['orden' => $motivo]);
        $this->post(route('ordenes.cancelar', $orden40), ['confirmacion' => 'si'])
            ->assertRedirect(route('ordenes.detalle', $orden40))
            ->assertSessionHasErrors(['orden' => $motivo]);

        $this->assertNull($orden40->fresh()->cancelada_en);
        $this->followingRedirects()->post(route('ordenes.cancelar', $orden40), ['confirmacion' => 'si'])->assertSee($motivo);
    }

    public function test_ca_22_3_orden_ya_cancelada(): void
    {
        [$orden43, $vestido] = $this->orden43ConAbono(['cancelada_en' => '2026-09-14 16:00:00']);
        $this->actingAs($this->duena);

        // El detalle no ofrece registrar pagos, cambiar estados, entregar ni cancelar otra vez
        $this->get(route('ordenes.detalle', $orden43))
            ->assertOk()
            ->assertDontSee(route('pagos.nuevo', $orden43))
            ->assertDontSee(route('prendas.acciones', [$orden43, $vestido]))
            ->assertDontSee(route('ordenes.confirmar-entrega', $orden43))
            ->assertDontSee(route('ordenes.confirmar-cancelacion', $orden43));

        // Un pago nuevo no se permite (RN-30)
        $this->get(route('pagos.nuevo', $orden43))
            ->assertRedirect(route('ordenes.detalle', $orden43))
            ->assertSessionHasErrors(['orden' => 'No se pueden registrar pagos en una orden cancelada.']);
        $this->assertReglaIncumplida('RN-30', fn () => app(RegistrarPago::class)->ejecutar($orden43, 1000, $this->efectivo->id, (string) Str::uuid()));
        $this->assertSame(1, Pago::count());

        // Ni cambiar sus prendas. Agregar prendas a una orden existente llega con HU-11, que prueba su propio RN-24
        $this->assertReglaIncumplida('RN-24', fn () => app(CambiarEstadoDePrenda::class)->ejecutar($vestido, EstadoDePrenda::Terminada));
        $this->assertSame(EstadoDePrenda::EnProceso, $vestido->fresh()->estado);

        // Ni se cancela otra vez: la fecha de la cancelación no cambia
        $this->post(route('ordenes.cancelar', $orden43), ['confirmacion' => 'si'])
            ->assertSessionHasErrors(['orden' => 'Esta orden está cancelada y no admite cambios.']);
        $this->assertSame('2026-09-14 16:00', $orden43->fresh()->cancelada_en?->format('Y-m-d H:i'));
    }

    public function test_rn_24_cancelar_una_orden(): void
    {
        [$orden43] = $this->orden43ConAbono();
        // Una entrega parcial no impide cancelar lo que queda: la orden no está Entregada
        $parcial = $this->orden(44, [['Camisa', 'Entallar los costados', 'entregada'], ['Vestido', 'Subir el ruedo', 'pendiente']]);
        $this->actingAs($this->duena);
        $cancelar = app(CancelarOrden::class);

        $cancelar->ejecutar($orden43);
        $cancelar->ejecutar($parcial);

        $this->assertNotNull($orden43->fresh()->cancelada_en);
        $this->assertNotNull($parcial->fresh()->cancelada_en);
        $this->assertSame(1, Pago::where('orden_id', $orden43->id)->whereNull('anulado_en')->count());
        $this->assertReglaIncumplida('RN-30', fn () => app(RegistrarPago::class)->ejecutar($orden43->fresh(), 5000, $this->efectivo->id, (string) Str::uuid()));
        $this->assertReglaIncumplida('RN-24', fn () => $cancelar->ejecutar($orden43->fresh()));
    }

    public function test_rnf_10_sin_confirmacion_no_se_cancela(): void
    {
        [$orden43] = $this->orden43ConAbono();
        $this->actingAs($this->duena);

        foreach ([[], ['confirmacion' => 'no']] as $datos) {
            $this->post(route('ordenes.cancelar', $orden43), $datos)->assertRedirect(route('ordenes.confirmar-cancelacion', $orden43));
        }

        $this->assertNull($orden43->fresh()->cancelada_en);
    }

    /**
     * La #0043 de Luis: un vestido En proceso por $25.000 con un abono de $5.000.
     *
     * @param  array<string, mixed>  $atributos
     * @return array{0: Orden, 1: Prenda}
     */
    private function orden43ConAbono(array $atributos = []): array
    {
        $orden = $this->orden(43, [['Vestido', 'Ajustar la cintura', 'en_proceso']], $atributos);
        Pago::factory()->create(['orden_id' => $orden->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => 5000, 'pagado_en' => '2026-09-08 09:20:00']);

        return [$orden, $orden->prendas()->sole()];
    }

    /**
     * @param  list<array{0: string, 1: string, 2: string}>  $prendas
     * @param  array<string, mixed>  $atributos
     */
    private function orden(int $numero, array $prendas, array $atributos = []): Orden
    {
        $orden = Orden::factory()->create(['cliente_id' => $this->luis->id, 'numero' => $numero, 'recibida_en' => '2026-09-08 09:15:00', ...$atributos]);

        foreach ($prendas as [$tipo, $arreglo, $estado]) {
            $this->tipos[$tipo] ??= TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => $tipo])->id;
            Prenda::factory()->create([
                'orden_id' => $orden->id,
                'tipo_prenda_id' => $this->tipos[$tipo],
                'descripcion_arreglo' => $arreglo,
                'estado' => $estado,
                'precio' => 25000,
                'entregada_en' => $estado === 'entregada' ? '2026-09-12 11:00:00' : null,
            ]);
        }

        return $orden;
    }

    private function assertReglaIncumplida(string $regla, callable $accion): void
    {
        try {
            $accion();
            $this->fail("Se esperaba {$regla}.");
        } catch (ReglaIncumplida $incumplida) {
            $this->assertSame($regla, $incumplida->regla);
        }
    }
}
