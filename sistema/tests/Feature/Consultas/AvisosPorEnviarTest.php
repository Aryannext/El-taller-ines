<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\AvisosPorEnviar;
use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-29 · Los avisos que esperan salir desde el WhatsApp de la dueña (PT-18). La #0042 de Marta quedó lista el martes
 * 15 de septiembre a las 4:00 p. m. y debe $21.000; son las 4:05 p. m.
 */
class AvisosPorEnviarTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Orden $orden42;

    private Prenda $mangas;

    private Aviso $aviso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        // RN-46: el aviso nombra al taller, así que su nombre no puede ser el que invente la factory
        $this->duena->negocio->update(['nombre' => 'Modistería Inés']);
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón', 'celular' => '3104567890']);
        $camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);

        $this->orden42 = Orden::factory()->create(['cliente_id' => $marta->id, 'numero' => 42, 'recibida_en' => '2026-09-07 09:15:00', 'lista_en' => '2026-09-15 16:00:00']);
        $prenda = ['orden_id' => $this->orden42->id, 'tipo_prenda_id' => $camisa->id, 'estado' => 'terminada'];
        Prenda::factory()->create([...$prenda, 'descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => 15000]);
        Prenda::factory()->create([...$prenda, 'descripcion_arreglo' => 'Entallar los costados', 'precio' => 8000]);
        $this->mangas = Prenda::factory()->create([...$prenda, 'descripcion_arreglo' => 'Entallar y acortar mangas', 'precio' => 8000]);
        Pago::factory()->create(['orden_id' => $this->orden42->id, 'valor' => 10000, 'pagado_en' => '2026-09-07 09:20:00']);

        // El envío automático no salió: la API no está configurada (RN-40)
        $this->aviso = Aviso::factory()->create([
            'orden_id' => $this->orden42->id,
            'ciclo_lista_en' => '2026-09-15 16:00:00',
            'estado' => 'pendiente_asistido',
            'canal' => null,
            'mensaje' => null,
            'generado_en' => '2026-09-15 16:00:05',
            'resuelto_en' => null,
        ]);

        $this->fijarReloj('2026-09-15 16:05:00');
        $this->actingAs($this->duena);
    }

    public function test_ca_29_1_aviso_pendiente(): void
    {
        // Se alcanza desde el panel, que dice cuántos esperan
        $this->get(route('panel'))
            ->assertOk()
            ->assertSee('Hay 1 aviso por enviar desde tu WhatsApp.')
            ->assertSee(route('avisos.pendientes'));

        $this->get(route('avisos.pendientes'))
            ->assertOk()
            ->assertSeeInOrder([
                'Avisos por enviar',
                '1 cliente espera saber que su ropa está lista',
                'Marta Rincón',
                '#0042',
                '310 456 7890',
                'Por enviar',
                'Mensaje',
                'Hola Marta, le escribimos de Modistería Inés. Su orden #0042 ya está lista 🧵 Son 3 prendas, con un saldo de $21.000. La esperamos cuando pueda pasar.',
                'Abrir WhatsApp y enviar',
                '¿Ya lo enviaste?',
            ]);
    }

    public function test_ca_29_2_abrir_whatsapp(): void
    {
        $esperado = 'https://wa.me/573104567890?text='.rawurlencode(
            'Hola Marta, le escribimos de Modistería Inés. Su orden #0042 ya está lista 🧵 Son 3 prendas, con un saldo de $21.000. La esperamos cuando pueda pasar.',
        );

        $this->get(route('avisos.abrir-whatsapp', $this->aviso))->assertRedirect($esperado);

        // Abrir WhatsApp no resuelve el aviso: eso lo hace la confirmación (CA-29.4)
        $this->assertSame('pendiente_asistido', $this->aviso->fresh()->estado);
    }

    public function test_ca_30_2_aviso_asistido_pendiente(): void
    {
        // El pantalón vuelve a En proceso antes de que la dueña envíe el aviso
        $this->mangas->update(['estado' => 'en_proceso']);
        $this->orden42->update(['lista_en' => null]);

        $this->assertSame(0, app(AvisosPorEnviar::class)->contar());
        $this->get(route('avisos.pendientes'))
            ->assertOk()
            ->assertSee('No hay avisos por enviar.')
            ->assertDontSee('Marta Rincón');
        $this->get(route('panel'))->assertOk()->assertDontSee('aviso por enviar');
    }

    public function test_los_avisos_ya_resueltos_no_aparecen(): void
    {
        $this->aviso->update(['estado' => 'enviado', 'canal' => 'api_oficial', 'mensaje' => 'Hola Marta…', 'resuelto_en' => '2026-09-15 16:02:00']);

        $this->assertSame([], app(AvisosPorEnviar::class)->listar());
        $this->get(route('avisos.pendientes'))->assertOk()->assertSee('No hay avisos por enviar.');
    }
}
