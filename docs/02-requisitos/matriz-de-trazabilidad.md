# Matriz de trazabilidad

> **Archivo generado.** Se crea con `python scripts/generar_matriz.py` a partir de los documentos de análisis. No se edita a mano: se corrige el documento de origen y se vuelve a generar. Hay una [versión con buscador](matriz-de-trazabilidad.html).

## Cómo se lee

La cadena completa va del problema a la prueba:

**Causa** (árbol de problemas) → **Medio** (árbol de objetivos) → **Objetivo específico** → **Requisito funcional** → **Regla de negocio** → **Historia de usuario** → **Criterios de aceptación** → **Prueba**

La columna **Prueba** se completa en el Sprint 3, cuando cada criterio de aceptación se convierta en una prueba automática.

## Inventario

| Causas | Medios | Objetivos | Requisitos funcionales | Reglas | Historias | Criterios | Requisitos no funcionales |
| --- | --- | --- | --- | --- | --- | --- | --- |
| 11 | 11 | 6 | 41 | 44 | 36 | 125 | 35 |

## Verificación

| Comprobación | Resultado | Pendientes |
| --- | --- | --- |
| Todos los códigos citados existen | Cumple | — |
| Causas con al menos un medio | Cumple | — |
| Efectos con al menos un fin | Cumple | — |
| Medios incluidos en un objetivo específico | Cumple | — |
| Objetivos específicos con al menos un requisito funcional | Cumple | — |
| Requisitos funcionales con al menos una historia | Cumple | — |
| Reglas de negocio citadas por un requisito funcional | Cumple | — |
| Reglas de negocio citadas por una historia | Cumple | — |
| Historias con criterios de aceptación | Cumple | — |
| Historias que nombran la causa o el efecto del que nacen | Cumple | — |
| Requisitos no funcionales exigidos en alguna historia | Informativo | RNF-01, RNF-02, RNF-05, RNF-06, RNF-07, RNF-08, RNF-09, RNF-11, RNF-12, RNF-15, RNF-16, RNF-18, RNF-23, RNF-24, RNF-26, RNF-27, RNF-28, RNF-29, RNF-30, RNF-31, RNF-32, RNF-33, RNF-34, RNF-35 |

## 1. Del problema a los objetivos

Cada causa del árbol de problemas, el medio que la resuelve, el objetivo específico que lo incluye y cuántos requisitos e historias lo desarrollan.

| Causa | Descripción | Medios | Objetivos | Requisitos | Historias |
| --- | --- | --- | --- | --- | --- |
| C-01 | La información del taller (clientes, prendas, arreglos, precios y abonos) vive solo en la memoria de la dueña | M-01 | OE-01 | 13 | 13 |
| C-01.1 | No se anota nada: no hay cuaderno, recibo ni ningún otro registro | M-01.1 | OE-01 | 13 | 13 |
| C-01.2 | No existe un lugar donde consultar qué prendas hay, de quién son, qué arreglo llevan y cuánto deben | M-01.2 | OE-01 | 13 | 13 |
| C-02 | No se lleva el estado de avance de cada prenda ni de cada orden | M-02 | OE-02 | 6 | 5 |
| C-02.1 | Una orden puede darse por lista con prendas sin terminar | M-02.1 | OE-02 | 6 | 5 |
| C-03 | La comunicación con el cliente sobre el estado de su prenda es informal y no avisa cuando está lista | M-03 | OE-03 | 7 | 6 |
| C-04 | Lo que debe cada cliente se lleva de memoria: no hay registro de pagos ni abonos | M-04 | OE-04 | 10 | 9 |
| C-04.1 | Un saldo llevado a mano, sumando y restando, se descuadra | M-04.1 | OE-04 | 10 | 9 |
| C-05 | No hay seguimiento de las órdenes vencidas ni de las prendas sin reclamar: no se sabe cuántas hay | M-05 | OE-05 | 5 | 5 |
| C-06 | Las prendas por arreglar y las ya arregladas se guardan juntas en un rincón, sin nada que las identifique | M-06 | OE-06 | 5 | 5 |
| C-06.1 | Un cliente puede traer varias prendas a la vez (entre 3 y 5) y la dueña olvida cuáles son suyas | M-06.1 | OE-06 | 5 | 5 |

## 2. Requisitos funcionales

De qué causa, medio y objetivo nace cada requisito, qué reglas debe cumplir y qué historias lo desarrollan. "Soporte" marca los requisitos de acceso, que protegen la información sin nacer de un objetivo específico.

