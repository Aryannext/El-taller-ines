<?php

namespace Database\Factories\Modelos;

use App\Modelos\Negocio;
use App\Modelos\TipoPrenda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoPrenda>
 */
class TipoPrendaFactory extends Factory
{
    protected $model = TipoPrenda::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'nombre' => ucfirst(fake()->unique()->word()),
            'activo' => true,
        ];
    }
}
