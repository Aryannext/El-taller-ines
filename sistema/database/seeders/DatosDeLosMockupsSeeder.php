<?php

namespace Database\Seeders;

use App\Dominio\Compartido\Reloj;
use App\Modelos\MetodoPago;
use App\Modelos\Negocio;
use App\Modelos\TipoPrenda;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Los datos de ejemplo del modelo de datos, que son los mismos de los mockups
 * (docs/03-diseno/modelo-de-datos/datos-de-ejemplo.sql). Se usan para las pruebas manuales y para
 * demostrar el sistema.
 *
 *     php artisan db:seed --class=DatosDeLosMockupsSeeder
 *
 * En el archivo original «hoy» es el miércoles 16 de septiembre de 2026. Aquí todas las fechas se
 * corren para que «hoy» sea el día en que se carga: así el panel muestra lo mismo que los mockups
 * sin importar cuándo se mire.
 *
 * Se cuelga del negocio que ya creó NegocioInicialSeeder, para que la dueña entre con la contraseña
 * que eligió al instalar. El segundo negocio existe solo para comprobar que uno no ve los datos del
 * otro (RN-01, RNF-22).
 */
class DatosDeLosMockupsSeeder extends Seeder
{
    /** El día que los mockups llaman «hoy». */
    private const HOY_EN_LOS_MOCKUPS = '2026-09-16';

    private int $dias = 0;

    public function run(): void
    {
        $negocio = Negocio::withoutGlobalScopes()->where('nombre', 'Taller de costura')->first();

        if ($negocio === null) {
            throw new RuntimeException('Primero corre NegocioInicialSeeder: los datos de los mockups se cuelgan de su negocio.');
        }

        if (DB::table('clientes')->where('negocio_id', $negocio->id)->exists()) {
            throw new RuntimeException('El negocio ya tiene clientes. Corre «php artisan migrate:fresh» antes, para no mezclar datos.');
        }

        // El «hoy» del sistema, no el del reloj del sistema operativo: es el mismo que usan las
        // consultas del panel, así que las fechas corridas encajan con lo que ellas consideran hoy (RN-09)
        $hoy = CarbonImmutable::instance(app(Reloj::class)->hoy());

        $this->dias = (int) CarbonImmutable::parse(self::HOY_EN_LOS_MOCKUPS)->diffInDays($hoy, false);

        DB::transaction(function () use ($negocio): void {
            $tipos = $this->tiposDePrenda($negocio->id);
            $metodos = $this->metodosDePago($negocio->id);
            $clientes = $this->clientes($negocio->id);
            $ordenes = $this->ordenes($negocio->id, $clientes);
            $prendas = $this->prendas($ordenes, $tipos);

            $this->fotos($prendas);
            $this->pagos($ordenes, $metodos);
            $this->avisos($ordenes);
            $this->otroNegocio();
        });

        $this->command->info(sprintf(
            'Datos de los mockups cargados, con las fechas corridas %d días: «hoy» es %s.',
            $this->dias,
            $hoy->toDateString()
        ));
    }

    /**
     * NegocioInicialSeeder ya creó los seis tipos iniciales (RF-16). Los mockups agregan «Overol», que
     * la dueña escribió con «Otro» (RN-43), y muestran «Chaqueta» desactivada.
     *
     * @return array<string,int>
     */
    private function tiposDePrenda(int $negocioId): array
    {
        TipoPrenda::withoutGlobalScopes()
            ->where('negocio_id', $negocioId)
            ->where('nombre', 'Chaqueta')
            ->update(['activo' => false]);

        $overol = TipoPrenda::withoutGlobalScopes()->firstOrCreate(
            ['negocio_id' => $negocioId, 'nombre' => 'Overol'],
            ['activo' => true]
        );

        $tipos = TipoPrenda::withoutGlobalScopes()
            ->where('negocio_id', $negocioId)
            ->pluck('id', 'nombre')
            ->all();
        $tipos['Overol'] = $overol->id;

        return $tipos;
    }

    /**
     * @return array<string,int>
     */
    private function metodosDePago(int $negocioId): array
    {
        return MetodoPago::withoutGlobalScopes()
            ->where('negocio_id', $negocioId)
            ->pluck('id', 'nombre')
            ->all();
    }

