<?php

namespace Database\Factories\Modelos;

use App\Modelos\Cliente;
use App\Modelos\Orden;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Una orden sin prendas: sirve para preparar datos de prueba. En el sistema, RegistrarOrden exige al menos una (RN-06).
 *
 * @extends Factory<Orden>
 */
class OrdenFactory extends Factory
{
    protected $model = Orden::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            // El negocio de la orden es siempre el de su cliente (ADR-004)
            'negocio_id' => fn (array $atributos) => Cliente::withoutGlobalScopes()->findOrFail($atributos['cliente_id'])->negocio_id,
            'numero' => fake()->unique()->numberBetween(1, 9000),
            'fecha_entrega_acordada' => '2026-09-20',
            'recibida_en' => '2026-09-14 10:00:00',
        ];
    }
}
