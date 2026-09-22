<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Compartido\Reloj;
use App\Dominio\Pagos\Dinero;
use App\Modelos\Pago;
use DateTimeImmutable;

/**
 * CU-29 · HU-27 · Ver cuánto dinero he recibido (PT-22): la suma de los pagos no anulados de un período (RN-33).
 * Los días son los de Colombia (RN-09): un pago de las 11:30 p. m. cuenta en su día (CA-27.2).
 */
class DineroRecibido
{
    public const PERIODOS = ['hoy', 'semana', 'mes', 'fechas'];

    public function __construct(private readonly Reloj $reloj) {}

    /**
     * Los días que abarca un período, contados desde hoy. «fechas» usa las que eligió la dueña.
     *
     * @return array{desde: DateTimeImmutable, hasta: DateTimeImmutable}
     */
    public function periodo(string $periodo, ?DateTimeImmutable $desde = null, ?DateTimeImmutable $hasta = null): array
    {
        $hoy = $this->reloj->hoy();

        [$desde, $hasta] = match ($periodo) {
            'hoy' => [$hoy, $hoy],
            // De lunes a domingo, como se cuenta una semana de trabajo
            'semana' => [$hoy->modify('monday this week'), $hoy->modify('sunday this week')],
            'fechas' => [$desde ?? $hoy, $hasta ?? $hoy],
            default => [$hoy->modify('first day of this month'), $hoy->modify('last day of this month')],
        };

        return ['desde' => $desde, 'hasta' => $hasta];
    }

    /**
     * @return array{recibido: Dinero, pagos: int, anulados: int}
     */
    public function entre(DateTimeImmutable $desde, DateTimeImmutable $hasta): array
    {
        // Una sola consulta. Los pagos no guardan su negocio: el filtro llega por la orden (RNF-22)
        $fila = Pago::query()
            ->whereHas('orden')
            ->whereBetween('pagado_en', [$desde->format('Y-m-d').' 00:00:00', $hasta->format('Y-m-d').' 23:59:59'])
            ->selectRaw('coalesce(sum(case when anulado_en is null then valor end), 0) as recibido')
            ->selectRaw('coalesce(sum(case when anulado_en is null then 1 else 0 end), 0) as pagos')
            ->selectRaw('coalesce(sum(case when anulado_en is null then 0 else 1 end), 0) as anulados')
            ->toBase()
            ->first();

        return [
            // RN-31: el anulado no suma, pero se cuenta para decir que no está incluido
            'recibido' => Dinero::pesos((int) ($fila->recibido ?? 0)),
            'pagos' => (int) ($fila->pagos ?? 0),
            'anulados' => (int) ($fila->anulados ?? 0),
        ];
    }
}
