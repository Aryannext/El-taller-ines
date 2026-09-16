<?php

namespace App\Http\Controladores;

use App\Aplicacion\Avisos\ConfirmarEnvioAsistido;
use App\Aplicacion\Consultas\AvisosPorEnviar;
use App\Dominio\Avisos\MensajeDeAviso;
use App\Dominio\Clientes\Celular;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Infraestructura\Avisos\WhatsAppAsistidoCanal;
use App\Modelos\Aviso;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * HU-29 · Los avisos que la dueña envía con un toque desde su WhatsApp, cuando la API oficial no está configurada
 * o el envío automático falló (ADR-003, RN-40).
 */
class AvisoController
{
    public function __construct(private readonly WhatsAppAsistidoCanal $canal) {}

    /**
     * PT-18 · Avisos por enviar, con el mensaje redactado en este momento (RN-42).
     */
    public function pendientes(AvisosPorEnviar $avisosPorEnviar): View
    {
        $pendientes = array_map(fn (array $fila) => [
            ...$fila,
            'enlace' => $this->enlaceDeWhatsApp($fila['aviso'], $fila['mensaje']),
        ], $avisosPorEnviar->listar());

        return view('pantallas.pt-18-avisos-pendientes', ['pendientes' => $pendientes]);
    }

    /**
     * Abre WhatsApp con el mensaje escrito. No cambia el aviso: solo la confirmación lo marca enviado (CA-29.4).
     */
    public function abrirWhatsapp(Aviso $aviso, AvisosPorEnviar $avisosPorEnviar): RedirectResponse
    {
        $mensaje = $avisosPorEnviar->mensajeDe($aviso);
        if ($mensaje === null || $aviso->estado !== 'pendiente_asistido') {
            return $this->aLosPendientes('Este aviso ya no está pendiente de envío.');
        }

        return redirect()->away($this->enlaceDeWhatsApp($aviso, $mensaje));
    }

    public function confirmarEnvio(Aviso $aviso, ConfirmarEnvioAsistido $confirmarEnvio): RedirectResponse
    {
        try {
            $confirmarEnvio->ejecutar($aviso);
        } catch (ReglaIncumplida $regla) {
            return $this->aLosPendientes($regla->mensajeParaUsuaria);
        }

        return redirect()->route('avisos.pendientes')
            ->with('exito', 'El aviso a '.$aviso->orden->cliente->nombre.' quedó registrado como enviado.');
    }

    private function enlaceDeWhatsApp(Aviso $aviso, MensajeDeAviso $mensaje): string
    {
        return $this->canal->enlace(Celular::desde($aviso->orden->cliente->celular), $mensaje);
    }

    private function aLosPendientes(string $motivo): RedirectResponse
    {
        return redirect()->route('avisos.pendientes')->withErrors(['aviso' => $motivo]);
    }
}
