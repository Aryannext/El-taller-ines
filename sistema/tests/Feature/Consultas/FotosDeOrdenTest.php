<?php

namespace Tests\Feature\Consultas;

use App\Aplicacion\Consultas\FotosDeOrden;
use App\Modelos\Cliente;
use App\Modelos\Foto;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * HU-18 · Ver las fotos de una orden para reconocer las prendas. Ver una foto ampliada (CA-18.2) se revisa a mano en PM-05;
 * aquí se comprueba que cada foto lleva a su imagen completa. Los datos se crean antes de iniciar sesión.
 */
class FotosDeOrdenTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Orden $orden42;

    private Prenda $camisaSinFotos;

    private Foto $pantalonCompleto;

    private Foto $basta;

    private Foto $camisaBlanca;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('privado');
        $this->duena = Usuario::factory()->create();
        $negocio = ['negocio_id' => $this->duena->negocio_id];
        $marta = Cliente::factory()->create([...$negocio, 'nombre' => 'Marta Rincón']);
        $pantalon = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Pantalón']);
        $camisa = TipoPrenda::factory()->create([...$negocio, 'nombre' => 'Camisa']);

        // La #0042: un pantalón con 2 fotos, una camisa con 1 y otra camisa sin fotos
        $this->orden42 = Orden::factory()->create(['cliente_id' => $marta->id, 'numero' => 42]);
        $conFotos = Prenda::factory()->create(['orden_id' => $this->orden42->id, 'tipo_prenda_id' => $pantalon->id, 'descripcion_arreglo' => 'Subir basta 3 cm', 'estado' => 'terminada']);
        $camisaBlanca = Prenda::factory()->create(['orden_id' => $this->orden42->id, 'tipo_prenda_id' => $camisa->id, 'descripcion_arreglo' => 'Entallar los costados', 'estado' => 'terminada']);
        $this->camisaSinFotos = Prenda::factory()->create(['orden_id' => $this->orden42->id, 'tipo_prenda_id' => $camisa->id, 'descripcion_arreglo' => 'Entallar y acortar mangas']);

        // Registradas en desorden: la pantalla las muestra por su posición
        $this->basta = Foto::factory()->create(['prenda_id' => $conFotos->id, 'posicion' => 2]);
        $this->pantalonCompleto = Foto::factory()->create(['prenda_id' => $conFotos->id, 'posicion' => 1]);
        $this->camisaBlanca = Foto::factory()->create(['prenda_id' => $camisaBlanca->id, 'posicion' => 1]);
        Storage::disk('privado')->put($this->basta->ruta, 'detalle de la basta');
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_18_1_fotos_agrupadas(): void
    {
        $this->actingAs($this->duena);

        $vista = app(FotosDeOrden::class)->obtener($this->orden42);
        $this->assertSame(
            [[$this->pantalonCompleto->id, $this->basta->id], [$this->camisaBlanca->id], []],
            $vista['orden']->prendas->map(fn (Prenda $prenda) => $prenda->fotos->pluck('id')->all())->all(),
        );

        $pagina = $this->get(route('fotos.de-orden', $this->orden42))
            ->assertOk()
            ->assertSeeInOrder([
                'Fotos de la', '#0042', 'Marta Rincón · 3 prendas',
                'Toca una foto para verla grande y comparar con las prendas del rincón.',
                'Pantalón', 'Subir basta 3 cm', 'Terminada', route('fotos.mostrar', $this->pantalonCompleto), route('fotos.mostrar', $this->basta),
                'Camisa', 'Entallar los costados', route('fotos.mostrar', $this->camisaBlanca),
                'Camisa', 'Entallar y acortar mangas', 'Pendiente', 'Esta prenda no tiene fotos.', route('prendas.editar', [$this->orden42, $this->camisaSinFotos]),
            ]);

        // CA-18.2: cada foto lleva a su imagen completa; con JavaScript se abre en el visor
        foreach ([$this->pantalonCompleto, $this->basta, $this->camisaBlanca] as $foto) {
            $pagina->assertSee('<a class="foto-ampliable" href="'.route('fotos.mostrar', $foto).'" data-ampliar>', false);
        }

        // Desde el detalle de la orden se llega con «Ver fotos juntas»
        $detalle = $this->get(route('ordenes.detalle', $this->orden42))
            ->assertSee('<a href="'.route('fotos.de-orden', $this->orden42).'">Ver fotos juntas</a>', false);

        // …y tocando las miniaturas de una prenda, que llevan directo a sus fotos grandes.
        // En PM-05 el enlace del título pasó desapercibido: las miniaturas son lo que se toca
        foreach ([$this->pantalonCompleto->prenda_id => 'Pantalón', $this->camisaBlanca->prenda_id => 'Camisa'] as $prendaId => $tipo) {
            $detalle->assertSee(
                '<a class="miniaturas" href="'.route('fotos.de-orden', $this->orden42).'#prenda-'.$prendaId.'" aria-label="Ver las fotos de '.$tipo.'">',
                false
            );
            $pagina->assertSee('id="prenda-'.$prendaId.'"', false);
        }
    }

    public function test_ca_18_3_fotos_privadas(): void
    {
        $enlace = route('fotos.mostrar', $this->basta);

        // Sin sesión, el enlace lleva a iniciar sesión y la imagen no sale
        $sinSesion = $this->get($enlace)->assertRedirect(route('sesion.formulario'));
        $this->assertStringNotContainsString('detalle de la basta', (string) $sinSesion->getContent());
        $this->get(route('fotos.de-orden', $this->orden42))->assertRedirect(route('sesion.formulario'));

        // Con la sesión del negocio sí se ve, y el navegador no la guarda (CA-01.4)
        $this->actingAs($this->duena);
        $conSesion = $this->get($enlace)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->assertStringContainsString('no-store', (string) $conSesion->headers->get('Cache-Control'));
        $this->assertSame('detalle de la basta', file_get_contents($conSesion->baseResponse->getFile()->getPathname()));

        // Al salir, el mismo enlace ya no la entrega
        $this->post(route('sesion.salir'));
        $this->get($enlace)->assertRedirect(route('sesion.formulario'));
    }

    public function test_rnf_25_una_foto_de_otro_negocio_no_se_encuentra(): void
    {
        $deOtroTaller = Foto::factory()->create();
        $this->actingAs($this->duena);
        $fotosDeOrden = app(FotosDeOrden::class);

        $this->assertTrue($fotosDeOrden->foto((string) $this->basta->id)?->is($this->basta));
        $this->assertNull($fotosDeOrden->foto((string) $deOtroTaller->id));
        $this->assertNull($fotosDeOrden->foto('9999'));
        $this->assertNull($fotosDeOrden->foto('1 OR 1=1'));
        $this->get(route('fotos.mostrar', $deOtroTaller))->assertNotFound();
    }
}
