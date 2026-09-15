<?php

declare(strict_types=1);

namespace App\Aplicacion\Fotos;

use App\Aplicacion\Ordenes\CorregirPrenda;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Fotos\AlmacenDeFotos;
use App\Modelos\Foto;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * HU-17 · Las fotos de una prenda, para reconocerla en el rincón (RN-17). La imagen se reduce antes de guardarla (RNF-03).
 *
 * Reducir una foto tarda, así que se guarda el archivo antes de abrir la transacción y la fila se registra dentro.
 * Si algo falla, el archivo se borra. RegistrarOrden usa guardarArchivos, registrar y descartar; PT-13 usa ejecutar.
 */
class AgregarFoto
{
    public const MAXIMO_POR_PRENDA = 3;

    public function __construct(
        private readonly AlmacenDeFotos $almacen,
        private readonly CorregirPrenda $corregirPrenda,
    ) {}

    /**
     * @param  list<string>  $rutasTemporales
     * @return list<Foto>
     */
    public function ejecutar(Prenda $prenda, array $rutasTemporales): array
    {
        // Antes de reducir: no se gasta tiempo en fotos que no caben
        $this->exigirEspacio($prenda->fotos()->count(), count($rutasTemporales));
        $archivos = $this->guardarArchivos($rutasTemporales, $prenda->orden->negocio_id);

        try {
            return DB::transaction(function () use ($prenda, $archivos): array {
                // Bloquear la orden, como los demás cambios de la prenda: dos envíos al mismo tiempo no pasan de tres fotos
                $orden = Orden::whereKey($prenda->orden_id)->lockForUpdate()->firstOrFail();
                $prenda->refresh();
                // Una prenda Entregada o Devuelta, o de una orden cancelada, no cambia (RN-15, RN-24)
                $this->corregirPrenda->exigirQueSePuedaCorregir($prenda, $orden);

                return $this->registrar($prenda, $archivos);
            });
        } catch (Throwable $error) {
            $this->descartar($archivos);

            throw $error;
        }
    }

    /**
     * Reduce y guarda los archivos, sin tocar la base de datos.
     *
     * @param  list<string>  $rutasTemporales
     * @return list<array{ruta: string, ancho_px: int, alto_px: int, bytes: int}>
     */
    public function guardarArchivos(array $rutasTemporales, int $negocioId): array
    {
        $archivos = [];

        try {
            foreach ($rutasTemporales as $rutaTemporal) {
                $archivos[] = $this->almacen->guardar($rutaTemporal, "fotos/{$negocioId}");
            }
        } catch (Throwable $error) {
            $this->descartar($archivos);

            throw $error;
        }

        return $archivos;
    }

    /**
     * RN-17: hasta tres fotos, cada una en la primera posición libre de 1 a 3. Se llama dentro de la transacción.
     *
     * @param  list<array{ruta: string, ancho_px: int, alto_px: int, bytes: int}>  $archivos
     * @return list<Foto>
     */
    public function registrar(Prenda $prenda, array $archivos): array
    {
        $ocupadas = $prenda->fotos()->pluck('posicion')->map(fn ($posicion) => (int) $posicion)->all();
        $this->exigirEspacio(count($ocupadas), count($archivos));
        $libres = array_values(array_diff(range(1, self::MAXIMO_POR_PRENDA), $ocupadas));

        $fotos = [];
        foreach ($archivos as $indice => $archivo) {
            $fotos[] = $prenda->fotos()->create([...$archivo, 'posicion' => $libres[$indice]]);
        }

        return $fotos;
    }

    /**
     * @param  list<array{ruta: string, ancho_px: int, alto_px: int, bytes: int}>  $archivos
     */
    public function descartar(array $archivos): void
    {
        foreach ($archivos as $archivo) {
            $this->almacen->eliminar($archivo['ruta']);
        }
    }

    private function exigirEspacio(int $tiene, int $nuevas): void
    {
        if ($tiene + $nuevas <= self::MAXIMO_POR_PRENDA) {
            return;
        }

        throw new ReglaIncumplida('RN-17', $tiene >= self::MAXIMO_POR_PRENDA
            ? 'Esta prenda ya tiene 3 fotos. Elimina una para agregar otra.'
            : 'Cada prenda puede tener hasta 3 fotos.', 'fotos');
    }
}
