<?php

namespace Database\Factories\Modelos;

use App\Modelos\MetodoPago;
use App\Modelos\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MetodoPago>
 */
class MetodoPagoFactory extends Factory
{
    protected $model = MetodoPago::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'nombre' => ucfirst(fake()->unique()->word()),
            'activo' => true,
        ];
    }
}
