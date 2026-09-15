<?php

namespace Database\Factories\Modelos;

use App\Modelos\MetodoPago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Un pago con un método de la lista del mismo negocio que su orden. No revisa el saldo (RN-28 es de RegistrarPago).
 *
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        return [
            'orden_id' => Orden::factory(),
            'metodo_pago_id' => fn (array $atributos) => MetodoPago::factory()->create([
                'negocio_id' => Orden::withoutGlobalScopes()->findOrFail($atributos['orden_id'])->negocio_id,
            ])->id,
            'valor' => 10000,
            'pagado_en' => '2026-09-14 10:30:00',
        ];
    }

    /**
     * RN-31: un pago anulado guarda la fecha y el motivo, y no se borra.
     */
    public function anulado(string $motivo = 'Se registró dos veces'): static
    {
        return $this->state(['anulado_en' => '2026-09-14 11:00:00', 'motivo_anulacion' => $motivo]);
    }
}
