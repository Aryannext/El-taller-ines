# Sistema

Aplicación Laravel de Puntada. Antes de cambiar el código, lee:

- [Arquitectura](../docs/03-diseno/arquitectura/README.md): en qué clase vive cada regla.
- [Especificación técnica](../docs/04-especificacion-tecnica/README.md): rutas, validaciones, mensajes, seguridad y despliegue.
- [Plan de pruebas](../docs/05-pruebas/plan-de-pruebas.md): qué prueba escribir antes de cada historia.

## Comandos

| Qué | Comando |
| --- | --- |
| Pruebas | `php artisan test` |
| Estilo | `vendor/bin/pint` |
| Servidor local | `php artisan serve` |
| Comparar las migraciones con `esquema.sql` | `python ../scripts/comparar_migraciones.py` |

Las pruebas usan la base `taller_pruebas` de MySQL 8.4, nunca SQLite.
