<?php

namespace App\Http\Controladores;

use App\Aplicacion\Consultas\FotosDeOrden;
use App\Aplicacion\Fotos\AgregarFoto;
use App\Aplicacion\Fotos\EliminarFoto;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Fotos\AlmacenDeFotos;
use App\Http\Solicitudes\FotoRequest;
use App\Modelos\Foto;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FotoController
{
    /**
     * PT-10 · Las fotos de la orden agrupadas por prenda, para reconocer las prendas en el rincón (HU-18).
     */
    public function deOrden(Orden $orden, FotosDeOrden $fotosDeOrden): View
    {
        return view('pantallas.pt-10-fotos-orden', $fotosDeOrden->obtener($orden));
    }

    /**
     * PT-13 · Tomar o elegir fotos para una prenda que ya existe (HU-17).
     */
    public function guardar(FotoRequest $solicitud, Orden $orden, Prenda $prenda, AgregarFoto $agregarFoto): RedirectResponse
    {
        try {
            $fotos = $agregarFoto->ejecutar($prenda, $solicitud->rutasTemporales());
        } catch (ReglaIncumplida $regla) {
            // RN-17 se muestra junto a las fotos; RN-15 y RN-24 impiden cambiar la prenda y vuelven al detalle
            return $regla->campo !== null
                ? redirect()->route('prendas.editar', [$orden, $prenda])->withErrors([$regla->campo => $regla->mensajeParaUsuaria])
                : redirect()->route('ordenes.detalle', $orden)->withErrors(['prenda' => $regla->mensajeParaUsuaria]);
        }

        return redirect()->route('prendas.editar', [$orden, $prenda])
            ->with('exito', count($fotos) === 1 ? 'La foto quedó guardada.' : 'Las fotos quedaron guardadas.');
    }

    /**
     * HU-19 · Eliminar una foto borrosa o equivocada. El cuadro de diálogo de PT-13 manda la confirmación;
     * sin ella no se borra nada (RNF-10). La prenda se conserva aunque quede sin fotos (CA-19.2).
     */
    public function eliminar(Request $solicitud, Foto $foto, EliminarFoto $eliminarFoto): RedirectResponse
    {
        $prenda = $foto->prenda;
        $orden = $prenda->orden;
        $volver = redirect()->route('prendas.editar', [$orden, $prenda]);

        if ($solicitud->input('confirmacion') !== 'si') {
            return $volver->withErrors(['fotos' => 'Para eliminar la foto hay que confirmarlo en el cuadro que aparece.']);
        }

        try {
            $eliminarFoto->ejecutar($foto);
        } catch (ReglaIncumplida $regla) {
            return $volver->withErrors(['fotos' => $regla->mensajeParaUsuaria]);
        }

        return $volver->with('exito', 'La foto se eliminó.');
    }

    /**
     * Entrega una foto desde el disco privado, solo con sesión y del propio negocio (RNF-25, diagrama 11).
     * {foto} ya viene buscada por FotosDeOrden::foto(). La usan las miniaturas de PT-09, PT-10 y PT-13.
     */
    public function mostrar(Foto $foto, AlmacenDeFotos $almacen): BinaryFileResponse
    {
        return response()->file($almacen->entregar($foto->ruta), ['Content-Type' => 'image/jpeg']);
    }
}
