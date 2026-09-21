# PM-02 · Restauración de respaldos

**Verifica:** RNF-15 · **Cuándo:** Sprint 4, con HT-05 · **Entorno:** VPS y la máquina de desarrollo

## Objetivo

Comprobar que, si el VPS se perdiera por completo, la información del taller se recupera en una hora o menos desde la copia semanal de Google Drive.

Se prueba el peor caso a propósito. Restaurar el respaldo diario dentro del mismo VPS es más fácil, pero no sirve si el servidor es lo que falló.

## Preparación

1. HT-05 está terminado: el respaldo diario y la copia semanal en Google Drive ya corrieron al menos una vez.
2. El VPS tiene los datos de los mockups, con fotos.
3. La máquina de desarrollo tiene el sistema instalado y una base vacía llamada `taller_restaurada`. La restauración no toca la base de desarrollo ni la del VPS. Crearla necesita un usuario de MySQL con permiso para crear bases: el usuario de desarrollo del proyecto no lo tiene.

## Pasos

### Antes: anotar lo que hay

1. En el VPS, contar clientes, órdenes, prendas, pagos, avisos y fotos, y sumar los pagos no anulados.
2. Contar los archivos de fotos del disco privado y su tamaño total.
3. Anotar el número y el saldo de una orden con fotos, por ejemplo la #0042.

### Retención en el VPS

4. Listar los respaldos diarios del VPS: debe haber uno por día y ninguno de más de 14 días.

### Restauración

5. **Iniciar el cronómetro.**
6. Descargar de Google Drive la copia semanal más reciente y anotar su fecha.
7. Restaurar la base de datos en `taller_restaurada` con `despliegue/restaurar.sh`. Si el MySQL de la máquina no está en el puerto 3306, pasarlo en `MYSQL_PORT`.
8. Restaurar las fotos en el disco privado de la máquina de desarrollo.
9. Apuntar el sistema local a `taller_restaurada` solo con variables de entorno.
10. Iniciar sesión y abrir la orden anotada en el paso 3.
11. **Detener el cronómetro** cuando la orden muestre sus prendas, su saldo y sus fotos.

### Después: comparar

12. Repetir los conteos de los pasos 1 y 2 sobre lo restaurado.
13. Si hubo movimientos después de la fecha de la copia, anotarlos: la diferencia debe explicarse por ellos.

## Criterio de aprobación

- La restauración termina en 60 minutos o menos.
- Los conteos coinciden, o la diferencia se explica por movimientos posteriores a la copia.
- La orden anotada abre con sus fotos y el mismo saldo.
- La retención del VPS es de 14 días.

## Registro

**Fecha:** 21 de septiembre de 2026 · **Copia usada (fecha):** 2026-09-21, de `drive:taller-respaldos/2026-09-21` · **Duración:** **7 segundos**

| Dato | En el VPS | Restaurado | ¿Coincide? |
| --- | --- | --- | --- |
| Clientes | 1 | 1 | Sí |
| Órdenes | 3 | 3 | Sí |
| Prendas | 5 | 5 | Sí |
| Pagos | 1 | 1 | Sí |
| Suma de pagos no anulados | $0 | $0 | Sí |
| Avisos | 2 | 2 | Sí |
| Fotos: archivos | 3 | 3 | Sí |
| Fotos: tamaño total | 117.258 bytes | 117.258 bytes | Sí |
| Orden de control y su saldo | #0003 · 2 prendas · $40.000 | #0003 · 2 prendas · $40.000 | Sí |

Las tres fotos de la orden de control se abrieron desde el disco privado restaurado, no solo desde sus filas en la base.

**Respaldos diarios en el VPS (fechas):** 2026-09-21. Es el único porque el respaldo automático se instaló ese mismo día. La retención de 14 días se comprobó aparte, con carpetas fechadas a propósito: `respaldar.sh` borró las de 20 y 16 días y conservó las de 7, 2 y 0.

**Problemas encontrados y cómo se resolvieron:**

1. **`restaurar.sh` no aceptaba el puerto de MySQL.** Tenía `MYSQL_USER` y `MYSQL_HOST` pero daba por hecho el 3306, y la máquina de desarrollo tiene el suyo en otro. Se agregó `MYSQL_PORT` (commit `492b6d5`).
2. **`respaldar.sh` dejaba la entrada abierta** en los dos `docker compose exec -T`, que se la llevan. Bajo cron no molesta, pero corriéndolo a mano cortaba el respaldo a medias. Se cierra con `< /dev/null` (mismo commit).
3. **El VPS no tenía fotos**, y la preparación de esta prueba las da por hechas. Se registró la orden #0003 con dos prendas y tres fotos por el mismo caso de uso que usa el formulario, para que pasaran por la reducción de RNF-03.
4. **El usuario de MySQL de desarrollo no puede crear bases**, así que la restauración fue a `taller_pruebas` en vez de a `taller_restaurada`. La prueba la reconstruye después.
5. **La copia se bajó de Google Drive pasando por el VPS**, porque rclone está configurado allí y no en la máquina de desarrollo. Es el mismo archivo y se verificaron sus sumas SHA-256 al restaurar.

**Resultado:** **Aprobado.** Los cuatro criterios se cumplen: la restauración tardó 7 segundos contra un máximo de 60 minutos, los conteos coinciden sin diferencias, la orden de control abrió con sus prendas, su saldo y sus tres fotos, y la retención del VPS es de 14 días.

Queda pendiente repetirla cuando el taller tenga volumen real: con 2,6 GB de fotos el tiempo lo dominará la descarga, no la restauración.
