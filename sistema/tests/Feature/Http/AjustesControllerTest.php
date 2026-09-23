<?php

namespace Tests\Feature\Http;

use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-38 · El nombre del taller y el de la usuaria, que antes ponía el instalador (RF-43, RN-46, RN-47).
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class AjustesControllerTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create(['nombre' => 'Dueña del taller']);
        $this->duena->negocio->update(['nombre' => 'Taller de costura']);
        $this->fijarReloj('2026-09-23 14:30:00');
    }

    public function test_ca_38_1_el_nombre_de_mi_taller(): void
    {
        // Una orden lista, con su aviso por enviar: ahí es donde el nombre del taller llega al cliente
        $marta = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Marta Rincón']);
        $orden = Orden::factory()->create([
            'cliente_id' => $marta->id,
            'numero' => 42,
            'recibida_en' => '2026-09-20 09:00:00',
            'lista_en' => '2026-09-23 10:00:00',
        ]);
        Prenda::factory()->create(['orden_id' => $orden->id, 'precio' => 15000, 'estado' => 'terminada']);
        $aviso = Aviso::factory()->create([
            'orden_id' => $orden->id,
            'ciclo_lista_en' => '2026-09-23 10:00:00',
            'estado' => 'pendiente_asistido',
            'canal' => null,
            'mensaje' => null,
            'resuelto_en' => null,
        ]);
        $this->actingAs($this->duena);

        $this->put(route('ajustes.taller'), ['nombre_negocio' => 'Modistería Inés', 'nombre_usuaria' => 'Inés'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ajustes'));

        $this->assertSame('Modistería Inés', $this->duena->negocio->fresh()->nombre);

        // Se ve en la pantalla Hoy…
        $this->get(route('panel'))->assertOk()->assertSee('Modistería Inés');

        // …y el aviso que espera a Marta dice de qué taller le escriben (RN-46)
        $this->get(route('avisos.pendientes'))
            ->assertOk()
            ->assertSee('le escribimos de Modistería Inés', false);
        $this->assertSame('pendiente_asistido', $aviso->fresh()->estado);
    }

    public function test_ca_38_2_mi_nombre(): void
    {
        $this->actingAs($this->duena);

        $this->get(route('panel'))->assertOk()->assertSee('Dueña del taller');

        $this->put(route('ajustes.taller'), ['nombre_negocio' => 'Modistería Inés', 'nombre_usuaria' => 'Inés'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Inés', $this->duena->fresh()->nombre);
        $this->get(route('panel'))->assertOk()->assertSee('Inés')->assertDontSee('Dueña del taller');
        $this->get(route('ajustes'))->assertOk()->assertSee('value="Inés"', false);
    }

    public function test_ca_38_3_el_saludo_con_la_hora(): void
    {
        $this->duena->update(['nombre' => 'Inés']);
        $this->actingAs($this->duena);

        // RN-47: el saludo sale de la hora del taller, no de la del servidor
        $saludos = [
            '2026-09-23 07:00:00' => 'Buenos días, Inés',
            '2026-09-23 11:59:00' => 'Buenos días, Inés',
            '2026-09-23 12:00:00' => 'Buenas tardes, Inés',
            '2026-09-23 18:59:00' => 'Buenas tardes, Inés',
            '2026-09-23 19:00:00' => 'Buenas noches, Inés',
            '2026-09-23 23:30:00' => 'Buenas noches, Inés',
        ];

        foreach ($saludos as $momento => $esperado) {
            $this->fijarReloj($momento);
            $this->get(route('panel'))->assertOk()->assertSee($esperado);
        }
    }

    public function test_ca_38_4_sin_dejarlo_en_blanco(): void
    {
        $this->actingAs($this->duena);

        $this->from(route('ajustes'))->put(route('ajustes.taller'), ['nombre_negocio' => '   ', 'nombre_usuaria' => 'Inés'])
            ->assertRedirect(route('ajustes'))
            ->assertSessionHasErrors(['nombre_negocio' => 'Tu taller necesita un nombre.']);

        $this->from(route('ajustes'))->put(route('ajustes.taller'), ['nombre_negocio' => 'Modistería Inés', 'nombre_usuaria' => ''])
            ->assertSessionHasErrors(['nombre_usuaria' => 'Escribe tu nombre.']);

        // Nada cambió: se conservan los dos nombres
        $this->assertSame('Taller de costura', $this->duena->negocio->fresh()->nombre);
        $this->assertSame('Dueña del taller', $this->duena->fresh()->nombre);
    }

    public function test_rn_01_los_nombres_son_de_cada_negocio(): void
    {
        $otraDuena = Usuario::factory()->create(['nombre' => 'Otra dueña']);
        $otraDuena->negocio->update(['nombre' => 'Arreglos Donde Rosa']);
        $this->actingAs($this->duena);

        $this->put(route('ajustes.taller'), ['nombre_negocio' => 'Modistería Inés', 'nombre_usuaria' => 'Inés'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Arreglos Donde Rosa', $otraDuena->negocio->fresh()->nombre);
        $this->assertSame('Otra dueña', $otraDuena->fresh()->nombre);
    }

    public function test_ca_16_3_agregar(): void
    {
        TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Camisa']);
        $this->actingAs($this->duena);

        $this->get(route('ajustes'))->assertOk()->assertSee('Agregar un tipo');

        $this->post(route('ajustes.tipos.agregar'), ['nombre' => 'Overol'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ajustes'));

        // Aparece al registrar una prenda nueva
        $overol = TipoPrenda::where('nombre', 'Overol')->sole();
        $this->assertTrue($overol->activo);
        $this->assertSame($this->duena->negocio_id, $overol->negocio_id);
        $this->get(route('ordenes.nueva'))->assertOk()->assertSee('Overol');

        // RN-43: dos tipos del mismo negocio no se llaman igual
        $this->from(route('ajustes'))->post(route('ajustes.tipos.agregar'), ['nombre' => 'Camisa'])
            ->assertSessionHasErrors(['nombre' => 'Ya existe un tipo de prenda con ese nombre.']);
        $this->assertSame(2, TipoPrenda::count());
    }
}
