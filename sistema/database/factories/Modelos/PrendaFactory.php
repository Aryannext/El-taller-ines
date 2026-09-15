<?php

namespace Database\Factories\Modelos;

use App\Modelos\Orden;
use App\Modelos\Prenda;
use App\Modelos\TipoPrenda;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Una prenda Pendiente con un tipo de la lista del mismo negocio que su orden.
 * Para una prenda Entregada o Devuelta hay que dar también su fecha (ck_prendas_entregada, ck_prendas_devuelta).
 *
 * @extends Factory<Prenda>
 */
class PrendaFactory extends Factory
{
    protected $model = Prenda::class;

    public function definition(): array
    {
        return [
            'orden_id' => Orden::factory(),
            'tipo_prenda_id' => fn (array $atributos) => TipoPrenda::factory()->create([
                'negocio_id' => Orden::withoutGlobalScopes()->findOrFail($atributos['orden_id'])->negocio_id,
            ])->id,
            'descripcion_arreglo' => 'Subir basta 3 cm',
            'precio' => 15000,
            'estado' => 'pendiente',
        ];
    }
}