| Requisito | Prioridad | Causas | Medios | Objetivos | Reglas | Historias | Criterios | Prueba |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| RF-01 | Must | — | — | Soporte | RN-01 | HU-01 | 5 | Sprint 3 |
| RF-02 | Must | — | — | Soporte | — | HU-01 | 5 | Sprint 3 |
| RF-03 | Must | — | — | Soporte | — | HU-02 | 4 | Sprint 3 |
| RF-04 | Must | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-02, RN-03, RN-04 | HU-03 | 4 | Sprint 3 |
| RF-05 | Must | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-01 | HU-04 | 4 | Sprint 3 |
| RF-06 | Must | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-27, RN-29 | HU-05 | 3 | Sprint 3 |
| RF-07 | Must | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-02, RN-03 | HU-06 | 3 | Sprint 3 |
| RF-08 | Must | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-05, RN-06, RN-07, RN-10, RN-11, RN-12 | HU-07 | 6 | Sprint 3 |
| RF-09 | Must | C-06, C-06.1 | M-06, M-06.1 | OE-06 | RN-08 | HU-08 | 3 | Sprint 3 |
| RF-10 | Should | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-02, RN-03 | HU-10 | 2 | Sprint 3 |
| RF-11 | Should | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-10, RN-11, RN-18, RN-24 | HU-11 | 3 | Sprint 3 |
| RF-12 | Must | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-11, RN-15, RN-16 | HU-12 | 3 | Sprint 3 |
| RF-13 | Should | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-06, RN-15, RN-16 | HU-13 | 4 | Sprint 3 |
| RF-14 | Must | C-01, C-01.1, C-01.2, C-02, C-02.1, C-03, C-04, C-04.1, C-05, C-06, C-06.1 | M-01, M-01.1, M-01.2, M-02, M-02.1, M-03, M-04, M-04.1, M-05, M-06, M-06.1 | OE-01, OE-02, OE-03, OE-04, OE-05, OE-06 | RN-18, RN-22, RN-23, RN-26, RN-27, RN-29, RN-41 | HU-14 | 3 | Sprint 3 |
| RF-15 | Must | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-08, RN-18 | HU-15 | 3 | Sprint 3 |
| RF-16 | Must | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-10, RN-43 | HU-07, HU-09 | 9 | Sprint 3 |
| RF-17 | Could | C-01, C-01.1, C-01.2 | M-01, M-01.1, M-01.2 | OE-01 | RN-01, RN-10, RN-43 | HU-16 | 2 | Sprint 3 |
| RF-18 | Must | C-06, C-06.1 | M-06, M-06.1 | OE-06 | RN-17 | HU-17 | 5 | Sprint 3 |
| RF-19 | Must | C-06, C-06.1 | M-06, M-06.1 | OE-06 | RN-17 | HU-18 | 3 | Sprint 3 |
| RF-20 | Should | C-06, C-06.1 | M-06, M-06.1 | OE-06 | RN-17 | HU-19 | 2 | Sprint 3 |
| RF-21 | Must | C-02, C-02.1 | M-02, M-02.1 | OE-02 | RN-12, RN-13, RN-14, RN-15, RN-24 | HU-20 | 6 | Sprint 3 |
| RF-22 | Must | C-02, C-02.1 | M-02, M-02.1 | OE-02 | RN-18, RN-19, RN-22 | HU-20 | 6 | Sprint 3 |
| RF-23 | Must | C-02, C-02.1 | M-02, M-02.1 | OE-02 | RN-20, RN-23 | HU-21 | 5 | Sprint 3 |
| RF-24 | Must | C-04, C-04.1 | M-04, M-04.1 | OE-04 | RN-21 | HU-21 | 5 | Sprint 3 |
| RF-25 | Must | C-02, C-02.1 | M-02, M-02.1 | OE-02 | RN-24 | HU-22 | 3 | Sprint 3 |
| RF-26 | Must | C-04, C-04.1 | M-04, M-04.1 | OE-04 | RN-25, RN-28, RN-30 | HU-23 | 7 | Sprint 3 |
| RF-27 | Should | C-04, C-04.1 | M-04, M-04.1 | OE-04 | RN-25, RN-28 | HU-24 | 2 | Sprint 3 |
| RF-28 | Must | C-04, C-04.1 | M-04, M-04.1 | OE-04 | RN-26, RN-27, RN-29 | HU-23 | 7 | Sprint 3 |
| RF-29 | Must | C-04, C-04.1 | M-04, M-04.1 | OE-04 | RN-31 | HU-25 | 3 | Sprint 3 |
| RF-30 | Should | C-04, C-04.1 | M-04, M-04.1 | OE-04 | RN-29, RN-32 | HU-26 | 2 | Sprint 3 |
| RF-31 | Should | C-04, C-04.1 | M-04, M-04.1 | OE-04 | RN-09, RN-33 | HU-27 | 3 | Sprint 3 |
| RF-32 | Must | C-03 | M-03 | OE-03 | RN-37, RN-38 | HU-28 | 5 | Sprint 3 |
| RF-33 | Must | C-03 | M-03 | OE-03 | RN-40, RN-42 | HU-28 | 5 | Sprint 3 |
| RF-34 | Must | C-03 | M-03 | OE-03 | RN-40, RN-41, RN-42 | HU-29 | 4 | Sprint 3 |
| RF-35 | Must | C-03 | M-03 | OE-03 | RN-39 | HU-30 | 3 | Sprint 3 |
| RF-36 | Must | C-03 | M-03 | OE-03 | RN-41 | HU-31 | 1 | Sprint 3 |
| RF-37 | Must | C-03, C-04, C-04.1, C-05 | M-03, M-04, M-04.1, M-05 | OE-03, OE-04, OE-05 | RN-32, RN-34, RN-35, RN-40 | HU-32 | 3 | Sprint 3 |
| RF-38 | Must | C-05 | M-05 | OE-05 | RN-09, RN-34 | HU-33 | 3 | Sprint 3 |
| RF-39 | Must | C-05 | M-05 | OE-05 | RN-35, RN-36 | HU-34 | 3 | Sprint 3 |
| RF-40 | Could | C-05 | M-05 | OE-05 | RN-35 | HU-35 | 2 | Sprint 3 |
| RF-41 | Should | C-02, C-02.1, C-04, C-04.1 | M-02, M-02.1, M-04, M-04.1 | OE-02, OE-04 | RN-12, RN-16, RN-26, RN-44 | HU-36 | 5 | Sprint 3 |

