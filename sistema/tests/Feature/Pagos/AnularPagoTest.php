<?php

namespace Tests\Feature\Pagos;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Pagos\AnularPago;
use App\Aplicacion\Pagos\RegistrarPago;
use App\Modelos\Cliente;
use App\Modelos\MetodoPago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * HU-25 · Anular un pago mal registrado. Hoy es el jueves 17 de septiembre de 2026 a las 11:00 a. m.
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class AnularPagoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private MetodoPago $efectivo;

    private Orden $orden42;

    private Pago $abono;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón']);
        $camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);
        $this->efectivo = MetodoPago::factory()->create([...$negocio, 'nombre' => 'Efectivo']);

        // La #0042 vale $31.000 y tiene un abono de $15.000 que en realidad era de $5.000
        $this->orden42 = Orden::factory()->create(['cliente_id' => $marta->id, 'numero' => 42, 'recibida_en' => '2026-09-07 09:15:00']);
        foreach ([15000, 8000, 8000] as $precio) {
            Prenda::factory()->create(['orden_id' => $this->orden42->id, 'tipo_prenda_id' => $camisa->id, 'precio' => $precio]);
        }
        $this->abono = Pago::factory()->create(['orden_id' => $this->orden42->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => 15000, 'pagado_en' => '2026-09-07 10:25:00']);
        $this->fijarReloj('2026-09-17 11:00:00');
    }

    public function test_ca_25_1_anulacion(): void
    {
        $this->actingAs($this->duena);

        $this->get(route('ordenes.detalle', $this->orden42))->assertSee(route('pagos.confirmar-anulacion', [$this->orden42, $this->abono]));
        $this->get(route('pagos.confirmar-anulacion', [$this->orden42, $this->abono]))
            ->assertOk()
            ->assertSeeInOrder(['¿Anular este pago?', 'Abono · Efectivo', 'Lunes 7 sep 2026 · 10:25 a. m.', '$15.000', 'no se borra', 'Motivo', 'Es obligatorio.', 'Saldo después de anular', '$31.000', 'Anular pago', 'No anular']);

        $this->anular('valor mal digitado')
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.detalle', $this->orden42))
            ->assertSessionHas('exito', 'El pago de $15.000 quedó anulado.');

        // Deja de contar, pero sigue ahí con la fecha y el motivo
        $this->assertSame(1, Pago::count());
        $anulado = $this->abono->fresh();
        $this->assertSame(['2026-09-17 11:00', 'valor mal digitado'], [$anulado->anulado_en?->format('Y-m-d H:i'), $anulado->motivo_anulacion]);
        $this->assertSame(31000, app(DetalleDeOrden::class)->obtener($this->orden42->fresh())['saldo']->valor());

        $this->get(route('ordenes.detalle', $this->orden42))
            ->assertSeeInOrder(['El pago de $15.000 quedó anulado.', 'Abono · 7 sep 2026 · Efectivo', 'Anulado el 17 sep 2026: valor mal digitado', '<dd class="anulado">− $15.000</dd>', 'Saldo', '$31.000'], false);
    }

    public function test_ca_25_2_sin_motivo(): void
    {
        $this->actingAs($this->duena);
        $confirmar = route('pagos.confirmar-anulacion', [$this->orden42, $this->abono]);

        foreach (['', '   '] as $motivo) {
            $this->from($confirmar)->post(route('pagos.anular', [$this->orden42, $this->abono]), ['motivo_anulacion' => $motivo, 'confirmacion' => 'si'])
                ->assertRedirect($confirmar)
                ->assertSessionHasErrors(['motivo_anulacion' => 'Escribe el motivo de la anulación.']);
        }
        $this->assertNull($this->abono->fresh()->anulado_en);

        // La hoja vuelve con el mensaje junto al motivo (se sigue la redirección: ver RegistrarPagoTest, CA-23.3)
        $this->followingRedirects()->from($confirmar)->post(route('pagos.anular', [$this->orden42, $this->abono]), ['motivo_anulacion' => '', 'confirmacion' => 'si'])
            ->assertOk()
            ->assertSee('<div class="campo con-error">', false)
            ->assertSee('Escribe el motivo de la anulación.');
    }

    public function test_ca_25_3_no_se_borra(): void
    {
        $this->abono->update(['anulado_en' => '2026-09-15 09:00:00', 'motivo_anulacion' => 'valor mal digitado']);
        $this->actingAs($this->duena);
        $motivo = 'Este pago ya está anulado.';

        // El detalle no ofrece anularlo otra vez
        $this->get(route('ordenes.detalle', $this->orden42))->assertDontSee(route('pagos.confirmar-anulacion', [$this->orden42, $this->abono]));
        // Ni por la dirección
        $this->get(route('pagos.confirmar-anulacion', [$this->orden42, $this->abono]))
            ->assertRedirect(route('ordenes.detalle', $this->orden42))
            ->assertSessionHasErrors(['orden' => $motivo]);
        $this->anular('otro motivo')
            ->assertRedirect(route('ordenes.detalle', $this->orden42))
            ->assertSessionHasErrors(['orden' => $motivo]);
        $this->assertSame(['2026-09-15 09:00', 'valor mal digitado'], [$this->abono->fresh()->anulado_en?->format('Y-m-d H:i'), $this->abono->fresh()->motivo_anulacion]);

        // No existe ninguna ruta para borrar un pago
        $rutasQueBorranPagos = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta) => str_contains($ruta->uri(), 'pagos') && in_array('DELETE', $ruta->methods(), true))
            ->all();
        $this->assertSame([], $rutasQueBorranPagos);
        $this->assertContains($this->delete("/ordenes/42/pagos/{$this->abono->id}")->getStatusCode(), [404, 405]);
        $this->assertSame(1, Pago::count());
    }

    public function test_rn_31_un_pago_no_se_borra_se_anula(): void
    {
        // Orden de $31.000 sin pagos: se registró por error un abono de $15.000 en vez de $5.000
        $this->abono->delete();
        $this->actingAs($this->duena);
        $registrar = app(RegistrarPago::class);

        $equivocado = $registrar->ejecutar($this->orden42, 15000, $this->efectivo->id, (string) Str::uuid());
        app(AnularPago::class)->ejecutar($equivocado, 'valor mal digitado');
        $registrar->ejecutar($this->orden42, 5000, $this->efectivo->id, (string) Str::uuid());

        // Ambos pagos quedan visibles; solo el segundo cuenta
        $this->assertSame(26000, app(DetalleDeOrden::class)->obtener($this->orden42->fresh())['saldo']->valor());
        $this->assertSame([15000, 5000], Pago::orderBy('id')->pluck('valor')->all());
        $this->get(route('ordenes.detalle', $this->orden42))
            ->assertSeeInOrder(['Anulado el 17 sep 2026: valor mal digitado', '− $15.000', '− $5.000', 'Saldo', '$26.000']);
    }

    public function test_rnf_10_sin_confirmacion_no_se_anula(): void
    {
        $this->actingAs($this->duena);

        $this->post(route('pagos.anular', [$this->orden42, $this->abono]), ['motivo_anulacion' => 'valor mal digitado'])
            ->assertRedirect(route('pagos.confirmar-anulacion', [$this->orden42, $this->abono]));

        $this->assertNull($this->abono->fresh()->anulado_en);
    }

    public function test_el_pago_se_busca_dentro_de_la_orden_de_la_direccion(): void
    {
        $orden43 = Orden::factory()->create(['cliente_id' => $this->orden42->cliente_id, 'numero' => 43]);
        $this->actingAs($this->duena);

        $this->get(route('pagos.confirmar-anulacion', [$orden43, $this->abono]))->assertNotFound();
        $this->post(route('pagos.anular', [$orden43, $this->abono]), ['motivo_anulacion' => 'x', 'confirmacion' => 'si'])->assertNotFound();
        $this->assertNull($this->abono->fresh()->anulado_en);
    }

    private function anular(string $motivo): TestResponse
    {
        return $this->from(route('pagos.confirmar-anulacion', [$this->orden42, $this->abono]))
            ->post(route('pagos.anular', [$this->orden42, $this->abono]), ['motivo_anulacion' => $motivo, 'confirmacion' => 'si']);
    }
}
