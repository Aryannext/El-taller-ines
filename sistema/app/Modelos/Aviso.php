<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aviso extends Model
{
    use HasFactory;

    public const CREATED_AT = 'generado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'avisos';

    protected $fillable = ['ciclo_lista_en', 'estado', 'canal', 'mensaje', 'intentos', 'id_mensaje_whatsapp', 'resuelto_en'];

    protected function casts(): array
    {
        return [
            'intentos' => 'integer',
            'ciclo_lista_en' => 'immutable_datetime',
            'generado_en' => 'immutable_datetime',
            'resuelto_en' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Orden, $this>
     */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class);
    }
}
