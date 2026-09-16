<?php

declare(strict_types=1);

namespace App\Aplicacion\Avisos;

use App\Aplicacion\Consultas\AvisosPorEnviar;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Modelos\Aviso;

/**
 * HU-29 · La dueña confirma que envió el aviso desde su WhatsApp. Hasta entonces el aviso sigue pendiente (CA-29.4).
 */
class ConfirmarEnvioAsistido
{
    public const CANAL = 'asistido';

    public function __construct(
        private readonly Reloj $reloj,
        private readonly AvisosPorEnviar $avisosPorEnviar,
    ) {}

    public function ejecutar(Aviso $aviso): Aviso
    {
        if ($aviso->estado !== 'pendiente_asistido') {
            throw new ReglaIncumplida('RN-41', 'Este aviso ya no está pendiente de envío.');
        }

        $mensaje = $this->avisosPorEnviar->mensajeDe($aviso);
        if ($mensaje === null) {
            // RN-39: la orden dejó de estar lista mientras tanto; el aviso no se envía y queda descartado
            $aviso->update(['estado' => 'descartado', 'resuelto_en' => $this->reloj->ahora()]);

            throw new ReglaIncumplida('RN-39', 'La orden ya no está lista, así que su aviso salió de la lista.');
        }

        // RN-41: queda la constancia con el canal, el mensaje enviado y la hora
        $aviso->update([
            'estado' => 'enviado',
            'canal' => self::CANAL,
            'mensaje' => $mensaje->texto(),
            'resuelto_en' => $this->reloj->ahora(),
        ]);

        return $aviso;
    }
}
