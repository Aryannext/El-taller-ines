# Casos de prueba · EP-08 · Seguimiento

> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde las [historias de usuario](../../02-requisitos/historias-de-usuario.md), la [arquitectura](../../03-diseno/arquitectura/README.md) y el [plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.

Cada criterio de aceptación es un caso de prueba con su mismo código. La clase de prueba de cada historia es la del caso de uso que la implementa; cuando un criterio usa otra, aparece junto al método. Las rutas son relativas a `sistema/tests/`.

**Resumen:** 4 historias · 11 casos · 11 automáticos · 0 automáticos y manuales · 0 manuales.

## HU-32 · Ver el panel del día

**Prioridad:** Must · **Reglas:** RN-32, RN-34, RN-35, RN-40 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/PanelDelDiaTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-32.1** Cifras del día | Dado que hay $33.000 por cobrar, 2 órdenes atrasadas, 1 orden sin reclamar con 2 prendas y 1 aviso pendiente, cuando entro al sistema | veo esas cuatro cifras en el panel | Funcionalidad | Automática | `test_ca_32_1_cifras_del_dia` |
| **CA-32.2** Ir al detalle | Dado que estoy en el panel, cuando toco las órdenes atrasadas | veo la lista de órdenes atrasadas | Funcionalidad | Automática | `test_ca_32_2_ir_al_detalle` |
| **CA-32.3** Todo al día | Dado que no hay nada atrasado, sin reclamar ni pendiente de aviso, cuando entro al sistema | esos indicadores aparecen en cero | Funcionalidad | Automática | `test_ca_32_3_todo_al_dia` |

## HU-33 · Ver las órdenes atrasadas

**Prioridad:** Must · **Reglas:** RN-09, RN-34 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/OrdenesAtrasadasTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-33.1** Más atrasada primero | Dado que hoy es 16 de septiembre, la #0042 está En proceso con entrega el 15 y la #0044 En proceso con entrega el 12, cuando abro las órdenes atrasadas | veo la #0044 con 4 días de atraso y después la #0042 con 1 día | Funcionalidad | Automática | `test_ca_33_1_mas_atrasada_primero` |
| **CA-33.2** Lista no es atrasada | Dado que la #0043 está Lista para entregar con entrega el 15 de septiembre, cuando abro las órdenes atrasadas | la #0043 no aparece | Funcionalidad | Automática | `test_ca_33_2_lista_no_es_atrasada` |
| **CA-33.3** Hora de Colombia | Dado que son las 10:00 p. m. del 14 de septiembre y la #0045 está En proceso con entrega el 15, cuando abro las órdenes atrasadas | la #0045 no aparece | Funcionalidad | Automática | `test_ca_33_3_hora_de_colombia` |

## HU-34 · Ver las órdenes sin reclamar

**Prioridad:** Must · **Reglas:** RN-35, RN-36 · **Calidad:** — · **Clase de prueba:** `Feature/Consultas/OrdenesSinReclamarTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-34.1** Orden sin reclamar | Dado que el plazo es de 30 días, hoy es 16 de septiembre y la #0030 quedó lista el 1 de agosto con 2 camisas Terminadas, cuando abro las órdenes sin reclamar | veo la #0030 con 46 días de espera y 2 prendas sin reclamar | Funcionalidad | Automática | `test_ca_34_1_orden_sin_reclamar` |
| **CA-34.2** Dentro del plazo | Dado que la #0042 quedó lista el 1 de septiembre, cuando abro las órdenes sin reclamar | la #0042 no aparece, porque lleva 15 días | Funcionalidad | Automática | `test_ca_34_2_dentro_del_plazo` |
| **CA-34.3** Mayor espera primero | Dado que la #0030 lleva 46 días de espera y la #0025 lleva 60, cuando abro las órdenes sin reclamar | veo primero la #0025 | Funcionalidad | Automática | `test_ca_34_3_mayor_espera_primero` |

## HU-35 · Cambiar el plazo para considerar una orden sin reclamar

**Prioridad:** Could · **Reglas:** RN-35 · **Calidad:** — · **Clase de prueba:** `Feature/Configuracion/CambiarPlazoSinReclamarTest.php`

| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |
| --- | --- | --- | --- | --- | --- |
| **CA-35.1** Nuevo plazo | Dado que el plazo es de 30 días y la #0042 lleva 45 días de espera, cuando cambio el plazo a 60 días | la #0042 deja de aparecer entre las sin reclamar | Funcionalidad | Automática | `test_ca_35_1_nuevo_plazo` |
| **CA-35.2** Fuera de rango | Dado que estoy cambiando el plazo, cuando escribo 0 o 400 | no se guarda y veo que debe estar entre 1 y 365 días | Funcionalidad | Automática | `test_ca_35_2_fuera_de_rango` |
