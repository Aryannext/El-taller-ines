# Casos de prueba · EP-06 · Pagos

> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde las [historias de usuario](../../02-requisitos/historias-de-usuario.md), la [arquitectura](../../03-diseno/arquitectura/README.md) y el [plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.

Cada criterio de aceptación es un caso de prueba con su mismo código. La clase de prueba de cada historia es la del caso de uso que la implementa; cuando un criterio usa otra, aparece junto al método. Las rutas son relativas a `sistema/tests/`.

**Resumen:** 5 historias · 17 casos · 17 automáticos · 0 automáticos y manuales · 0 manuales.

## HU-23 · Registrar un pago o abono

**Prioridad:** Must · **Reglas:** RN-25, RN-26, RN-27, RN-28, RN-29, RN-30 · **Calidad:** RNF-14 · **Clase de prueba:** `Feature/Pagos/RegistrarPagoTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-23.1** Abono | Dado que la #0042 vale $31.000 y no tiene pagos, cuando registro $10.000 en efectivo | el saldo queda en $21.000 y el estado de pago es Por cobrar | Funcionalidad | Automática | `test_ca_23_1_abono` |
| **CA-23.2** Pago por Nequi | Dado que la #0042 tiene saldo de $21.000, cuando registro $21.000 por Nequi | el saldo queda en $0 y la orden queda Pagada | Funcionalidad | Automática | `test_ca_23_2_pago_por_nequi` |
| **CA-23.3** Supera el saldo | Dado que la #0042 tiene saldo de $21.000, cuando registro $25.000 | no se guarda y veo "El pago no puede superar el saldo pendiente de $21.000" | Funcionalidad | Automática | `test_ca_23_3_supera_el_saldo` |
| **CA-23.4** Valor cero | Dado que la #0042 tiene saldo de $21.000, cuando registro $0 | no se guarda y veo que el valor debe ser mayor que cero | Funcionalidad | Automática | `test_ca_23_4_valor_cero` |
| **CA-23.5** Doble toque | Dado que la #0042 tiene saldo de $21.000, cuando toco guardar dos veces seguidas un pago de $10.000 | se registra un solo pago y el saldo queda en $11.000 | Funcionalidad | Automática | `test_ca_23_5_doble_toque` |
| **CA-23.6** Después de entregar | Dado que la #0040 está Entregada con saldo de $12.000, cuando registro $12.000 | se guarda y la orden queda Pagada | Funcionalidad | Automática | `test_ca_23_6_despues_de_entregar` |
| **CA-23.7** Orden cancelada | Dado que la #0043 está Cancelada, cuando intento registrarle un pago | no se permite | Funcionalidad | Automática | `test_ca_23_7_orden_cancelada` |

## HU-24 · Registrar un abono al recibir la orden

**Prioridad:** Should · **Reglas:** RN-25, RN-28 · **Calidad:** RNF-13 · **Clase de prueba:** `Feature/Ordenes/RegistrarOrdenTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-24.1** Abono inicial | Dado que estoy registrando una orden de $31.000, cuando agrego un abono inicial de $10.000 en efectivo y guardo | la orden queda guardada con un pago de $10.000 y saldo de $21.000 | Funcionalidad | Automática | `test_ca_24_1_abono_inicial` |
| **CA-24.2** Abono mayor que la orden | Dado que estoy registrando una orden de $31.000, cuando agrego un abono inicial de $40.000 y guardo | no se guarda ni la orden ni el pago, y veo que el abono no puede superar $31.000 | Funcionalidad | Automática | `test_ca_24_2_abono_mayor_que_la_orden` |

## HU-25 · Anular un pago mal registrado

**Prioridad:** Must · **Reglas:** RN-27, RN-31 · **Calidad:** RNF-10 · **Clase de prueba:** `Feature/Pagos/AnularPagoTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-25.1** Anulación | Dado que la #0042 vale $31.000 y tiene un abono de $15.000 que en realidad era de $5.000, cuando lo anulo con el motivo "valor mal digitado" | deja de contar, el saldo vuelve a $31.000 y el pago sigue visible como anulado, con la fecha y el motivo | Funcionalidad | Automática | `test_ca_25_1_anulacion` |
| **CA-25.2** Sin motivo | Dado que voy a anular un pago, cuando intento hacerlo sin escribir el motivo | no se anula y veo que el motivo es obligatorio | Funcionalidad | Automática | `test_ca_25_2_sin_motivo` |
| **CA-25.3** No se borra | Dado que un pago ya está anulado, cuando busco cómo anularlo otra vez o eliminarlo | esas opciones no existen | Funcionalidad | Automática | `test_ca_25_3_no_se_borra` |

## HU-26 · Ver quién me debe

**Prioridad:** Should · **Reglas:** RN-29, RN-32 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/QuienMeDebeTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-26.1** Lista y total | Dado que la #0040 está Entregada con saldo de $12.000, la #0042 En proceso con saldo de $21.000 y la #0041 Cancelada con saldo de $8.000, cuando abro la lista de órdenes por cobrar | veo primero la #0042 y después la #0040, con un total por cobrar de $33.000 | Funcionalidad | Automática | `test_ca_26_1_lista_y_total` |
| **CA-26.2** Las pagadas no aparecen | Dado que una orden está Pagada, cuando abro la lista de órdenes por cobrar | esa orden no aparece | Funcionalidad | Automática | `test_ca_26_2_las_pagadas_no_aparecen` |

## HU-27 · Ver cuánto dinero he recibido

**Prioridad:** Should · **Reglas:** RN-09, RN-33 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/DineroRecibidoTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-27.1** Mes | Dado que en septiembre hay pagos de $10.000, $21.000 y $15.000, este último anulado, cuando consulto lo recibido en septiembre | veo $31.000 | Funcionalidad | Automática | `test_ca_27_1_mes` |
| **CA-27.2** Pago de noche | Dado que registré un pago de $8.000 el 14 de septiembre a las 11:30 p. m., cuando consulto lo recibido el 14 de septiembre | el total incluye esos $8.000 | Funcionalidad | Automática | `test_ca_27_2_pago_de_noche` |
| **CA-27.3** Rango | Dado que hay pagos en distintos días de septiembre, cuando elijo del 1 al 15 de septiembre | veo lo recibido solo en ese rango | Funcionalidad | Automática | `test_ca_27_3_rango` |
