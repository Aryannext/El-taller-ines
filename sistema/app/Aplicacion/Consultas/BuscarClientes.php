<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Modelos\Cliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * HU-04 · Buscar un cliente por parte del nombre o por su celular (RF-05). Solo ve los del negocio de la sesión (RN-01).
 */
class BuscarClientes
{
    /**
     * @return Collection<int, Cliente>
     */
    public function listar(string $texto): Collection
    {
        $texto = trim($texto);
        $consulta = Cliente::query()->orderBy('nombre');

        if ($texto === '') {
            return $consulta->get();
        }

        return $consulta->where(function (Builder $condicion) use ($texto): void {
            // La intercalación utf8mb4_0900_ai_ci compara sin tildes ni mayúsculas: «maria» encuentra a María
            $condicion->where('nombre', 'like', $this->contiene($texto));

            if (preg_match('/^[\d\s]+$/', $texto) === 1) {
                $condicion->orWhere('celular', 'like', $this->contiene(preg_replace('/\s+/', '', $texto) ?? ''));
            }
        })->get();
    }

    /**
     * «%» y «_» son comodines de LIKE: se escapan para buscar el texto tal como se escribió.
     */
    private function contiene(string $texto): string
    {
        return '%'.addcslashes($texto, '\\%_').'%';
    }
}
