<?php

namespace Tests\Feature\Http;

use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * HU-37 · Entrar con la cuenta de Google (RF-42, RN-45). No hay registro abierto: un correo que nadie
 * registró no entra y no crea nada. La conversación con Google se finge; lo que se prueba es qué hace el sistema.
 */
class EntrarConGoogleTest extends TestCase
{
    use RefreshDatabase;

    private const IDENTIFICADOR = 'identificador-de-prueba.apps.googleusercontent.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.identificador' => self::IDENTIFICADOR, 'services.google.secreto' => 'secreto-de-prueba']);
    }

    public function test_ca_37_1_correo_registrado(): void
    {
        $duena = Usuario::factory()->create(['correo' => 'taller@gmail.com']);
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['id_token' => $this->identificadorFirmado('taller@gmail.com')]),
        ]);

        // La pantalla ofrece el botón y manda a Google con un estado propio de esta sesión
        $this->get(route('sesion.formulario'))
            ->assertOk()
            ->assertSee('Entrar con Google')
            ->assertSee(route('sesion.google'), false);

        $aGoogle = $this->get(route('sesion.google'));
        $aGoogle->assertRedirectContains('accounts.google.com');
        $aGoogle->assertRedirectContains('scope=openid+email');
        $estado = session('google_estado');
        $this->assertIsString($estado);

        $this->get(route('sesion.google.respuesta', ['code' => 'codigo-de-google', 'state' => $estado]))
            ->assertRedirect(route('panel'));

        $this->assertAuthenticatedAs($duena);
        $this->get(route('panel'))->assertOk();
    }

    public function test_ca_37_2_correo_desconocido(): void
    {
        Usuario::factory()->create(['correo' => 'taller@gmail.com']);
        $usuariasAntes = Usuario::count();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['id_token' => $this->identificadorFirmado('otra.persona@gmail.com')]),
        ]);

        $this->get(route('sesion.google'));
        $this->from(route('sesion.formulario'))
            ->get(route('sesion.google.respuesta', ['code' => 'codigo-de-google', 'state' => session('google_estado')]))
            ->assertRedirect(route('sesion.formulario'))
            ->assertSessionHasErrors(['usuario' => 'Ese correo no tiene acceso al sistema. Pídeselo a quien te lo instaló.']);

        // RN-45: no entra, y no se crea ninguna usuaria ni ningún negocio
        $this->assertGuest();
        $this->assertSame($usuariasAntes, Usuario::count());
        $this->assertSame(0, Usuario::where('correo', 'otra.persona@gmail.com')->count());
    }

    public function test_ca_37_3_la_contrasena_sigue_sirviendo(): void
    {
        $duena = Usuario::factory()->create(['usuario' => 'taller', 'contrasena' => 'una-clave-larga', 'correo' => 'taller@gmail.com']);

        $this->post(route('sesion.entrar'), ['usuario' => 'taller', 'contrasena' => 'una-clave-larga'])
            ->assertRedirect(route('panel'));

        $this->assertAuthenticatedAs($duena);
    }

    public function test_ca_37_4_sin_google_configurado(): void
    {
        config(['services.google.identificador' => null, 'services.google.secreto' => null]);

        $this->get(route('sesion.formulario'))
            ->assertOk()
            ->assertDontSee('Entrar con Google')
            ->assertSee('Usuario')
            ->assertSee('Contraseña');

        // Y aunque alguien escriba la dirección a mano, no se va a ninguna parte
        $this->get(route('sesion.google'))->assertRedirect(route('sesion.formulario'));
    }

    public function test_rn_45_solo_entra_un_correo_ya_registrado(): void
    {
        // El ejemplo de la regla: el correo de la dueña quedó registrado al instalar; el de otra persona, no
        $duena = Usuario::factory()->create(['correo' => 'taller@gmail.com']);
        $usuariasAntes = Usuario::count();

        // Un solo simulacro para los dos intentos: Http::fake no reemplaza al anterior, así que responde según el código
        Http::fake(fn ($peticion) => Http::response([
            'id_token' => $this->identificadorFirmado($peticion['code'] === 'codigo-de-la-duena' ? 'taller@gmail.com' : 'alguien.mas@gmail.com'),
        ]));

        // Primero, quien no está registrada: no entra y no se crea nada
        $this->get(route('sesion.google'));
        $this->from(route('sesion.formulario'))
            ->get(route('sesion.google.respuesta', ['code' => 'codigo-de-otra', 'state' => session('google_estado')]))
            ->assertSessionHasErrors(['usuario' => 'Ese correo no tiene acceso al sistema. Pídeselo a quien te lo instaló.']);
        $this->assertGuest();
        $this->assertSame($usuariasAntes, Usuario::count());
        $this->assertSame(0, Usuario::where('correo', 'alguien.mas@gmail.com')->count());

        // Y después la dueña, con el correo que sí está registrado
        $this->get(route('sesion.google'));
        $this->get(route('sesion.google.respuesta', ['code' => 'codigo-de-la-duena', 'state' => session('google_estado')]))
            ->assertRedirect(route('panel'));
        $this->assertAuthenticatedAs($duena);
    }

    public function test_rn_45_la_vuelta_tiene_que_corresponder_a_esta_sesion(): void
    {
        Usuario::factory()->create(['correo' => 'taller@gmail.com']);
        Http::fake();

        // Sin haber pasado por el botón no hay estado guardado: la vuelta no vale
        $this->get(route('sesion.google.respuesta', ['code' => 'codigo-de-google', 'state' => 'inventado']))
            ->assertRedirect(route('sesion.formulario'));
        $this->assertGuest();

        // Con estado, pero con otro distinto al guardado, tampoco
        $this->get(route('sesion.google'));
        $this->get(route('sesion.google.respuesta', ['code' => 'codigo-de-google', 'state' => 'otro']))
            ->assertRedirect(route('sesion.formulario'));
        $this->assertGuest();

        // Y si la usuaria cancela en Google, vuelve sin código
        $this->get(route('sesion.google'));
        $this->get(route('sesion.google.respuesta', ['state' => session('google_estado'), 'error' => 'access_denied']))
            ->assertRedirect(route('sesion.formulario'));
        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_rn_45_no_entra_una_identidad_que_no_es_de_este_sistema_ni_una_vencida(): void
    {
        Usuario::factory()->create(['correo' => 'taller@gmail.com']);
        $mensaje = 'No se pudo confirmar tu cuenta de Google. Intenta otra vez o entra con tu usuario y contraseña.';

        $rechazadas = [
            'de otro sistema' => $this->identificadorFirmado('taller@gmail.com', aud: 'otro-sistema.apps.googleusercontent.com'),
            'vencida' => $this->identificadorFirmado('taller@gmail.com', exp: time() - 60),
            'de otro emisor' => $this->identificadorFirmado('taller@gmail.com', iss: 'https://impostor.example'),
            'con el correo sin verificar' => $this->identificadorFirmado('taller@gmail.com', verificado: false),
        ];

        // El simulacro se arma una sola vez y responde con la identidad que toque: Http::fake no reemplaza al anterior
        $porRechazar = $rechazadas;
        Http::fake(function () use (&$porRechazar) {
            return Http::response(['id_token' => array_shift($porRechazar)]);
        });

        foreach ($rechazadas as $por => $identificador) {
            $this->get(route('sesion.google'));
            $this->from(route('sesion.formulario'))
                ->get(route('sesion.google.respuesta', ['code' => 'codigo-de-google', 'state' => session('google_estado')]))
                ->assertSessionHasErrors(['usuario' => $mensaje]);
            $this->assertGuest('web', "Una identidad {$por} no debería dejar entrar.");
        }

        // Y si Google responde con un error, tampoco
        Http::fake(['oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_grant'], 400)]);
        $this->get(route('sesion.google'));
        $this->from(route('sesion.formulario'))
            ->get(route('sesion.google.respuesta', ['code' => 'codigo-usado', 'state' => session('google_estado')]))
            ->assertSessionHasErrors(['usuario' => $mensaje]);
        $this->assertGuest();
    }

    /**
     * Un identificador como el que devuelve Google: tres partes separadas por punto, y el contenido en el medio.
     * La firma no se comprueba —el identificador viene de Google, servidor a servidor—, así que aquí va de relleno.
     */
    private function identificadorFirmado(string $correo, ?string $aud = null, ?int $exp = null, string $iss = 'https://accounts.google.com', bool $verificado = true): string
    {
        $contenido = [
            'iss' => $iss,
            'aud' => $aud ?? self::IDENTIFICADOR,
            'exp' => $exp ?? time() + 600,
            'email' => $correo,
            'email_verified' => $verificado,
        ];

        $parte = fn (array $datos) => rtrim(strtr(base64_encode((string) json_encode($datos)), '+/', '-_'), '=');

        return $parte(['alg' => 'RS256']).'.'.$parte($contenido).'.firma-de-relleno';
    }
}
