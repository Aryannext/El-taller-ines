<?php

namespace Database\Factories\Modelos;

use App\Modelos\Foto;
use App\Modelos\Prenda;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * El registro de una foto. El archivo no se crea: las pruebas que lo necesitan lo ponen en Storage::fake('privado').
 *
 * @extends Factory<Foto>
 */
class FotoFactory extends Factory
{
    protected $model = Foto::class;

    public function definition(): array
    {
        return [
            'prenda_id' => Prenda::factory(),
            'posicion' => 1,
            'ruta' => 'fotos/prueba/'.Str::uuid().'.jpg',
            'ancho_px' => 1200,
            'alto_px' => 1600,
            'bytes' => 250000,
        ];
    }
}
