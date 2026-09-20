<?php

namespace Tests\Feature\Programacion;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * HT-05 · Las tareas que corre el contenedor «programador». Los respaldos no se prueban aquí porque
 * no son tareas de Laravel: los lanza el cron del host (despliegue/cron/taller).
 */
final class TareasProgramadasTest extends TestCase
{
    public function test_los_trabajos_fallidos_se_limpian_los_domingos(): void
    {
        $tarea = $this->tareaQueCorre('queue:prune-failed');

        // Domingo a las 4 de la mañana, después de la copia a Google Drive de las 3
        $this->assertSame('0 4 * * 0', $tarea->expression);
        $this->assertStringContainsString('--hours=336', $tarea->command ?? '');
    }

    public function test_las_tareas_usan_la_hora_de_colombia(): void
    {
        // Un respaldo a las 2 de la mañana en Bogotá no es a las 2 en el VPS, que corre en UTC
        foreach (app(Schedule::class)->events() as $tarea) {
            $this->assertSame('America/Bogota', $tarea->timezone ?? config('app.timezone'));
        }
    }

    private function tareaQueCorre(string $comando): Event
    {
        foreach (app(Schedule::class)->events() as $tarea) {
            if (str_contains($tarea->command ?? '', $comando)) {
                return $tarea;
            }
        }

        $this->fail("Ninguna tarea programada corre {$comando}.");
    }
}
