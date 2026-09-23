<?php

namespace Tests\Feature\Configuracion;

use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-35 · Cambiar el plazo tras el cual una orden lista se considera sin reclamar (RN-35).
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class CambiarPlazoSinReclamarTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $this->fijarReloj('2026-09-15 09:00:00');
    }

    public function test_ca_35_1_nuevo_plazo(): void
    {
        // La #0042 quedó lista el 1 de agosto: lleva 45 días esperando, más que los 30 del plazo inicial
        $marta = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Marta Rincón']);
        $orden = Orden::factory()->create([
            'cliente_id' => $marta->id,
            'numero' => 42,
            'recibida_en' => '2026-07-28 09:00:00',
            'lista_en' => '2026-08-01 16:00:00',
        ]);
        Prenda::factory()->create(['orden_id' => $orden->id, 'precio' => 15000, 'estado' => 'terminada']);
        $this->actingAs($this->duena);

        $this->assertSame(30, $this->duena->negocio->fresh()->dias_sin_reclamar);
        $this->get(route('seguimiento.sin-reclamar'))->assertOk()->assertSee('#0042');

        $this->get(route('ajustes'))->assertOk()->assertSeeInOrder(['Órdenes sin reclamar', 'Una orden lista queda sin reclamar después de', 'value="30"', 'días']);

        $this->put(route('ajustes.plazo'), ['dias_sin_reclamar' => '60'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('exito', 'El plazo quedó en 60 días.')
            ->assertRedirect(route('ajustes'));

        // Con el plazo en 60 días, la #0042 ya no está sin reclamar
        $this->assertSame(60, $this->duena->negocio->fresh()->dias_sin_reclamar);
        $this->get(route('seguimiento.sin-reclamar'))->assertOk()->assertDontSee('#0042');
        $this->get(route('ajustes'))->assertSee('value="60"', false);
    }

    public function test_ca_35_2_fuera_de_rango(): void
    {
        $this->actingAs($this->duena);
        $mensaje = 'El plazo debe estar entre 1 y 365 días.';

        foreach (['0', '400', '-3', 'muchos'] as $invalido) {
            $this->from(route('ajustes'))->put(route('ajustes.plazo'), ['dias_sin_reclamar' => $invalido])
                ->assertRedirect(route('ajustes'))
                ->assertSessionHasErrors(['dias_sin_reclamar' => $mensaje]);
        }

        $this->assertSame(30, $this->duena->negocio->fresh()->dias_sin_reclamar);
        $this->followingRedirects()->from(route('ajustes'))->put(route('ajustes.plazo'), ['dias_sin_reclamar' => '0'])->assertSee($mensaje);

        // Los extremos sí se admiten
        foreach ([1, 365] as $valido) {
            $this->put(route('ajustes.plazo'), ['dias_sin_reclamar' => (string) $valido])->assertSessionHasNoErrors();
            $this->assertSame($valido, $this->duena->negocio->fresh()->dias_sin_reclamar);
        }
    }

    public function test_rn_01_el_plazo_es_de_cada_negocio(): void
    {
        $otraDuena = Usuario::factory()->create();
        $this->actingAs($this->duena);

        $this->put(route('ajustes.plazo'), ['dias_sin_reclamar' => '90'])->assertSessionHasNoErrors();

        $this->assertSame(90, $this->duena->negocio->fresh()->dias_sin_reclamar);
        $this->assertSame(30, $otraDuena->negocio->fresh()->dias_sin_reclamar);
    }
}
