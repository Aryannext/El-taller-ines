<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Foto extends Model
{
    use HasFactory;

    public const CREATED_AT = 'creado_en';

    // Una foto no se modifica: se elimina y se toma otra
    public const UPDATED_AT = null;

    protected $table = 'fotos';

    protected $fillable = ['posicion', 'ruta', 'ancho_px', 'alto_px', 'bytes'];

    protected function casts(): array
    {
        return [
            'posicion' => 'integer',
            'ancho_px' => 'integer',
            'alto_px' => 'integer',
            'bytes' => 'integer',
        ];
    }

    /**
     * La foto no guarda su negocio: se busca a través de su orden, que sí lo filtra (RN-01, RNF-25).
     * Sin esto, /fotos/{id} entregaría la foto de otro taller.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()->whereKey($value)->whereHas('prenda.orden')->first();
    }

    /**
     * @return BelongsTo<Prenda, $this>
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(Prenda::class);
    }
}