## 3. Historias de usuario

De qué causa o efecto nace cada historia y qué requisitos, reglas y requisitos no funcionales hace cumplir.

| Historia | Título | Épica | Actor | Nace de | Requisitos | Reglas | Calidad | Prioridad | Puntos | Criterios | Prueba |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| HU-01 | Iniciar y cerrar sesión | EP-01 | Dueña del taller | C-01 | RF-01, RF-02 | RN-01 | RNF-20, RNF-21 | Must | 3 | 5 | Sprint 3 |
| HU-02 | Cambiar mi contraseña | EP-01 | Dueña del taller | C-01 | RF-03 | — | RNF-19 | Must | 2 | 4 | Sprint 3 |
| HU-03 | Registrar un cliente | EP-02 | Dueña del taller | C-01.1 | RF-04 | RN-02, RN-03, RN-04 | — | Must | 2 | 4 | Sprint 3 |
| HU-04 | Buscar un cliente | EP-02 | Dueña del taller | C-01.2 | RF-05 | RN-01 | — | Must | 2 | 4 | Sprint 3 |
| HU-05 | Consultar la ficha de un cliente | EP-02 | Dueña del taller | C-01.2, C-04 | RF-06 | RN-27, RN-29 | — | Must | 3 | 3 | Sprint 3 |
| HU-06 | Corregir los datos de un cliente | EP-02 | Dueña del taller | C-03 | RF-07 | RN-02, RN-03 | — | Must | 1 | 3 | Sprint 3 |
| HU-07 | Registrar una orden con sus prendas | EP-03 | Dueña del taller | C-01, C-01.1, C-06.1 | RF-08, RF-16 | RN-05, RN-06, RN-07, RN-10, RN-11, RN-12, RN-18, RN-26 | RNF-13 | Must | 5 | 6 | Sprint 3 |
| HU-08 | Obtener el número de la orden para marcar la bolsa | EP-03 | Dueña del taller | C-06 | RF-09 | RN-08 | — | Must | 1 | 3 | Sprint 3 |
| HU-09 | Escribir un tipo de prenda que no está en la lista | EP-03 | Dueña del taller | C-01.1 | RF-16 | RN-10, RN-43 | — | Must | 2 | 3 | Sprint 3 |
| HU-10 | Registrar un cliente nuevo mientras registro su orden | EP-03 | Dueña del taller | C-01 | RF-10 | RN-02, RN-03 | — | Should | 3 | 2 | Sprint 3 |
| HU-11 | Agregar una prenda a una orden que ya existe | EP-03 | Dueña del taller | C-01.1 | RF-11 | RN-10, RN-11, RN-18, RN-22, RN-24 | — | Should | 2 | 3 | Sprint 3 |
| HU-12 | Corregir la descripción o el precio de una prenda | EP-03 | Dueña del taller | C-01.1, C-04.1 | RF-12 | RN-11, RN-15, RN-16, RN-26, RN-27 | — | Must | 2 | 3 | Sprint 3 |
| HU-13 | Eliminar una prenda registrada por error | EP-03 | Dueña del taller | C-04.1 | RF-13 | RN-06, RN-15, RN-16 | RNF-10 | Should | 2 | 4 | Sprint 3 |
| HU-14 | Consultar el detalle de una orden | EP-03 | Dueña del taller | C-01.2, C-02 | RF-14 | RN-01, RN-18, RN-22, RN-23, RN-26, RN-27, RN-29, RN-41 | RNF-22 | Must | 3 | 3 | Sprint 3 |
| HU-15 | Listar y buscar órdenes | EP-03 | Dueña del taller | C-02, C-06 | RF-15 | RN-08, RN-18 | — | Must | 2 | 3 | Sprint 3 |
| HU-16 | Renombrar o desactivar tipos de prenda | EP-03 | Dueña del taller | C-01.1 | RF-17 | RN-01, RN-10, RN-43 | — | Could | 2 | 2 | Sprint 3 |
| HU-17 | Tomar fotos de las prendas | EP-04 | Dueña del taller | C-06, C-06.1 | RF-18 | RN-17 | RNF-03 | Must | 3 | 5 | Sprint 3 |
| HU-18 | Ver las fotos de una orden para reconocer las prendas | EP-04 | Dueña del taller | E-06 | RF-19 | RN-17 | RNF-25 | Must | 2 | 3 | Sprint 3 |
| HU-19 | Eliminar una foto | EP-04 | Dueña del taller | C-06 | RF-20 | RN-17 | RNF-10 | Should | 1 | 2 | Sprint 3 |
| HU-20 | Actualizar el estado de una prenda | EP-05 | Dueña del taller | C-02, C-02.1 | RF-21, RF-22 | RN-12, RN-13, RN-14, RN-15, RN-18, RN-19, RN-22, RN-24 | — | Must | 5 | 6 | Sprint 3 |
| HU-21 | Entregar la orden al cliente | EP-05 | Dueña del taller | E-02 | RF-23, RF-24 | RN-20, RN-21, RN-23, RN-29 | — | Must | 3 | 5 | Sprint 3 |
| HU-22 | Cancelar una orden | EP-05 | Dueña del taller | C-02, C-04 | RF-25 | RN-08, RN-24, RN-32 | RNF-10 | Must | 2 | 3 | Sprint 3 |
| HU-23 | Registrar un pago o abono | EP-06 | Dueña del taller | C-04, C-04.1 | RF-26, RF-28 | RN-25, RN-26, RN-27, RN-28, RN-29, RN-30 | RNF-14 | Must | 3 | 7 | Sprint 3 |
| HU-24 | Registrar un abono al recibir la orden | EP-06 | Dueña del taller | C-04 | RF-27 | RN-25, RN-28 | RNF-13 | Should | 2 | 2 | Sprint 3 |
| HU-25 | Anular un pago mal registrado | EP-06 | Dueña del taller | C-04.1, E-03 | RF-29 | RN-27, RN-31 | RNF-10 | Must | 2 | 3 | Sprint 3 |
| HU-26 | Ver quién me debe | EP-06 | Dueña del taller | C-04, E-03 | RF-30 | RN-29, RN-32 | — | Should | 2 | 2 | Sprint 3 |
| HU-27 | Ver cuánto dinero he recibido | EP-06 | Dueña del taller | E-03, E-03.1 | RF-31 | RN-09, RN-33 | — | Should | 2 | 3 | Sprint 3 |
| HU-28 | Recibir un aviso cuando mi ropa está lista | EP-07 | Cliente del taller | C-03, E-01.1 | RF-32, RF-33 | RN-37, RN-38, RN-40, RN-41, RN-42 | RNF-04, RNF-17 | Must | 5 | 5 | Sprint 3 |
| HU-29 | Enviar con un toque los avisos pendientes | EP-07 | Dueña del taller | C-03 | RF-34 | RN-40, RN-41, RN-42 | — | Must | 3 | 4 | Sprint 3 |
| HU-30 | No avisar una orden que ya no está lista | EP-07 | Dueña del taller | C-02.1, C-03 | RF-35 | RN-38, RN-39 | — | Must | 2 | 3 | Sprint 3 |
| HU-31 | Consultar los avisos de una orden | EP-07 | Dueña del taller | C-03 | RF-36 | RN-41 | — | Must | 1 | 1 | Sprint 3 |
| HU-32 | Ver el panel del día | EP-08 | Dueña del taller | C-05, E-01 | RF-37 | RN-32, RN-34, RN-35, RN-40 | — | Must | 3 | 3 | Sprint 3 |
| HU-33 | Ver las órdenes atrasadas | EP-08 | Dueña del taller | C-05, E-01 | RF-38 | RN-09, RN-34 | — | Must | 2 | 3 | Sprint 3 |
| HU-34 | Ver las órdenes sin reclamar | EP-08 | Dueña del taller | E-04 | RF-39 | RN-35, RN-36 | — | Must | 2 | 3 | Sprint 3 |
| HU-35 | Cambiar el plazo para considerar una orden sin reclamar | EP-08 | Dueña del taller | C-05 | RF-40 | RN-35 | — | Could | 1 | 2 | Sprint 3 |
| HU-36 | Devolver una prenda sin arreglar | EP-05 | Dueña del taller | C-04.1, E-01 | RF-41 | RN-12, RN-16, RN-26, RN-44 | RNF-10 | Should | 2 | 5 | Sprint 3 |

