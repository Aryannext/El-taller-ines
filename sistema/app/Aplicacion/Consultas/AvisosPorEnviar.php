<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Avisos\MensajeDeAviso;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Modelos\Aviso;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use Illuminate\Database\Eloquent\Collection;

/**
 * HU-29 · Los avisos que esperan el envío asistido (PT-18). Solo los de órdenes que siguen listas: si una prenda volvió
 * a En proceso, el aviso ya no hace falta (RN-39, CA-30.2). El mensaje se arma con los datos de este momento (RN-42).
 */
class AvisosPorEnviar
{
    public function __construct(private readonly CalculadoraDeSaldo $calculadora) {}

    /**
     * @return list<array{aviso: Aviso, mensaje: MensajeDeAviso, numero: string}>
     */
    public function listar(): array
    {
        return $this->pendientes()
            ->map(fn (Aviso $aviso) => [
                'aviso' => $aviso,
                'mensaje' => $this->mensajeDe($aviso),
                'numero' => NumeroDeOrden::desde($aviso->orden->numero)->formato(),
            ])
            ->filter(fn (array $fila) => $fila['mensaje'] !== null)
            ->values()
            ->all();
    }

    public function contar(): int
    {
        return $this->pendientes()->filter(fn (Aviso $aviso) => $this->mensajeDe($aviso) !== null)->count();
    }

    /**
     * El aviso de la dirección, solo si es de una orden del negocio de la sesión (RNF-22, RNF-25).
     */
    public function aviso(string $id): ?Aviso
    {
        return ctype_digit($id) ? Aviso::with(self::RELACIONES)->whereHas('orden')->find((int) $id) : null;
    }

    /**
     * El mensaje con los datos de este momento, o null si la orden ya no está lista (RN-39, RN-42).
     */
    public function mensajeDe(Aviso $aviso): ?MensajeDeAviso
    {
        $orden = $aviso->orden;
        $orden->loadMissing(['cliente', 'negocio', 'prendas', 'pagos']);
        $estado = EstadoDeOrden::calcular($orden->prendas->map(fn (Prenda $prenda) => $prenda->estado), $orden->cancelada_en !== null);

        if ($estado !== EstadoDeOrden::ListaParaEntregar || $orden->lista_en?->getTimestamp() !== $aviso->ciclo_lista_en->getTimestamp()) {
            return null;
        }

        $valor = $this->calculadora->valor($orden->prendas->map(fn (Prenda $prenda) => [$prenda->precio, $prenda->estado]));
        $pagado = $this->calculadora->pagado($orden->pagos->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null]));

        return MensajeDeAviso::construir(
            $orden->cliente->nombre,
            // RN-46: el cliente sabe de qué taller le escriben
            $orden->negocio->nombre,
            NumeroDeOrden::desde($orden->numero),
            $orden->prendas->filter(fn (Prenda $prenda) => $prenda->estado === EstadoDePrenda::Terminada)->count(),
            $valor->restar($pagado),
        );
    }

    /** @var list<string> */
    private const RELACIONES = ['orden.cliente', 'orden.negocio', 'orden.prendas', 'orden.pagos'];

    /**
     * @return Collection<int, Aviso>
     */
    private function pendientes(): Collection
    {
        // whereHas aplica el filtro por negocio de la orden: el aviso no guarda el suyo (RNF-22)
        return Aviso::with(self::RELACIONES)
            ->whereHas('orden')
            ->where('estado', 'pendiente_asistido')
            ->orderBy('generado_en')
            ->orderBy('id')
            ->get();
    }
}
