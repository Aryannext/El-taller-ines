# Casos de prueba · Reglas de negocio

> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde las [reglas de negocio](../../02-requisitos/reglas-de-negocio.md), la [arquitectura](../../03-diseno/arquitectura/README.md#dónde-vive-cada-regla-en-el-código) y el [plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.

Cada regla tiene al menos una prueba automática que usa su ejemplo (RNF-28). El nivel sale de la capa donde la arquitectura ubica la regla, y la columna de historias dice qué historias dejarían de necesitarla si se recortaran. Las rutas son relativas a `sistema/tests/`.

**Resumen:** 47 reglas · 17 unitarias · 24 de integración · 5 de funcionalidad · 1 de aislamiento.

## Negocio

| Regla | Ejemplo que se prueba | Capa | Nivel | Prueba | Historias |
| --- | --- | --- | --- | --- | --- |
| **RN-01** La información pertenece a un negocio | El negocio A tiene la cliente Marta. Una usuaria del negocio B busca "Marta" y no obtiene resultados. | Modelos | Aislamiento | `Feature/Aislamiento/AislamientoEntreNegociosTest.php`<br>`test_rn_01_la_informacion_pertenece_a_un_negocio` | HU-01, HU-38, HU-37, HU-04, HU-14, HU-16 |
| **RN-45** Solo entra un correo ya registrado | La dueña del taller entra con `taller@gmail.com`, que quedó registrado al instalar el sistema, y pasa al panel. Alguien más entra con otro correo de Google y el sistema le dice que ese correo no tiene acceso, sin crear nada. | Aplicación | Integración | `Feature/Acceso/EntrarConGoogleTest.php`<br>`test_rn_45_solo_entra_un_correo_ya_registrado` | HU-37 |

## Clientes

| Regla | Ejemplo que se prueba | Capa | Nivel | Prueba | Historias |
| --- | --- | --- | --- | --- | --- |
| **RN-02** Datos mínimos de un cliente | Se intenta registrar a "Luis Pardo" sin teléfono: no se guarda y se indica que el teléfono es obligatorio. | Http | Funcionalidad | `Feature/Http/ClienteRequestTest.php`<br>`test_rn_02_datos_minimos_de_un_cliente` | HU-03, HU-06, HU-10 |
| **RN-03** El teléfono debe poder recibir WhatsApp | `3104567890` se acepta. `6014567890` (fijo) y `310456789` (9 dígitos) se rechazan. | Dominio | Unitaria | `Unit/Dominio/Clientes/CelularTest.php`<br>`test_rn_03_el_telefono_debe_poder_recibir_whatsapp` | HU-03, HU-06, HU-10 |
| **RN-04** El teléfono no es único | Una madre y su hija se registran como clientes distintas con el mismo celular. | Base de datos | Integración | `Feature/BaseDeDatos/EsquemaTest.php`<br>`test_rn_04_el_telefono_no_es_unico` | HU-03 |

## Órdenes

| Regla | Ejemplo que se prueba | Capa | Nivel | Prueba | Historias |
| --- | --- | --- | --- | --- | --- |
| **RN-05** Una orden, un cliente, una visita | Marta deja un pantalón y dos camisas el lunes: es una orden con tres prendas. Si vuelve el jueves con un vestido, es otra orden. | Base de datos | Integración | `Feature/BaseDeDatos/EsquemaTest.php`<br>`test_rn_05_una_orden_un_cliente_una_visita` | HU-07 |
| **RN-06** Una orden tiene al menos una prenda | Se intenta guardar una orden para Marta sin agregar prendas: no se guarda. | Aplicación | Integración | `Feature/Ordenes/RegistrarOrdenTest.php`<br>`test_rn_06_una_orden_tiene_al_menos_una_prenda` | HU-07, HU-13 |
| **RN-07** La entrega no puede ser antes de la recepción | Orden recibida el 14 de septiembre: entrega el 13 se rechaza; entrega el 14 o el 20 se acepta. | Http | Funcionalidad | `Feature/Http/OrdenRequestTest.php`<br>`test_rn_07_la_entrega_no_puede_ser_antes_de_la_recepcion` | HU-07 |
| **RN-08** Número de orden | La última orden del negocio es #0041 y está cancelada. La siguiente es #0042. | Aplicación | Integración | `Feature/Ordenes/RegistrarOrdenTest.php`<br>`test_rn_08_numero_de_orden` | HU-08, HU-15, HU-22 |
| **RN-09** Las fechas se interpretan en hora de Colombia | Una orden con entrega el 15 de septiembre se consulta el 14 a las 10:00 p. m.: no está atrasada. | Infraestructura | Integración | `Feature/Infraestructura/RelojDeColombiaTest.php`<br>`test_rn_09_las_fechas_se_interpretan_en_hora_de_colombia` | HU-27, HU-33 |

## Prendas

| Regla | Ejemplo que se prueba | Capa | Nivel | Prueba | Historias |
| --- | --- | --- | --- | --- | --- |
| **RN-10** Datos obligatorios de una prenda | Pantalón, "subir basta 3 cm", $15.000: se guarda. Pantalón sin descripción: no se guarda. | Http | Funcionalidad | `Feature/Http/PrendaRequestTest.php`<br>`test_rn_10_datos_obligatorios_de_una_prenda` | HU-07, HU-09, HU-11, HU-16 |
| **RN-11** El precio es un valor entero en pesos | $15.000 se acepta. $0 y $15.000,50 se rechazan. | Dominio | Unitaria | `Unit/Dominio/Pagos/DineroTest.php`<br>`test_rn_11_el_precio_es_un_valor_entero_en_pesos` | HU-07, HU-11, HU-12 |
| **RN-12** Estados de una prenda | Se registra una camisa: queda Pendiente. No puede estar Terminada y Entregada a la vez. | Dominio | Unitaria | `Unit/Dominio/Ordenes/EstadoDePrendaTest.php`<br>`test_rn_12_estados_de_una_prenda` | HU-07, HU-20, HU-36 |
| **RN-13** Solo se entrega lo terminado | Un vestido En proceso no se puede marcar como Entregado. | Dominio | Unitaria | `Unit/Dominio/Ordenes/TransicionesDePrendaTest.php`<br>`test_rn_13_solo_se_entrega_lo_terminado` | HU-20 |
| **RN-14** Un retoque devuelve la prenda a En proceso | Marta se mide el pantalón Terminado y la basta quedó larga: el pantalón vuelve a En proceso. | Dominio | Unitaria | `Unit/Dominio/Ordenes/TransicionesDePrendaTest.php`<br>`test_rn_14_un_retoque_devuelve_la_prenda_a_en_proceso` | HU-20 |
| **RN-15** Una prenda entregada no se modifica | Se intenta cambiar el precio de una camisa ya entregada: no se permite. | Dominio | Unitaria | `Unit/Dominio/Ordenes/TransicionesDePrendaTest.php`<br>`test_rn_15_una_prenda_entregada_no_se_modifica` | HU-12, HU-13, HU-20 |
| **RN-16** Lo pagado no puede quedar por encima del valor | Orden de $30.000 con $25.000 pagados. Eliminar una prenda de $10.000 dejaría el valor en $20.000: no se permite. Primero hay que anular el pago que sobra. | Dominio | Unitaria | `Unit/Dominio/Pagos/ReglasDeValorTest.php`<br>`test_rn_16_lo_pagado_no_puede_quedar_por_encima_del_valor` | HU-12, HU-13, HU-36 |
| **RN-17** Fotos de una prenda | Se registra un vestido con prisa y sin foto: se guarda. Después se le toman dos fotos. Se intenta agregar una cuarta cuando ya tiene tres: no se permite. Se borra una: el vestido sigue registrado con las otras. | Aplicación | Integración | `Feature/Fotos/AgregarFotoTest.php`<br>`test_rn_17_fotos_de_una_prenda` | HU-17, HU-18, HU-19 |
| **RN-43** Tipo de prenda escrito por la usuaria | La usuaria elige «Otro» y escribe "Overol": la prenda se registra como overol y "Overol" aparece en la lista desde la siguiente prenda. Otro día elige «Otro» y escribe "overol": se usa el tipo "Overol" que ya existe. | Aplicación | Integración | `Feature/Ordenes/ResolverTipoDePrendaTest.php`<br>`test_rn_43_tipo_de_prenda_escrito_por_la_usuaria` | HU-09, HU-16 |
| **RN-44** Devolver una prenda sin arreglar | La #0042 tiene un pantalón Terminado de $15.000 y dos camisas Pendientes de $8.000, sin pagos. Marta llega en la fecha acordada y prefiere llevarse una camisa sin arreglar: la camisa queda Devuelta y el valor de la orden baja a $23.000. El pantalón Terminado no se puede devolver sin arreglar. | Aplicación | Integración | `Feature/Ordenes/DevolverPrendaSinArreglarTest.php`<br>`test_rn_44_devolver_una_prenda_sin_arreglar` | HU-36 |

## Estado de la orden

| Regla | Ejemplo que se prueba | Capa | Nivel | Prueba | Historias |
| --- | --- | --- | --- | --- | --- |
| **RN-18** El estado de la orden se calcula de sus prendas | \| Prendas \| Estado de la orden \| | Dominio | Unitaria | `Unit/Dominio/Ordenes/EstadoDeOrdenTest.php`<br>`test_rn_18_el_estado_de_la_orden_se_calcula_de_sus_prendas` | HU-07, HU-11, HU-14, HU-15, HU-20 |
| **RN-19** El estado de avance no se cambia a mano | No existe la acción "marcar orden como lista": la orden queda lista cuando se termina su última prenda pendiente. | Http | Funcionalidad | `Feature/Http/PrendaControllerTest.php`<br>`test_rn_19_el_estado_de_avance_no_se_cambia_a_mano` | HU-20 |
| **RN-20** Entregar la orden | Orden con camisa Terminada y vestido En proceso. Marta recoge la camisa: la camisa queda Entregada y la orden sigue En proceso. | Aplicación | Integración | `Feature/Ordenes/EntregarOrdenTest.php`<br>`test_rn_20_entregar_la_orden` | HU-21 |
| **RN-21** Entregar con saldo pendiente | Orden con saldo de $12.000. Al entregar, el sistema muestra "Marta debe $12.000. ¿Entregar de todos modos?" y solo entrega si la usuaria confirma. | Aplicación | Integración | `Feature/Ordenes/EntregarOrdenTest.php`<br>`test_rn_21_entregar_con_saldo_pendiente` | HU-21 |
| **RN-22** Fecha en que la orden quedó lista | La orden queda lista el 10 de septiembre a las 4:00 p. m. El 12 una prenda vuelve a En proceso: la fecha se borra. El 13 vuelve a quedar lista: la nueva fecha es el 13. | Aplicación | Integración | `Feature/Ordenes/SincronizarEstadoDeOrdenTest.php`<br>`test_rn_22_fecha_en_que_la_orden_quedo_lista` | HU-11, HU-14, HU-20 |
| **RN-23** Fecha de entrega real | Marta recoge su última prenda el 15 de septiembre a las 11:20 a. m.: esa es la fecha de entrega de la orden. | Aplicación | Integración | `Feature/Ordenes/EntregarOrdenTest.php`<br>`test_rn_23_fecha_de_entrega_real` | HU-14, HU-21 |
| **RN-24** Cancelar una orden | Luis desiste de su orden En proceso, que tiene un abono de $5.000: se cancela y el abono sigue registrado. Luego se intenta registrarle otro pago: no se permite. | Aplicación | Integración | `Feature/Ordenes/CancelarOrdenTest.php`<br>`test_rn_24_cancelar_una_orden` | HU-11, HU-20, HU-22 |

## Pagos y saldo

| Regla | Ejemplo que se prueba | Capa | Nivel | Prueba | Historias |
| --- | --- | --- | --- | --- | --- |
| **RN-25** Datos de un pago | Abono de $10.000 en efectivo el 14 de septiembre a la orden #0042: se acepta. Un pago de $0 se rechaza. | Http | Funcionalidad | `Feature/Http/PagoRequestTest.php`<br>`test_rn_25_datos_de_un_pago` | HU-23, HU-24 |
| **RN-26** Valor de la orden | Pantalón $15.000 + camisa $8.000 + camisa $8.000 = valor de la orden $31.000. Si una camisa se devuelve sin arreglar, el valor queda en $23.000. | Dominio | Unitaria | `Unit/Dominio/Pagos/CalculadoraDeSaldoTest.php`<br>`test_rn_26_valor_de_la_orden` | HU-07, HU-12, HU-14, HU-36, HU-23 |
| **RN-27** Saldo pendiente | Valor $31.000. Pagos: $10.000 y $5.000, este último anulado. Saldo pendiente: $31.000 − $10.000 = $21.000. | Dominio | Unitaria | `Unit/Dominio/Pagos/CalculadoraDeSaldoTest.php`<br>`test_rn_27_saldo_pendiente` | HU-05, HU-12, HU-14, HU-23, HU-25 |
| **RN-28** El abono no puede superar el saldo | Saldo pendiente $21.000. Un abono de $25.000 se rechaza; uno de $21.000 se acepta y la orden queda Pagada. | Aplicación | Integración | `Feature/Pagos/RegistrarPagoTest.php`<br>`test_rn_28_el_abono_no_puede_superar_el_saldo` | HU-23, HU-24 |
| **RN-29** Estado de pago | Una orden puede estar Entregada y Por cobrar, o En proceso y Pagada. | Dominio | Unitaria | `Unit/Dominio/Pagos/CalculadoraDeSaldoTest.php`<br>`test_rn_29_estado_de_pago` | HU-05, HU-14, HU-21, HU-23, HU-26 |
| **RN-30** Pagos después de entregar | Marta se llevó su orden debiendo $12.000 y vuelve a la semana a pagarlos: el pago se registra y la orden queda Pagada. | Aplicación | Integración | `Feature/Pagos/RegistrarPagoTest.php`<br>`test_rn_30_pagos_despues_de_entregar` | HU-23 |
| **RN-31** Un pago no se borra: se anula | Orden de $31.000 sin pagos. Se registró por error un abono de $15.000 en vez de $5.000. Se anula con el motivo "valor mal digitado" y se registra el de $5.000: el saldo queda en $26.000. Ambos pagos quedan visibles; solo el segundo cuenta. | Aplicación | Integración | `Feature/Pagos/AnularPagoTest.php`<br>`test_rn_31_un_pago_no_se_borra_se_anula` | HU-25 |
| **RN-32** Total por cobrar del negocio | Orden #0040 entregada con saldo $12.000, orden #0042 en proceso con saldo $21.000 y orden #0041 cancelada con saldo $8.000: total por cobrar $33.000. | Aplicación | Integración | `Feature/Consultas/QuienMeDebeTest.php`<br>`test_rn_32_total_por_cobrar_del_negocio` | HU-22, HU-26, HU-32 |
| **RN-33** Dinero recibido en un período | En septiembre hay pagos de $10.000, $21.000 y $15.000 (anulado): lo recibido en septiembre es $31.000. | Aplicación | Integración | `Feature/Consultas/DineroRecibidoTest.php`<br>`test_rn_33_dinero_recibido_en_un_periodo` | HU-27 |

## Seguimiento

| Regla | Ejemplo que se prueba | Capa | Nivel | Prueba | Historias |
| --- | --- | --- | --- | --- | --- |
| **RN-34** Orden atrasada | Hoy es 16 de septiembre. La orden #0042, En proceso con entrega el 15, está atrasada. La #0043, Lista para entregar con entrega el 15, no está atrasada: el trabajo está hecho y lo que falta es que el cliente la recoja. | Dominio | Unitaria | `Unit/Dominio/Ordenes/ReglasDeSeguimientoTest.php`<br>`test_rn_34_orden_atrasada` | HU-32, HU-33 |
| **RN-35** Orden sin reclamar | Con un plazo de 30 días, una orden que quedó lista el 1 de agosto está sin reclamar el 1 de septiembre. Tiene dos camisas Terminadas: son dos prendas sin reclamar. | Dominio | Unitaria | `Unit/Dominio/Ordenes/ReglasDeSeguimientoTest.php`<br>`test_rn_35_orden_sin_reclamar` | HU-32, HU-34, HU-35 |
| **RN-36** Días de espera | Quedó lista el 1 de septiembre a las 5:00 p. m. El 16 de septiembre lleva 15 días de espera. | Dominio | Unitaria | `Unit/Dominio/Ordenes/ReglasDeSeguimientoTest.php`<br>`test_rn_36_dias_de_espera` | HU-34 |

## Avisos

| Regla | Ejemplo que se prueba | Capa | Nivel | Prueba | Historias |
| --- | --- | --- | --- | --- | --- |
| **RN-37** Al quedar lista la orden se genera su aviso | Se marca Terminado el último pantalón de la orden #0042: sin ninguna otra acción, se genera el aviso a Marta. | Aplicación | Integración | `Feature/Ordenes/SincronizarEstadoDeOrdenTest.php`<br>`test_rn_37_al_quedar_lista_la_orden_se_genera_su_aviso` | HU-28 |
| **RN-46** Lo que dice el aviso | Marta tiene lista la #0042, con 3 prendas y $21.000 de saldo: «Hola Marta, le escribimos de Modistería Inés. Su orden #0042 ya está lista 🧵 Son 3 prendas, con un saldo de $21.000. La esperamos cuando pueda pasar.» Si la orden estuviera pagada, la frase del dinero sería «y ya está pagada: solo pasar a recogerla». | Dominio | Unitaria | `Unit/Dominio/Avisos/MensajeDeAvisoTest.php`<br>`test_rn_46_lo_que_dice_el_aviso` | HU-38 |
| **RN-47** El saludo cambia con la hora | Inés abre el sistema a las 2:30 p. m. y lee «Buenas tardes, Inés». | Dominio | Unitaria | `Unit/Dominio/Compartido/SaludoDelDiaTest.php`<br>`test_rn_47_el_saludo_cambia_con_la_hora` | HU-38 |
| **RN-38** Un solo aviso por cada vez que la orden queda lista | El envío falla y se reintenta tres veces hasta salir: Marta recibe un solo mensaje. Si una prenda vuelve a En proceso y la orden queda lista otra vez, sí se genera un aviso nuevo. | Aplicación | Integración | `Feature/Avisos/GenerarAvisoTest.php`<br>`test_rn_38_un_solo_aviso_por_cada_vez_que_la_orden_queda_lista` | HU-28, HU-30 |
| **RN-39** No se avisa una orden que ya no está lista | El aviso queda en cola y, antes de salir, Marta se mide el pantalón y vuelve a En proceso: el aviso se descarta. | Aplicación | Integración | `Feature/Avisos/EnviarAvisoTest.php`<br>`test_rn_39_no_se_avisa_una_orden_que_ya_no_esta_lista` | HU-30 |
| **RN-40** Canal del aviso | El negocio no ha configurado la API oficial: el aviso a Marta aparece listo para enviarse con un toque desde el WhatsApp de la usuaria. | Aplicación | Integración | `Feature/Avisos/EnviarAvisoTest.php`<br>`test_rn_40_canal_del_aviso` | HU-28, HU-29, HU-32 |
| **RN-41** Constancia de cada aviso | Aviso a Marta · 14 sep 2026, 3:12 p. m. · API oficial · "Hola Marta, tu orden #0042 está lista…" · enviado. | Aplicación | Integración | `Feature/Avisos/EnviarAvisoTest.php`<br>`test_rn_41_constancia_de_cada_aviso` | HU-14, HU-28, HU-29, HU-31 |
| **RN-42** El aviso usa los datos del momento del envío | El aviso se generó con saldo de $21.000, pero Marta abonó $10.000 antes de que saliera: el mensaje dice que debe $11.000. | Dominio | Unitaria | `Unit/Dominio/Avisos/MensajeDeAvisoTest.php`<br>`test_rn_42_el_aviso_usa_los_datos_del_momento_del_envio` | HU-28, HU-29 |
