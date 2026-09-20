<?php

namespace Tests\Feature\Avisos;

use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-31 · La constancia de lo que se le avisó al cliente, en el detalle de la orden (CU-31, PT-09, RN-41).
 * Sirve para responder con seguridad cuando un cliente dice que no le avisaron.
 */
class AvisosDeLaOrdenTest extends TestCase
{
    use RefreshDatabase;

    private Orden $orden42;

    protected function setUp(): void
    {
        parent::setUp();

        $duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $duena->negocio_id];
        $marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón', 'celular' => '3104567890']);
        $camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);

        $this->orden42 = Orden::factory()->create([
            'cliente_id' => $marta->id,
            'numero' => 42,
            'fecha_entrega_acordada' => '2026-09-14',
            'lista_en' => '2026-09-15 16:00:00',
        ]);
        Prenda::factory()->create(['orden_id' => $this->orden42->id, 'tipo_prenda_id' => $camisa->id, 'estado' => 'terminada']);

        $this->fijarReloj('2026-09-16 09:00:00');
        $this->actingAs($duena);
    }

    public function test_ca_31_1_historial_de_avisos(): void
    {
        // El primer ciclo se descartó: la orden dejó de estar lista antes de que saliera el aviso (RN-39)
        Aviso::factory()->create([
            'orden_id' => $this->orden42->id,
            'ciclo_lista_en' => '2026-09-12 09:00:00',
            'estado' => 'descartado',
            'canal' => null,
            'mensaje' => null,
            'generado_en' => '2026-09-12 09:00:05',
            'resuelto_en' => '2026-09-12 11:30:00',
        ]);

        // El segundo sí salió por el canal automático
        Aviso::factory()->create([
            'orden_id' => $this->orden42->id,
            'ciclo_lista_en' => '2026-09-15 16:00:00',
            'estado' => 'enviado',
            'canal' => 'evolution_api',
            'mensaje' => 'Hola Marta, tu orden #0042 del taller está lista para recoger.',
            'generado_en' => '2026-09-15 16:00:05',
            'resuelto_en' => '2026-09-15 16:00:12',
        ]);

        // RN-41: de cada aviso, su fecha y hora, su canal, su mensaje y su resultado
        $this->get(route('ordenes.detalle', $this->orden42))
            ->assertOk()
            ->assertSeeInOrder([
                'Avisos al cliente',
                '12 sep 2026',
                '11:30 a. m.',
                'No se envió: la orden dejó de estar lista antes de enviarlo.',
                'Descartado',
                '15 sep 2026',
                '4:00 p. m.',
                'WhatsApp automático',
                'Hola Marta, tu orden #0042 del taller está lista para recoger.',
                'Enviado',
            ]);
    }

    public function test_una_orden_sin_avisos_no_muestra_la_seccion(): void
    {
        $this->get(route('ordenes.detalle', $this->orden42))
            ->assertOk()
            ->assertDontSee('Avisos al cliente');
    }
}
