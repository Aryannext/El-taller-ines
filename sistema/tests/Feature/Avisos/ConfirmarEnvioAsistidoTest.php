<?php

namespace Tests\Feature\Avisos;

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
 * HU-29 · La dueña confirma que envió el aviso desde su WhatsApp (RN-41). Con los datos de AvisosPorEnviarTest:
 * la #0042 de Marta quedó lista a las 4:00 p. m. con saldo de $21.000, y la dueña confirma a las 4:05 p. m.
 */
class ConfirmarEnvioAsistidoTest extends TestCase
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
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón', 'celular' => '3104567890']);
        $camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);
        $this->orden42 = Orden::factory()->create(['cliente_id' => $marta->id, 'numero' => 42, 'recibida_en' => '2026-09-07 09:15:00', 'lista_en' => '2026-09-15 16:00:00']);

        $prenda = ['orden_id' => $this->orden42->id, 'tipo_prenda_id' => $camisa->id, 'estado' => 'terminada'];
        Prenda::factory()->create([...$prenda, 'descripcion_arreglo' => 'Subir basta 3 cm', 'precio' => 15000]);
        Prenda::factory()->create([...$prenda, 'descripcion_arreglo' => 'Entallar los costados', 'precio' => 8000]);
        $this->mangas = Prenda::factory()->create([...$prenda, 'descripcion_arreglo' => 'Entallar y acortar mangas', 'precio' => 8000]);
        Pago::factory()->create(['orden_id' => $this->orden42->id, 'valor' => 10000, 'pagado_en' => '2026-09-07 09:20:00']);
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

    public function test_ca_29_3_confirmo_el_envio(): void
    {
        $this->get(route('avisos.abrir-whatsapp', $this->aviso))->assertRedirectContains('wa.me');

        $this->post(route('avisos.confirmar-envio', $this->aviso))
            ->assertRedirect(route('avisos.pendientes'))
            ->assertSessionHas('exito', 'El aviso a Marta Rincón quedó registrado como enviado.');

        // RN-41: queda la constancia con el canal, el mensaje de ese momento y la hora
        $aviso = $this->aviso->fresh();
        $this->assertSame(
            ['enviado', 'asistido', '2026-09-15 16:05'],
            [$aviso->estado, $aviso->canal, $aviso->resuelto_en?->format('Y-m-d H:i')],
        );
        $this->assertSame('Hola Marta, tu orden #0042 del taller está lista para recoger. Prendas listas: 3. Saldo pendiente: $21.000. Te esperamos.', $aviso->mensaje);

        // Y sale de los pendientes
        $this->get(route('avisos.pendientes'))
            ->assertOk()
            ->assertSee('El aviso a Marta Rincón quedó registrado como enviado.')
            ->assertSee('No hay avisos por enviar.');
    }

    public function test_ca_29_4_no_lo_envie(): void
    {
        // Abre WhatsApp y vuelve sin confirmar: el aviso sigue esperando
        $this->get(route('avisos.abrir-whatsapp', $this->aviso))->assertRedirectContains('wa.me');

        $aviso = $this->aviso->fresh();
        $this->assertSame(['pendiente_asistido', null, null, null], [$aviso->estado, $aviso->canal, $aviso->mensaje, $aviso->resuelto_en]);
        $this->get(route('avisos.pendientes'))->assertOk()->assertSee('Marta Rincón');
    }

    public function test_un_aviso_ya_resuelto_no_se_confirma_otra_vez(): void
    {
        $this->post(route('avisos.confirmar-envio', $this->aviso))->assertSessionHasNoErrors();

        $this->post(route('avisos.confirmar-envio', $this->aviso))
            ->assertSessionHasErrors(['aviso' => 'Este aviso ya no está pendiente de envío.']);
        $this->assertSame('2026-09-15 16:05', $this->aviso->fresh()->resuelto_en?->format('Y-m-d H:i'));
    }

    public function test_si_la_orden_dejo_de_estar_lista_el_aviso_se_descarta(): void
    {
        // RN-39: una prenda volvió a En proceso antes de que la dueña confirmara
        $this->mangas->update(['estado' => 'en_proceso']);
        $this->orden42->update(['lista_en' => null]);

        $this->post(route('avisos.confirmar-envio', $this->aviso))
            ->assertRedirect(route('avisos.pendientes'))
            ->assertSessionHasErrors(['aviso' => 'La orden ya no está lista, así que su aviso salió de la lista.']);

        $aviso = $this->aviso->fresh();
        $this->assertSame(['descartado', null, '2026-09-15 16:05'], [$aviso->estado, $aviso->mensaje, $aviso->resuelto_en?->format('Y-m-d H:i')]);
    }
}