## 4. Reglas de negocio

Origen de cada regla y qué requisitos e historias la hacen cumplir.

| Regla | Nombre | Tipo | Origen | Requisitos | Historias | Prueba |
| --- | --- | --- | --- | --- | --- | --- |
| RN-01 | La información pertenece a un negocio | Restricción | ADR-002 | RF-01, RF-05, RF-17 | HU-01, HU-04, HU-14, HU-16 | Sprint 3 |
| RN-02 | Datos mínimos de un cliente | Restricción | F-01, M-01 | RF-04, RF-07, RF-10 | HU-03, HU-06, HU-10 | Sprint 3 |
| RN-03 | El teléfono debe poder recibir WhatsApp | Restricción | ADR-003, F-05, M-03 | RF-04, RF-07, RF-10 | HU-03, HU-06, HU-10 | Sprint 3 |
| RN-04 | El teléfono no es único | Estructural | F-01 | RF-04 | HU-03 | Sprint 3 |
| RN-05 | Una orden, un cliente, una visita | Estructural | F-01, F-05, M-01, M-06 | RF-08 | HU-07 | Sprint 3 |
| RN-06 | Una orden tiene al menos una prenda | Restricción | F-02, M-01.1 | RF-08, RF-13 | HU-07, HU-13 | Sprint 3 |
| RN-07 | La entrega no puede ser antes de la recepción | Restricción | F-01, M-01.1 | RF-08 | HU-07 | Sprint 3 |
| RN-08 | Número de orden | Derivación | F-05, M-06 | RF-09, RF-15 | HU-08, HU-15, HU-22 | Sprint 3 |
| RN-09 | Las fechas se interpretan en hora de Colombia | Restricción | F-02, M-05 | RF-31, RF-38 | HU-27, HU-33 | Sprint 3 |
| RN-10 | Datos obligatorios de una prenda | Restricción | F-01, F-05, M-01.1 | RF-08, RF-11, RF-16, RF-17 | HU-07, HU-09, HU-11, HU-16 | Sprint 3 |
| RN-11 | El precio es un valor entero en pesos | Restricción | F-01, F-02, M-04.1 | RF-08, RF-11, RF-12 | HU-07, HU-11, HU-12 | Sprint 3 |
| RN-12 | Estados de una prenda | Estructural | F-01, M-02 | RF-08, RF-21, RF-41 | HU-07, HU-20, HU-36 | Sprint 3 |
| RN-13 | Solo se entrega lo terminado | Restricción | F-02, M-02.1 | RF-21 | HU-20 | Sprint 3 |
| RN-14 | Un retoque devuelve la prenda a En proceso | Restricción | F-05, M-02 | RF-21 | HU-20 | Sprint 3 |
| RN-15 | Una prenda entregada no se modifica | Restricción | F-02, M-04 | RF-12, RF-13, RF-21 | HU-12, HU-13, HU-20 | Sprint 3 |
| RN-16 | Lo pagado no puede quedar por encima del valor | Restricción | F-02, M-04.1 | RF-12, RF-13, RF-41 | HU-12, HU-13, HU-36 | Sprint 3 |
| RN-17 | Fotos de una prenda | Restricción | F-01, F-05, M-06.1 | RF-18, RF-19, RF-20 | HU-17, HU-18, HU-19 | Sprint 3 |
| RN-18 | El estado de la orden se calcula de sus prendas | Derivación | C-02.1, F-01, F-02, M-02, M-02.1 | RF-11, RF-14, RF-15, RF-22 | HU-07, HU-11, HU-14, HU-15, HU-20 | Sprint 3 |
| RN-19 | El estado de avance no se cambia a mano | Restricción | F-02, M-02.1 | RF-22 | HU-20 | Sprint 3 |
| RN-20 | Entregar la orden | Desencadenador | F-01, M-02 | RF-23 | HU-21 | Sprint 3 |
| RN-21 | Entregar con saldo pendiente | Restricción | F-02, F-05, FN-02, M-04 | RF-24 | HU-21 | Sprint 3 |
| RN-22 | Fecha en que la orden quedó lista | Desencadenador | F-02, M-05 | RF-14, RF-22 | HU-11, HU-14, HU-20 | Sprint 3 |
| RN-23 | Fecha de entrega real | Desencadenador | F-01, FN-01 | RF-14, RF-23 | HU-14, HU-21 | Sprint 3 |
| RN-24 | Cancelar una orden | Restricción | F-01, M-02 | RF-11, RF-21, RF-25 | HU-11, HU-20, HU-22 | Sprint 3 |
| RN-25 | Datos de un pago | Estructural | F-01, F-05, M-04 | RF-26, RF-27 | HU-23, HU-24 | Sprint 3 |
| RN-26 | Valor de la orden | Derivación | F-02, M-04.1 | RF-14, RF-28, RF-41 | HU-07, HU-12, HU-14, HU-23, HU-36 | Sprint 3 |
| RN-27 | Saldo pendiente | Derivación | C-04.1, F-02, M-04.1 | RF-06, RF-14, RF-28 | HU-05, HU-12, HU-14, HU-23, HU-25 | Sprint 3 |
| RN-28 | El abono no puede superar el saldo | Restricción | F-01, F-02, M-04 | RF-26, RF-27 | HU-23, HU-24 | Sprint 3 |
| RN-29 | Estado de pago | Derivación | F-01, F-02, FN-03, M-04 | RF-06, RF-14, RF-28, RF-30 | HU-05, HU-14, HU-21, HU-23, HU-26 | Sprint 3 |
| RN-30 | Pagos después de entregar | Restricción | F-01, M-04 | RF-26 | HU-23 | Sprint 3 |
| RN-31 | Un pago no se borra: se anula | Restricción | F-02, FN-03, M-04 | RF-29 | HU-25 | Sprint 3 |
| RN-32 | Total por cobrar del negocio | Derivación | FN-03, M-04, OE-04 | RF-30, RF-37 | HU-22, HU-26, HU-32 | Sprint 3 |
| RN-33 | Dinero recibido en un período | Derivación | FN-03, M-04 | RF-31 | HU-27 | Sprint 3 |
| RN-34 | Orden atrasada | Derivación | C-05, E-01, M-05 | RF-37, RF-38 | HU-32, HU-33 | Sprint 3 |
| RN-35 | Orden sin reclamar | Derivación | C-05, E-04, F-01, F-05, M-05 | RF-37, RF-39, RF-40 | HU-32, HU-34, HU-35 | Sprint 3 |
| RN-36 | Días de espera | Derivación | FN-04, M-05 | RF-39 | HU-34 | Sprint 3 |
| RN-37 | Al quedar lista la orden se genera su aviso | Desencadenador | ADR-003, C-03, F-01, M-03 | RF-32 | HU-28 | Sprint 3 |
| RN-38 | Un solo aviso por cada vez que la orden queda lista | Restricción | F-01, M-03 | RF-32 | HU-28, HU-30 | Sprint 3 |
| RN-39 | No se avisa una orden que ya no está lista | Restricción | F-02, M-03 | RF-35 | HU-30 | Sprint 3 |
| RN-40 | Canal del aviso | Restricción | ADR-003, M-03 | RF-33, RF-34, RF-37 | HU-28, HU-29, HU-32 | Sprint 3 |
| RN-41 | Constancia de cada aviso | Estructural | F-01, M-03, OE-03 | RF-14, RF-34, RF-36 | HU-14, HU-28, HU-29, HU-31 | Sprint 3 |
| RN-42 | El aviso usa los datos del momento del envío | Restricción | F-01, F-02, M-03 | RF-33, RF-34 | HU-28, HU-29 | Sprint 3 |
| RN-43 | Tipo de prenda escrito por la usuaria | Desencadenador | F-05, M-01.1 | RF-16, RF-17 | HU-09, HU-16 | Sprint 3 |
| RN-44 | Devolver una prenda sin arreglar | Restricción | E-01, F-05, M-04.1 | RF-41 | HU-36 | Sprint 3 |

