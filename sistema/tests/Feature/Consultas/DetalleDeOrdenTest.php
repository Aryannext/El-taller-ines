<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Pagos\EstadoDePago;
use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\Foto;
use App\Modelos\MetodoPago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HU-14 · Consultar el detalle de una orden. Las órdenes son las de los mockups (docs/03-diseno/mockups/README.md).
 * Los datos se crean antes de iniciar sesión, porque al crear se asigna el negocio de la sesión.
 */
class DetalleDeOrdenTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $pantalon;

    private TipoPrenda $camisa;

    private MetodoPago $efectivo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $this->marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón', 'celular' => '3104567890']);
        $this->pantalon = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Pantalón']);
        $this->camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);
        $this->efectivo = MetodoPago::factory()->create([...$negocio, 'nombre' => 'Efectivo']);
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_14_1_orden_en_proceso(): void
    {
        Storage::fake('privado');
        $orden = Orden::factory()->create([
            'cliente_id' => $this->marta->id,
            'numero' => 42,
            'recibida_en' => '2026-09-07 09:15:00',
            'fecha_entrega_acordada' => '2026-09-20',
        ]);
        $pantalon = $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 15000, 'terminada');
        $this->prenda($orden, $this->camisa, 'Entallar los costados', 8000, 'en_proceso');
        $this->prenda($orden, $this->camisa, 'Entallar y acortar mangas', 8000, 'pendiente');
        $foto = Foto::factory()->create(['prenda_id' => $pantalon->id]);
        Storage::disk('privado')->put($foto->ruta, 'imagen del pantalón');
        Pago::factory()->create(['orden_id' => $orden->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => 10000, 'pagado_en' => '2026-09-07 09:20:00']);

        $this->actingAs($this->duena);

        $detalle = app(DetalleDeOrden::class)->obtener($orden);
        $this->assertSame(EstadoDeOrden::EnProceso, $detalle['estado']);
        $this->assertSame(EstadoDePago::PorCobrar, $detalle['estadoDePago']);
        $this->assertSame(31000, $detalle['valor']->valor());
        $this->assertSame(21000, $detalle['saldo']->valor());

        $this->get(route('ordenes.detalle', $orden))
            ->assertOk()
            ->assertSeeInOrder([
                '#0042', 'Marta Rincón', '310 456 7890',
                'En proceso', 'Por cobrar',
                'Recibida', 'Lunes 7 sep 2026', 'Entrega acordada', 'Domingo 20 sep 2026',
                'Pantalón', 'Subir basta 3 cm', 'Terminada', route('fotos.mostrar', $foto), '$15.000',
                'Camisa', 'Entallar los costados', 'En proceso', 'Sin foto', '$8.000',
                'Camisa', 'Entallar y acortar mangas', 'Pendiente', 'Sin foto', '$8.000',
                'Valor de la orden', '$31.000',
                'Abono · 7 sep 2026 · Efectivo', '− $10.000',
                'Saldo', '$21.000',
            ])
            ->assertDontSee('Quedó lista')
            ->assertDontSee('Avisos al cliente');

        // La miniatura se entrega desde el disco privado (RNF-25)
        $miniatura = $this->get(route('fotos.mostrar', $foto))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertSame('imagen del pantalón', file_get_contents($miniatura->baseResponse->getFile()->getPathname()));
    }

    public function test_ca_14_2_orden_entregada(): void
    {
        $orden = Orden::factory()->create([
            'cliente_id' => $this->marta->id,
            'numero' => 40,
            'recibida_en' => '2026-09-01 10:00:00',
            'fecha_entrega_acordada' => '2026-09-10',
            'lista_en' => '2026-09-09 16:00:00',
        ]);
        $this->prenda($orden, $this->pantalon, 'Subir basta 3 cm', 12000, 'entregada', '2026-09-10 11:20:00');
        $this->prenda($orden, $this->camisa, 'Entallar', 8000, 'entregada', '2026-09-12 17:45:00');
        Pago::factory()->create(['orden_id' => $orden->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => 8000]);
        Pago::factory()->anulado('Se registró dos veces')->create(['orden_id' => $orden->id, 'metodo_pago_id' => $this->efectivo->id, 'valor' => 8000]);
        Aviso::factory()->create([
            'orden_id' => $orden->id,
            'ciclo_lista_en' => '2026-09-09 16:00:00',
            'canal' => 'api_oficial',
            'mensaje' => 'Hola Marta, tu orden #0040 está lista para recoger.',
            'generado_en' => '2026-09-09 16:00:05',
            'resuelto_en' => '2026-09-09 16:00:30',
        ]);

        $this->actingAs($this->duena);

        $detalle = app(DetalleDeOrden::class)->obtener($orden);
        $this->assertSame(EstadoDeOrden::Entregada, $detalle['estado']);
        // RN-23: la entrega real es la de la última prenda
        $this->assertSame('2026-09-12 17:45', $detalle['entregadaEn']?->format('Y-m-d H:i'));
        // RN-27: el pago anulado no cuenta; entregada y Por cobrar a la vez (RN-29)
        $this->assertSame(12000, $detalle['saldo']->valor());
        $this->assertSame(EstadoDePago::PorCobrar, $detalle['estadoDePago']);

        $this->get(route('ordenes.detalle', $orden))
            ->assertOk()
            ->assertSeeInOrder([
                'Entregada', 'Por cobrar',
                'Quedó lista', '9 sep 2026 · 4:00 p. m.',
                'Entregada', '12 sep 2026 · 5:45 p. m.',
                'Abono · 14 sep 2026 · Efectivo', '− $8.000',
                'Abono · 14 sep 2026 · Efectivo', 'Anulado el 14 sep 2026: Se registró dos veces',
                'Saldo', '$12.000',
                'Avisos al cliente', '9 sep 2026 · 4:00 p. m.', 'API oficial', 'Hola Marta, tu orden #0040 está lista para recoger.', 'Enviado',
            ]);
    }

    public function test_ca_14_3_orden_de_otro_negocio(): void
    {
        $deOtroNegocio = Orden::factory()->create(['numero' => 42]);
        Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => 7]);

        $this->actingAs($this->duena);

        // El enlace de la #0042 del otro taller responde igual que un número que no existe
        $this->get(route('ordenes.detalle', $deOtroNegocio))->assertNotFound();
        $this->get('/ordenes/9999')->assertNotFound();
        $this->get(route('ordenes.detalle', 7))->assertOk();
    }

    private function prenda(Orden $orden, TipoPrenda $tipo, string $arreglo, int $precio, string $estado, ?string $entregadaEn = null): Prenda
    {
        return Prenda::factory()->create([
            'orden_id' => $orden->id,
            'tipo_prenda_id' => $tipo->id,
            'descripcion_arreglo' => $arreglo,
            'precio' => $precio,
            'estado' => $estado,
            'entregada_en' => $entregadaEn,
        ]);
    }
}
