<?php

namespace Tests\Feature\Http;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * RN-19 · El estado de avance no se cambia a mano: la orden cambia cuando cambian sus prendas, o al entregarla (RN-20) o cancelarla (RN-24).
 */
class PrendaControllerTest extends TestCase
{
    public function test_rn_19_el_estado_de_avance_no_se_cambia_a_mano(): void
    {
        // La única ruta que cambia un estado es la de una prenda; no existe «marcar orden como lista»
        $rutasConEstado = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta) => str_contains($ruta->uri(), 'estado'))
            ->map(fn ($ruta) => implode('|', array_diff($ruta->methods(), ['HEAD'])).' '.$ruta->uri())
            ->values()
            ->all();

        $this->assertSame(['POST ordenes/{orden}/prendas/{prenda}/estado'], $rutasConEstado);
        $this->assertSame('PrendaController@cambiarEstado', class_basename(Route::getRoutes()->getByName('prendas.cambiar-estado')?->getActionName() ?? ''));
    }
}
