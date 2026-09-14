# Casos de prueba · EP-07 · Avisos

> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde las [historias de usuario](../../02-requisitos/historias-de-usuario.md), la [arquitectura](../../03-diseno/arquitectura/README.md) y el [plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.

Cada criterio de aceptación es un caso de prueba con su mismo código. La clase de prueba de cada historia es la del caso de uso que la implementa; cuando un criterio usa otra, aparece junto al método. Las rutas son relativas a `sistema/tests/`.

**Resumen:** 4 historias · 13 casos · 11 automáticos · 2 automáticos y manuales · 0 manuales.

## HU-28 · Recibir un aviso cuando mi ropa está lista

**Prioridad:** Must · **Reglas:** RN-37, RN-38, RN-40, RN-41, RN-42 · **Calidad:** RNF-04, RNF-17 · **Clase de prueba:** `Feature/Avisos/GenerarAvisoTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-28.1** Sale solo | Dado que el negocio tiene configurada la API oficial y a la #0042 de Marta le falta una prenda, cuando la dueña marca Terminada esa última prenda | sin ninguna otra acción, Marta recibe un WhatsApp con el número de orden, la cantidad de prendas listas y su saldo, y el aviso queda registrado como enviado por la API oficial | Funcionalidad | Automática y manual | `test_ca_28_1_sale_solo`<br>y [PM-08](../pruebas-manuales/PM-08-aviso-real-por-whatsapp.md) |
| **CA-28.2** No hace esperar | Dado que WhatsApp tarda en responder, cuando la dueña marca Terminada la última prenda | la pantalla responde en menos de 1 segundo | Funcionalidad | Automática | `test_ca_28_2_no_hace_esperar` |
| **CA-28.3** Datos del momento | Dado que el aviso está en espera con saldo de $21.000 y Marta abona $10.000 antes de que salga, cuando se envía el aviso | el mensaje dice que debe $11.000 | Integración | Automática | `Feature/Avisos/EnviarAvisoTest.php`<br>`test_ca_28_3_datos_del_momento` |
| **CA-28.4** Reintentos sin duplicar | Dado que la API falla dos veces y a la tercera responde, cuando se reintenta el envío | Marta recibe un solo mensaje | Integración | Automática | `Feature/Avisos/EnviarAvisoTest.php`<br>`test_ca_28_4_reintentos_sin_duplicar` |
| **CA-28.5** Falla persistente | Dado que la API falla en los 3 reintentos, cuando termina el último intento | el aviso queda pendiente de envío asistido | Integración | Automática | `Feature/Avisos/EnviarAvisoTest.php`<br>`test_ca_28_5_falla_persistente` |

## HU-29 · Enviar con un toque los avisos pendientes

**Prioridad:** Must · **Reglas:** RN-40, RN-41, RN-42 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/AvisosPorEnviarTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-29.1** Aviso pendiente | Dado que el negocio no tiene configurada la API y la #0042 queda Lista, cuando abro los avisos pendientes | veo el aviso a Marta con el mensaje ya redactado | Funcionalidad | Automática | `test_ca_29_1_aviso_pendiente` |
| **CA-29.2** Abrir WhatsApp | Dado que estoy viendo el aviso a Marta, cuando toco enviar | se abre WhatsApp con el celular 3104567890 y el mensaje escrito | Funcionalidad | Automática y manual | `test_ca_29_2_abrir_whatsapp`<br>y [PM-04](../pruebas-manuales/PM-04-app-en-el-celular.md) |
| **CA-29.3** Confirmo el envío | Dado que abrí WhatsApp desde el aviso, cuando vuelvo al sistema y confirmo que lo envié | el aviso queda registrado como enviado por envío asistido y sale de los pendientes | Funcionalidad | Automática | `Feature/Avisos/ConfirmarEnvioAsistidoTest.php`<br>`test_ca_29_3_confirmo_el_envio` |
| **CA-29.4** No lo envié | Dado que abrí WhatsApp desde el aviso, cuando vuelvo al sistema sin confirmar | el aviso sigue pendiente | Funcionalidad | Automática | `Feature/Avisos/ConfirmarEnvioAsistidoTest.php`<br>`test_ca_29_4_no_lo_envie` |

## HU-30 · No avisar una orden que ya no está lista

**Prioridad:** Must · **Reglas:** RN-38, RN-39 · **Calidad:** — · **Clase de prueba:** `Feature/Avisos/EnviarAvisoTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-30.1** Aviso en espera | Dado que el aviso de la #0042 está en espera y el pantalón vuelve a En proceso, cuando llega el momento de enviarlo | no se envía y queda registrado como descartado | Integración | Automática | `test_ca_30_1_aviso_en_espera` |
| **CA-30.2** Aviso asistido pendiente | Dado que el aviso de la #0042 está pendiente de envío asistido y el pantalón vuelve a En proceso, cuando abro los avisos pendientes | ese aviso ya no aparece | Funcionalidad | Automática | `Feature/Consultas/AvisosPorEnviarTest.php`<br>`test_ca_30_2_aviso_asistido_pendiente` |
| **CA-30.3** Vuelve a quedar lista | Dado que el aviso de la #0042 se descartó, cuando la orden vuelve a quedar Lista para entregar | se genera un aviso nuevo | Integración | Automática | `Feature/Avisos/GenerarAvisoTest.php`<br>`test_ca_30_3_vuelve_a_quedar_lista` |

## HU-31 · Consultar los avisos de una orden

**Prioridad:** Must · **Reglas:** RN-41 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/DetalleDeOrdenTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-31.1** Historial de avisos | Dado que la #0042 tiene un aviso descartado y otro enviado, cuando abro sus avisos | veo los dos con fecha y hora, canal, mensaje y resultado | Funcionalidad | Automática | `test_ca_31_1_historial_de_avisos` |
