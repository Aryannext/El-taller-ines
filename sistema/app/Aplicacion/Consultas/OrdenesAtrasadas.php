<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Ordenes\ReglasDeSeguimiento;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Database\Eloquent\Builder;

/**
 * CU-34 · Las órdenes En proceso cuya fecha de entrega acordada ya pasó (HU-33, PT-20, RF-38).
 * Una orden Lista para entregar no está atrasada: el trabajo está hecho (RN-34, CA-33.2).
 * «Hoy» sale del reloj de Colombia, no del servidor (RN-09, CA-33.3).
 */
class OrdenesAtrasadas
{
    public function __construct(
        private readonly ListarOrdenes $ordenes,
        private readonly Reloj $reloj,
        private readonly ReglasDeSeguimiento $reglas,
    ) {}

    /**
     * De la más atrasada a la menos (CA-33.1).
     *
     * @return list<array{orden: Orden, numero: string, diasDeAtraso: int, prendas: string}>
     */
    public function listar(): array
    {
        // RNF-01: el cliente y las prendas de la página se cargan juntos, no uno por orden
        return $this->atrasadas()
            ->with(['cliente', 'prendas' => fn ($prendas) => $prendas->orderBy('id'), 'prendas.tipoPrenda'])
            ->orderBy('fecha_entrega_acordada')
            ->orderBy('numero')
            ->get()
            ->map(fn (Orden $orden) => [
                'orden' => $orden,
                'numero' => NumeroDeOrden::desde($orden->numero)->formato(),
                'diasDeAtraso' => $this->diasDeAtraso($orden),
                'prendas' => $orden->prendas->map(fn (Prenda $prenda) => $prenda->tipoPrenda->nombre)->join(', ', ' y '),
            ])
            ->all();
    }

    public function contar(): int
    {
        return $this->atrasadas()->count();
    }

    /**
     * Los días calendario que pasaron desde la fecha acordada hasta hoy.
     */
    public function diasDeAtraso(Orden $orden): int
    {
        return $this->reglas->diasDeAtraso($orden->fecha_entrega_acordada, $this->reloj->hoy());
    }

    /**
     * RN-34 en SQL, la misma regla de ReglasDeSeguimiento::estaAtrasada: En proceso y con la fecha acordada anterior a hoy. El estado se filtra con el mismo
     * cálculo que la lista de órdenes (RN-18), para no escribirlo dos veces.
     *
     * @return Builder<Orden>
     */
    private function atrasadas(): Builder
    {
        return $this->ordenes
            ->conEstado(Orden::query(), EstadoDeOrden::EnProceso)
            ->whereDate('fecha_entrega_acordada', '<', $this->reloj->hoy());
    }
}
