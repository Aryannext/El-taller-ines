<?php

namespace Tests\Feature\Http;

use App\Modelos\Cliente;
use App\Modelos\Usuario;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * RNF-23 · El sistema resiste los ataques web más comunes. La búsqueda con inyección SQL se prueba en
 * BuscarClientesTest; aquí van el código HTML en un dato y la protección contra solicitudes de otro sitio.
 * PM-07 revisa lo mismo sobre el sistema desplegado, con la lista del OWASP Top 10.
 */
class SeguridadWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_rnf_23_un_dato_con_codigo_html_se_muestra_como_texto(): void
    {
        $duena = Usuario::factory()->create();
        $this->actingAs($duena);

        $this->post(route('clientes.guardar'), ['nombre' => '<script>alert(1)</script>', 'celular' => '3001112233'])
            ->assertSessionHasNoErrors();

        $cliente = Cliente::where('celular', '3001112233')->sole();
        $this->assertSame('<script>alert(1)</script>', $cliente->nombre);

        // En la pantalla sale escapado: el navegador lo lee como texto, no como un guion que se ejecuta
        foreach ([route('clientes.buscar'), route('clientes.ficha', $cliente)] as $pantalla) {
            $pagina = (string) $this->get($pantalla)->assertOk()->getContent();
            $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $pagina);
            $this->assertStringNotContainsString('<script>alert(1)</script>', $pagina);
        }
    }

    public function test_rnf_23_las_rutas_del_sistema_exigen_el_token_del_formulario(): void
    {
        // Laravel no valida el token dentro de las pruebas, así que se comprueba que la protección esté puesta
        // en el grupo «web», que es el que usan todas las rutas del sistema (bootstrap/app.php)
        $this->assertContains(PreventRequestForgery::class, app(Kernel::class)->getMiddlewareGroups()['web']);

        $sinProteger = [];
        foreach (Route::getRoutes()->getRoutes() as $ruta) {
            $escribe = array_intersect($ruta->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== [];
            if ($escribe && ! in_array('web', $ruta->gatherMiddleware(), true)) {
                $sinProteger[] = $ruta->uri();
            }
        }

        $this->assertSame([], $sinProteger, 'Toda ruta que escribe pasa por el grupo web, que exige el token.');
    }

    public function test_rnf_23_la_politica_de_contenido_deja_pasar_lo_que_la_app_sí_usa(): void
    {
        // El 22 de septiembre la política dejó sin iconos a toda la app: son SVG escritos en «data:» dentro
        // de la hoja de estilos, y img-src no los permitía. Esta prueba compara la política con lo que la
        // hoja de estilos realmente carga, para que un cambio en cualquiera de las dos no rompa la otra.
        $configuracion = file_get_contents(base_path('../despliegue/apache/taller.conf'));
        $estilos = file_get_contents(public_path('css/estilos.css'));

        $this->assertIsString($configuracion);
        $this->assertIsString($estilos);
        $this->assertMatchesRegularExpression('/Content-Security-Policy/', $configuracion);

        preg_match('/img-src ([^;"]+)/', $configuracion, $imagenes);
        $permitido = $imagenes[1] ?? '';

        $this->assertStringContainsString("'self'", $permitido);

        if (str_contains($estilos, 'url("data:')) {
            $this->assertStringContainsString('data:', $permitido, 'La hoja de estilos carga imágenes «data:» (los iconos) y la política debe permitirlas.');
        }

        // Lo que sigue prohibido: traer código de afuera
        $this->assertStringContainsString("script-src 'self'", $configuracion);
        $this->assertStringNotContainsString("'unsafe-inline'", $configuracion);
        $this->assertStringNotContainsString("'unsafe-eval'", $configuracion);
    }
}
