<?php

namespace Tests\Feature\Http;

use Tests\TestCase;

/**
 * En el VPS el sistema vive en https://proyectosena.online/taller, detrás del Nginx del portafolio (HT-04).
 * Laravel debe armar las direcciones con ese prefijo, o los enlaces, los formularios y los estilos apuntarían fuera de /taller.
 */
class ProxyInversoTest extends TestCase
{
    private const CABECERAS_DE_NGINX = [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'proyectosena.online',
        'X-Forwarded-Port' => '443',
        'X-Forwarded-Prefix' => '/taller',
    ];

    // Una solicitud por prueba: después de la primera, el cliente de pruebas armaría la siguiente dirección ya con /taller

    public function test_detras_de_nginx_las_direcciones_llevan_la_ruta_del_portafolio(): void
    {
        $this->withHeaders(self::CABECERAS_DE_NGINX)
            ->get('/entrar')
            ->assertOk()
            ->assertSee('action="https://proyectosena.online/taller/entrar"', false)
            ->assertSee('https://proyectosena.online/taller/css/estilos.css', false);
    }

    public function test_detras_de_nginx_las_redirecciones_se_quedan_en_la_ruta_del_portafolio(): void
    {
        // Sin sesión, el panel lleva al inicio de sesión dentro de /taller
        $this->withHeaders(self::CABECERAS_DE_NGINX)
            ->get('/')
            ->assertRedirect('https://proyectosena.online/taller/entrar');
    }

    public function test_sin_proxy_las_direcciones_no_cambian(): void
    {
        // Las pruebas piden las páginas en APP_URL, así que la dirección esperada sale de la misma configuración
        $this->get('/entrar')
            ->assertOk()
            ->assertSee('action="'.route('sesion.entrar').'"', false)
            ->assertDontSee('/taller/', false);
    }
}
