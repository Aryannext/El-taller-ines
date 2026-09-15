<?php

namespace Tests\Feature\Configuracion;

use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * HU-02 · Cambiar mi contraseña.
 */
class CambiarContrasenaTest extends TestCase
{
    use RefreshDatabase;

    private const ACTUAL = 'clave-actual-1';

    private Usuario $duena;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create(['usuario' => 'taller', 'contrasena' => self::ACTUAL]);
    }

    public function test_ca_02_1_cambio_correcto(): void
    {
        $this->cambiar(self::ACTUAL, 'nueva-segura', 'nueva-segura')
            ->assertRedirect(route('ajustes'))
            ->assertSessionHasNoErrors();

        // La sesión en la que se cambió sigue abierta
        $this->get(route('ajustes'))->assertOk()->assertSee('Tu contraseña cambió.');

        $this->post(route('sesion.salir'));
        $this->app['auth']->forgetGuards();

        $this->post(route('sesion.entrar'), ['usuario' => 'taller', 'contrasena' => self::ACTUAL])
            ->assertSessionHasErrors('usuario');
        $this->post(route('sesion.entrar'), ['usuario' => 'taller', 'contrasena' => 'nueva-segura'])
            ->assertRedirect(route('panel'));
        $this->assertAuthenticatedAs($this->duena);
    }

    public function test_ca_02_2_contrasena_actual_incorrecta(): void
    {
        $this->cambiar('no-es-la-actual', 'nueva-segura', 'nueva-segura')
            ->assertSessionHasErrors(['contrasena_actual' => 'La contraseña actual no es correcta.']);

        $this->assertContrasenaSinCambios();
    }

    public function test_ca_02_3_contrasena_corta(): void
    {
        $this->cambiar(self::ACTUAL, 'corta1', 'corta1')
            ->assertSessionHasErrors(['contrasena_nueva' => 'La nueva contraseña debe tener al menos 8 caracteres.']);

        $this->assertContrasenaSinCambios();
    }

    public function test_ca_02_4_no_coinciden(): void
    {
        $this->cambiar(self::ACTUAL, 'nueva-segura', 'otra-distinta')
            ->assertSessionHasErrors(['contrasena_nueva' => 'Las dos contraseñas no coinciden.']);

        $this->assertContrasenaSinCambios();
    }

    public function test_rnf_19_la_contrasena_nunca_se_guarda_en_texto_plano(): void
    {
        $this->cambiar(self::ACTUAL, 'nueva-segura', 'nueva-segura');

        $guardada = $this->duena->fresh()->getAuthPassword();
        $this->assertNotSame('nueva-segura', $guardada);
        $this->assertTrue(Hash::check('nueva-segura', $guardada));
        $this->assertNull(session()->getOldInput('contrasena_nueva'), 'La contraseña escrita no debe quedar en la sesión');
    }

    private function cambiar(string $actual, string $nueva, string $confirmacion): TestResponse
    {
        return $this->actingAs($this->duena)
            ->from(route('ajustes'))
            ->put(route('ajustes.contrasena'), [
                'contrasena_actual' => $actual,
                'contrasena_nueva' => $nueva,
                'contrasena_nueva_confirmation' => $confirmacion,
            ]);
    }

    private function assertContrasenaSinCambios(): void
    {
        $this->assertTrue(Hash::check(self::ACTUAL, $this->duena->fresh()->getAuthPassword()));
    }
}
