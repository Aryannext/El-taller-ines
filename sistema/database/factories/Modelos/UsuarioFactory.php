<?php

namespace Database\Factories\Modelos;

use App\Modelos\Negocio;
use App\Modelos\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'nombre' => fake()->name(),
            'usuario' => fake()->unique()->userName(),
            'contrasena' => 'clave-de-prueba',
        ];
    }
}
