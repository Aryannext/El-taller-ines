<?php

namespace Tests\Feature\Ordenes;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Ordenes\RegistrarOrden;
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
use Database\Seeders\NegocioInicialSeeder;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

/**
 * HU-07 · Registrar una orden con sus prendas y HU-08 · Obtener el número de la orden. Hoy es el 14 de septiembre de 2026, como en los criterios.
 */
class RegistrarOrdenTest extends TestCase
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
        $this->marta = Cliente::factory()->create([
            'negocio_id' => $this->duena->negocio_id,
            'nombre' => 'Marta Rincón',
            'celular' => '3104567890',
        ]);
        foreach (NegocioInicialSeeder::TIPOS_DE_PRENDA as $nombre) {
            $this->tipos[$nombre] = TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => $nombre])->id;
        }

        $this->actingAs($this->duena);
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_07_1_orden_completa(): void
    {
        $this->registrar()->assertSessionHasNoErrors()->assertRedirect(route('ordenes.guardada', Orden::sole()));

        $detalle = app(DetalleDeOrden::class)->obtener(Orden::sole());
        $this->assertSame(EstadoDeOrden::EnProceso, $detalle['estado']);
        $this->assertSame('$31.000', $detalle['valor']->formato());
        $this->assertSame('2026-09-20', $detalle['orden']->fecha_entrega_acordada->format('Y-m-d'));
        $this->assertSame('2026-09-14', $detalle['orden']->recibida_en->format('Y-m-d'));
        $this->assertSame(
            [EstadoDePrenda::Pendiente, EstadoDePrenda::Pendiente, EstadoDePrenda::Pendiente],
            $detalle['orden']->prendas->pluck('estado')->all(),
        );
        $this->assertSame(['Pantalón', 'Camisa', 'Camisa'], $detalle['orden']->prendas->map(fn (Prenda $prenda) => $prenda->tipoPrenda->nombre)->all());

        $this->get(route('ordenes.guardada', Orden::sole()))
            ->assertOk()
            ->assertSee('La orden de Marta Rincón quedó guardada.')
            ->assertSee('#0001')
            ->assertSee('$31.000')
            ->assertSee('Domingo 20 sep 2026');
    }

    public function test_ca_07_2_sin_prendas(): void
    {
        $this->registrar(['prendas' => []])
            ->assertSessionHasErrors(['prendas' => 'Agrega al menos una prenda.']);

        $this->assertSame(0, Orden::count());
    }

    public function test_ca_07_3_entrega_antes_de_hoy(): void
    {
        $this->registrar(['fecha_entrega_acordada' => '2026-09-13'])
            ->assertSessionHasErrors(['fecha_entrega_acordada' => 'La entrega no puede ser antes de la fecha de recepción.']);

        $this->assertSame(0, Orden::count());
    }

    public function test_ca_07_4_entrega_el_mismo_dia(): void
    {
        $this->registrar(['fecha_entrega_acordada' => '2026-09-14'])->assertSessionHasNoErrors();

        $this->assertSame(1, Orden::count());
    }

    public function test_ca_07_5_una_prenda_invalida(): void
    {
        $prendas = $this->datos()['prendas'];
        $prendas[2]['precio'] = '0';

        $this->registrar(['prendas' => $prendas])
            ->assertSessionHasErrors(['prendas.2.precio' => 'El precio debe ser mayor que cero.']);

        $this->assertSame(0, Orden::count());
        $this->assertSame(0, Prenda::count());
    }

    public function test_ca_07_6_lista_de_tipos(): void
    {
        TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Overol', 'activo' => false]);

        $this->get(route('ordenes.nueva'))
            ->assertOk()
            ->assertSeeInOrder(['Pantalón', 'Camisa', 'Blusa', 'Vestido', 'Falda', 'Chaqueta', 'Otro…'])
            ->assertDontSee('Overol');
    }

    public function test_ca_08_1_numero_destacado(): void
    {
        Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => 41]);

        $this->followingRedirects()->registrar()
            ->assertOk()
            ->assertSeeInOrder(['Escribe este número en la bolsa', '<span class="numero-orden">#0042</span>'], false);
    }

    public function test_ca_08_2_no_se_reutiliza(): void
    {
        Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => 41, 'cancelada_en' => '2026-09-10 09:00:00']);

        $this->registrar()->assertSessionHasNoErrors();

        $this->assertSame([41, 42], Orden::orderBy('numero')->pluck('numero')->all());
    }

    public function test_ca_08_3_siempre_visible(): void
    {
        Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => 41]);
        $this->registrar()->assertSessionHasNoErrors();

        // Otro día, la dueña abre la orden desde la lista o la ficha del cliente
        $this->fijarReloj('2026-09-17 08:30:00');

        $this->get(route('ordenes.detalle', Orden::where('numero', 42)->sole()))
            ->assertOk()
            ->assertSeeInOrder(['<header class="barra">', '#0042', '</header>', '<main'], false)
            ->assertSee('Marta Rincón')
            ->assertSee('$31.000');
    }

    public function test_rn_06_una_orden_tiene_al_menos_una_prenda(): void
    {
        try {
            app(RegistrarOrden::class)->ejecutar($this->marta, new DateTimeImmutable('2026-09-20'), [], (string) Str::uuid());
            $this->fail('Se registró una orden sin prendas');
        } catch (ReglaIncumplida $error) {
            $this->assertSame('RN-06', $error->regla);
        }

        $this->assertSame(0, Orden::count());
    }

    public function test_rn_08_numero_de_orden(): void
    {
        Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => 41, 'cancelada_en' => '2026-09-10 09:00:00']);
        // Los números de otro negocio no cuentan
        Orden::factory()->create(['numero' => 99]);

        $this->registrar()->assertSessionHasNoErrors();

        $nueva = Orden::where('numero', 42)->sole();
        $this->get(route('ordenes.guardada', $nueva))->assertSee('#0042');
    }

    public function test_rnf_13_si_algo_falla_a_mitad_no_queda_nada_guardado(): void
    {
        Prenda::creating(function (Prenda $prenda): void {
            if ($prenda->descripcion_arreglo === 'falla a propósito') {
                throw new RuntimeException('Falla simulada a mitad de la orden');
            }
        });

        try {
            app(RegistrarOrden::class)->ejecutar($this->marta, new DateTimeImmutable('2026-09-20'), [
                ['tipo_prenda_id' => 'otro', 'tipo_otro' => 'Overol', 'descripcion_arreglo' => 'Cambiar la cremallera', 'precio' => 18000],
                ['tipo_prenda_id' => $this->tipos['Camisa'], 'tipo_otro' => null, 'descripcion_arreglo' => 'falla a propósito', 'precio' => 8000],
            ], (string) Str::uuid());
            $this->fail('Se esperaba la falla simulada');
        } catch (RuntimeException) {
        }

        $this->assertSame(0, Orden::count());
        $this->assertSame(0, Prenda::count());
        $this->assertFalse(TipoPrenda::where('nombre', 'Overol')->exists(), 'El tipo escrito con «Otro» tampoco queda');
    }

    public function test_rnf_14_enviar_dos_veces_el_mismo_formulario_no_duplica_la_orden(): void
    {
        $datos = $this->datos();

        $primera = $this->post(route('ordenes.guardar'), $datos);
        $segunda = $this->post(route('ordenes.guardar'), $datos);

        $this->assertSame(1, Orden::count());
        $this->assertSame($primera->headers->get('Location'), $segunda->headers->get('Location'));
    }

    public function test_ca_24_1_abono_inicial(): void
    {
        // HU-24: la orden vale $31.000 y Marta abona $10.000 en efectivo al dejar la ropa
        $efectivo = MetodoPago::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Efectivo']);

        $this->get(route('ordenes.nueva'))
            ->assertOk()
            ->assertSeeInOrder(['Abono al dejar la ropa', '¿Cuánto abona?', 'opcional', 'Método', 'Efectivo']);

        $this->registrar(['abono' => '$10.000', 'metodo_pago_id' => (string) $efectivo->id])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.guardada', Orden::sole()));

        $orden = Orden::sole();
        $pago = $orden->pagos()->sole();
        $this->assertSame(10000, $pago->valor);
        $this->assertSame($efectivo->id, $pago->metodo_pago_id);
        // El abono queda con la fecha en que se recibió la orden (RN-09)
        $this->assertSame('2026-09-14 10:00:00', $pago->pagado_en->format('Y-m-d H:i:s'));

        $detalle = app(DetalleDeOrden::class)->obtener($orden);
        $this->assertSame(31000, $detalle['valor']->valor());
        $this->assertSame(21000, $detalle['saldo']->valor());
    }

    public function test_ca_24_2_abono_mayor_que_la_orden(): void
    {
        // RN-28: el abono no puede superar el valor de la orden, y si falla no queda ni la orden ni el pago (RNF-13)
        $efectivo = MetodoPago::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Efectivo']);

        $this->registrar(['abono' => '40.000', 'metodo_pago_id' => (string) $efectivo->id])
            ->assertRedirect(route('ordenes.nueva'))
            ->assertSessionHasErrors(['abono' => 'El abono no puede superar el valor de la orden: $31.000.']);

        $this->assertSame(0, Orden::count());
        $this->assertSame(0, Pago::count());
        $this->assertSame(0, Prenda::count());
    }

    public function test_hu_24_el_abono_pide_decir_como_pago_y_no_admite_cero(): void
    {
        MetodoPago::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Efectivo']);

        $this->registrar(['abono' => '10.000'])
            ->assertSessionHasErrors(['metodo_pago_id' => 'Elige cómo pagó el abono.']);
        $this->registrar(['abono' => '0'])
            ->assertSessionHasErrors(['abono' => 'El abono debe ser mayor que cero.']);
        $this->assertSame(0, Orden::count());

        // Sin abono, la orden se guarda igual y no queda ningún pago
        $this->registrar(['abono' => ''])->assertSessionHasNoErrors();
        $this->assertSame(1, Orden::count());
        $this->assertSame(0, Pago::count());
    }

    /**
     * La orden de CA-07.1: entrega el 20 de septiembre, un pantalón y dos camisas.
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datos(array $cambios = []): array
    {
        return array_replace([
            'token_formulario' => (string) Str::uuid(),
            'cliente_id' => $this->marta->id,
            'fecha_entrega_acordada' => '2026-09-20',
            'prendas' => [
                ['tipo_prenda_id' => $this->tipos['Pantalón'], 'descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => '$15.000'],
                ['tipo_prenda_id' => $this->tipos['Camisa'], 'descripcion_arreglo' => 'Entallar', 'precio' => '8.000'],
                ['tipo_prenda_id' => $this->tipos['Camisa'], 'descripcion_arreglo' => 'Entallar', 'precio' => '8000'],
            ],
        ], $cambios);
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    private function registrar(array $cambios = []): TestResponse
    {
        return $this->from(route('ordenes.nueva'))->post(route('ordenes.guardar'), $this->datos($cambios));
    }
}
