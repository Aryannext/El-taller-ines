# PM-02 · Restauración de respaldos

**Verifica:** RNF-15 · **Cuándo:** Sprint 4, con HT-05 · **Entorno:** VPS y la máquina de desarrollo

## Objetivo

Comprobar que, si el VPS se perdiera por completo, la información del taller se recupera en una hora o menos desde la copia semanal de Google Drive.

Se prueba el peor caso a propósito. Restaurar el respaldo diario dentro del mismo VPS es más fácil, pero no sirve si el servidor es lo que falló.

## Preparación

1. HT-05 está terminado: el respaldo diario y la copia semanal en Google Drive ya corrieron al menos una vez.
2. El VPS tiene los datos de los mockups, con fotos.
3. La máquina de desarrollo tiene el sistema instalado y una base vacía llamada `taller_restaurada`. La restauración no toca la base de desarrollo ni la del VPS.

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
7. Restaurar la base de datos en `taller_restaurada`.
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

**Fecha:** · **Copia usada (fecha):** · **Hora de inicio:** · **Hora de fin:** · **Duración:**

| Dato | En el VPS | Restaurado | ¿Coincide? |
| --- | --- | --- | --- |
| Clientes | | | |
| Órdenes | | | |
| Prendas | | | |
| Pagos | | | |
| Suma de pagos no anulados | | | |
| Avisos | | | |
| Fotos: archivos | | | |
| Fotos: tamaño total | | | |
| Orden de control y su saldo | | | |

**Respaldos diarios en el VPS (fechas):**

**Problemas encontrados y cómo se resolvieron:**

**Resultado:**
