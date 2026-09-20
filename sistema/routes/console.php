<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Las tareas de la aplicación. Las corre el contenedor «programador» con «php artisan schedule:work»,
 * en la hora de Colombia que fija config/app.php.
 *
 * Los respaldos no están aquí: los corre el cron del host (despliegue/cron/taller), porque mysqldump
 * vive dentro del contenedor db y alcanzarlo desde otro contenedor exigiría montarle el socket de
 * Docker. Ver docs/04-especificacion-tecnica/07-despliegue-y-operacion.md.
 */

// Los trabajos fallidos de la cola de avisos se conservan 14 días, los mismos que los respaldos (RNF-15)
Schedule::command('queue:prune-failed --hours=336')->weeklyOn(0, '4:00');
