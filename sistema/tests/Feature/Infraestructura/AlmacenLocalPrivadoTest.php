<?php

namespace Tests\Feature\Infraestructura;

use App\Dominio\Fotos\AlmacenDeFotos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Soporte\ImagenDePrueba;
use Tests\TestCase;

/**
 * RNF-03 · Las fotos se guardan reducidas, con el AlmacenLocalPrivado real sobre un disco falso.
 * Si PHP no tiene GD, estas pruebas fallan en vez de saltarse (plan de pruebas, riesgos).
 * Cada imagen falsa se guarda en una variable: su archivo temporal se borra cuando el objeto deja de existir.
 */
class AlmacenLocalPrivadoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('privado');
    }

    public function test_rnf_03_una_foto_pesada_se_guarda_reducida(): void
    {
        $foto = ImagenDePrueba::pesada();
        $this->assertGreaterThan(5 * 1024 * 1024, $foto->getSize());

        $guardada = app(AlmacenDeFotos::class)->guardar((string) $foto->getRealPath(), 'fotos/7');

        $this->assertMatchesRegularExpression('#^fotos/7/[0-9a-f-]{36}\.jpg$#', $guardada['ruta']);
        $disco = Storage::disk('privado');
        $disco->assertExists($guardada['ruta']);
        [$ancho, $alto, $tipo] = getimagesize($disco->path($guardada['ruta']));

        $this->assertSame(IMAGETYPE_JPEG, $tipo);
        $this->assertLessThanOrEqual(1600, max($ancho, $alto));
        $this->assertLessThanOrEqual(400 * 1024, $disco->size($guardada['ruta']));
        // Conserva la proporción y registra lo que de verdad quedó guardado
        $this->assertEqualsWithDelta(4 / 3, $ancho / $alto, 0.01);
        $this->assertSame([$ancho, $alto, $disco->size($guardada['ruta'])], [$guardada['ancho_px'], $guardada['alto_px'], $guardada['bytes']]);
    }

    public function test_rnf_03_una_foto_pequena_no_se_agranda_y_queda_en_jpeg(): void
    {
        $camisa = UploadedFile::fake()->image('camisa.png', 800, 600);

        $guardada = app(AlmacenDeFotos::class)->guardar((string) $camisa->getRealPath(), 'fotos/7');

        [$ancho, $alto, $tipo] = getimagesize(Storage::disk('privado')->path($guardada['ruta']));
        $this->assertSame([800, 600, IMAGETYPE_JPEG], [$ancho, $alto, $tipo]);
    }

    public function test_rnf_25_se_guarda_en_el_disco_privado_y_se_puede_eliminar(): void
    {
        Storage::fake('public');
        $almacen = app(AlmacenDeFotos::class);
        $falda = UploadedFile::fake()->image('falda.jpg', 400, 400);

        $guardada = $almacen->guardar((string) $falda->getRealPath(), 'fotos/7');

        $this->assertSame(Storage::disk('privado')->path($guardada['ruta']), $almacen->entregar($guardada['ruta']));
        Storage::disk('public')->assertMissing($guardada['ruta']);

        $almacen->eliminar($guardada['ruta']);
        Storage::disk('privado')->assertMissing($guardada['ruta']);
    }
}
