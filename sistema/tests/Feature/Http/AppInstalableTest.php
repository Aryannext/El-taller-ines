<?php

namespace Tests\Feature\Http;

use Tests\TestCase;

/**
 * HT-07 · El sistema se instala en el celular como app (ADR-006, RNF-35). Los archivos los sirve el servidor web, no Laravel,
 * así que aquí se comprueba que existan y sean coherentes entre sí: el manifiesto, sus íconos, el trabajador y la página sin conexión.
 */
class AppInstalableTest extends TestCase
{
    public function test_rnf_35_el_manifiesto_describe_la_app_y_sus_iconos(): void
    {
        $manifiesto = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('El-taller-ines', $manifiesto['name']);
        $this->assertSame('Taller', $manifiesto['short_name']);
        $this->assertSame(['es-CO', 'standalone', 'portrait'], [$manifiesto['lang'], $manifiesto['display'], $manifiesto['orientation']]);
        // Relativos al manifiesto: el sistema vive en /taller del portafolio (HT-04)
        $this->assertSame(['./', './'], [$manifiesto['start_url'], $manifiesto['scope']]);
        // Los colores de los mockups
        $this->assertSame(['#f3f4f8', '#2a44a8'], [$manifiesto['background_color'], $manifiesto['theme_color']]);

        $tamanos = [];
        foreach ($manifiesto['icons'] as $icono) {
            $archivo = public_path($icono['src']);
            $this->assertFileExists($archivo);
            [$ancho, $alto] = (array) getimagesize($archivo);
            $this->assertSame($icono['sizes'], "{$ancho}x{$alto}", "El icono {$icono['src']} no mide lo que dice el manifiesto.");
            $tamanos[] = $icono['sizes'].($icono['purpose'] ?? '');
        }
        // Android necesita uno de 192, uno de 512 y uno recortable (CA de HT-07)
        $this->assertSame(['192x192', '512x512', '512x512maskable'], $tamanos);
    }

    public function test_rnf_35_hay_pagina_sin_conexion_y_el_trabajador_la_guarda(): void
    {
        $sinConexion = (string) file_get_contents(public_path('sin-conexion.html'));
        $this->assertStringContainsString('El sistema necesita conexión para guardar y consultar tus órdenes.', $sinConexion);
        // Sin la raíz del dominio: la página se sirve desde /taller
        $this->assertStringContainsString('href="css/estilos.css"', $sinConexion);

        $trabajador = (string) file_get_contents(public_path('sw.js'));
        foreach (['sin-conexion.html', 'css/estilos.css', 'js/app.js', 'iconos/icono-192.png'] as $guardado) {
            $this->assertStringContainsString($guardado, $trabajador);
        }
        // RNF-25: de las páginas del taller solo responde con la de sin conexión cuando falla la red; nunca las guarda
        $this->assertStringContainsString('fetch(solicitud).catch(() => caches.match(SIN_CONEXION))', $trabajador);
        // Lo que sí guarda se pide primero a la red, o un despliegue nuevo no se vería
        $this->assertStringContainsString('DE_ENTRADA.includes(ruta)', $trabajador);
        $this->assertSame(1, substr_count($trabajador, 'cache.put'), 'Solo los archivos fijos se guardan al responder la red.');
    }

    public function test_rnf_35_las_paginas_enlazan_el_manifiesto_con_su_ruta(): void
    {
        $this->get('/entrar')
            ->assertOk()
            ->assertSee('rel="manifest" href="http://localhost:8000/manifest.webmanifest"', false)
            ->assertSee('name="theme-color" content="#2a44a8"', false)
            ->assertSee('rel="apple-touch-icon"', false);
    }
}
