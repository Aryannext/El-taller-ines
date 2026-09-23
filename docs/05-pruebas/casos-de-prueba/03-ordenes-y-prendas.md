# Casos de prueba · EP-03 · Órdenes y prendas

> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde las [historias de usuario](../../02-requisitos/historias-de-usuario.md), la [arquitectura](../../03-diseno/arquitectura/README.md) y el [plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.

Cada criterio de aceptación es un caso de prueba con su mismo código. La clase de prueba de cada historia es la del caso de uso que la implementa; cuando un criterio usa otra, aparece junto al método. Las rutas son relativas a `sistema/tests/`.

**Resumen:** 10 historias · 33 casos · 32 automáticos · 1 automáticos y manuales · 0 manuales.

## HU-07 · Registrar una orden con sus prendas

**Prioridad:** Must · **Reglas:** RN-05, RN-06, RN-07, RN-10, RN-11, RN-12, RN-18, RN-26 · **Calidad:** RNF-13 · **Clase de prueba:** `Feature/Ordenes/RegistrarOrdenTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-07.1** Orden completa | Dado que Marta está registrada y hoy es 14 de septiembre, cuando registro una orden con entrega el 20 de septiembre, un pantalón "subir basta 3 cm" de $15.000 y dos camisas "entallar" de $8.000 cada una | la orden queda En proceso con valor de $31.000 y sus tres prendas en Pendiente | Funcionalidad | Automática | `test_ca_07_1_orden_completa` |
| **CA-07.2** Sin prendas | Dado que estoy registrando una orden para Marta, cuando intento guardarla sin prendas | no se guarda y veo que debe tener al menos una prenda | Funcionalidad | Automática | `test_ca_07_2_sin_prendas` |
| **CA-07.3** Entrega antes de hoy | Dado que hoy es 14 de septiembre, cuando pongo como entrega el 13 de septiembre | no se guarda y veo que la entrega no puede ser antes de la fecha de recepción | Funcionalidad | Automática | `test_ca_07_3_entrega_antes_de_hoy` |
| **CA-07.4** Entrega el mismo día | Dado que hoy es 14 de septiembre, cuando pongo como entrega el 14 de septiembre | la orden se guarda | Funcionalidad | Automática | `test_ca_07_4_entrega_el_mismo_dia` |
| **CA-07.5** Una prenda inválida | Dado que registro tres prendas y una camisa tiene precio $0, cuando intento guardar | no se guarda ninguna parte de la orden y la camisa indica que el precio debe ser mayor que cero | Funcionalidad | Automática | `test_ca_07_5_una_prenda_invalida` |
| **CA-07.6** Lista de tipos | Dado que estoy agregando una prenda, cuando abro la lista de tipos | veo pantalón, camisa, blusa, vestido, falda, chaqueta y la opción «Otro» | Funcionalidad | Automática | `test_ca_07_6_lista_de_tipos` |

## HU-08 · Obtener el número de la orden para marcar la bolsa

**Prioridad:** Must · **Reglas:** RN-08 · **Calidad:** — · **Clase de prueba:** `Feature/Ordenes/RegistrarOrdenTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-08.1** Número destacado | Dado que la última orden del negocio es la #0041, cuando guardo una orden nueva | veo destacado "#0042" con la indicación de escribirlo en la bolsa | Funcionalidad | Automática | `test_ca_08_1_numero_destacado` |
| **CA-08.2** No se reutiliza | Dado que la #0041 fue cancelada, cuando guardo una orden nueva | su número es #0042, no #0041 | Funcionalidad | Automática | `test_ca_08_2_no_se_reutiliza` |
| **CA-08.3** Siempre visible | Dado que existe la orden #0042, cuando la abro otro día | el número aparece en la parte superior | Funcionalidad | Automática | `test_ca_08_3_siempre_visible` |

## HU-09 · Escribir un tipo de prenda que no está en la lista

**Prioridad:** Must · **Reglas:** RN-10, RN-43 · **Calidad:** — · **Clase de prueba:** `Feature/Ordenes/ResolverTipoDePrendaTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-09.1** Tipo nuevo | Dado que "Overol" no está en la lista, cuando elijo «Otro» y escribo "Overol" | la prenda queda como Overol y, en la siguiente prenda, Overol aparece en la lista | Funcionalidad | Automática | `test_ca_09_1_tipo_nuevo` |
| **CA-09.2** Tipo repetido | Dado que "Overol" ya está en la lista, cuando elijo «Otro» y escribo "overol" | se usa el tipo Overol existente y la lista no lo muestra dos veces | Funcionalidad | Automática | `test_ca_09_2_tipo_repetido` |
| **CA-09.3** «Otro» vacío | Dado que elegí «Otro», cuando intento guardar sin escribir el tipo | no se guarda y veo que debo escribir el tipo de prenda | Funcionalidad | Automática | `test_ca_09_3_otro_vacio` |

## HU-10 · Registrar un cliente nuevo mientras registro su orden

**Prioridad:** Should · **Reglas:** RN-02, RN-03 · **Calidad:** — · **Clase de prueba:** `Feature/Clientes/RegistrarClienteTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-10.1** Cliente nuevo | Dado que estoy llenando una orden con dos prendas y el cliente no está registrado, cuando registro a Luis Pardo con el celular 3001112233 desde la orden | vuelvo a la orden con Luis seleccionado y las dos prendas siguen escritas | Funcionalidad | Automática y manual | `test_ca_10_1_cliente_nuevo`<br>y [PM-05](../pruebas-manuales/PM-05-pantallas-y-navegadores.md) |
| **CA-10.2** Datos inválidos | Dado que estoy registrando al cliente nuevo desde la orden, cuando escribo un celular inválido | el cliente no se registra y sigo en la orden con lo que ya había escrito | Funcionalidad | Automática | `test_ca_10_2_datos_invalidos` |

## HU-11 · Agregar una prenda a una orden que ya existe

**Prioridad:** Should · **Reglas:** RN-10, RN-11, RN-18, RN-22, RN-24 · **Calidad:** — · **Clase de prueba:** `Feature/Ordenes/AgregarPrendaTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-11.1** Orden lista | Dado que la #0042 está Lista para entregar, cuando le agrego una falda "subir ruedo" de $10.000 | la falda queda Pendiente, la orden vuelve a En proceso, su valor sube $10.000 y se borra la fecha en que había quedado lista | Funcionalidad | Automática | `test_ca_11_1_orden_lista` |
| **CA-11.2** Orden entregada | Dado que la #0040 está Entregada, cuando intento agregarle una prenda | no se permite | Funcionalidad | Automática | `test_ca_11_2_orden_entregada` |
| **CA-11.3** Orden cancelada | Dado que la #0041 está Cancelada, cuando intento agregarle una prenda | no se permite | Funcionalidad | Automática | `test_ca_11_3_orden_cancelada` |

## HU-12 · Corregir la descripción o el precio de una prenda

**Prioridad:** Must · **Reglas:** RN-11, RN-15, RN-16, RN-26, RN-27 · **Calidad:** — · **Clase de prueba:** `Feature/Ordenes/CorregirPrendaTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-12.1** Subir el precio | Dado que la #0042 vale $31.000, no tiene pagos y su pantalón cuesta $15.000, cuando cambio el precio del pantalón a $18.000 | el valor de la orden y su saldo quedan en $34.000 | Funcionalidad | Automática | `test_ca_12_1_subir_el_precio` |
| **CA-12.2** Por debajo de lo pagado | Dado que la #0042 vale $31.000 y tiene $25.000 pagados, cuando intento bajar el pantalón a $5.000 | no se guarda y veo que lo pagado superaría el valor de la orden y que primero debo anular el pago que sobra | Funcionalidad | Automática | `test_ca_12_2_por_debajo_de_lo_pagado` |
| **CA-12.3** Prenda entregada | Dado que una camisa de la #0040 está Entregada, cuando intento editarla | no se permite | Funcionalidad | Automática | `test_ca_12_3_prenda_entregada` |

## HU-13 · Eliminar una prenda registrada por error

**Prioridad:** Should · **Reglas:** RN-06, RN-15, RN-16 · **Calidad:** RNF-10 · **Clase de prueba:** `Feature/Ordenes/EliminarPrendaTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-13.1** Eliminación correcta | Dado que la #0042 tiene un pantalón de $15.000 y dos camisas de $8.000, sin pagos, cuando elimino una camisa y lo confirmo | la orden queda con dos prendas y un valor de $23.000 | Funcionalidad | Automática | `test_ca_13_1_eliminacion_correcta` |
| **CA-13.2** Me arrepiento | Dado que pedí eliminar una camisa, cuando respondo que no en la confirmación | nada cambia | Funcionalidad | Automática | `test_ca_13_2_me_arrepiento` |
| **CA-13.3** Única prenda | Dado que la orden tiene una sola prenda, cuando intento eliminarla | no se permite y el sistema sugiere cancelar la orden | Funcionalidad | Automática | `test_ca_13_3_unica_prenda` |
| **CA-13.4** Prenda entregada | Dado que la prenda está Entregada, cuando intento eliminarla | no se permite | Funcionalidad | Automática | `test_ca_13_4_prenda_entregada` |

## HU-14 · Consultar el detalle de una orden

**Prioridad:** Must · **Reglas:** RN-01, RN-18, RN-22, RN-23, RN-26, RN-27, RN-29, RN-41 · **Calidad:** RNF-22 · **Clase de prueba:** `Feature/Consultas/DetalleDeOrdenTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-14.1** Orden en proceso | Dado que la #0042 de Marta tiene una prenda Terminada, una En proceso y una Pendiente, un abono de $10.000 y entrega el 20 de septiembre, cuando abro la orden | veo el cliente, el número, la fecha de recepción y la de entrega acordada, las tres prendas con su estado y sus fotos, el valor de $31.000, el abono, el saldo de $21.000, el estado de avance En proceso y el estado de pago Por cobrar | Funcionalidad | Automática | `test_ca_14_1_orden_en_proceso` |
| **CA-14.2** Orden entregada | Dado que la #0040 quedó lista y ya se entregó, cuando abro la orden | veo además la fecha en que quedó lista, la fecha de entrega real y los avisos enviados | Funcionalidad | Automática | `test_ca_14_2_orden_entregada` |
| **CA-14.3** Orden de otro negocio | Dado que conozco el enlace de una orden de otro negocio, cuando intento abrirla | el sistema responde como si la orden no existiera | Funcionalidad | Automática | `test_ca_14_3_orden_de_otro_negocio` |

## HU-15 · Listar y buscar órdenes

**Prioridad:** Must · **Reglas:** RN-08, RN-18 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/ListarOrdenesTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-15.1** Filtrar por estado | Dado que hay órdenes En proceso, Listas para entregar y Entregadas, cuando filtro por Lista para entregar | solo veo las órdenes listas | Funcionalidad | Automática | `test_ca_15_1_filtrar_por_estado` |
| **CA-15.2** Buscar por número | Dado que existe la #0042, cuando busco "42" o "#0042" | se abre la orden #0042 | Funcionalidad | Automática | `test_ca_15_2_buscar_por_numero` |
| **CA-15.3** Número inexistente | Dado que no existe la orden #9999, cuando busco "#9999" | veo que no hay una orden con ese número | Funcionalidad | Automática | `test_ca_15_3_numero_inexistente` |

## HU-16 · Agregar, renombrar o desactivar tipos de prenda

**Prioridad:** Could · **Reglas:** RN-01, RN-10, RN-43 · **Calidad:** — · **Clase de prueba:** `Feature/Configuracion/GestionarTiposDePrendaTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-16.1** Renombrar | Dado que el tipo "Overol" tiene 2 prendas, cuando lo renombro como "Enterizo" | las dos prendas muestran Enterizo | Funcionalidad | Automática | `test_ca_16_1_renombrar` |
| **CA-16.2** Desactivar | Dado que el tipo "Chaqueta" tiene prendas registradas, cuando lo desactivo | ya no aparece al registrar prendas nuevas y las prendas que ya eran chaqueta lo conservan | Funcionalidad | Automática | `test_ca_16_2_desactivar` |
| **CA-16.3** Agregar | Dado que arreglo overoles y ese tipo no está en mi lista, cuando lo agrego desde Ajustes | aparece al registrar una prenda nueva, y si escribo un nombre que ya existe el sistema me lo dice | Funcionalidad | Automática | `test_ca_16_3_agregar` |
