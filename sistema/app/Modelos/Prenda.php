<?php

namespace App\Modelos;

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
            'entregada_en' => 'immutable_datetime',
            'devuelta_en' => 'immutable_datetime',
        ];
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class);
    }

    public function tipoPrenda(): BelongsTo
    {
        return $this->belongsTo(TipoPrenda::class);
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class);
    }
}
