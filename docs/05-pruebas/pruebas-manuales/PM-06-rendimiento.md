# PM-06 · Rendimiento con el volumen de 3 años

**Verifica:** RNF-01 · **Cuándo:** Sprint 4, con HT-06 · **Entorno:** VPS

## Objetivo

Comprobar que las pantallas de uso diario responden en 500 ms o menos en el percentil 95 con el volumen de un taller después de 3 años, y que la página se puede usar en 3 segundos o menos en una red 4G.

El percentil 95 significa que 95 de cada 100 solicitudes tardan eso o menos. Se usa en vez del promedio porque el promedio esconde las respuestas lentas.

## Preparación

1. HT-06 está terminado: el generador crea 500 clientes, 750 órdenes, 2.200 prendas y 1.500 pagos.
2. Cargar el volumen en el VPS. El taller aún no usa el sistema, así que no hay datos reales que proteger. Se borra al terminar.
3. Elegir para la medición una orden de 10 prendas y un término de búsqueda que devuelva varios clientes.
4. Tener a mano el usuario y la contraseña: el script de medición inicia la sesión y pide la contraseña sin mostrarla.

## Pasos

### Tiempo de respuesta del servidor

1. Correr el script de medición, que por cada pantalla hace 5 solicitudes de calentamiento que no se cuentan:

   ```sh
   python scripts/medir_rendimiento.py https://proyectosena.online/taller --usuario taller --orden <la de 10 prendas>
   ```

2. Luego hace 100 solicitudes seguidas y reporta el percentil 95 y el máximo de cada pantalla, que se copian a la tabla del registro.
3. Anotar la carga del VPS durante la medición.

### Carga en 4G simulada

4. Abrir cada pantalla en Chrome con Lighthouse, perfil móvil. Ese perfil simula una red 4G lenta y un celular de gama media.
5. Anotar el Largest Contentful Paint (LCP), que es el momento en que aparece el contenido principal. Se toma como la medida de «página utilizable».

### Limpieza

6. Borrar el volumen de prueba y volver a cargar los datos de los mockups.

## Criterio de aprobación

- El percentil 95 del servidor es de 500 ms o menos en las cinco pantallas.
- El LCP es de 3 s o menos en las cinco pantallas.

## Registro

**Fecha:** 21 de septiembre de 2026 · **Commit:** `492b6d5` · **Plan del VPS:** 2 CPU y 8 GB de RAM, compartido con otros proyectos · **Volumen cargado:** 753 órdenes, 501 clientes, 2.205 prendas y 1.501 pagos

| Pantalla | Percentil 95 | Máximo | LCP en 4G simulada | ¿Cumple? |
| --- | --- | --- | --- | --- |
| Panel del día (PT-02) | 53 ms | 79 ms | pendiente | Sí |
| Detalle de una orden de 10 prendas (PT-09) | 43 ms | 48 ms | pendiente | Sí |
| Búsqueda de clientes (PT-03) | 28 ms | 37 ms | pendiente | Sí |
| Órdenes atrasadas (PT-20) | 52 ms | 86 ms | pendiente | Sí |
| Órdenes sin reclamar (PT-21) | 52 ms | 55 ms | pendiente | Sí |

100 solicitudes por pantalla tras 5 de calentamiento, con `scripts/medir_rendimiento.py`. La carga del VPS durante la medición fue de 0,46 en el primer minuto; el servidor atiende también el portafolio y los proyectos de otra usuaria, así que esa cifra no es solo del taller.

**Resultado:** **Aprobado en el tiempo de respuesta del servidor.** Las cinco pantallas están entre 8 y 18 veces por debajo del máximo de 500 ms que fija RNF-01, y ninguna de las 500 solicitudes medidas pasó de 86 ms.

El dato que más importa es el detalle de una orden de 10 prendas: 43 ms, el segundo más rápido de las cinco. Es el escenario de F-02, donde la versión 1 repetía una consulta por cada prenda y abrir una orden grande se sentía lento. `ConsultasPorPaginaTest` ya comprobaba que esa pantalla hace las mismas 7 consultas con 1 prenda y con 10; esta medición lo confirma con volumen real y sobre el servidor.

**Falta el LCP en 4G simulada.** Se mide con Lighthouse en perfil móvil, desde el navegador, sobre cada una de las cinco pantallas. Es la mitad de la prueba que no se puede automatizar desde el servidor, y queda pendiente de una corrida manual.

**Si no cumple, consulta lenta encontrada y corrección:** no aplica; no hubo ninguna pantalla lenta.
