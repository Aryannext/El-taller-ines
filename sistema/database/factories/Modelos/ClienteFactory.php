<?php

namespace Database\Factories\Modelos;

use App\Modelos\Cliente;
use App\Modelos\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'nombre' => fake()->name(),
            // Celular colombiano: 10 dígitos que empiezan por 3 (RN-03)
            'celular' => '3'.fake()->numerify('#########'),
        ];
    }
}
