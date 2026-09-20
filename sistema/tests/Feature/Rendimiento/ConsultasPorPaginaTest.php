<?php

namespace Tests\Feature\Rendimiento;

use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RNF-02 · Ninguna pantalla de uso diario pasa de 15 consultas, y el detalle de una orden no consulta
 * más por tener más prendas. Nace de F-02: la versión 1 repetía una consulta por cada prenda, así que
 * abrir una orden grande se sentía lento.
 *
 * El volumen de 3 años no hace falta aquí: lo que se cuenta son las consultas, no el tiempo, y eso no
 * depende de cuántas filas haya. El tiempo se mide en el VPS con PM-06.
 */
class ConsultasPorPaginaTest extends TestCase
{
    use RefreshDatabase;

    private const TOPE = 15;

    private Usuario $duena;

    private TipoPrenda $vestido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->duena = Usuario::factory()->create();
        $this->vestido = TipoPrenda::factory()->create([
            'negocio_id' => $this->duena->negocio_id,
            'nombre' => 'Vestido',
        ]);

        $this->fijarReloj('2026-09-16 09:00:00');
        $this->actingAs($this->duena);
    }

    public function test_rnf_02_el_detalle_no_consulta_mas_por_cada_prenda(): void
    {
        $una = $this->orden(41, 1);
        $diez = $this->orden(42, 10);

        $conUna = $this->consultasDe(route('ordenes.detalle', $una));
        $conDiez = $this->consultasDe(route('ordenes.detalle', $diez));

        // La misma cifra con 1 y con 10: si creciera, habría una consulta por prenda
        $this->assertSame(
            $conUna,
            $conDiez,
            "El detalle hizo {$conUna} consultas con 1 prenda y {$conDiez} con 10: el número crece con las prendas."
        );
        $this->assertLessThanOrEqual(self::TOPE, $conDiez);
    }

    public function test_rnf_02_el_panel_del_dia_no_pasa_del_tope(): void
    {
        $this->orden(43, 3);
        $this->orden(44, 10);

        $this->assertNoPasaDelTope(route('panel'), 'El panel del día');
    }

    public function test_rnf_02_la_busqueda_de_clientes_no_pasa_del_tope(): void
    {
        foreach (range(1, 12) as $i) {
            Cliente::factory()->create([
                'negocio_id' => $this->duena->negocio_id,
                'nombre' => "Ana Gómez {$i}",
            ]);
        }

        $this->assertNoPasaDelTope(route('clientes.buscar', ['q' => 'Ana']), 'La búsqueda de clientes');
    }

    public function test_rnf_02_las_listas_de_seguimiento_no_pasan_del_tope(): void
    {
        // Diez órdenes atrasadas y diez sin reclamar, para que las listas tengan de dónde crecer
        foreach (range(50, 59) as $numero) {
            $this->orden($numero, 2, '2026-09-10', 'en_proceso', recibida: '2026-09-05 10:00:00');
        }

        foreach (range(60, 69) as $numero) {
            $this->orden($numero, 2, '2026-07-01', 'terminada', '2026-07-01 10:00:00', '2026-06-25 10:00:00');
        }

        $this->assertNoPasaDelTope(route('seguimiento.atrasadas'), 'Las órdenes atrasadas');
        $this->assertNoPasaDelTope(route('seguimiento.sin-reclamar'), 'Las órdenes sin reclamar');
    }

    private function assertNoPasaDelTope(string $url, string $pantalla): void
    {
        $consultas = $this->consultasDe($url);

        $this->assertLessThanOrEqual(
            self::TOPE,
            $consultas,
            "{$pantalla} hizo {$consultas} consultas y el tope es ".self::TOPE.'.'
        );
    }

    /**
     * Las consultas de una solicitud. La primera no se cuenta: ahí se resuelve lo que solo pasa una vez,
     * como cargar la configuración, y no volvería a ocurrir en el uso real.
     */
    private function consultasDe(string $url): int
    {
        $this->get($url)->assertOk();

        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->get($url)->assertOk();

        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $consultas;
    }

    private function orden(
        int $numero,
        int $prendas,
        string $entrega = '2026-09-20',
        string $estado = 'pendiente',
        ?string $listaEn = null,
        string $recibida = '2026-09-14 10:00:00',
    ): Orden {
        $cliente = Cliente::factory()->create(['negocio_id' => $this->duena->negocio_id]);

        $orden = Orden::factory()->create([
            'negocio_id' => $this->duena->negocio_id,
            'cliente_id' => $cliente->id,
            'numero' => $numero,
            'fecha_entrega_acordada' => $entrega,
            'recibida_en' => $recibida,
            'lista_en' => $listaEn,
        ]);

        Prenda::factory()->count($prendas)->create([
            'orden_id' => $orden->id,
            'tipo_prenda_id' => $this->vestido->id,
            'estado' => $estado,
        ]);

        return $orden;
    }
}
