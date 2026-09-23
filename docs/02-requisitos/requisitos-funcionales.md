# Requisitos funcionales

**Estado:** borrador · Sprint 1 · se valida con el instructor (F-04)

## Cómo están escritos

Cada requisito cumple las características de un buen requisito según ISO/IEC/IEEE 29148:

| Característica | Qué significa aquí |
| --- | --- |
| **Necesario** | Nace de un objetivo específico; si se quita, un objetivo queda sin cumplir |
| **Singular** | Pide una sola capacidad |
| **Sin ambigüedad** | Usa los términos del [glosario](reglas-de-negocio.md#glosario) y se entiende de una sola forma |
| **Verificable** | Se puede comprobar con una prueba; los criterios de aceptación estarán en las historias de usuario |
| **Trazable** | Indica el objetivo, las reglas de negocio y la fuente de las que nace |
| **Factible** | Está dentro del [alcance](../01-problema/alcance.md) |

Todos se redactan como "El sistema debe permitir…" o "El sistema debe…", y las reglas de negocio que citan se cumplen siempre, aunque el requisito no las repita.

### Prioridad (MoSCoW)

| Prioridad | Significa |
| --- | --- |
| **Must** · Debe | Sin él no se cumple un objetivo específico. Se entrega sí o sí |
| **Should** · Debería | Importante, pero el objetivo se cumple sin él. Se entrega si el plan lo permite |
| **Could** · Podría | Deseable. Es lo primero que se recorta |
| **Won't** · No esta vez | Fuera de esta entrega; está en el alcance con su motivo |

**Resumen:** 41 requisitos · 31 Must · 8 Should · 2 Could.

---

## Acceso

| Código | Requisito | Prioridad | Objetivo | Reglas | Fuente |
| --- | --- | --- | --- | --- | --- |
| **RF-01** | El sistema debe permitir a la usuaria iniciar sesión con su usuario y contraseña. | Must | Todos | RN-01 | F-01 |
| **RF-02** | El sistema debe permitir a la usuaria cerrar su sesión. | Must | Todos | — | F-01 |
| **RF-03** | El sistema debe permitir a la usuaria cambiar su contraseña, indicando la contraseña actual. | Must | Todos | — | F-01 |

## Clientes

| Código | Requisito | Prioridad | Objetivo | Reglas | Fuente |
| --- | --- | --- | --- | --- | --- |
| **RF-04** | El sistema debe permitir a la usuaria registrar un cliente con su nombre y su celular. | Must | OE-01 | RN-02, RN-03, RN-04 | F-01 |
| **RF-05** | El sistema debe permitir a la usuaria buscar clientes por una parte del nombre, sin distinguir mayúsculas ni tildes, o por su celular. | Must | OE-01 | RN-01 | F-01 · F-02 |
| **RF-06** | El sistema debe mostrar la ficha de un cliente con sus datos, sus órdenes (número, fecha de recepción, estado de avance y estado de pago) y el total que debe. | Must | OE-01 | RN-27, RN-29 | F-01 |
| **RF-07** | El sistema debe permitir a la usuaria editar el nombre y el celular de un cliente. | Must | OE-01 | RN-02, RN-03 | F-01 |

## Órdenes y prendas

| Código | Requisito | Prioridad | Objetivo | Reglas | Fuente |
| --- | --- | --- | --- | --- | --- |
| **RF-08** | El sistema debe permitir a la usuaria registrar, en una sola operación, una orden para un cliente con su fecha de entrega acordada y una o varias prendas, cada una con tipo, descripción del arreglo y precio. | Must | OE-01 | RN-05, RN-06, RN-07, RN-10, RN-11, RN-12 | F-01 · F-05 |
| **RF-09** | El sistema debe asignar el número de orden al guardarla y mostrarlo destacado, para que la usuaria lo escriba en la bolsa. | Must | OE-06 | RN-08 | F-05 |
| **RF-10** | El sistema debe permitir a la usuaria registrar un cliente nuevo desde el registro de la orden, sin perder lo que ya diligenció. | Should | OE-01 | RN-02, RN-03 | F-01 |
| **RF-11** | El sistema debe permitir a la usuaria agregar prendas a una orden que esté En proceso o Lista para entregar. | Should | OE-01 | RN-10, RN-11, RN-18, RN-24 | F-01 · F-02 |
| **RF-12** | El sistema debe permitir a la usuaria editar la descripción del arreglo y el precio de una prenda que no esté Entregada. | Must | OE-01 | RN-11, RN-15, RN-16 | F-02 · F-05 |
| **RF-13** | El sistema debe permitir a la usuaria eliminar una prenda que no esté Entregada, siempre que no sea la única prenda de la orden. | Should | OE-01 | RN-06, RN-15, RN-16 | F-02 |
| **RF-14** | El sistema debe mostrar el detalle de una orden: cliente, número, fechas de recepción, entrega acordada, orden lista y entrega real; prendas con su estado y fotos; valor, pagos, saldo pendiente, estado de avance, estado de pago y avisos. | Must | OE-01 a OE-06 | RN-18, RN-22, RN-23, RN-26, RN-27, RN-29, RN-41 | F-01 |
| **RF-15** | El sistema debe permitir a la usuaria listar las órdenes filtrando por estado de avance y buscar una orden por su número. | Must | OE-01 | RN-08, RN-18 | F-01 |
| **RF-16** | El sistema debe permitir a la usuaria elegir el tipo de prenda de la lista del negocio, que inicia con pantalón, camisa, blusa, vestido, falda y chaqueta, o escribir uno nuevo con la opción «Otro». | Must | OE-01 | RN-10, RN-43 | F-01 · F-05 |
| **RF-17** | El sistema debe permitir a la usuaria renombrar y desactivar los tipos de prenda de su negocio, incluidos los que se agregaron con «Otro». | Could | OE-01 | RN-01, RN-10, RN-43 | ADR-002 · F-05 |

## Identificación de prendas

| Código | Requisito | Prioridad | Objetivo | Reglas | Fuente |
| --- | --- | --- | --- | --- | --- |
| **RF-18** | El sistema debe permitir a la usuaria tomar una foto con la cámara del teléfono o elegirla de la galería y asociarla a una prenda, al registrarla o después. | Must | OE-06 | RN-17 | F-05 |
| **RF-19** | El sistema debe mostrar juntas las fotos de todas las prendas de una orden y permitir ampliar cada una. | Must | OE-06 | RN-17 | F-05 |
| **RF-20** | El sistema debe permitir a la usuaria eliminar una foto de una prenda. | Should | OE-06 | RN-17 | F-01 |

## Estados

| Código | Requisito | Prioridad | Objetivo | Reglas | Fuente |
| --- | --- | --- | --- | --- | --- |
| **RF-21** | El sistema debe permitir a la usuaria cambiar el estado de una prenda, ofreciendo solo los cambios permitidos por las reglas. | Must | OE-02 | RN-12, RN-13, RN-14, RN-15, RN-24 | F-01 · F-05 |
| **RF-22** | El sistema debe calcular y mostrar automáticamente el estado de avance de cada orden, y registrar la fecha en que quedó lista. | Must | OE-02 | RN-18, RN-19, RN-22 | F-02 |
| **RF-23** | El sistema debe permitir a la usuaria entregar una orden, total o parcialmente, registrando la fecha de entrega. | Must | OE-02 | RN-20, RN-23 | F-01 |
| **RF-24** | El sistema debe pedir confirmación, mostrando el saldo pendiente, antes de entregar una orden que tenga saldo. | Must | OE-04 | RN-21 | F-05 |
| **RF-25** | El sistema debe permitir a la usuaria cancelar una orden que no esté Entregada, previa confirmación. | Must | OE-02 | RN-24 | F-01 |
| **RF-41** | El sistema debe permitir a la usuaria devolver al cliente sin arreglar una prenda Pendiente o En proceso, previa confirmación, dejando de contar su precio en el valor de la orden. | Should | OE-02 · OE-04 | RN-12, RN-16, RN-26, RN-44 | F-05 |
| **RF-42** | El sistema debe permitir a la usuaria entrar con su cuenta de Google, si el correo de esa cuenta ya está registrado en el sistema. El inicio de sesión con usuario y contraseña sigue disponible. | Should | Todos | RN-01, RN-45 | F-05 |

## Pagos

| Código | Requisito | Prioridad | Objetivo | Reglas | Fuente |
| --- | --- | --- | --- | --- | --- |
| **RF-26** | El sistema debe permitir a la usuaria registrar un pago a una orden con su valor y su método. La fecha del pago es la del registro. | Must | OE-04 | RN-25, RN-28, RN-30 | F-01 · F-05 |
| **RF-27** | El sistema debe permitir a la usuaria registrar un abono inicial al crear la orden. | Should | OE-04 | RN-25, RN-28 | F-01 |
| **RF-28** | El sistema debe mostrar en cada orden su valor, sus pagos, el saldo pendiente calculado y el estado de pago. | Must | OE-04 | RN-26, RN-27, RN-29 | F-02 |
| **RF-29** | El sistema debe permitir a la usuaria anular un pago indicando el motivo, y mostrar los pagos anulados diferenciados de los válidos. | Must | OE-04 | RN-31 | F-02 |
| **RF-30** | El sistema debe listar las órdenes con saldo pendiente, de la mayor deuda a la menor, con el total por cobrar del negocio. | Should | OE-04 | RN-29, RN-32 | F-02 |
| **RF-31** | El sistema debe mostrar el dinero recibido hoy, en la semana, en el mes o en un rango de fechas elegido. | Should | OE-04 | RN-09, RN-33 | F-01 |

## Avisos

| Código | Requisito | Prioridad | Objetivo | Reglas | Fuente |
| --- | --- | --- | --- | --- | --- |
| **RF-32** | El sistema debe generar automáticamente el aviso al cliente cuando su orden quede Lista para entregar. | Must | OE-03 | RN-37, RN-38 | F-01 · ADR-003 |
| **RF-33** | El sistema debe enviar el aviso por la API oficial de WhatsApp, en segundo plano y con reintentos, cuando el negocio la tenga configurada. | Must | OE-03 | RN-40, RN-42 | F-05 · ADR-003 |
| **RF-34** | El sistema debe mostrar los avisos pendientes de envío asistido y, al elegir uno, abrir WhatsApp con el celular del cliente y el mensaje redactado, registrando el envío. | Must | OE-03 | RN-40, RN-41, RN-42 | ADR-003 |
| **RF-35** | El sistema debe descartar, sin enviarlo, el aviso de una orden que ya no esté Lista para entregar al momento del envío. | Must | OE-03 | RN-39 | F-02 |
| **RF-36** | El sistema debe mostrar los avisos de una orden con su fecha y hora, canal, mensaje y resultado. | Must | OE-03 | RN-41 | F-01 |

## Seguimiento

| Código | Requisito | Prioridad | Objetivo | Reglas | Fuente |
| --- | --- | --- | --- | --- | --- |
| **RF-37** | El sistema debe mostrar al entrar un panel con el total por cobrar, la cantidad de órdenes atrasadas, la cantidad de órdenes y prendas sin reclamar y la cantidad de avisos pendientes de envío asistido. | Must | OE-03 · OE-04 · OE-05 | RN-32, RN-34, RN-35, RN-40 | F-01 |
| **RF-38** | El sistema debe listar las órdenes atrasadas con su cliente, número, fecha de entrega acordada y días de atraso (días calendario desde esa fecha hasta hoy), de la más atrasada a la menos. | Must | OE-05 | RN-09, RN-34 | F-01 |
| **RF-39** | El sistema debe listar las órdenes sin reclamar con su cliente, número, días de espera y prendas sin reclamar, de la de mayor espera a la de menor. | Must | OE-05 | RN-35, RN-36 | F-05 |
| **RF-40** | El sistema debe permitir a la usuaria cambiar el número de días a partir del cual una orden lista se considera sin reclamar, entre 1 y 365. | Could | OE-05 | RN-35 | F-05 |

---

## Relación con la especificación original

La especificación de la versión 1 (F-01) tenía 62 requisitos funcionales. Los que no aparecen aquí quedaron fuera por el criterio del [alcance](../01-problema/alcance.md): no nacen de una causa del árbol de problemas o no caben en esta entrega. La relación completa entre problema, objetivos, requisitos e historias de usuario estará en la matriz de trazabilidad.
