<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    public const CREATED_AT = 'pagado_en';

    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'pagos';

    protected $fillable = ['metodo_pago_id', 'valor', 'anulado_en', 'motivo_anulacion', 'token_formulario'];

    protected function casts(): array
    {
        return [
            'valor' => 'integer',
            'pagado_en' => 'immutable_datetime',
            'anulado_en' => 'immutable_datetime',
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
     * @return BelongsTo<MetodoPago, $this>
     */
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class);
    }
}
