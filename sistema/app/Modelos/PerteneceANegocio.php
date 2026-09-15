<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Filtro global de las tablas raíz: una usuaria solo consulta y crea información de su negocio (RN-01, ADR-002).
 *
 * - Con sesión, filtra por el negocio de la usuaria y lo asigna al crear.
 * - En una ruta web sin sesión no devuelve nada, para que un error de configuración no exponga datos.
 * - En la cola y en la consola no hay sesión y no filtra: esos procesos trabajan con identificadores concretos.
 */
trait PerteneceANegocio
{
    public static function bootPerteneceANegocio(): void
    {
        static::addGlobalScope('negocio', function (Builder $consulta): void {
            $usuario = Auth::user();

            if ($usuario !== null) {
                $consulta->where($consulta->qualifyColumn('negocio_id'), $usuario->negocio_id);
            } elseif (request()->route() !== null) {
                $consulta->whereRaw('1 = 0');
            }
        });

        static::creating(function (Model $modelo): void {
            $usuario = Auth::user();

            if ($usuario !== null && $modelo->getAttribute('negocio_id') === null) {
                $modelo->setAttribute('negocio_id', $usuario->negocio_id);
            }
        });
    }

    /**
     * @return BelongsTo<Negocio, $this>
     */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
