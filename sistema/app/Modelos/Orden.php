<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Su estado, valor y saldo no se guardan: se calculan con el dominio (RN-18, RN-26, RN-27).
 */
class Orden extends Model
{
    use HasFactory;
    use PerteneceANegocio;

    public const CREATED_AT = 'recibida_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'ordenes';

    protected $fillable = ['cliente_id', 'numero', 'fecha_entrega_acordada', 'lista_en', 'cancelada_en', 'token_formulario'];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'fecha_entrega_acordada' => 'immutable_date',
            'recibida_en' => 'immutable_datetime',
            'lista_en' => 'immutable_datetime',
            'cancelada_en' => 'immutable_datetime',
        ];
    }

    /**
     * La dirección usa el número escrito en la bolsa: /ordenes/42 (RN-08).
     */
    public function getRouteKeyName(): string
    {
        return 'numero';
    }

    /**
     * @return BelongsTo<Cliente, $this>
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * @return HasMany<Prenda, $this>
     */
    public function prendas(): HasMany
    {
        return $this->hasMany(Prenda::class);
    }

    /**
     * @return HasMany<Pago, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * @return HasMany<Aviso, $this>
     */
    public function avisos(): HasMany
    {
        return $this->hasMany(Aviso::class);
    }

    /**
     * @return HasManyThrough<Foto, Prenda, $this>
     */
    public function fotos(): HasManyThrough
    {
        return $this->hasManyThrough(Foto::class, Prenda::class);
    }
}
