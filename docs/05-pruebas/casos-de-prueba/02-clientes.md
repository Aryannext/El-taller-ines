# Casos de prueba · EP-02 · Clientes

> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde las [historias de usuario](../../02-requisitos/historias-de-usuario.md), la [arquitectura](../../03-diseno/arquitectura/README.md) y el [plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.

Cada criterio de aceptación es un caso de prueba con su mismo código. La clase de prueba de cada historia es la del caso de uso que la implementa; cuando un criterio usa otra, aparece junto al método. Las rutas son relativas a `sistema/tests/`.

**Resumen:** 4 historias · 14 casos · 14 automáticos · 0 automáticos y manuales · 0 manuales.

## HU-03 · Registrar un cliente

**Prioridad:** Must · **Reglas:** RN-02, RN-03, RN-04 · **Calidad:** — · **Clase de prueba:** `Feature/Clientes/RegistrarClienteTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-03.1** Registro correcto | Dado que Marta Rincón no está registrada, cuando la registro con el celular 3104567890 | queda registrada y veo su ficha | Funcionalidad | Automática | `test_ca_03_1_registro_correcto` |
| **CA-03.2** Sin celular | Dado que Marta Rincón no está registrada, cuando intento registrarla sin celular | no se guarda y el campo del celular indica que es obligatorio | Funcionalidad | Automática | `test_ca_03_2_sin_celular` |
| **CA-03.3** Número fijo | Dado que Marta Rincón no está registrada, cuando escribo el número 6014567890 | no se guarda y veo "Escribe un celular colombiano de 10 dígitos que empiece por 3" | Funcionalidad | Automática | `test_ca_03_3_numero_fijo` |
| **CA-03.4** Celular compartido | Dado que Marta está registrada con el 3104567890, cuando registro a su hija Laura con el mismo celular | Laura queda registrada como otra cliente | Funcionalidad | Automática | `test_ca_03_4_celular_compartido` |

## HU-04 · Buscar un cliente

**Prioridad:** Must · **Reglas:** RN-01 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/BuscarClientesTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-04.1** Por nombre, sin tildes | Dado que está registrada "María Gómez", cuando busco "maria" | aparece María Gómez | Funcionalidad | Automática | `test_ca_04_1_por_nombre_sin_tildes` |
| **CA-04.2** Por celular | Dado que Marta está registrada con el 3104567890, cuando busco 3104567890 | aparece Marta Rincón | Funcionalidad | Automática | `test_ca_04_2_por_celular` |
| **CA-04.3** Sin resultados | Dado que no hay clientes llamados Pedro, cuando busco "pedro" | veo que no hay resultados y la opción de registrar un cliente nuevo | Funcionalidad | Automática | `test_ca_04_3_sin_resultados` |
| **CA-04.4** Solo mi negocio | Dado que otro negocio tiene una cliente llamada Marta, cuando busco "marta" | solo aparecen las clientes de mi negocio | Funcionalidad | Automática | `test_ca_04_4_solo_mi_negocio` |

## HU-05 · Consultar la ficha de un cliente

**Prioridad:** Must · **Reglas:** RN-27, RN-29 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/FichaDeClienteTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-05.1** Órdenes y deuda | Dado que Marta tiene la orden #0040 Entregada con saldo de $12.000 y la #0042 En proceso con saldo de $21.000, cuando abro su ficha | veo sus datos, las dos órdenes con número, fecha de recepción, estado de avance y estado de pago, y que debe $33.000 en total | Funcionalidad | Automática | `test_ca_05_1_ordenes_y_deuda` |
| **CA-05.2** Las canceladas no suman | Dado que Marta también tiene la orden #0041 Cancelada con saldo de $8.000, cuando abro su ficha | la #0041 aparece como Cancelada y el total que debe sigue siendo $33.000 | Funcionalidad | Automática | `test_ca_05_2_las_canceladas_no_suman` |
| **CA-05.3** Cliente sin órdenes | Dado que Laura no tiene órdenes, cuando abro su ficha | veo que no tiene órdenes y que no debe nada | Funcionalidad | Automática | `test_ca_05_3_cliente_sin_ordenes` |

## HU-06 · Corregir los datos de un cliente

**Prioridad:** Must · **Reglas:** RN-02, RN-03 · **Calidad:** — · **Clase de prueba:** `Feature/Clientes/CorregirClienteTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-06.1** Nuevo celular | Dado que Marta cambió de celular, cuando lo corrijo por 3157654321 | se guarda y los avisos siguientes le llegan a ese número | Funcionalidad | Automática | `test_ca_06_1_nuevo_celular` |
| **CA-06.2** Nombre vacío | Dado que estoy corrigiendo a Marta, cuando borro su nombre e intento guardar | no se guarda y veo que el nombre es obligatorio | Funcionalidad | Automática | `test_ca_06_2_nombre_vacio` |
| **CA-06.3** Celular incompleto | Dado que estoy corrigiendo a Marta, cuando escribo un celular de 9 dígitos | no se guarda y veo cómo debe ser el celular | Funcionalidad | Automática | `test_ca_06_3_celular_incompleto` |
