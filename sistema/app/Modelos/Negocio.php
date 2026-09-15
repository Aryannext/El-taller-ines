<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Negocio extends Model
{
    use HasFactory;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'negocios';

    protected $fillable = ['nombre', 'dias_sin_reclamar'];

    protected function casts(): array
    {
        return ['dias_sin_reclamar' => 'integer'];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function tiposPrenda(): HasMany
    {
        return $this->hasMany(TipoPrenda::class);
    }

    public function metodosPago(): HasMany
    {
        return $this->hasMany(MetodoPago::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class);
    }
}