    /**
     * @return array<int,int> el id real de cada cliente, por el número que tiene en el archivo de ejemplo
     */
    private function clientes(int $negocioId): array
    {
        $ejemplo = [
            1 => ['Marta Rincón', '3104567890', '2026-09-02 09:55:00'],
            2 => ['Luis Pardo', '3001112233', '2026-09-08 10:55:00'],
            3 => ['Sandra Ruiz', '3159876543', '2026-09-11 09:25:00'],
            4 => ['Carmen Díaz', '3112345678', '2026-07-27 08:55:00'],
            5 => ['Ana Beltrán', '3205550101', '2026-09-12 13:55:00'],
            6 => ['María Gómez', '3012223344', '2026-08-20 09:55:00'],
            7 => ['Mariana López', '3174008821', '2026-09-01 12:00:00'],
        ];

        $ids = [];

        foreach ($ejemplo as $numero => [$nombre, $celular, $creado]) {
            $ids[$numero] = (int) DB::table('clientes')->insertGetId([
                'negocio_id' => $negocioId,
                'nombre' => $nombre,
                'celular' => $celular,
                'creado_en' => $this->fecha($creado),
                'actualizado_en' => $this->fecha($creado),
            ]);
        }

        return $ids;
    }

    /**
     * @param  array<int,int>  $clientes
     * @return array<int,int>
     */
    private function ordenes(int $negocioId, array $clientes): array
    {
        $ejemplo = [
            1 => [4, 30, '2026-07-31', '2026-07-27 09:00:00', '2026-08-01 17:00:00', null],
            2 => [6, 39, '2026-08-25', '2026-08-20 10:00:00', '2026-08-24 15:00:00', null],
            3 => [1, 40, '2026-09-08', '2026-09-02 10:00:00', '2026-09-08 16:00:00', null],
            4 => [1, 41, '2026-09-09', '2026-09-04 15:00:00', null, '2026-09-05 09:00:00'],
            5 => [1, 42, '2026-09-19', '2026-09-07 10:25:00', null, null],
            6 => [2, 44, '2026-09-12', '2026-09-08 11:00:00', null, null],
            7 => [3, 45, '2026-09-15', '2026-09-11 09:30:00', null, null],
            8 => [5, 46, '2026-09-16', '2026-09-12 14:00:00', '2026-09-16 09:40:00', null],
        ];

        $ids = [];

        foreach ($ejemplo as $numero => [$cliente, $consecutivo, $acordada, $recibida, $lista, $cancelada]) {
            $ids[$numero] = (int) DB::table('ordenes')->insertGetId([
                'negocio_id' => $negocioId,
                'cliente_id' => $clientes[$cliente],
                'numero' => $consecutivo,
                'fecha_entrega_acordada' => $this->fecha($acordada, soloElDia: true),
                'recibida_en' => $this->fecha($recibida),
                'lista_en' => $this->fecha($lista),
                'cancelada_en' => $this->fecha($cancelada),
                'actualizado_en' => $this->fecha($recibida),
            ]);
        }

        return $ids;
    }

    /**
     * @param  array<int,int>  $ordenes
     * @param  array<string,int>  $tipos
     * @return array<int,int>
     */
    private function prendas(array $ordenes, array $tipos): array
    {
        $ejemplo = [
            1 => [1, 'Camisa', 'Entallar los costados', 8000, 'terminada', null, '2026-07-27 09:00:00'],
            2 => [1, 'Camisa', 'Acortar las mangas', 8000, 'terminada', null, '2026-07-27 09:00:00'],
            3 => [2, 'Blusa', 'Ajustar los costados', 10000, 'entregada', '2026-08-25 10:00:00', '2026-08-20 10:00:00'],
            4 => [3, 'Pantalón', 'Ajustar la cintura', 20000, 'entregada', '2026-09-10 11:20:00', '2026-09-02 10:00:00'],
            5 => [4, 'Falda', 'Subir el ruedo', 8000, 'pendiente', null, '2026-09-04 15:00:00'],
            6 => [5, 'Pantalón', 'Subir basta 3 cm', 15000, 'terminada', null, '2026-09-07 10:25:00'],
            7 => [5, 'Camisa', 'Entallar los costados', 8000, 'terminada', null, '2026-09-07 10:25:00'],
            8 => [5, 'Camisa', 'Entallar y acortar mangas', 8000, 'pendiente', null, '2026-09-15 16:11:00'],
            9 => [6, 'Vestido', 'Ajustar cintura', 23000, 'en_proceso', null, '2026-09-08 11:00:00'],
            10 => [7, 'Falda', 'Subir ruedo', 20000, 'en_proceso', null, '2026-09-11 09:30:00'],
            11 => [8, 'Chaqueta', 'Cambiar la cremallera', 20000, 'terminada', null, '2026-09-12 14:00:00'],
        ];

        $ids = [];

        foreach ($ejemplo as $numero => [$orden, $tipo, $arreglo, $precio, $estado, $entregada, $creado]) {
            $ids[$numero] = (int) DB::table('prendas')->insertGetId([
                'orden_id' => $ordenes[$orden],
                'tipo_prenda_id' => $tipos[$tipo],
                'descripcion_arreglo' => $arreglo,
                'precio' => $precio,
                'estado' => $estado,
                'entregada_en' => $this->fecha($entregada),
                'devuelta_en' => null,
                'creado_en' => $this->fecha($creado),
                'actualizado_en' => $this->fecha($creado),
            ]);
        }

        return $ids;
    }

