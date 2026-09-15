<?php

namespace Tests\Arquitectura;

use PHPUnit\Framework\TestCase;

/**
 * Los controladores reciben los modelos por el enlace de rutas, pero no consultan la base de datos:
 * eso lo hacen los casos de uso y las consultas de app/Aplicacion (ADR-005). PHPat no distingue un
 * modelo recibido como parámetro de uno consultado, por eso esta regla se revisa en el código.
 */
class ControladoresSinConsultasTest extends TestCase
{
    public function test_rnf_27_los_controladores_no_consultan_la_base_de_datos(): void
    {
        $consulta = '/\bDB::|::(query|where\w*|find\w*|all|first\w*|create|insert|update|destroy)\(/';
        $infracciones = [];

        foreach (glob(dirname(__DIR__, 2).'/app/Http/Controladores/*.php') ?: [] as $archivo) {
            foreach (file($archivo) as $indice => $linea) {
                if (preg_match($consulta, $linea)) {
                    $infracciones[] = basename($archivo).':'.($indice + 1).' '.trim($linea);
                }
            }
        }

        $this->assertSame([], $infracciones, 'Mueve la consulta a un caso de uso o a una consulta de app/Aplicacion.');
    }
}