## 5. Requisitos no funcionales

Los que aparecen en historias se prueban con ellas; los demás se verifican sobre el sistema completo, como indica su columna de verificación.

| Requisito | Descripción | Característica | Historias | Verificación |
| --- | --- | --- | --- | --- |
| RNF-01 | Las pantallas de uso diario responden rápido con el volumen de 3 años | Eficiencia de desempeño | — | Datos de prueba con el volumen de referencia; medición del servidor y de las herramientas del navegador |
| RNF-02 | El número de consultas a la base de datos no crece con la cantidad de prendas | Eficiencia de desempeño | — | Prueba automática que cuenta las consultas con órdenes de 1 y de 10 prendas (F-02: la versión 1 hacía consultas repetidas por cada prenda) |
| RNF-03 | Las fotos se reducen antes de guardarse | Eficiencia de desempeño | HU-17 | Prueba automática que sube una foto de 5 MB y revisa lo guardado |
| RNF-04 | Registrar una orden o cambiar un estado no espera a WhatsApp | Eficiencia de desempeño | HU-28 | Prueba automática con un canal de aviso simulado que tarda 10 s (ADR-003) |
| RNF-05 | El sistema funciona en los navegadores del taller | Compatibilidad | — | Lista de chequeo manual en cada navegador antes de la entrega |
| RNF-06 | La integración con WhatsApp pasa por un solo adaptador por canal | Compatibilidad | — | Revisión de código, pruebas de cada adaptador y un envío real por Evolution API (ADR-003, ADR-007) |
| RNF-07 | El diseño es primero para el celular | Usabilidad | — | Revisión de cada pantalla a 360 px |
| RNF-08 | Los datos se muestran como se leen en Colombia | Usabilidad | — | Pruebas automáticas de formato |
| RNF-09 | Los errores dicen qué pasó y cómo corregirlo | Usabilidad | — | Revisión de todos los mensajes contra una lista de chequeo |
| RNF-10 | Las acciones que no se pueden deshacer piden confirmación | Usabilidad | HU-13, HU-19, HU-22, HU-25, HU-36 | Pruebas automáticas de cada acción |
| RNF-11 | El sistema es accesible | Usabilidad | — | Auditoría con Lighthouse en cada pantalla |
| RNF-12 | Registrar una orden es rápido para alguien que no conoce el sistema | Usabilidad | — | Prueba de usabilidad con 3 compañeros de formación, con su registro |
| RNF-13 | Las operaciones que tocan varios datos se hacen completas o no se hacen | Fiabilidad | HU-07, HU-24 | Pruebas automáticas que fuerzan un error a mitad de la operación y revisan la base |
| RNF-14 | Enviar dos veces el mismo formulario no duplica registros | Fiabilidad | HU-23 | Prueba automática que envía dos veces la misma solicitud (F-02) |
| RNF-15 | La información se respalda y se puede recuperar | Fiabilidad | — | Restauración probada al menos una vez antes de la entrega, con su registro; copia semanal visible en Google Drive |
| RNF-16 | El sistema está disponible en el horario del taller | Fiabilidad | — | Monitor externo que revisa el sistema cada 5 minutos desde el despliegue |
| RNF-17 | Un fallo de WhatsApp no deja al cliente sin aviso | Fiabilidad | HU-28 | Prueba automática con un canal que siempre falla |
| RNF-18 | Toda la comunicación va cifrada | Seguridad | — | Revisión de la configuración del servidor y solicitud de prueba por HTTP |
| RNF-19 | Las contraseñas no se pueden leer | Seguridad | HU-02 | Prueba automática del registro y cambio de contraseña |
| RNF-20 | Se limitan los intentos de adivinar una contraseña | Seguridad | HU-01 | Prueba automática con 6 intentos fallidos |
| RNF-21 | Una sesión abandonada se cierra | Seguridad | HU-01 | Prueba automática de expiración |
| RNF-22 | Ningún negocio ve ni modifica los datos de otro | Seguridad | HU-14 | Prueba automática con dos negocios que recorre todas las rutas (ADR-002, RN-01) |
| RNF-23 | El sistema resiste los ataques web más comunes | Seguridad | — | Revisión con lista de chequeo OWASP Top 10 y pruebas automáticas de CSRF y control de acceso |
| RNF-24 | Las credenciales no están en el código | Seguridad | — | Búsqueda automática de secretos en el repositorio |
| RNF-25 | Las fotos de los clientes no son públicas | Seguridad | HU-18 | Prueba automática que pide una foto sin sesión y desde otro negocio |
| RNF-26 | Los datos personales se tratan según la ley colombiana | Seguridad | — | Revisión de formularios y de la política publicada |
| RNF-27 | Las reglas de negocio están separadas de la interfaz y de la base de datos | Mantenibilidad | — | Pruebas automáticas de arquitectura y revisión de código |
| RNF-28 | Las reglas de negocio están probadas | Mantenibilidad | — | Reporte de cobertura y matriz regla → prueba |
| RNF-29 | El código sigue un estilo único y no tiene errores detectables sin ejecutarlo | Mantenibilidad | — | Ejecución de las herramientas en cada integración |
| RNF-30 | Cada cambio se valida automáticamente | Mantenibilidad | — | Historial de ejecuciones de GitHub Actions |
| RNF-31 | La estructura de la base de datos está versionada | Mantenibilidad | — | Revisión: la base se reconstruye completa solo con las migraciones |
| RNF-32 | La base de datos está normalizada | Mantenibilidad | — | Documento de normalización del modelo de datos |
| RNF-33 | El sistema se instala siguiendo el manual | Portabilidad | — | Instalación de prueba en una máquina distinta a la de desarrollo, con su registro |
| RNF-34 | La configuración cambia sin tocar el código | Portabilidad | — | Despliegue en el VPS usando el mismo código del repositorio |
| RNF-35 | El sistema se instala en el celular como una app | Portabilidad | — | Instalación del APK en un celular Android real, toma de una foto y apertura de WhatsApp desde el APK, e instalación desde el navegador en otro celular, con su registro |

