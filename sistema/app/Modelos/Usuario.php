<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * La usuaria no usa el filtro por negocio: el inicio de sesión la busca en todos los negocios.
 */
class Usuario extends Authenticatable
{
    use HasFactory;

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'usuarios';

    protected $authPasswordName = 'contrasena';

    protected $rememberTokenName = 'token_recordar';

    protected $fillable = ['nombre', 'usuario', 'correo', 'contrasena'];

    protected $hidden = ['contrasena', 'token_recordar'];

    protected function casts(): array
    {
        return ['contrasena' => 'hashed'];
    }

    /**
     * @return BelongsTo<Negocio, $this>
     */
    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
