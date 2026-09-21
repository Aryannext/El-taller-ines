<?php

namespace Tests\Feature\BaseDeDatos;

use App\Aplicacion\Consultas\PanelDelDia;
use App\Modelos\Aviso;
use App\Modelos\Cliente;
use App\Modelos\Foto;
use App\Modelos\MetodoPago;
use App\Modelos\Negocio;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Database\Seeders\DatosDeLosMockupsSeeder;
use Database\Seeders\NegocioInicialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Los datos de los mockups son los de docs/03-diseno/modelo-de-datos/datos-de-ejemplo.sql, y de ellos
 * dependen PM-02 y PM-06. Esta prueba no los usa como datos de prueba —para eso están las fábricas—,
 * sino que comprueba el seeder: que cargue lo que el archivo dice y que el panel muestre lo mismo que
 * el mockup, sin importar el día en que se corra.
 */
class DatosDeLosMockupsSeederTest extends TestCase
{
    use RefreshDatabase;

    private Negocio $negocio;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('privado');

        // Lo que deja la instalación: el negocio, sus seis tipos y sus dos métodos de pago
        $this->negocio = Negocio::factory()->create(['nombre' => 'Taller de costura']);

        foreach (NegocioInicialSeeder::TIPOS_DE_PRENDA as $nombre) {
            TipoPrenda::factory()->create(['negocio_id' => $this->negocio->id, 'nombre' => $nombre]);
        }

        foreach (NegocioInicialSeeder::METODOS_DE_PAGO as $nombre) {
            MetodoPago::factory()->create(['negocio_id' => $this->negocio->id, 'nombre' => $nombre]);
        }

        // Con la sesión de la dueña: lo que separa un taller de otro es el filtro que cuelga de ella,
        // así que sin iniciar sesión el panel sumaría los dos negocios (ADR-002, RNF-22)
        $this->actingAs(Usuario::factory()->create(['negocio_id' => $this->negocio->id]));
    }

    public function test_carga_los_datos_de_ejemplo_del_modelo(): void
    {
        $this->seed(DatosDeLosMockupsSeeder::class);

        $this->assertSame(8, Cliente::withoutGlobalScopes()->count());
        $this->assertSame(9, Orden::withoutGlobalScopes()->count());
        $this->assertSame(12, Prenda::withoutGlobalScopes()->count());
        $this->assertSame(4, Foto::withoutGlobalScopes()->count());
        $this->assertSame(7, Pago::withoutGlobalScopes()->count());
        $this->assertSame(5, Aviso::withoutGlobalScopes()->count());

        // El segundo taller existe para que las pruebas de aislamiento tengan datos ajenos (RN-01)
        $this->assertSame(2, Negocio::withoutGlobalScopes()->count());
    }

    public function test_cada_foto_tiene_su_archivo(): void
    {
        $this->seed(DatosDeLosMockupsSeeder::class);

        $disco = Storage::disk('privado');

        foreach (Foto::withoutGlobalScopes()->get() as $foto) {
            $disco->assertExists($foto->ruta);
            // PM-02 suma el tamaño de las fotos y lo compara con lo restaurado: la fila tiene que
            // decir lo que el archivo pesa de verdad
            $this->assertSame($disco->size($foto->ruta), $foto->bytes);
            $this->assertLessThanOrEqual(400 * 1024, $foto->bytes, 'RNF-03: ninguna foto pasa de 400 KB.');
        }
    }

    public function test_el_panel_muestra_lo_mismo_que_el_mockup_cualquier_dia(): void
    {
        // El archivo de ejemplo está escrito para el 16 de septiembre de 2026; aquí «hoy» es otro día
        $this->fijarReloj('2027-03-08 11:00:00');
        $this->seed(DatosDeLosMockupsSeeder::class);

        $panel = app(PanelDelDia::class)->obtener($this->negocio);

        $this->assertSame('$76.000', $panel['porCobrar']->formato());
        $this->assertSame(2, $panel['atrasadas']);
        $this->assertSame(1, $panel['ordenesSinReclamar']);
        $this->assertSame(2, $panel['prendasSinReclamar']);
        $this->assertSame(1, $panel['avisosPorEnviar']);
    }

    public function test_la_orden_de_las_fotos_es_la_del_mockup(): void
    {
        $this->seed(DatosDeLosMockupsSeeder::class);

        $orden = Orden::withoutGlobalScopes()->where('numero', 42)->sole();

        $this->assertSame(3, $orden->prendas()->count());
        $this->assertSame(3, Foto::withoutGlobalScopes()->whereIn('prenda_id', $orden->prendas()->pluck('id'))->count());
    }

    public function test_se_niega_a_cargar_dos_veces(): void
    {
        $this->seed(DatosDeLosMockupsSeeder::class);

        // Cargarlos otra vez duplicaría clientes y órdenes en silencio
        $this->expectExceptionMessage('ya tiene clientes');
        $this->seed(DatosDeLosMockupsSeeder::class);
    }
}