    /**
     * Las cuatro fotos de los mockups, con su archivo de verdad en el disco privado: PM-02 cuenta los
     * archivos y suma su tamaño, así que una fila sin archivo no serviría. El tamaño que se guarda es
     * el del archivo generado, no el del ejemplo, para que los dos coincidan.
     *
     * @param  array<int,int>  $prendas
     */
    private function fotos(array $prendas): void
    {
        $ejemplo = [
            [6, 1, 'fotos/1/0042/prenda-6-1.jpg', 1200, 1600, '2026-09-07 10:25:00'],
            [6, 2, 'fotos/1/0042/prenda-6-2.jpg', 1600, 1200, '2026-09-07 10:25:00'],
            [7, 1, 'fotos/1/0042/prenda-7-1.jpg', 1200, 1600, '2026-09-07 10:25:00'],
            [11, 1, 'fotos/1/0046/prenda-11-1.jpg', 1200, 1600, '2026-09-12 14:00:00'],
        ];

        $disco = Storage::disk('privado');

        foreach ($ejemplo as [$prenda, $posicion, $ruta, $ancho, $alto, $creado]) {
            $disco->put($ruta, $this->imagen($ancho, $alto));

            DB::table('fotos')->insert([
                'prenda_id' => $prendas[$prenda],
                'posicion' => $posicion,
                'ruta' => $ruta,
                'ancho_px' => $ancho,
                'alto_px' => $alto,
                // La tabla de fotos no lleva actualizado_en: una foto no se edita, se reemplaza (RN-17)
                'bytes' => $disco->size($ruta),
                'creado_en' => $this->fecha($creado),
            ]);
        }
    }

    /**
     * Una imagen de relleno que pesa bastante menos de los 400 KB que permite RNF-03. No es una foto
     * de una prenda: sirve para que la pantalla tenga algo que mostrar y para contarla y medirla.
     */
    private function imagen(int $ancho, int $alto): string
    {
        $lienzo = imagecreatetruecolor($ancho, $alto);

        for ($y = 0; $y < $alto; $y++) {
            $tono = (int) (200 - 120 * ($y / $alto));
            $color = imagecolorallocate($lienzo, $tono, (int) ($tono * 0.85), (int) ($tono * 0.7));
            imagefilledrectangle($lienzo, 0, $y, $ancho, $y, $color);
        }

        ob_start();
        imagejpeg($lienzo, null, 80);
        $jpeg = (string) ob_get_clean();
        imagedestroy($lienzo);

        return $jpeg;
    }

    /**
     * @param  array<int,int>  $ordenes
     * @param  array<string,int>  $metodos
     */
    private function pagos(array $ordenes, array $metodos): void
    {
        $ejemplo = [
            [3, 'Efectivo', 8000, '2026-09-02 10:00:00', null, null],
            [2, 'Efectivo', 10000, '2026-08-25 10:00:00', null, null],
            [5, 'Efectivo', 10000, '2026-09-07 10:25:00', null, null],
            [6, 'Efectivo', 5000, '2026-09-08 11:00:00', null, null],
            [7, 'Nequi', 15000, '2026-09-11 09:35:00', '2026-09-11 09:40:00', 'Registrado en la orden equivocada'],
            [7, 'Nequi', 20000, '2026-09-16 09:15:00', null, null],
            [8, 'Efectivo', 11000, '2026-09-12 14:00:00', null, null],
        ];

        foreach ($ejemplo as [$orden, $metodo, $valor, $pagado, $anulado, $motivo]) {
            DB::table('pagos')->insert([
                'orden_id' => $ordenes[$orden],
                'metodo_pago_id' => $metodos[$metodo],
                'valor' => $valor,
                'pagado_en' => $this->fecha($pagado),
                'anulado_en' => $this->fecha($anulado),
                'motivo_anulacion' => $motivo,
                'actualizado_en' => $this->fecha($pagado),
            ]);
        }
    }

