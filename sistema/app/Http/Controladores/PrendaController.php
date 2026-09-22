<?php

namespace App\Http\Controladores;

use App\Aplicacion\Configuracion\GestionarTiposDePrenda;
use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Ordenes\AgregarPrenda;
use App\Aplicacion\Ordenes\CambiarEstadoDePrenda;
use App\Aplicacion\Ordenes\CorregirPrenda;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Http\Solicitudes\PrendaRequest;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Las acciones sobre una prenda. No hay ninguna para cambiar el estado de la orden: se calcula desde sus prendas (RN-19).
 */
class PrendaController
{
    /**
     * PT-06 con una sola prenda: la que se olvidó registrar al recibir la orden (HU-11). Si la orden no la admite, vuelve al detalle con el motivo.
     */
    public function nueva(Orden $orden, AgregarPrenda $agregarPrenda, DetalleDeOrden $detalleDeOrden, GestionarTiposDePrenda $tiposDePrenda): View|RedirectResponse
    {
        try {
            $agregarPrenda->exigirQueSePuedaAgregar($orden);
        } catch (ReglaIncumplida $regla) {
            return $this->alDetalleConElMotivo($orden, $regla);
        }

        return view('pantallas.pt-06-agregar-prenda', [...$detalleDeOrden->obtener($orden), 'tipos' => $tiposDePrenda->activos()]);
    }

    public function agregar(PrendaRequest $solicitud, Orden $orden, AgregarPrenda $agregarPrenda): RedirectResponse
    {
        try {
            $prenda = $agregarPrenda->ejecutar(
                $orden,
                $solicitud->validated('tipo_prenda_id') === 'otro' ? 'otro' : (int) $solicitud->validated('tipo_prenda_id'),
                $solicitud->validated('tipo_otro'),
                $solicitud->validated('descripcion_arreglo'),
                (int) $solicitud->validated('precio'),
                $solicitud->rutasDeFotos(),
            );
        } catch (ReglaIncumplida $regla) {
            // RN-17 se muestra junto a las fotos; RN-18 y RN-24 impiden agregar y vuelven al detalle
            return $regla->campo !== null
                ? back()->withInput()->withErrors([$regla->campo => $regla->mensajeParaUsuaria])
                : $this->alDetalleConElMotivo($orden, $regla);
        }

        return redirect()->route('ordenes.detalle', $orden)->with('exito', "Listo: «{$prenda->descripcion_arreglo}» quedó en la orden.");
    }

    /**
     * PT-11 · Acciones de una prenda: en qué va y qué más se puede hacer (HU-20). Solo ofrece los cambios permitidos (CA-20.2).
     */
    public function acciones(Orden $orden, Prenda $prenda, CambiarEstadoDePrenda $cambiarEstado, DetalleDeOrden $detalleDeOrden): View|RedirectResponse
    {
        try {
            $cambiarEstado->exigirQueSePuedaCambiar($prenda, $orden);
        } catch (ReglaIncumplida $regla) {
            return $this->alDetalleConElMotivo($orden, $regla);
        }

        return view('pantallas.pt-11-acciones-prenda', [
            ...$detalleDeOrden->obtener($orden),
            'prenda' => $prenda,
            'estadosPosibles' => $cambiarEstado->estadosPosibles($prenda, $orden),
        ]);
    }

    public function cambiarEstado(Request $solicitud, Orden $orden, Prenda $prenda, CambiarEstadoDePrenda $cambiarEstado): RedirectResponse
    {
        // Solo los valores posibles; si el cambio se permite desde el estado actual lo decide TransicionesDePrenda (03-validaciones-y-mensajes)
        $datos = $solicitud->validate(
            ['estado' => ['required', 'in:pendiente,en_proceso,terminada']],
            ['estado.required' => 'Elige uno de los estados que se muestran.', 'estado.in' => 'Elige uno de los estados que se muestran.'],
        );
        $hacia = EstadoDePrenda::from($datos['estado']);

        try {
            $estadoDeLaOrden = $cambiarEstado->ejecutar($prenda, $hacia);
        } catch (ReglaIncumplida $regla) {
            return $regla->campo !== null
                ? redirect()->route('prendas.acciones', [$orden, $prenda])->withErrors([$regla->campo => $regla->mensajeParaUsuaria])
                : $this->alDetalleConElMotivo($orden, $regla);
        }

        // CA-20.3: si con esta prenda la orden quedó lista, eso es lo que importa decir
        $mensaje = $estadoDeLaOrden === EstadoDeOrden::ListaParaEntregar && $hacia === EstadoDePrenda::Terminada
            ? 'La orden quedó lista para entregar.'
            : "Listo: «{$prenda->descripcion_arreglo}» ahora está ".mb_strtolower($hacia->etiqueta()).'.';

        return redirect()->route('ordenes.detalle', $orden)->with('exito', $mensaje);
    }

    /**
     * PT-13 · Corregir el arreglo o el precio (HU-12). Si la prenda no se puede corregir, vuelve al detalle con el motivo (CA-12.3).
     */
    public function editar(Orden $orden, Prenda $prenda, CorregirPrenda $corregirPrenda, DetalleDeOrden $detalleDeOrden): View|RedirectResponse
    {
        try {
            $corregirPrenda->exigirQueSePuedaCorregir($prenda, $orden);
        } catch (ReglaIncumplida $regla) {
            return $this->alDetalleConElMotivo($orden, $regla);
        }

        return view('pantallas.pt-13-corregir-prenda', [...$detalleDeOrden->obtener($orden), 'prenda' => $prenda]);
    }

    public function corregir(PrendaRequest $solicitud, Orden $orden, Prenda $prenda, CorregirPrenda $corregirPrenda): RedirectResponse
    {
        try {
            $corregirPrenda->ejecutar($prenda, $solicitud->validated('descripcion_arreglo'), (int) $solicitud->validated('precio'));
        } catch (ReglaIncumplida $regla) {
            // RN-16 se muestra junto al precio; RN-15 y RN-24 impiden corregir la prenda y vuelven al detalle
            return $regla->campo !== null
                ? back()->withInput()->withErrors([$regla->campo => $regla->mensajeParaUsuaria])
                : $this->alDetalleConElMotivo($orden, $regla);
        }

        return redirect()->route('ordenes.detalle', $orden)->with('exito', 'Los cambios de la prenda quedaron guardados.');
    }

    private function alDetalleConElMotivo(Orden $orden, ReglaIncumplida $regla): RedirectResponse
    {
        return redirect()->route('ordenes.detalle', $orden)->withErrors(['prenda' => $regla->mensajeParaUsuaria]);
    }
}
