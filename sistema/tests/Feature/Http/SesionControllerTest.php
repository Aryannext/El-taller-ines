<?php

namespace Tests\Feature\Http;

use App\Modelos\Negocio;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * HU-01 · Iniciar y cerrar sesión.
 */
class SesionControllerTest extends TestCase
{
    use RefreshDatabase;

    private const CONTRASENA = 'clave-del-taller';

    private Usuario $duena;

    protected function setUp(): void
    {
        parent::setUp();

        $negocio = Negocio::factory()->create(['nombre' => 'Taller de costura']);
        $this->duena = Usuario::factory()->create([
            'negocio_id' => $negocio->id,
            'usuario' => 'taller',
            'contrasena' => self::CONTRASENA,
        ]);
    }

    public function test_ca_01_1_acceso_correcto(): void
    {
        $this->post(route('sesion.entrar'), ['usuario' => 'taller', 'contrasena' => self::CONTRASENA])
            ->assertRedirect(route('panel'));

        $this->assertAuthenticatedAs($this->duena);
        $this->get(route('panel'))->assertOk()->assertSee('Taller de costura');
    }

    public function test_ca_01_2_datos_incorrectos(): void
    {
        $this->from(route('sesion.formulario'))
            ->post(route('sesion.entrar'), ['usuario' => 'taller', 'contrasena' => 'clave-equivocada'])
            ->assertRedirect(route('sesion.formulario'))
            ->assertSessionHasErrors(['usuario' => 'Usuario o contraseña incorrectos.']);

        // El mismo mensaje si el que falla es el usuario: no revela cuál de los dos
        $this->post(route('sesion.entrar'), ['usuario' => 'otra-persona', 'contrasena' => self::CONTRASENA])
            ->assertSessionHasErrors(['usuario' => 'Usuario o contraseña incorrectos.']);

        $this->assertGuest();
        $this->assertNull(session()->getOldInput('contrasena'), 'La contraseña escrita no debe quedar en la sesión');
    }

    public function test_ca_01_3_intentos_repetidos(): void
    {
        foreach (range(1, 5) as $intento) {
            $this->post(route('sesion.entrar'), ['usuario' => 'taller', 'contrasena' => 'clave-equivocada']);
        }

        $this->post(route('sesion.entrar'), ['usuario' => 'taller', 'contrasena' => self::CONTRASENA])
            ->assertSessionHasErrors('usuario');

        $this->assertGuest();
        $this->assertMatchesRegularExpression(
            '/^Hiciste demasiados intentos\. Espera \d+ segundos y vuelve a intentarlo\.$/',
            session('errors')->first('usuario'),
        );
    }

    public function test_ca_01_4_cerrar_sesion(): void
    {
        $this->post(route('sesion.entrar'), ['usuario' => 'taller', 'contrasena' => self::CONTRASENA]);
        $this->assertStringContainsString('no-store', (string) $this->get(route('panel'))->headers->get('Cache-Control'));

        $this->post(route('sesion.salir'))->assertRedirect(route('sesion.formulario'));
        $this->assertGuest();

        $this->app['auth']->forgetGuards();
        $this->get(route('panel'))->assertRedirect(route('sesion.formulario'));
    }

    public function test_ca_01_5_sesion_abandonada(): void
    {
        // En archivos, como en el VPS: la sesión vence según la hora de su último uso (RNF-21)
        config(['session.driver' => 'file']);
        $inicio = now();
        $cookie = $this->post(route('sesion.entrar'), ['usuario' => 'taller', 'contrasena' => self::CONTRASENA])
            ->getCookie(config('session.cookie'))
            ->getValue();

        $this->travelTo($inicio->copy()->addMinutes(8 * 60 - 1));
        $this->volverConLaMismaSesion($cookie)->assertOk();

        $this->travelTo($inicio->copy()->addMinutes(8 * 60 + 1));
        $this->volverConLaMismaSesion($cookie)->assertRedirect(route('sesion.formulario'));
    }

    /**
     * Simula que la usuaria vuelve con la cookie de su sesión: Laravel debe leer la sesión guardada, no la de memoria.
     */
    private function volverConLaMismaSesion(string $cookie): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->forgetDrivers();
        // El guard de sesión recibe esta instancia: sin olvidarla, seguiría viendo la sesión en memoria
        $this->app->forgetInstance('session.store');

        return $this->withCookie(config('session.cookie'), $cookie)->get(route('panel'));
    }
}
