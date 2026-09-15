<?php

namespace App\Modelos;

use App\Dominio\Ordenes\EstadoDePrenda;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prenda extends Model
{
    use HasFactory;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'prendas';

    protected $fillable = ['tipo_prenda_id', 'descripcion_arreglo', 'precio', 'estado', 'entregada_en', 'devuelta_en'];

    protected function casts(): array
    {
        return [
            'precio' => 'integer',
            'estado' => EstadoDePrenda::class,
            'entregada_en' => 'immutable_datetime',
            'devuelta_en' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Orden, $this>
     */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class);
    }

    /**
     * @return BelongsTo<TipoPrenda, $this>
     */
    public function tipoPrenda(): BelongsTo
    {
        return $this->belongsTo(TipoPrenda::class);
    }

    /**
     * @return HasMany<Foto, $this>
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class);
    }
}
