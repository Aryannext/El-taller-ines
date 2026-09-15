<?php

namespace Tests\Feature\Fotos;

use App\Aplicacion\Ordenes\RegistrarOrden;
use App\Modelos\Cliente;
use App\Modelos\Foto;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\Soporte\ImagenDePrueba;
use Tests\TestCase;

/**
 * HU-17 · Tomar fotos de las prendas. Tomarla con la cámara del celular (CA-17.1) se prueba a mano en PM-04:
 * al servidor, una foto de la cámara y una de la galería le llegan igual. Los datos se crean antes de iniciar sesión.
 */
class AgregarFotoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $duena;

    private Cliente $marta;

    private TipoPrenda $vestido;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('privado');
        $this->duena = Usuario::factory()->create();
        $this->marta = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Marta Rincón']);
        $this->vestido = TipoPrenda::factory()->create(['negocio_id' => $this->duena->negocio_id, 'nombre' => 'Vestido']);
        $this->fijarReloj('2026-09-14 10:00:00');
    }

    public function test_ca_17_2_desde_la_galeria(): void
    {
        $this->actingAs($this->duena);

        // Al registrar la orden
        $this->registrar([UploadedFile::fake()->image('vestido.jpg', 1200, 1600)])->assertSessionHasNoErrors();

        $orden = Orden::sole();
        $vestido = Prenda::sole();
        $foto = $vestido->fotos()->sole();
        $this->assertSame(1, $foto->posicion);
        $this->assertStringStartsWith("fotos/{$this->duena->negocio_id}/", $foto->ruta);
        Storage::disk('privado')->assertExists($foto->ruta);
        $this->get(route('ordenes.detalle', $orden))->assertSee(route('fotos.mostrar', $foto));

        // Después, desde Corregir prenda
        $this->post(route('fotos.agregar', [$orden, $vestido]), ['fotos' => [UploadedFile::fake()->image('ruedo.png', 900, 900)]])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('prendas.editar', [$orden, $vestido]))
            ->assertSessionHas('exito', 'La foto quedó guardada.');

        $this->assertSame([1, 2], $vestido->fotos()->orderBy('posicion')->pluck('posicion')->all());
        $this->get(route('prendas.editar', [$orden, $vestido]))
            ->assertSee('2 de 3')
            ->assertSee(route('fotos.mostrar', $vestido->fotos()->where('posicion', 2)->sole()))
            ->assertSee('La foto quedó guardada.');
    }

    public function test_ca_17_3_maximo_tres(): void
    {
        [$orden, $vestido] = $this->ordenConVestido(fotos: 3);
        $this->actingAs($this->duena);
        $editar = route('prendas.editar', [$orden, $vestido]);

        $this->from($editar)
            ->post(route('fotos.agregar', [$orden, $vestido]), ['fotos' => [UploadedFile::fake()->image('cuarta.jpg', 600, 600)]])
            ->assertRedirect($editar)
            ->assertSessionHasErrors(['fotos' => 'Esta prenda ya tiene 3 fotos. Elimina una para agregar otra.']);

        $this->assertSame(3, $vestido->fotos()->count());
        $this->assertSame([], Storage::disk('privado')->allFiles());
        $this->get($editar)->assertSee('3 de 3')->assertDontSee(route('fotos.agregar', [$orden, $vestido]));

        // Al registrar una orden tampoco caben cuatro
        $this->registrar(array_map(fn () => UploadedFile::fake()->image('vestido.jpg', 600, 600), range(1, 4)))
            ->assertSessionHasErrors(['prendas.0.fotos' => 'Cada prenda puede tener hasta 3 fotos.']);
        $this->assertSame(1, Orden::count());
    }

    public function test_ca_17_4_sin_foto(): void
    {
        $this->actingAs($this->duena);

        $this->get(route('ordenes.nueva'))->assertSee('Sin foto: tómale una para reconocerla después.');

        $this->registrar([])->assertSessionHasNoErrors();

        $orden = Orden::sole();
        $vestido = Prenda::sole();
        $this->assertSame(0, Foto::count());
        // El detalle sugiere tomarle una y lleva a donde se agrega
        $this->get(route('ordenes.detalle', $orden))
            ->assertSeeInOrder(['Vestido', route('prendas.editar', [$orden, $vestido]), 'Sin foto · tomar una']);
        $this->get(route('prendas.editar', [$orden, $vestido]))
            ->assertSee('0 de 3')
            ->assertSee('Sin foto: tómale una para reconocerla después.');
    }

    public function test_ca_17_5_foto_pesada(): void
    {
        [$orden, $vestido] = $this->ordenConVestido(fotos: 0);
        $this->actingAs($this->duena);
        $pesada = ImagenDePrueba::pesada();
        $this->assertGreaterThan(5 * 1024 * 1024, $pesada->getSize());

        $this->post(route('fotos.agregar', [$orden, $vestido]), ['fotos' => [$pesada]])->assertSessionHasNoErrors();

        $foto = $vestido->fotos()->sole();
        $disco = Storage::disk('privado');
        [$ancho, $alto] = getimagesize($disco->path($foto->ruta));
        $this->assertLessThanOrEqual(1600, max($ancho, $alto));
        $this->assertLessThanOrEqual(400 * 1024, $disco->size($foto->ruta));
        $this->assertSame([$ancho, $alto, $disco->size($foto->ruta)], [$foto->ancho_px, $foto->alto_px, $foto->bytes]);
    }

    public function test_rn_17_fotos_de_una_prenda(): void
    {
        // Se registró con prisa y sin foto; después se le toman dos
        [$orden, $vestido] = $this->ordenConVestido(fotos: 0);
        $this->actingAs($this->duena);
        $agregar = route('fotos.agregar', [$orden, $vestido]);
        $posiciones = fn () => $vestido->fotos()->orderBy('posicion')->pluck('posicion')->all();

        $this->post($agregar, ['fotos' => [$this->imagen(), $this->imagen()]])->assertSessionHas('exito', 'Las fotos quedaron guardadas.');
        $this->assertSame([1, 2], $posiciones());

        // Con dos, no caben dos más: no se guarda ninguna, ni en la base ni en el disco
        $this->post($agregar, ['fotos' => [$this->imagen(), $this->imagen()]])
            ->assertSessionHasErrors(['fotos' => 'Cada prenda puede tener hasta 3 fotos.']);
        $this->assertSame([1, 2], $posiciones());
        $this->assertCount(2, Storage::disk('privado')->allFiles('fotos'));

        // Si se quita una, la nueva ocupa su lugar (eliminar con confirmación llega con HU-19)
        $vestido->fotos()->where('posicion', 1)->delete();
        $this->post($agregar, ['fotos' => [$this->imagen()]])->assertSessionHasNoErrors();
        $this->post($agregar, ['fotos' => [$this->imagen()]])->assertSessionHasNoErrors();
        $this->assertSame([1, 2, 3], $posiciones());

        $this->post($agregar, ['fotos' => [$this->imagen()]])
            ->assertSessionHasErrors(['fotos' => 'Esta prenda ya tiene 3 fotos. Elimina una para agregar otra.']);
    }

    public function test_rn_15_una_prenda_entregada_no_recibe_fotos(): void
    {
        [$orden, $vestido] = $this->ordenConVestido(fotos: 0, estado: 'entregada');
        $this->actingAs($this->duena);

        $this->post(route('fotos.agregar', [$orden, $vestido]), ['fotos' => [$this->imagen()]])
            ->assertRedirect(route('ordenes.detalle', $orden))
            ->assertSessionHasErrors(['prenda' => 'Esta prenda ya fue entregada y no se puede modificar.']);

        $this->assertSame(0, Foto::count());
        $this->assertSame([], Storage::disk('privado')->allFiles());
    }

    public function test_rnf_03_solo_se_aceptan_imagenes_de_hasta_10_mb(): void
    {
        [$orden, $vestido] = $this->ordenConVestido(fotos: 0);
        $this->actingAs($this->duena);
        $agregar = route('fotos.agregar', [$orden, $vestido]);

        $this->post($agregar, ['fotos' => [UploadedFile::fake()->create('lista.pdf', 100, 'application/pdf')]])
            ->assertSessionHasErrors(['fotos.0' => 'La foto debe ser JPG, PNG o WebP.']);
        $this->post($agregar, ['fotos' => [UploadedFile::fake()->image('enorme.jpg', 800, 800)->size(10241)]])
            ->assertSessionHasErrors(['fotos.0' => 'La foto no puede pesar más de 10 MB.']);
        $this->post($agregar, [])->assertSessionHasErrors(['fotos' => 'Toma o elige una foto.']);

        $this->assertSame(0, Foto::count());
    }

    public function test_rnf_13_si_la_orden_no_se_guarda_sus_fotos_no_quedan_en_el_disco(): void
    {
        Prenda::creating(function (Prenda $prenda): void {
            if ($prenda->descripcion_arreglo === 'falla a propósito') {
                throw new RuntimeException('Falla simulada a mitad de la orden');
            }
        });
        $this->actingAs($this->duena);
        // En variables: el archivo temporal de una imagen falsa se borra cuando el objeto deja de existir
        $fotoDelVestido = $this->imagen();
        $fotoDeLaFalda = $this->imagen();

        try {
            app(RegistrarOrden::class)->ejecutar($this->marta, new DateTimeImmutable('2026-09-20'), [
                ['tipo_prenda_id' => $this->vestido->id, 'tipo_otro' => null, 'descripcion_arreglo' => 'Subir ruedo', 'precio' => 16000, 'fotos' => [(string) $fotoDelVestido->getRealPath()]],
                ['tipo_prenda_id' => $this->vestido->id, 'tipo_otro' => null, 'descripcion_arreglo' => 'falla a propósito', 'precio' => 9000, 'fotos' => [(string) $fotoDeLaFalda->getRealPath()]],
            ], (string) Str::uuid());
            $this->fail('Se esperaba la falla simulada');
        } catch (RuntimeException) {
        }

        $this->assertSame(0, Orden::count());
        $this->assertSame(0, Foto::count());
        $this->assertSame([], Storage::disk('privado')->allFiles());
    }

    /**
     * Un vestido de la #0042 de Marta, con fotos ya registradas en las primeras posiciones.
     *
     * @return array{0: Orden, 1: Prenda}
     */
    private function ordenConVestido(int $fotos, string $estado = 'pendiente'): array
    {
        $orden = Orden::factory()->create(['cliente_id' => $this->marta->id, 'numero' => 42]);
        $vestido = Prenda::factory()->create([
            'orden_id' => $orden->id,
            'tipo_prenda_id' => $this->vestido->id,
            'descripcion_arreglo' => 'Subir ruedo',
            'precio' => 16000,
            'estado' => $estado,
            'entregada_en' => $estado === 'entregada' ? '2026-09-15 11:00:00' : null,
        ]);
        for ($posicion = 1; $posicion <= $fotos; $posicion++) {
            Foto::factory()->create(['prenda_id' => $vestido->id, 'posicion' => $posicion]);
        }

        return [$orden, $vestido];
    }

    private function imagen(): UploadedFile
    {
        return UploadedFile::fake()->image('vestido.jpg', 1200, 1600);
    }

    /**
     * Una orden de Marta con un vestido y, si se dan, sus fotos.
     *
     * @param  list<UploadedFile>  $fotos
     */
    private function registrar(array $fotos): TestResponse
    {
        $prenda = ['tipo_prenda_id' => $this->vestido->id, 'descripcion_arreglo' => 'Subir ruedo', 'precio' => '16000'];
        if ($fotos !== []) {
            $prenda['fotos'] = $fotos;
        }

        return $this->from(route('ordenes.nueva'))->post(route('ordenes.guardar'), [
            'token_formulario' => (string) Str::uuid(),
            'cliente_id' => $this->marta->id,
            'fecha_entrega_acordada' => '2026-09-20',
            'prendas' => [$prenda],
        ]);
    }
}