    /**
     * @param  array<int,int>  $ordenes
     */
    private function avisos(array $ordenes): void
    {
        $ejemplo = [
            [1, '2026-08-01 17:00:00', 'enviado', 'asistido', 'Hola Carmen, tu orden #0030 del taller está lista para recoger: 2 prendas. Saldo pendiente: $16.000. Te esperamos.', '2026-08-01 17:00:00', '2026-08-01 17:20:00'],
            [2, '2026-08-24 15:00:00', 'enviado', 'asistido', 'Hola María, tu orden #0039 del taller está lista para recoger: 1 prenda. Saldo pendiente: $10.000. Te esperamos.', '2026-08-24 15:00:00', '2026-08-24 15:05:00'],
            [3, '2026-09-08 16:00:00', 'enviado', 'asistido', 'Hola Marta, tu orden #0040 del taller está lista para recoger: 1 prenda. Saldo pendiente: $12.000. Te esperamos.', '2026-09-08 16:00:00', '2026-09-08 16:30:00'],
            [5, '2026-09-15 16:10:00', 'descartado', null, null, '2026-09-15 16:10:00', '2026-09-15 16:12:00'],
            [8, '2026-09-16 09:40:00', 'pendiente_asistido', null, null, '2026-09-16 09:40:00', null],
        ];

        foreach ($ejemplo as [$orden, $ciclo, $estado, $canal, $mensaje, $generado, $resuelto]) {
            DB::table('avisos')->insert([
                'orden_id' => $ordenes[$orden],
                'ciclo_lista_en' => $this->fecha($ciclo),
                'estado' => $estado,
                'canal' => $canal,
                'mensaje' => $mensaje,
                'intentos' => 0,
                'generado_en' => $this->fecha($generado),
                'resuelto_en' => $this->fecha($resuelto),
                'actualizado_en' => $this->fecha($generado),
            ]);
        }
    }

    /**
     * El segundo taller de los datos de ejemplo. No hace falta entrar con él: existe para que las
     * pruebas de aislamiento tengan datos ajenos que pedir (RN-01, RNF-22).
     */
    private function otroNegocio(): void
    {
        $otro = (int) DB::table('negocios')->insertGetId([
            'nombre' => 'Otro taller de prueba',
            'dias_sin_reclamar' => 30,
            'creado_en' => $this->fecha('2026-07-01 08:00:00'),
            'actualizado_en' => $this->fecha('2026-07-01 08:00:00'),
        ]);

        $tipo = (int) DB::table('tipos_prenda')->insertGetId([
            'negocio_id' => $otro,
            'nombre' => 'Pantalón',
            'activo' => true,
        ]);

        $cliente = (int) DB::table('clientes')->insertGetId([
            'negocio_id' => $otro,
            'nombre' => 'Marta Suárez',
            'celular' => '3108889999',
            'creado_en' => $this->fecha('2026-09-15 07:55:00'),
            'actualizado_en' => $this->fecha('2026-09-15 07:55:00'),
        ]);

        $orden = (int) DB::table('ordenes')->insertGetId([
            'negocio_id' => $otro,
            'cliente_id' => $cliente,
            'numero' => 1,
            'fecha_entrega_acordada' => $this->fecha('2026-09-20', soloElDia: true),
            'recibida_en' => $this->fecha('2026-09-15 08:00:00'),
            'lista_en' => null,
            'cancelada_en' => null,
            'actualizado_en' => $this->fecha('2026-09-15 08:00:00'),
        ]);

        DB::table('prendas')->insert([
            'orden_id' => $orden,
            'tipo_prenda_id' => $tipo,
            'descripcion_arreglo' => 'Subir basta',
            'precio' => 12000,
            'estado' => 'pendiente',
            'entregada_en' => null,
            'devuelta_en' => null,
            'creado_en' => $this->fecha('2026-09-15 08:00:00'),
            'actualizado_en' => $this->fecha('2026-09-15 08:00:00'),
        ]);
    }

    /**
     * La fecha del ejemplo, corrida para que «hoy» sea el día en que se carga.
     */
    private function fecha(?string $valor, bool $soloElDia = false): ?string
    {
        if ($valor === null) {
            return null;
        }

        $corrida = CarbonImmutable::parse($valor)->addDays($this->dias);

        return $soloElDia ? $corrida->toDateString() : $corrida->toDateTimeString();
    }
}
