<?php

namespace Database\Seeders;

use App\Dominio\Compartido\Reloj;
use App\Modelos\MetodoPago;
use App\Modelos\Negocio;
use App\Modelos\TipoPrenda;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * El volumen de referencia de un taller después de 3 años: 500 clientes, 750 órdenes, 2.200 prendas
 * y 1.500 pagos (RNF-01, RNF-02). Lo carga PM-06 para medir, y las pruebas de RNF-02 para contar
 * consultas. No es para producción: se borra al terminar la medición.
 *
 *     php artisan db:seed --class=VolumenDeTresAniosSeeder
 *
 * La misma semilla da siempre los mismos datos, para que dos mediciones se puedan comparar.
 */
class VolumenDeTresAniosSeeder extends Seeder
{
    private const CLIENTES = 500;

    private const ORDENES = 750;

    private const PRENDAS = 2200;

    private const PAGOS = 1500;

    private const SEMILLA = 2026;

    /** Cuántas filas se insertan de una vez: con este volumen no vale la pena afinarlo más. */
    private const LOTE = 250;

    public function run(): void
    {
        mt_srand(self::SEMILLA);

        $negocio = Negocio::withoutGlobalScopes()->first();

        if ($negocio === null) {
            throw new RuntimeException('Primero corre NegocioInicialSeeder: el volumen se cuelga de un negocio que ya exista.');
        }

        $tipos = TipoPrenda::withoutGlobalScopes()->where('negocio_id', $negocio->id)->pluck('id')->all();
        $metodos = MetodoPago::withoutGlobalScopes()->where('negocio_id', $negocio->id)->pluck('id')->all();

        if ($tipos === [] || $metodos === []) {
            throw new RuntimeException('El negocio no tiene tipos de prenda ni métodos de pago: corre NegocioInicialSeeder.');
        }

        // El «hoy» del sistema, el mismo que usan las consultas del panel y de seguimiento (RN-09)
        $hoy = CarbonImmutable::instance(app(Reloj::class)->hoy());

        $clientes = $this->clientes($negocio->id, $hoy);
        $ordenes = $this->ordenes($negocio->id, $clientes, $hoy);
        $totales = $this->prendas($ordenes, $tipos, $hoy);
        $pagos = $this->pagos($ordenes, $totales, $metodos, $hoy);

        $this->command->info(sprintf(
            'Volumen de 3 años: %d clientes, %d órdenes, %d prendas y %d pagos.',
            count($clientes),
            count($ordenes),
            self::PRENDAS,
            $pagos
        ));
    }

    /**
     * @return list<int>
     */
    private function clientes(int $negocioId, CarbonImmutable $hoy): array
    {
        $nombres = ['María', 'Luz', 'Carmen', 'Rosa', 'Ana', 'Gloria', 'Marta', 'Diana', 'Sandra', 'Paula',
            'José', 'Luis', 'Carlos', 'Jorge', 'Andrés', 'Miguel', 'Julián', 'Óscar', 'Fabián', 'Camilo'];
        $apellidos = ['Gómez', 'Rodríguez', 'Martínez', 'López', 'García', 'Pérez', 'Sánchez', 'Ramírez',
            'Torres', 'Vargas', 'Castro', 'Rojas', 'Moreno', 'Muñoz', 'Álvarez', 'Ortiz', 'Suárez', 'Cárdenas'];

        $filas = [];

        for ($i = 0; $i < self::CLIENTES; $i++) {
            $creado = $hoy->subDays(mt_rand(0, 1095))->setTime(mt_rand(8, 18), mt_rand(0, 59));
            $filas[] = [
                'negocio_id' => $negocioId,
                'nombre' => $nombres[mt_rand(0, count($nombres) - 1)].' '.$apellidos[mt_rand(0, count($apellidos) - 1)],
                'celular' => '3'.mt_rand(100000000, 999999999),
                'creado_en' => $creado,
                'actualizado_en' => $creado,
            ];
        }

        return $this->insertar('clientes', $filas);
    }

