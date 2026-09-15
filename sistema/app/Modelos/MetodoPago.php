<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    use HasFactory;
    use PerteneceANegocio;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'metodos_pago';

    protected $fillable = ['nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /**
     * @return HasMany<Pago, $this>
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
}
