<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No guarda su negocio: en las rutas, {foto} se busca con FotosDeOrden::foto(), a través de su prenda y su orden (RNF-25).
 */
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
     * @return BelongsTo<Prenda, $this>
     */
    public function prenda(): BelongsTo
    {
        return $this->belongsTo(Prenda::class);
    }
}
