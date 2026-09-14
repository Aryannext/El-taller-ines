# Reglas de negocio

**Estado:** borrador · Sprint 1 · se valida con el instructor (F-04)

## Cómo están escritas

- **Una regla es una sola afirmación** sobre el negocio. No describe pantallas ni tecnología: debe seguir siendo cierta aunque el sistema se rehiciera con otra herramienta.
- **Cada regla tiene un tipo:**

| Tipo | Qué expresa | Ejemplo |
| --- | --- | --- |
| **Estructural** | Qué es cada cosa y cómo se relaciona con las demás | Una orden pertenece a un solo cliente |
| **Restricción** | Lo que el negocio no permite | No se acepta un abono mayor que el saldo |
| **Derivación** | Un dato o estado que se calcula, no se escribe | El saldo es el valor menos los pagos válidos |
| **Desencadenador** | Algo que ocurre solo cuando pasa otra cosa | Al quedar lista una orden se genera su aviso |

- **Cada regla dice de dónde nace:** el medio o la causa del [árbol de objetivos](../01-problema/arbol-de-objetivos.md) y la [fuente](fuentes-de-requisitos.md). Las que vienen de la especificación original citan su código anterior (por ejemplo, `v1 RN-06`).
- **Cada regla trae un ejemplo con datos concretos.** Cada ejemplo se convertirá en al menos una prueba automática.

**Resumen:** 42 reglas · 5 estructurales · 23 restricciones · 10 derivaciones · 4 desencadenadores.

## Glosario

Los términos tienen el mismo significado en todos los documentos, el código y las pantallas.

