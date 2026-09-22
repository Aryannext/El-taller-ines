<?php

namespace Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RNF-26 · La política de tratamiento de datos se publica en el sistema y se lee sin iniciar sesión (Ley 1581 de 2012).
 * PM-07, sección D, la revisa sobre el sistema desplegado.
 */
class PoliticaDeDatosTest extends TestCase
{
    use RefreshDatabase;

    public function test_rnf_26_la_politica_se_lee_sin_iniciar_sesion(): void
    {
        $this->get(route('politica-de-datos'))
            ->assertOk()
            ->assertSeeInOrder([
                'Política de tratamiento de datos', 'Ley 1581 de 2012',
                'Qué datos se guardan', 'su nombre y su número de celular',
                'Para qué se usan', 'avisarle por WhatsApp',
                'Quién responde por ellos', 'Derechos del cliente', 'Cuánto tiempo se conservan',
            ]);

        // Desde el inicio de sesión se llega a ella, que es donde la ve quien no ha entrado
        $this->get(route('sesion.formulario'))->assertSee('href="'.route('politica-de-datos').'"', false);
    }
}
