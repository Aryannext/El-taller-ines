# Casos de prueba · EP-05 · Estados y entrega

> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde las [historias de usuario](../../02-requisitos/historias-de-usuario.md), la [arquitectura](../../03-diseno/arquitectura/README.md) y el [plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.

Cada criterio de aceptación es un caso de prueba con su mismo código. La clase de prueba de cada historia es la del caso de uso que la implementa; cuando un criterio usa otra, aparece junto al método. Las rutas son relativas a `sistema/tests/`.

**Resumen:** 4 historias · 19 casos · 19 automáticos · 0 automáticos y manuales · 0 manuales.

## HU-20 · Actualizar el estado de una prenda

**Prioridad:** Must · **Reglas:** RN-12, RN-13, RN-14, RN-15, RN-18, RN-19, RN-22, RN-24 · **Calidad:** — · **Clase de prueba:** `Feature/Ordenes/CambiarEstadoDePrendaTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-20.1** Avanzar | Dado que un pantalón está Pendiente, cuando lo marco En proceso | queda En proceso | Funcionalidad | Automática | `test_ca_20_1_avanzar` |
| **CA-20.2** Cambio no permitido | Dado que un vestido está En proceso, cuando reviso los estados a los que puede pasar | la opción Entregada no aparece | Funcionalidad | Automática | `test_ca_20_2_cambio_no_permitido` |
| **CA-20.3** La orden queda lista sola | Dado que la #0042 tiene dos prendas Terminadas y una En proceso, cuando marco Terminada la tercera | la orden pasa a Lista para entregar y se registra la fecha y hora en que quedó lista | Funcionalidad | Automática | `test_ca_20_3_la_orden_queda_lista_sola` |
| **CA-20.4** Retoque | Dado que la #0042 está Lista y a Marta le quedó larga la basta del pantalón al medírselo, cuando marco el pantalón En proceso | la orden vuelve a En proceso y se borra la fecha en que había quedado lista | Funcionalidad | Automática | `test_ca_20_4_retoque` |
| **CA-20.5** Sin cambio manual | Dado que abro cualquier orden, cuando busco cómo cambiar directamente su estado de avance | esa opción no existe | Funcionalidad | Automática | `test_ca_20_5_sin_cambio_manual` |
| **CA-20.6** Orden cancelada | Dado que la #0041 está Cancelada, cuando intento cambiar el estado de una de sus prendas | no se permite | Funcionalidad | Automática | `test_ca_20_6_orden_cancelada` |

## HU-21 · Entregar la orden al cliente

**Prioridad:** Must · **Reglas:** RN-20, RN-21, RN-23, RN-29 · **Calidad:** — · **Clase de prueba:** `Feature/Ordenes/EntregarOrdenTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-21.1** Entrega completa | Dado que la #0042 tiene sus 3 prendas Terminadas y saldo de $0, cuando la entrego | las 3 prendas quedan Entregadas y la orden queda Entregada con la fecha y hora de entrega | Funcionalidad | Automática | `test_ca_21_1_entrega_completa` |
| **CA-21.2** Entrega parcial | Dado que una orden tiene una camisa Terminada y un vestido En proceso, cuando la entrego | la camisa queda Entregada y la orden sigue En proceso | Funcionalidad | Automática | `test_ca_21_2_entrega_parcial` |
| **CA-21.3** Aviso de saldo | Dado que la #0042 tiene todo Terminado y saldo de $12.000, cuando la entrego | veo "Marta debe $12.000. ¿Entregar de todos modos?" | Funcionalidad | Automática | `test_ca_21_3_aviso_de_saldo` |
| **CA-21.4** Entrego debiendo | Dado que estoy viendo el aviso de saldo, cuando confirmo la entrega | la orden queda Entregada y Por cobrar, con saldo de $12.000 | Funcionalidad | Automática | `test_ca_21_4_entrego_debiendo` |
| **CA-21.5** No entrego | Dado que estoy viendo el aviso de saldo, cuando no confirmo | nada cambia | Funcionalidad | Automática | `test_ca_21_5_no_entrego` |

## HU-22 · Cancelar una orden

**Prioridad:** Must · **Reglas:** RN-08, RN-24, RN-32 · **Calidad:** RNF-10 · **Clase de prueba:** `Feature/Ordenes/CancelarOrdenTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-22.1** Cancelación | Dado que la #0043 de Luis está En proceso con un abono de $5.000, cuando la cancelo y lo confirmo | queda Cancelada, el abono sigue registrado y la orden no suma al total por cobrar | Funcionalidad | Automática | `test_ca_22_1_cancelacion` |
| **CA-22.2** Orden entregada | Dado que la #0040 está Entregada, cuando intento cancelarla | no se permite | Funcionalidad | Automática | `test_ca_22_2_orden_entregada` |
| **CA-22.3** Orden ya cancelada | Dado que la #0043 está Cancelada, cuando intento registrarle un pago o agregarle una prenda | no se permite | Funcionalidad | Automática | `test_ca_22_3_orden_ya_cancelada` |

## HU-36 · Devolver una prenda sin arreglar

**Prioridad:** Should · **Reglas:** RN-12, RN-16, RN-26, RN-44 · **Calidad:** RNF-10 · **Clase de prueba:** `Feature/Ordenes/DevolverPrendaSinArreglarTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-36.1** Devolución | Dado que la #0042 vale $31.000, sin pagos, con un pantalón Terminado de $15.000 y dos camisas Pendientes de $8.000, cuando devuelvo una camisa sin arreglar y lo confirmo | la camisa queda Devuelta, el valor y el saldo quedan en $23.000 y la orden sigue En proceso | Funcionalidad | Automática | `test_ca_36_1_devolucion` |
| **CA-36.2** Me arrepiento | Dado que pedí devolver una camisa sin arreglar, cuando respondo que no en la confirmación | nada cambia | Funcionalidad | Automática | `test_ca_36_2_me_arrepiento` |
| **CA-36.3** Prenda terminada | Dado que el pantalón de la #0042 está Terminado, cuando reviso sus opciones | la opción de devolver sin arreglar no aparece | Funcionalidad | Automática | `test_ca_36_3_prenda_terminada` |
| **CA-36.4** Única prenda por resolver | Dado que la #0043 tiene una sola prenda y está Pendiente, cuando intento devolverla sin arreglar | no se permite y el sistema sugiere cancelar la orden | Funcionalidad | Automática | `test_ca_36_4_unica_prenda_por_resolver` |
| **CA-36.5** Lo pagado supera el valor | Dado que la #0042 vale $31.000 y tiene $30.000 pagados, cuando intento devolver una camisa de $8.000 | no se permite y veo que primero debo anular el pago que sobra | Funcionalidad | Automática | `test_ca_36_5_lo_pagado_supera_el_valor` |
