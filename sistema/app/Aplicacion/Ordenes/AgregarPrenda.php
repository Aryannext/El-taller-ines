<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Aplicacion\Fotos\AgregarFoto;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Pagos\Dinero;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * HU-11 · Agregar una prenda que se olvidó registrar a una orden que ya existe. Queda Pendiente: si la orden estaba lista,
 * vuelve a En proceso y se borra la fecha en que había quedado lista (RN-18, RN-22).
 */
class AgregarPrenda
{
    public function __construct(
        private readonly ResolverTipoDePrenda $resolverTipoDePrenda,
        private readonly AgregarFoto $agregarFoto,
        private readonly SincronizarEstadoDeOrden $sincronizarEstado,
    ) {}

    /**
     * Lanza la regla que impide agregarle prendas a la orden, si la hay. PT-06 lo pregunta antes de mostrar el formulario (CA-11.2, CA-11.3).
     */
    public function exigirQueSePuedaAgregar(Orden $orden): void
    {
        if ($orden->cancelada_en !== null) {
            throw new ReglaIncumplida('RN-24', 'Esta orden está cancelada y no admite cambios.');
        }

        // RF-11: solo En proceso o Lista para entregar. Una entregada quedaría entregada con trabajo pendiente (F-02)
        $estado = EstadoDeOrden::calcular($orden->prendas()->get()->map(fn (Prenda $prenda) => $prenda->estado), false);
        if ($estado === EstadoDeOrden::Entregada) {
            throw new ReglaIncumplida('RN-18', 'Solo se pueden agregar prendas a una orden en proceso o lista para entregar.');
        }
    }

    /**
     * @param  list<string>  $fotos  rutas temporales de las fotos subidas
     */
    public function ejecutar(Orden $orden, int|string $tipoPrendaId, ?string $tipoOtro, string $descripcion, int $precio, array $fotos = []): Prenda
    {
        $valor = Dinero::precio($precio);
        // Las fotos se reducen antes de abrir la transacción, como al registrar la orden (RNF-03)
        $archivos = $this->agregarFoto->guardarArchivos($fotos, $orden->negocio_id);

        try {
            return DB::transaction(function () use ($orden, $tipoPrendaId, $tipoOtro, $descripcion, $valor, $archivos): Prenda {
                // Bloquear la orden: una entrega o una cancelación al mismo tiempo no se cuela entre la revisión y el guardado
                $orden = Orden::whereKey($orden->id)->lockForUpdate()->firstOrFail();
                $this->exigirQueSePuedaAgregar($orden);

                $tipo = $this->resolverTipoDePrenda->resolver($orden->negocio_id, $tipoPrendaId, $tipoOtro);
                $prenda = $orden->prendas()->create([
                    'tipo_prenda_id' => $tipo->id,
                    'descripcion_arreglo' => $descripcion,
                    'precio' => $valor->valor(),
                    'estado' => EstadoDePrenda::inicial(),
                ]);
                $this->agregarFoto->registrar($prenda, $archivos);

                // CA-11.1: con una prenda Pendiente, la orden lista vuelve a En proceso y sus avisos sin enviar se descartan (RN-39)
                $this->sincronizarEstado->sincronizar($orden);

                return $prenda;
            });
        } catch (Throwable $error) {
            // La prenda no quedó guardada: sus fotos no se quedan sueltas en el disco
            $this->agregarFoto->descartar($archivos);

            throw $error;
        }
    }
}
