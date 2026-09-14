<?php

namespace Tests\Feature\BaseDeDatos;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EsquemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rn_04_el_telefono_no_es_unico(): void
    {
        $negocio = DB::table('negocios')->insertGetId(['nombre' => 'Taller de costura']);

        DB::table('clientes')->insert([
            ['negocio_id' => $negocio, 'nombre' => 'Marta Rincón', 'celular' => '3104567890'],
            ['negocio_id' => $negocio, 'nombre' => 'Laura Rincón', 'celular' => '3104567890'],
        ]);

        $this->assertSame(2, DB::table('clientes')->where('celular', '3104567890')->count());
    }
}
