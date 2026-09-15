<?php

namespace App\Http\Controladores;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Ordenes\CorregirPrenda;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Http\Solicitudes\PrendaRequest;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PrendaController
{
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