    /**
     * @param  list<int>  $clientes
     * @return list<array{id:int,estado:string}>
     */
    private function ordenes(int $negocioId, array $clientes, CarbonImmutable $hoy): array
    {
        $desde = (int) DB::table('ordenes')->where('negocio_id', $negocioId)->max('numero');
        $estados = [];
        $filas = [];

        for ($i = 0; $i < self::ORDENES; $i++) {
            // Las últimas 60 caen en el mes que se está midiendo; el resto se reparte en los 3 años
            $diasAtras = $i < self::ORDENES - 60 ? mt_rand(60, 1095) : mt_rand(0, 59);
            $recibida = $hoy->subDays($diasAtras)->setTime(mt_rand(8, 18), mt_rand(0, 59));
            $acordada = $recibida->addDays(mt_rand(1, 10));
            $estado = $this->estadoDeLaOrden($diasAtras);
            $estados[] = $estado;

            $filas[] = [
                'negocio_id' => $negocioId,
                'cliente_id' => $clientes[mt_rand(0, count($clientes) - 1)],
                'numero' => $desde + $i + 1,
                'fecha_entrega_acordada' => $acordada->toDateString(),
                'recibida_en' => $recibida,
                'lista_en' => in_array($estado, ['lista', 'entregada'], true) ? $acordada->setTime(mt_rand(9, 17), 0) : null,
                'cancelada_en' => $estado === 'cancelada' ? $acordada->setTime(10, 0) : null,
                'token_formulario' => null,
                'actualizado_en' => $recibida,
            ];
        }

        $ids = $this->insertar('ordenes', $filas);
        $ordenes = [];

        foreach ($ids as $posicion => $id) {
            $ordenes[] = ['id' => $id, 'estado' => $estados[$posicion]];
        }

        return $ordenes;
    }

    /**
     * El estado de la orden sale de sus prendas (RN-18), pero se decide antes para que las listas de
     * seguimiento tengan contenido: órdenes atrasadas (RN-34) y sin reclamar hace más de 30 días (RN-35).
     */
    private function estadoDeLaOrden(int $diasAtras): string
    {
        if ($diasAtras > 120) {
            return mt_rand(1, 100) <= 4 ? 'cancelada' : 'entregada';
        }

        $suerte = mt_rand(1, 100);

        return match (true) {
            $suerte <= 3 => 'cancelada',
            $suerte <= 58 => 'entregada',
            $suerte <= 78 => 'lista',
            default => 'proceso',
        };
    }

    /**
     * @param  list<array{id:int,estado:string}>  $ordenes
     * @param  list<int>  $tipos
     * @return array<int,int> el total de cada orden, por su id
     */
    private function prendas(array $ordenes, array $tipos, CarbonImmutable $hoy): array
    {
        $arreglos = ['Subir basta 3 cm', 'Cambiar cierre', 'Ajustar cintura', 'Entubar bota', 'Coger dobladillo',
            'Reforzar costura', 'Cambiar botones', 'Ajustar hombros', 'Achicar de los lados', 'Zurcir descosido'];

        $cantidades = $this->cuantasPrendasPorOrden();
        $filas = [];
        $totales = [];

        foreach ($ordenes as $posicion => $orden) {
            $total = 0;

            for ($p = 0; $p < $cantidades[$posicion]; $p++) {
                $precio = mt_rand(6, 60) * 1000;
                $total += $precio;
                $creado = $hoy->subDays(mt_rand(1, 1095))->setTime(mt_rand(8, 18), 0);

                $filas[] = [
                    'orden_id' => $orden['id'],
                    'tipo_prenda_id' => $tipos[mt_rand(0, count($tipos) - 1)],
                    'descripcion_arreglo' => $arreglos[mt_rand(0, count($arreglos) - 1)],
                    'precio' => $precio,
                    'estado' => $this->estadoDeLaPrenda($orden['estado']),
                    'entregada_en' => $orden['estado'] === 'entregada' ? $creado : null,
                    'devuelta_en' => null,
                    'creado_en' => $creado,
                    'actualizado_en' => $creado,
                ];
            }

            $totales[$orden['id']] = $total;
        }

        $this->insertar('prendas', $filas);

        return $totales;
    }

    /**
     * Reparte 2.200 prendas en 750 órdenes. Las dos primeras fijan los extremos que mide RNF-02:
     * una orden de 10 prendas y otra de 1.
     *
     * @return list<int>
     */
    private function cuantasPrendasPorOrden(): array
    {
        $cantidades = [10, 1];
        $faltan = self::PRENDAS - 11;

        for ($i = 2; $i < self::ORDENES; $i++) {
            $restantes = self::ORDENES - $i;
            $cantidad = max(1, min(10, intdiv($faltan, $restantes) + mt_rand(0, 1)));
            // Sin pasarse: a cada orden que falta le tiene que quedar al menos una prenda
            $cantidad = min($cantidad, $faltan - $restantes + 1);
            $cantidades[] = $cantidad;
            $faltan -= $cantidad;
        }

        return $cantidades;
    }

