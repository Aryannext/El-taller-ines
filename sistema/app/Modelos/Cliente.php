<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;
    use PerteneceANegocio;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'clientes';

    protected $fillable = ['nombre', 'celular'];

    /**
     * @return HasMany<Orden, $this>
     */
    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class);
    }
}