## 6. Efectos y fines

Lo que el problema le cuesta al taller y lo que gana cuando se resuelve. Los fines se miden después de la implantación.

| Efecto | Descripción | Fin | Qué gana el taller |
| --- | --- | --- | --- |
| E-01 | Olvidos y retrasos en las entregas | FN-01 | Las entregas se cumplen a tiempo, sin olvidos |
| E-01.1 | Clientes insatisfechos y deterioro de la atención | FN-01.1 | Clientes satisfechos con la atención |
| E-02 | Prendas terminadas o entregadas sin haberse cobrado correctamente | FN-02 | Ninguna prenda se entrega sin cobrar o sin dejar su saldo registrado |
| E-02.1 | Pérdidas económicas para el negocio | FN-02.1 | Se reducen las pérdidas económicas del negocio |
| E-03 | Confusión en la información financiera: no se sabe con certeza cuánto se ha recibido y cuánto falta por cobrar | FN-03 | Se sabe con certeza cuánto se ha recibido y cuánto falta por cobrar |
| E-03.1 | Decisiones del negocio sin información confiable | FN-03.1 | Las decisiones del negocio se toman con información confiable |
| E-04 | Prendas que no se recogen durante dos meses o más, o nunca, sin que se sepa cuántas son | FN-04 | Se sabe cuántas prendas están sin reclamar y desde cuándo, para gestionarlas |
| E-06 | No se sabe con certeza qué prendas pertenecen a cada cliente | FN-06 | Se identifican sin dudas las prendas de cada cliente al trabajarlas y al entregarlas |