    private function estadoDeLaPrenda(string $estadoDeLaOrden): string
    {
        return match ($estadoDeLaOrden) {
            'entregada' => 'entregada',
            'lista' => 'terminada',
            'cancelada' => 'pendiente',
            default => mt_rand(1, 2) === 1 ? 'pendiente' : 'en_proceso',
        };
    }

    /**
     * @param  list<array{id:int,estado:string}>  $ordenes
     * @param  array<int,int>  $totales
     * @param  list<int>  $metodos
     */
    private function pagos(array $ordenes, array $totales, array $metodos, CarbonImmutable $hoy): int
    {
        // Una orden cancelada no recibe pagos (RN-25)
        $cobrables = array_values(array_filter($ordenes, fn (array $o) => $o['estado'] !== 'cancelada'));
        $cuantas = count($cobrables);

        // Los pagos se reparten entre las órdenes cobrables, dos o tres cada una. No se recorren las
        // órdenes en círculo: una entregada queda saldada en su primer abono y las vueltas siguientes
        // no tendrían nada que cobrarle, así que el volumen se quedaría corto.
        $porOrden = intdiv(self::PAGOS, $cuantas);
        $sobran = self::PAGOS % $cuantas;
        $filas = [];

        foreach ($cobrables as $posicion => $orden) {
            $cuantos = $porOrden + ($posicion < $sobran ? 1 : 0);

            if ($cuantos < 1) {
                continue;
            }

            $total = $totales[$orden['id']];

            // La entregada se pagó completa; las demás van por la mitad. Nunca más que el total, que
            // es lo que impide que el saldo quede negativo (RN-28)
            $aPagar = $orden['estado'] === 'entregada'
                ? $total
                : min($total, max(1000 * $cuantos, (int) round($total / 2 / 1000) * 1000));

            foreach ($this->repartir($aPagar, $cuantos) as $valor) {
                $pagado = $hoy->subDays(mt_rand(0, 1095))->setTime(mt_rand(8, 18), mt_rand(0, 59));
                $anulado = mt_rand(1, 100) <= 2;

                $filas[] = [
                    'orden_id' => $orden['id'],
                    'metodo_pago_id' => $metodos[mt_rand(0, count($metodos) - 1)],
                    'valor' => $valor,
                    'pagado_en' => $pagado,
                    'anulado_en' => $anulado ? $pagado->addDay() : null,
                    'motivo_anulacion' => $anulado ? 'Se registró en la orden equivocada' : null,
                    'token_formulario' => null,
                    'actualizado_en' => $pagado,
                ];
            }
        }

        $this->insertar('pagos', $filas);

        return count($filas);
    }

    /**
     * Parte lo que se cobra en abonos de mil pesos; el último se lleva el resto. Cada abono es mayor
     * que cero, como exige la columna (RN-25).
     *
     * @return list<int>
     */
    private function repartir(int $valor, int $partes): array
    {
        $abono = max(1000, intdiv(intdiv($valor, $partes), 1000) * 1000);
        $abonos = [];
        $queda = $valor;

        for ($i = 0; $i < $partes - 1; $i++) {
            // Dejando siempre mil pesos para cada abono que falta
            $este = max(1000, min($abono, $queda - ($partes - 1 - $i) * 1000));
            $abonos[] = $este;
            $queda -= $este;
        }

        $abonos[] = $queda;

        return $abonos;
    }

    /**
     * Inserta por lotes y devuelve los identificadores en el mismo orden en que se pasaron.
     *
     * @param  list<array<string,mixed>>  $filas
     * @return list<int>
     */
    private function insertar(string $tabla, array $filas): array
    {
        if ($filas === []) {
            return [];
        }

        $desde = (int) DB::table($tabla)->max('id');

        foreach (array_chunk($filas, self::LOTE) as $lote) {
            DB::table($tabla)->insert($lote);
        }

        return DB::table($tabla)->where('id', '>', $desde)->orderBy('id')->pluck('id')->all();
    }
}
