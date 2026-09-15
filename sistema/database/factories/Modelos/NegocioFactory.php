<?php

namespace Database\Factories\Modelos;

use App\Modelos\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Negocio>
 */
class NegocioFactory extends Factory
{
    protected $model = Negocio::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Taller '.fake()->unique()->lastName(),
            'dias_sin_reclamar' => 30,
        ];
    }
}