| Término | Significado |
| --- | --- |
| **Negocio** | Taller que usa el sistema. En esta entrega hay uno solo ([ADR-002](../03-diseno/adr/ADR-002-un-taller-preparado-para-varios.md)) |
| **Usuaria** | Persona del negocio que usa el sistema; hoy, la dueña |
| **Cliente** | Persona que lleva prendas a arreglar |
| **Orden** | Conjunto de prendas que un cliente deja en una misma visita; en el taller, lo que va en una bolsa |
| **Número de orden** | Identificador corto de la orden dentro del negocio (por ejemplo, #0042) que se escribe a mano en la bolsa |
| **Prenda** | Pieza de ropa de una orden, con su tipo, la descripción del arreglo y su precio |
| **Valor de la orden** | Suma de los precios de sus prendas |
| **Pago** | Dinero que el cliente entrega por una orden, sea el total o una parte (abono) |
| **Pago anulado** | Pago registrado por error que deja de contar, pero se conserva con su motivo |
| **Saldo pendiente** | Lo que el cliente todavía debe de una orden |
| **Orden atrasada** | Orden cuya fecha de entrega acordada ya pasó sin que el trabajo esté terminado |
| **Orden sin reclamar** | Orden terminada que el cliente no ha recogido después del plazo definido |
| **Aviso** | Mensaje al cliente para informarle que su orden está lista |
| **Canal de aviso** | Medio por el que sale el aviso: automático por la API oficial de WhatsApp o asistido ([ADR-003](../03-diseno/adr/ADR-003-canal-de-avisos-whatsapp.md)) |

---

## Negocio

### RN-01 · La información pertenece a un negocio

Todo cliente, orden, prenda, pago y aviso pertenece a un negocio, y una usuaria solo consulta y modifica la información del negocio al que pertenece.

**Tipo:** Restricción · **Origen:** ADR-002 · idea de negocio

**Ejemplo:** El negocio A tiene la cliente Marta. Una usuaria del negocio B busca "Marta" y no obtiene resultados.

## Clientes

### RN-02 · Datos mínimos de un cliente

Un cliente debe tener nombre y teléfono.

**Tipo:** Restricción · **Origen:** M-01 · F-01 (v1 RN-01)

**Ejemplo:** Se intenta registrar a "Luis Pardo" sin teléfono: no se guarda y se indica que el teléfono es obligatorio.

### RN-03 · El teléfono debe poder recibir WhatsApp

El teléfono del cliente debe ser un celular colombiano: 10 dígitos que empiezan por 3. Es el número al que llegan los avisos.

**Tipo:** Restricción · **Origen:** M-03 · ADR-003 · F-05

**Ejemplo:** `3104567890` se acepta. `6014567890` (fijo) y `310456789` (9 dígitos) se rechazan.

### RN-04 · El teléfono no es único

Varios clientes pueden tener el mismo teléfono.

**Tipo:** Estructural · **Origen:** F-01 (v1 RN-02)

**Ejemplo:** Una madre y su hija se registran como clientes distintas con el mismo celular.

## Órdenes

### RN-05 · Una orden, un cliente, una visita

Una orden pertenece a un solo cliente y agrupa las prendas que ese cliente deja en una misma visita.

**Tipo:** Estructural · **Origen:** M-01, M-06 · F-01 (v1 RN-03) · F-05

**Ejemplo:** Marta deja un pantalón y dos camisas el lunes: es una orden con tres prendas. Si vuelve el jueves con un vestido, es otra orden.

### RN-06 · Una orden tiene al menos una prenda

Una orden no se registra sin prendas: la orden y sus prendas se registran juntas.

**Tipo:** Restricción · **Origen:** M-01.1 · F-02 · modifica v1 RN-04

**Ejemplo:** Se intenta guardar una orden para Marta sin agregar prendas: no se guarda.

> En la versión 1 se permitían órdenes vacías, que aparecían como activas y atrasadas sin trabajo real y obligaron a excepciones en todos los cálculos.

### RN-07 · La entrega no puede ser antes de la recepción

La fecha de entrega acordada no puede ser anterior a la fecha en que se recibe la orden. Puede ser el mismo día.

**Tipo:** Restricción · **Origen:** M-01.1 · F-01 (v1 RN-05)

**Ejemplo:** Orden recibida el 14 de septiembre: entrega el 13 se rechaza; entrega el 14 o el 20 se acepta.

### RN-08 · Número de orden

Cada orden recibe un número consecutivo dentro de su negocio, que se muestra con al menos cuatro dígitos y nunca se reutiliza, aunque la orden se cancele.

**Tipo:** Derivación · **Origen:** M-06 · F-05

**Ejemplo:** La última orden del negocio es #0041 y está cancelada. La siguiente es #0042.

### RN-09 · Las fechas se interpretan en hora de Colombia

Todas las fechas y el "hoy" que usan las reglas se interpretan en la zona horaria de Colombia.

**Tipo:** Restricción · **Origen:** M-05 · F-02

**Ejemplo:** Una orden con entrega el 15 de septiembre se consulta el 14 a las 10:00 p. m.: no está atrasada.

> En la versión 1 las fechas se interpretaban en UTC y se mostraban un día antes.

## Prendas

### RN-10 · Datos obligatorios de una prenda

Una prenda debe tener tipo de prenda, descripción del arreglo y precio desde que se registra.

**Tipo:** Restricción · **Origen:** M-01.1 · F-01 (v1 RN-19, RN-24) · F-05

**Ejemplo:** Pantalón, "subir basta 3 cm", $15.000: se guarda. Pantalón sin descripción: no se guarda.

### RN-11 · El precio es un valor entero en pesos

El precio de una prenda es un número entero de pesos colombianos mayor que cero.

**Tipo:** Restricción · **Origen:** M-04.1 · F-01 (v1 RN-20) · F-02

**Ejemplo:** $15.000 se acepta. $0 y $15.000,50 se rechazan.

> En la versión 1 el dinero se guardaba como número decimal de coma flotante, que puede redondear mal.

### RN-12 · Estados de una prenda

Una prenda está siempre en uno solo de estos estados: **Pendiente**, **En proceso**, **Terminada** o **Entregada**. Toda prenda nueva empieza en Pendiente.

**Tipo:** Estructural · **Origen:** M-02 · F-01 (v1 RN-21)

**Ejemplo:** Se registra una camisa: queda Pendiente. No puede estar Terminada y Entregada a la vez.

### RN-13 · Solo se entrega lo terminado

Una prenda solo puede pasar a Entregada si está Terminada.

**Tipo:** Restricción · **Origen:** M-02.1 · F-02

**Ejemplo:** Un vestido En proceso no se puede marcar como Entregado.

### RN-14 · Un retoque devuelve la prenda a En proceso

Una prenda Terminada puede volver a En proceso cuando, al medírsela el cliente, le falta algo al arreglo.

**Tipo:** Restricción · **Origen:** M-02 · F-05

**Ejemplo:** Marta se mide el pantalón Terminado y la basta quedó larga: el pantalón vuelve a En proceso.

### RN-15 · Una prenda entregada no se modifica

Una prenda Entregada no se edita, no se elimina y no cambia de estado.

**Tipo:** Restricción · **Origen:** M-04 · F-02

**Ejemplo:** Se intenta cambiar el precio de una camisa ya entregada: no se permite.

### RN-16 · Lo pagado no puede quedar por encima del valor

No se puede bajar el precio de una prenda ni eliminarla si con eso el valor de la orden queda por debajo de lo ya pagado.

**Tipo:** Restricción · **Origen:** M-04.1 · F-02

**Ejemplo:** Orden de $30.000 con $25.000 pagados. Eliminar una prenda de $10.000 dejaría el valor en $20.000: no se permite. Primero hay que anular el pago que sobra.

### RN-17 · Fotos de una prenda

Una prenda puede tener de cero a tres fotos. Tomarlas es opcional: el sistema lo sugiere al registrar la prenda, pero no impide guardarla sin foto. Cada foto pertenece a una sola prenda, y eliminar una foto no elimina la prenda.

**Tipo:** Restricción · **Origen:** M-06.1 · F-01 (v1 RN-22, RN-23, RN-40) · F-05

**Ejemplo:** Se registra un vestido con prisa y sin foto: se guarda. Después se le toman dos fotos. Se intenta agregar una cuarta cuando ya tiene tres: no se permite. Se borra una: el vestido sigue registrado con las otras.

## Estado de la orden

### RN-18 · El estado de la orden se calcula de sus prendas

El estado de avance de una orden no se escribe: se obtiene del estado de sus prendas.

| Estado de la orden | Condición |
| --- | --- |
| **En proceso** | Al menos una prenda está Pendiente o En proceso |
| **Lista para entregar** | Ninguna prenda está Pendiente ni En proceso, y al menos una está Terminada |
| **Entregada** | Todas las prendas están Entregadas |
| **Cancelada** | La usuaria canceló la orden (RN-24); no depende de las prendas |

**Tipo:** Derivación · **Origen:** M-02, M-02.1 · C-02.1 · F-01 (v1 RN-06, RN-09, RN-10, RN-17) · F-02

**Ejemplo:**

| Prendas | Estado de la orden |
| --- | --- |
| Terminada, En proceso, Pendiente | En proceso |
| Terminada, Terminada, Terminada | Lista para entregar |
| Entregada, Terminada | Lista para entregar |
| Entregada, Entregada | Entregada |

### RN-19 · El estado de avance no se cambia a mano

Ninguna usuaria cambia directamente el estado de avance de una orden. La orden cambia cuando cambian sus prendas, o por las acciones de entregar (RN-20) y cancelar (RN-24).

**Tipo:** Restricción · **Origen:** M-02.1 · F-02

**Ejemplo:** No existe la acción "marcar orden como lista": la orden queda lista cuando se termina su última prenda pendiente.

> En la versión 1 el estado se podía mover a mano y quedaban órdenes listas con prendas sin terminar.

### RN-20 · Entregar la orden

Entregar una orden marca como Entregadas todas sus prendas Terminadas. Si alguna prenda sigue Pendiente o En proceso, la entrega es parcial y la orden conserva el estado que le corresponde por RN-18.

**Tipo:** Desencadenador · **Origen:** M-02 · F-01 (v1 RN-07, RN-08, RN-10)

**Ejemplo:** Orden con camisa Terminada y vestido En proceso. Marta recoge la camisa: la camisa queda Entregada y la orden sigue En proceso.

### RN-21 · Entregar con saldo pendiente

Se puede entregar una orden con saldo pendiente, pero la usuaria debe confirmarlo después de ver cuánto se debe.

**Tipo:** Restricción · **Origen:** M-04 · FN-02 · F-02 · F-05

**Ejemplo:** Orden con saldo de $12.000. Al entregar, el sistema muestra "Marta debe $12.000. ¿Entregar de todos modos?" y solo entrega si la usuaria confirma.

### RN-22 · Fecha en que la orden quedó lista

Cuando una orden pasa a Lista para entregar se registra la fecha y hora. Si vuelve a En proceso, esa fecha se borra. Si se entrega, se conserva.

**Tipo:** Desencadenador · **Origen:** M-05 · F-02

**Ejemplo:** La orden queda lista el 10 de septiembre a las 4:00 p. m. El 12 una prenda vuelve a En proceso: la fecha se borra. El 13 vuelve a quedar lista: la nueva fecha es el 13.

> Es la base para saber cuántos días lleva una orden sin reclamar (RN-35). En la versión 1 se medía desde la fecha de entrega acordada, que no es lo mismo.

### RN-23 · Fecha de entrega real

Cuando una orden pasa a Entregada se registra la fecha y hora de la entrega.

**Tipo:** Desencadenador · **Origen:** FN-01 · F-01 (v1 RN-36)

**Ejemplo:** Marta recoge su última prenda el 15 de septiembre a las 11:20 a. m.: esa es la fecha de entrega de la orden.

### RN-24 · Cancelar una orden

Solo se puede cancelar una orden que no esté Entregada. Una orden cancelada no admite prendas nuevas, pagos nuevos ni cambios de estado de sus prendas, conserva los pagos que ya tenía y no se puede reabrir.

**Tipo:** Restricción · **Origen:** M-02 · F-01 (v1 RN-11, RN-12, RN-13, RN-14)

**Ejemplo:** Luis desiste de su orden En proceso, que tiene un abono de $5.000: se cancela y el abono sigue registrado. Luego se intenta registrarle otro pago: no se permite.

## Pagos y saldo

### RN-25 · Datos de un pago

Un pago pertenece a una orden y tiene valor, fecha y método de pago. Su valor es un número entero de pesos mayor que cero. El método se elige entre los que usa el negocio; el taller usa **efectivo** y **Nequi**.

**Tipo:** Estructural · **Origen:** M-04 · F-01 (v1 RN-25, RN-26) · F-05

> El Nequi es la cuenta personal de la dueña, no una cuenta de negocio. El sistema solo registra que el pago fue por Nequi; no se conecta con Nequi ni confirma transferencias.

**Ejemplo:** Abono de $10.000 en efectivo el 14 de septiembre a la orden #0042: se acepta. Un pago de $0 se rechaza.

### RN-26 · Valor de la orden

El valor de una orden es la suma de los precios de sus prendas. No se escribe a mano.

**Tipo:** Derivación · **Origen:** M-04.1 · F-02

**Ejemplo:** Pantalón $15.000 + camisa $8.000 + camisa $8.000 = valor de la orden $31.000.

### RN-27 · Saldo pendiente

El saldo pendiente de una orden es su valor menos la suma de sus pagos no anulados. Siempre se calcula; ninguna usuaria lo escribe ni lo ajusta a mano.

**Tipo:** Derivación · **Origen:** M-04.1 · C-04.1 · F-02

**Ejemplo:** Valor $31.000. Pagos: $10.000 y $5.000, este último anulado. Saldo pendiente: $31.000 − $10.000 = $21.000.

### RN-28 · El abono no puede superar el saldo

Un pago no puede ser mayor que el saldo pendiente de la orden en el momento de registrarlo.

**Tipo:** Restricción · **Origen:** M-04 · F-01 (v1 RN-27, RN-29) · F-02

**Ejemplo:** Saldo pendiente $21.000. Un abono de $25.000 se rechaza; uno de $21.000 se acepta y la orden queda Pagada.

> En la versión 1, tocar dos veces el botón de guardar registraba el pago dos veces y dejaba el saldo negativo. Esta regla se comprueba al momento de guardar, no con el saldo que mostraba la pantalla.

### RN-29 · Estado de pago

Una orden está **Pagada** si su saldo pendiente es cero, y **Por cobrar** si es mayor que cero. El estado de pago es independiente del estado de avance.

**Tipo:** Derivación · **Origen:** M-04 · FN-03 · F-01 (v1 RN-28) · F-02

**Ejemplo:** Una orden puede estar Entregada y Por cobrar, o En proceso y Pagada.

### RN-30 · Pagos después de entregar

Una orden Entregada puede seguir recibiendo pagos mientras tenga saldo pendiente. Una orden Cancelada no recibe pagos.

**Tipo:** Restricción · **Origen:** M-04 · F-01 (v1 RN-30, RN-13)

**Ejemplo:** Marta se llevó su orden debiendo $12.000 y vuelve a la semana a pagarlos: el pago se registra y la orden queda Pagada.

### RN-31 · Un pago no se borra: se anula

Un pago registrado no se elimina. Si fue un error, se anula indicando el motivo; el pago anulado deja de contar en el saldo y se conserva con la fecha y el motivo de la anulación.

**Tipo:** Restricción · **Origen:** M-04 · FN-03 · F-02

**Ejemplo:** Se registró por error un abono de $50.000 en vez de $5.000. Se anula con el motivo "valor mal digitado" y se registra el de $5.000. Ambos quedan visibles; solo el segundo cuenta.

### RN-32 · Total por cobrar del negocio

El total por cobrar es la suma de los saldos pendientes de todas las órdenes no canceladas del negocio, incluidas las entregadas.

**Tipo:** Derivación · **Origen:** M-04 · FN-03 · OE-04

**Ejemplo:** Orden #0040 entregada con saldo $12.000, orden #0042 en proceso con saldo $21.000 y orden #0041 cancelada con saldo $8.000: total por cobrar $33.000.

### RN-33 · Dinero recibido en un período

El dinero recibido en un período es la suma de los pagos no anulados cuya fecha está dentro de ese período.

**Tipo:** Derivación · **Origen:** FN-03 · M-04

**Ejemplo:** En septiembre hay pagos de $10.000, $21.000 y $50.000 (anulado): lo recibido en septiembre es $31.000.

## Seguimiento

### RN-34 · Orden atrasada

Una orden está atrasada cuando está En proceso y su fecha de entrega acordada es anterior a hoy.

**Tipo:** Derivación · **Origen:** M-05 · C-05 · E-01

**Ejemplo:** Hoy es 16 de septiembre. La orden #0042, En proceso con entrega el 15, está atrasada. La #0043, Lista para entregar con entrega el 15, no está atrasada: el trabajo está hecho y lo que falta es que el cliente la recoja.

### RN-35 · Orden sin reclamar

Una orden está sin reclamar cuando lleva más de un número de días en Lista para entregar, contados desde la fecha en que quedó lista (RN-22). Cada negocio define ese número; si no lo cambia, son 30 días. Las prendas Terminadas de esas órdenes son las prendas sin reclamar.

**Tipo:** Derivación · **Origen:** M-05 · C-05 · E-04 · F-01 (v1 RN-37) · F-05

**Ejemplo:** Con un plazo de 30 días, una orden que quedó lista el 1 de agosto está sin reclamar el 1 de septiembre. Tiene dos camisas Terminadas: son dos prendas sin reclamar.

### RN-36 · Días de espera

Los días de espera de una orden lista son los días calendario transcurridos desde la fecha en que quedó lista hasta hoy.

**Tipo:** Derivación · **Origen:** M-05 · FN-04

**Ejemplo:** Quedó lista el 1 de septiembre a las 5:00 p. m. El 16 de septiembre lleva 15 días de espera.

## Avisos

### RN-37 · Al quedar lista la orden se genera su aviso

Cuando una orden pasa a Lista para entregar se genera automáticamente un aviso para su cliente.

**Tipo:** Desencadenador · **Origen:** M-03 · C-03 · F-01 (v1 RN-31) · ADR-003

**Ejemplo:** Se marca Terminado el último pantalón de la orden #0042: sin ninguna otra acción, se genera el aviso a Marta.

### RN-38 · Un solo aviso por cada vez que la orden queda lista

Por cada vez que una orden entra en Lista para entregar se envía como máximo un aviso de orden lista, aunque haya reintentos o toques repetidos.

**Tipo:** Restricción · **Origen:** M-03 · F-01 (modifica v1 RN-33)

**Ejemplo:** El envío falla y se reintenta tres veces hasta salir: Marta recibe un solo mensaje. Si una prenda vuelve a En proceso y la orden queda lista otra vez, sí se genera un aviso nuevo.

### RN-39 · No se avisa una orden que ya no está lista

Si en el momento de enviar el aviso la orden ya no está Lista para entregar, el aviso no se envía y queda registrado como descartado.

**Tipo:** Restricción · **Origen:** M-03 · F-02

**Ejemplo:** El aviso queda en cola y, antes de salir, Marta se mide el pantalón y vuelve a En proceso: el aviso se descarta.

> En la versión 1 se podía enviar el aviso de "orden lista" con la orden en cualquier estado.

### RN-40 · Canal del aviso

El aviso se envía primero por el canal automático. Si el negocio no lo tiene configurado, o el envío sigue fallando después de los reintentos, el aviso queda disponible para envío asistido.

**Tipo:** Restricción · **Origen:** M-03 · ADR-003

**Ejemplo:** El negocio no ha configurado la API oficial: el aviso a Marta aparece listo para enviarse con un toque desde el WhatsApp de la usuaria.

### RN-41 · Constancia de cada aviso

Cada aviso registra la fecha y hora, el canal, el mensaje y el resultado: enviado, pendiente de envío asistido o descartado.

**Tipo:** Estructural · **Origen:** M-03 · OE-03 · F-01 (v1 RN-32)

**Ejemplo:** Aviso a Marta · 14 sep 2026, 3:12 p. m. · API oficial · "Hola Marta, tu orden #0042 está lista…" · enviado.

### RN-42 · El aviso usa los datos del momento del envío

El contenido del aviso se arma con los datos de la orden en el momento de enviarlo: número de orden, cantidad de prendas listas y saldo pendiente.

**Tipo:** Restricción · **Origen:** M-03 · F-01 (modifica v1 RN-34) · F-02

**Ejemplo:** El aviso se generó con saldo de $21.000, pero Marta abonó $10.000 antes de que saliera: el mensaje dice que debe $11.000.

---

## Decisiones confirmadas

| Regla | Decisión | Fecha · fuente |
| --- | --- | --- |
| RN-03 | Solo se aceptan celulares colombianos; no hay clientes con número de otro país ni solo con teléfono fijo | 13 sep 2026 · F-05 |
| RN-17 | Fotos opcionales, máximo 3 por prenda | 13 sep 2026 · F-05 |
| RN-21 | Se puede entregar una orden con saldo pendiente, con confirmación | 13 sep 2026 · F-05 |
| RN-25 | Métodos de pago del taller: efectivo y Nequi (cuenta personal de la dueña) | 13 sep 2026 · F-05 |
| RN-35 | Una orden lista se considera sin reclamar a los 30 días, configurable por negocio | 13 sep 2026 · F-05 |

## Qué pasó con las reglas de la versión 1

La especificación original (F-01) tenía 40 reglas. Ninguna se pierde sin explicación.

| v1 | Decisión | En v2 | Motivo |
| --- | --- | --- | --- |
| RN-01 | Reutilizada | RN-02 | |
| RN-02 | Reutilizada | RN-04 | |
| RN-03 | Reutilizada | RN-05 | |
| RN-04 | Modificada | RN-06 | Las órdenes sin prendas generaban excepciones en todos los cálculos (F-02) |
| RN-05 | Reutilizada | RN-07 | |
| RN-06 | Reutilizada | RN-18 | |
| RN-07 | Reutilizada | RN-20 | |
| RN-08 | Reutilizada | RN-20 | |
| RN-09 | Reutilizada | RN-18 | |
| RN-10 | Reutilizada | RN-18, RN-20 | |
| RN-11 | Reutilizada | RN-24 | |
| RN-12 | Reutilizada | RN-24 | |
| RN-13 | Reutilizada | RN-24, RN-30 | |
| RN-14 | Reutilizada | RN-24 | |
| RN-15 | Descartada | — | Reabrir una orden entregada no nace de ninguna causa; los retoques antes de entregar los cubre RN-14 |
| RN-16 | Descartada | — | Depende de RN-15 |
| RN-17 | Reutilizada | RN-18 | |
| RN-18 | Reutilizada | RN-05, RN-10 | |
| RN-19 | Reutilizada | RN-10 | |
| RN-20 | Modificada | RN-11 | Se precisa que el precio es un entero en pesos |
| RN-21 | Reutilizada | RN-12 | |
| RN-22 | Reutilizada | RN-17 | |
| RN-23 | Reutilizada | RN-17 | |
| RN-24 | Reutilizada | RN-10 | |
| RN-25 | Reutilizada | RN-25 | |
| RN-26 | Reutilizada | RN-25 | |
| RN-27 | Modificada | RN-28 | Se compara contra el saldo al momento de guardar, que ya descuenta los pagos anulados |
| RN-28 | Reutilizada | RN-29 | |
| RN-29 | Reutilizada | RN-16, RN-28 | El saldo no queda negativo porque ninguna operación lo permite |
| RN-30 | Reutilizada | RN-30 | |
| RN-31 | Reutilizada | RN-37 | |
| RN-32 | Reutilizada | RN-41 | |
| RN-33 | Modificada | RN-38 | Los recordatorios diarios quedan fuera del alcance; se evita duplicar el aviso de orden lista |
| RN-34 | Modificada | RN-42 | El resumen por Telegram quedó fuera del alcance; el principio se aplica al aviso |
| RN-35 | Descartada | — | El historial de toda modificación respondía al efecto E-05, que se descartó. Se conserva el rastro de pagos anulados (RN-31) y de avisos (RN-41) |
| RN-36 | Reutilizada | RN-23 | |
| RN-37 | Modificada | RN-35 | Se mide desde la fecha en que la orden quedó lista, y el plazo lo define el negocio |
| RN-38 | Aplazada | — | Las órdenes próximas a vencer no están en el alcance de esta entrega; el seguimiento cubre atrasadas y sin reclamar |
| RN-39 | Descartada | — | Las notas adicionales por prenda quedaron fuera del alcance |
| RN-40 | Reutilizada | RN-17 | |
