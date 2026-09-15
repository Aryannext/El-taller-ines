<?php

namespace App\Http\Controladores;

use App\Modelos\Foto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FotoController
{
    /**
     * Entrega una foto desde el disco privado, solo con sesión y del propio negocio (RNF-25).
     * La usan las miniaturas de PT-09 (HU-14) y, con HU-18, las fotos de la orden.
     */
    public function mostrar(Foto $foto): StreamedResponse
    {
        return Storage::disk('privado')->response($foto->ruta);
    }
}
