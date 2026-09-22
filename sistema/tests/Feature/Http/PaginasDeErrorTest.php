<?php

namespace Tests\Feature\Http;

use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RNF-09 · Cuando algo sale mal, la pantalla dice qué pasó y qué hacer, en palabras del taller.
 * PM-07, A05 lo revisa sobre el sistema desplegado: un error nunca muestra detalles técnicos.
 */
class PaginasDeErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_rnf_09_una_direccion_que_no_existe_muestra_la_pagina_del_taller(): void
    {
        $this->actingAs(Usuario::factory()->create());

        $respuesta = $this->get('/ordenes/99999');

        $respuesta->assertNotFound()
            ->assertSee('Esa página no existe')
            ->assertSee('La dirección que abrió no corresponde a ninguna pantalla del sistema')
            ->assertSee('Ir a Hoy')
            ->assertSee('Órdenes')
            ->assertSee('Clientes')
            ->assertSee('Dinero');

        // Ni el texto crudo del servidor ni una pista de cómo está hecho por dentro (PM-07, A05)
        $respuesta->assertDontSee('Not Found')
            ->assertDontSee('NotFoundHttpException')
            ->assertDontSee('vendor/laravel');
    }

    public function test_rnf_09_cada_error_tiene_su_explicacion_y_su_salida(): void
    {
        $paginas = [
            403 => ['Eso no lo puede abrir', 'Ir a Hoy'],
            419 => ['La sesión se venció', 'Entrar otra vez'],
            429 => ['Espere un momento', 'Volver a entrar'],
            500 => ['Algo falló de este lado', 'Ir a Hoy'],
            503 => ['El sistema está en mantenimiento', 'Volver a intentar'],
        ];

        foreach ($paginas as $codigo => [$titulo, $salida]) {
            $html = view("errors.{$codigo}")->render();

            $this->assertStringContainsString($titulo, $html, "La página {$codigo} debe decir qué pasó");
            $this->assertStringContainsString($salida, $html, "La página {$codigo} debe ofrecer a dónde ir");
            $this->assertStringContainsString("Error {$codigo}", $html);
        }
    }
}
