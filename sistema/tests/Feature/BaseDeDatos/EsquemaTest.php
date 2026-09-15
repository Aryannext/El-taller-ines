<?php

namespace Tests\Feature\BaseDeDatos;

use Illuminate\Database\QueryException;
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

    public function test_rn_05_una_orden_un_cliente_una_visita(): void
    {
        $negocioA = DB::table('negocios')->insertGetId(['nombre' => 'Taller de costura']);
        $negocioB = DB::table('negocios')->insertGetId(['nombre' => 'Otro taller']);
        $marta = DB::table('clientes')->insertGetId(['negocio_id' => $negocioA, 'nombre' => 'Marta Rincón', 'celular' => '3104567890']);
        $orden = fn (int $negocio, int $numero) => DB::table('ordenes')->insert([
            'negocio_id' => $negocio,
            'cliente_id' => $marta,
            'numero' => $numero,
            'fecha_entrega_acordada' => '2026-09-20',
            'recibida_en' => '2026-09-14 10:00:00',
        ]);

        // El lunes deja un pantalón y dos camisas; el jueves vuelve con un vestido: son dos órdenes de la misma cliente
        $orden($negocioA, 1);
        $orden($negocioA, 2);
        $this->assertSame(2, DB::table('ordenes')->where('cliente_id', $marta)->count());

        // Una orden no puede ser de un negocio distinto al de su cliente (ADR-004)
        $this->expectException(QueryException::class);
        $orden($negocioB, 1);
    }
}
