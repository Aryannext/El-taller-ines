# PM-06 · Rendimiento con el volumen de 3 años

**Verifica:** RNF-01 · **Cuándo:** Sprint 4, con HT-06 · **Entorno:** VPS

## Objetivo

Comprobar que las pantallas de uso diario responden en 500 ms o menos en el percentil 95 con el volumen de un taller después de 3 años, y que la página se puede usar en 3 segundos o menos en una red 4G.

El percentil 95 significa que 95 de cada 100 solicitudes tardan eso o menos. Se usa en vez del promedio porque el promedio esconde las respuestas lentas.

## Preparación

1. HT-06 está terminado: el generador crea 500 clientes, 750 órdenes, 2.200 prendas y 1.500 pagos.
2. Cargar el volumen en el VPS. El taller aún no usa el sistema, así que no hay datos reales que proteger. Se borra al terminar.
3. Elegir para la medición una orden de 10 prendas y un término de búsqueda que devuelva varios clientes.
4. Tener una sesión iniciada para el script de medición.

## Pasos

### Tiempo de respuesta del servidor

1. Para cada pantalla de la tabla, hacer 5 solicitudes de calentamiento que no se cuentan.
2. Hacer 100 solicitudes seguidas con el script de medición y anotar el percentil 95 y el máximo.
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

**Fecha:** · **Commit:** · **Plan del VPS (CPU y memoria):** · **Volumen cargado:**

| Pantalla | Percentil 95 | Máximo | LCP en 4G simulada | ¿Cumple? |
| --- | --- | --- | --- | --- |
| Panel del día (PT-02) | | | | |
| Detalle de una orden de 10 prendas (PT-09) | | | | |
| Búsqueda de clientes (PT-03) | | | | |
| Órdenes atrasadas (PT-20) | | | | |
| Órdenes sin reclamar (PT-21) | | | | |

**Resultado:** · **Si no cumple, consulta lenta encontrada y corrección:**
