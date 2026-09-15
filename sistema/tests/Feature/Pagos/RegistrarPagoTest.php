<?php

namespace Tests\Feature\Pagos;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Consultas\FichaDeCliente;
use App\Aplicacion\Pagos\RegistrarPago;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Pagos\EstadoDePago;
use App\Modelos\Cliente;
use App\Modelos\MetodoPago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * HU-23 · Registrar un pago o abono. Hoy es el miércoles 16 de septiembre de 2026 a las 10:30 a. m.
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class RegistrarPagoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $camisa;

    private MetodoPago $efectivo;

    private MetodoPago $nequi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón']);
        $this->camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);
        $this->efectivo = MetodoPago::factory()->create([...$negocio, 'nombre' => 'Efectivo']);
        $this->nequi = MetodoPago::factory()->create([...$negocio, 'nombre' => 'Nequi']);
        $this->fijarReloj('2026-09-16 10:30:00');
    }

    public function test_ca_23_1_abono(): void
    {
        $orden = $this->orden42();
        $this->actingAs($this->duena);

        $this->get(route('ordenes.detalle', $orden))->assertSee(route('pagos.nuevo', $orden));
        $this->get(route('pagos.nuevo', $orden))
            ->assertOk()
            ->assertSeeInOrder(['Registrar pago', '#0042', 'Marta Rincón', 'saldo', '$31.000', 'Valor', 'Usar el saldo completo: $31.000', 'Método', 'Efectivo', 'Nequi', 'La fecha del pago es hoy, miércoles 16 sep 2026.', 'Guardar pago']);

        $this->pagar($orden, '$10.000', $this->efectivo)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHas('exito', 'El pago de $10.000 quedó registrado.');

        $pago = Pago::sole();
        $this->assertSame([10000, $this->efectivo->id, '2026-09-16 10:30'], [$pago->valor, $pago->metodo_pago_id, $pago->pagado_en->format('Y-m-d H:i')]);
        $detalle = app(DetalleDeOrden::class)->obtener($orden->fresh());
        $this->assertSame(21000, $detalle['saldo']->valor());
        $this->assertSame(EstadoDePago::PorCobrar, $detalle['estadoDePago']);

        $this->get(route('ordenes.detalle', $orden))
            ->assertSeeInOrder(['El pago de $10.000 quedó registrado.', 'Por cobrar', 'Abono · 16 sep 2026 · Efectivo', '− $10.000', 'Saldo', '$21.000', 'Registrar pago']);
    }

    public function test_ca_23_2_pago_por_nequi(): void
    {
        $orden = $this->orden42(pagado: 10000);
        $this->actingAs($this->duena);

        $this->pagar($orden, '21000', $this->nequi)->assertSessionHasNoErrors();

        $detalle = app(DetalleDeOrden::class)->obtener($orden->fresh());
        $this->assertSame(0, $detalle['saldo']->valor());
        $this->assertSame(EstadoDePago::Pagada, $detalle['estadoDePago']);

        // Pagada: el detalle ya no ofrece registrar pagos y la hoja no se abre
        $this->get(route('ordenes.detalle', $orden))
            ->assertSeeInOrder(['Pagada', '· Nequi', 'Saldo', '$0'])
            ->assertDontSee(route('pagos.nuevo', $orden));
        $this->get(route('pagos.nuevo', $orden))
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['orden' => 'Esta orden ya está pagada.']);
    }

    public function test_ca_23_3_supera_el_saldo(): void
    {
        $orden = $this->orden42(pagado: 10000);
        $this->actingAs($this->duena);
        $nuevo = route('pagos.nuevo', $orden);

        $this->from($nuevo)->post(route('pagos.guardar', $orden), $this->datos('25.000', $this->efectivo))
            ->assertRedirect($nuevo)
            ->assertSessionHasErrors(['valor' => 'El pago no puede superar el saldo pendiente de $21.000']);
        $this->assertSame(1, Pago::count());

        // La hoja vuelve con el mensaje junto al valor y lo que se escribió. Se revisa siguiendo la redirección:
        // assertSessionHasErrors deja los errores como objeto en la sesión de la prueba y, con la sesión en JSON,
        // la petición siguiente ya no los encuentra. En el navegador no pasa (comprobado aparte).
        $this->followingRedirects()->from($nuevo)->post(route('pagos.guardar', $orden), $this->datos('25.000', $this->efectivo))
            ->assertOk()
            ->assertSee('<div class="campo con-error">', false)
            ->assertSee('El pago no puede superar el saldo pendiente de $21.000')
            ->assertSee('value="25.000"', false);
        $this->assertSame(1, Pago::count());
    }

    public function test_ca_23_4_valor_cero(): void
    {
        $orden = $this->orden42(pagado: 10000);
        $this->actingAs($this->duena);

        $this->pagar($orden, '0', $this->efectivo)
            ->assertSessionHasErrors(['valor' => 'El valor debe ser mayor que cero.']);

        $this->assertSame(1, Pago::count());
    }

    public function test_ca_23_5_doble_toque(): void
    {
        $orden = $this->orden42(pagado: 10000);
        $this->actingAs($this->duena);
        $datos = $this->datos('10000', $this->efectivo);

        $primero = $this->post(route('pagos.guardar', $orden), $datos);
        $segundo = $this->post(route('pagos.guardar', $orden), $datos);

        $this->assertSame(2, Pago::count());
        $this->assertSame($primero->headers->get('Location'), $segundo->headers->get('Location'));
        $this->assertSame(11000, app(DetalleDeOrden::class)->obtener($orden->fresh())['saldo']->valor());
    }

    public function test_ca_23_6_despues_de_entregar(): void
    {
        $orden = $this->orden(40, [['entregada', 20000]], pagado: 8000);
        $this->actingAs($this->duena);

        $this->pagar($orden, '12.000', $this->efectivo)->assertSessionHasNoErrors();

        $detalle = app(DetalleDeOrden::class)->obtener($orden->fresh());
        $this->assertSame(EstadoDeOrden::Entregada, $detalle['estado']);
        $this->assertSame(EstadoDePago::Pagada, $detalle['estadoDePago']);
    }

    public function test_ca_23_7_orden_cancelada(): void
    {
        $orden = $this->orden(43, [['pendiente', 23000]], cancelada: true);
        $this->actingAs($this->duena);
        $motivo = 'No se pueden registrar pagos en una orden cancelada.';

        $this->get(route('pagos.nuevo', $orden))
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['orden' => $motivo]);
        $this->pagar($orden, '5000', $this->efectivo)
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['orden' => $motivo]);

        $this->assertSame(0, Pago::count());
        $this->followingRedirects()->get(route('pagos.nuevo', $orden))
            ->assertSee($motivo)
            ->assertDontSee(route('pagos.nuevo', $orden));
    }

    public function test_rn_28_el_abono_no_puede_superar_el_saldo(): void
    {
        $orden = $this->orden42(pagado: 10000);
        $this->actingAs($this->duena);
        $registrar = app(RegistrarPago::class);

        // Saldo de $21.000: $25.000 se rechaza
        $this->assertRegla('RN-28', 'El pago no puede superar el saldo pendiente de $21.000', fn () => $registrar->ejecutar($orden, 25000, $this->efectivo->id, (string) Str::uuid()));

        // Mientras la pantalla mostraba $21.000, alguien registró $15.000: se compara con el saldo al guardar
        Pago::factory()->create(['orden_id' => $orden->id, 'metodo_pago_id' => $this->nequi->id, 'valor' => 15000]);
        $this->assertRegla('RN-28', 'El pago no puede superar el saldo pendiente de $6.000', fn () => $registrar->ejecutar($orden, 21000, $this->efectivo->id, (string) Str::uuid()));

        // El saldo exacto sí se acepta y la orden queda Pagada
        $registrar->ejecutar($orden, 6000, $this->efectivo->id, (string) Str::uuid());
        $this->assertSame(EstadoDePago::Pagada, app(DetalleDeOrden::class)->obtener($orden->fresh())['estadoDePago']);
    }

    public function test_rn_30_pagos_despues_de_entregar(): void
    {
        $entregada = $this->orden(40, [['entregada', 20000]], pagado: 8000);
        $cancelada = $this->orden(43, [['pendiente', 23000]], cancelada: true);
        $this->actingAs($this->duena);
        $registrar = app(RegistrarPago::class);

        // Marta se llevó su orden debiendo $12.000 y vuelve a la semana a pagarlos
        $registrar->ejecutar($entregada, 12000, $this->efectivo->id, (string) Str::uuid());
        $this->assertSame(EstadoDePago::Pagada, app(DetalleDeOrden::class)->obtener($entregada->fresh())['estadoDePago']);

        $this->assertRegla('RN-30', 'No se pueden registrar pagos en una orden cancelada.', fn () => $registrar->ejecutar($cancelada, 5000, $this->efectivo->id, (string) Str::uuid()));
    }

    public function test_la_ficha_del_cliente_refleja_los_pagos_registrados(): void
    {
        // HU-05 se vuelve a probar con pagos registrados de verdad (plan de sprints)
        $orden42 = $this->orden42();
        $orden40 = $this->orden(40, [['entregada', 20000]]);
        $this->actingAs($this->duena);

        $this->pagar($orden42, '10000', $this->efectivo)->assertSessionHasNoErrors();
        $this->pagar($orden40, '8000', $this->nequi)->assertSessionHasNoErrors();

        $this->assertSame(33000, app(FichaDeCliente::class)->obtener($this->marta)['debe']->valor());
        $this->get(route('clientes.ficha', $this->marta))
            ->assertSeeInOrder(['Debe en total', '$33.000', 'Órdenes #0042 y #0040', '#0042', '$21.000', '#0040', '$12.000']);
    }

    /**
     * La #0042 de Marta: vale $31.000 con un pantalón de $15.000 y dos camisas de $8.000.
     */
    private function orden42(int $pagado = 0): Orden
    {
        return $this->orden(42, [['terminada', 15000], ['terminada', 8000], ['pendiente', 8000]], $pagado);
    }

    /**
     * @param  list<array{0: string, 1: int}>  $prendas  estado y precio de cada prenda
     */
    private function orden(int $numero, array $prendas, int $pagado = 0, bool $cancelada = false): Orden
    {
        $orden = Orden::factory()->create([
            'cliente_id' => $this->marta->id,
            'numero' => $numero,
            'recibida_en' => '2026-09-07 09:15:00',
            'cancelada_en' => $cancelada ? '2026-09-12 11:00:00' : null,
        ]);

        foreach ($prendas as [$estado, $precio]) {
            Prenda::factory()->create([
                'orden_id' => $orden->id,
                'tipo_prenda_id' => $this->camisa->id,
                'precio' => $precio,
                'estado' => $estado,
                'entregada_en' => $estado === 'entregada' ? '2026-09-10 11:20:00' : null,
            ]);
        }

        if ($pagado > 0) {
            Pago::factory()->create(['orden_id' => $orden->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => $pagado, 'pagado_en' => '2026-09-07 09:20:00']);
        }

        return $orden;
    }

    /**
     * @return array<string, string>
     */
    private function datos(string $valor, MetodoPago $metodo): array
    {
        return ['token_formulario' => (string) Str::uuid(), 'valor' => $valor, 'metodo_pago_id' => (string) $metodo->id];
    }

    private function pagar(Orden $orden, string $valor, MetodoPago $metodo): TestResponse
    {
        return $this->from(route('pagos.nuevo', $orden))->post(route('pagos.guardar', $orden), $this->datos($valor, $metodo));
    }

    private function assertRegla(string $regla, string $mensaje, callable $accion): void
    {
        try {
            $accion();
            $this->fail("Se esperaba {$regla}");
        } catch (ReglaIncumplida $error) {
            $this->assertSame([$regla, $mensaje], [$error->regla, $error->mensajeParaUsuaria]);
        }
    }
}
