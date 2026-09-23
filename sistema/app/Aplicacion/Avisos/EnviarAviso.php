<?php

declare(strict_types=1);

namespace App\Aplicacion\Avisos;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Dominio\Avisos\CanalDeAviso;
use App\Dominio\Avisos\MensajeDeAviso;
use App\Dominio\Clientes\Celular;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Modelos\Aviso;
use App\Modelos\Prenda;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Trabajo de la cola «avisos»: envía el aviso de orden lista con los datos de ese momento (RN-40 a RN-42). La pantalla no lo espera (RNF-04).
 * Si la API falla, la cola lo reintenta hasta 3 veces con espera creciente y después queda para el envío asistido (RNF-17).
 */
class EnviarAviso implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public int $avisoId)
    {
        $this->onQueue('avisos');
    }

    /**
     * Segundos de espera antes del segundo y del tercer intento.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(CanalDeAviso $canal, Reloj $reloj, DetalleDeOrden $detalleDeOrden): void
    {
        $aviso = Aviso::with('orden.cliente')->find($this->avisoId);
        // Ya se resolvió: un intento repetido no vuelve a enviarlo (RN-38)
        if ($aviso === null || $aviso->estado !== 'en_cola') {
            return;
        }

        $orden = $aviso->orden;
        $detalle = $detalleDeOrden->obtener($orden);
        // RN-39: si la orden ya no está lista, o volvió a quedar lista y tiene otro aviso, este no sale
        if ($detalle['estado'] !== EstadoDeOrden::ListaParaEntregar || $orden->lista_en?->getTimestamp() !== $aviso->ciclo_lista_en->getTimestamp()) {
            $aviso->update(['estado' => 'descartado', 'resuelto_en' => $reloj->ahora()]);

            return;
        }

        // RN-40: sin la API configurada, la dueña lo envía desde su WhatsApp. El mensaje se arma al abrirlo (RN-42)
        if (! $canal->estaDisponible()) {
            $aviso->update(['estado' => 'pendiente_asistido']);

            return;
        }

        $mensaje = MensajeDeAviso::construir(
            $orden->cliente->nombre,
            // RN-46: el cliente sabe de qué taller le escriben
            $orden->negocio->nombre,
            NumeroDeOrden::desde($orden->numero),
            $orden->prendas->filter(fn (Prenda $prenda) => $prenda->estado === EstadoDePrenda::Terminada)->count(),
            $detalle['saldo'],
        );

        $aviso->update(['intentos' => $aviso->intentos + 1]);
        // Un error temporal lanza una excepción y la cola reintenta
        $resultado = $canal->enviar(Celular::desde($orden->cliente->celular), $mensaje);

        if ($resultado->aceptado) {
            // RN-41: canal, mensaje y resultado
            $aviso->update([
                'estado' => 'enviado',
                'canal' => $resultado->canal,
                'mensaje' => $mensaje->texto(),
                'id_mensaje_whatsapp' => $resultado->idMensaje,
                'resuelto_en' => $reloj->ahora(),
            ]);

            return;
        }

        // Token vencido, número sin WhatsApp o plantilla no aprobada: reintentar no lo arregla. El registro no guarda el celular ni el token
        Log::warning('WhatsApp no aceptó el aviso; queda para envío asistido.', ['aviso' => $aviso->id, 'error' => $resultado->error]);
        $aviso->update(['estado' => 'pendiente_asistido']);
    }

    /**
     * Se agotaron los 3 intentos: el aviso queda para el envío asistido (RN-40, CA-28.5).
     */
    public function failed(?Throwable $error): void
    {
        Aviso::whereKey($this->avisoId)->where('estado', 'en_cola')->update(['estado' => 'pendiente_asistido']);
    }
}
