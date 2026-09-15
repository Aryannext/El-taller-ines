<?php

namespace Database\Factories\Modelos;

use App\Modelos\Aviso;
use App\Modelos\Orden;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Un aviso enviado por la API oficial (RN-41).
 *
 * @extends Factory<Aviso>
 */
class AvisoFactory extends Factory
{
    protected $model = Aviso::class;

    public function definition(): array
    {
        return [
            'orden_id' => Orden::factory(),
            'ciclo_lista_en' => '2026-09-15 16:00:00',
            'estado' => 'enviado',
            'canal' => 'api_oficial',
            'mensaje' => 'Hola, tu orden está lista para recoger.',
            'generado_en' => '2026-09-15 16:00:05',
            'resuelto_en' => '2026-09-15 16:00:30',
        ];
    }
}
