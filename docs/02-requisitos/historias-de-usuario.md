# Historias de usuario

**Estado:** borrador · Sprint 1 · se validan con el instructor (F-04)

## Cómo están escritas

Cada historia describe una necesidad desde quien la vive, no desde el sistema:

> **Como** [actor], **quiero** [qué], **para** [por qué le sirve].

Y además trae:

| Parte | Qué contiene |
| --- | --- |
| **Nació de** | La causa o el efecto del [árbol de problemas](../01-problema/arbol-de-problemas.md) y la [fuente](fuentes-de-requisitos.md) donde se conoció, con la situación real del taller |
| **Requisitos y reglas** | Los [requisitos funcionales](requisitos-funcionales.md), las [reglas de negocio](reglas-de-negocio.md) y los [requisitos no funcionales](requisitos-no-funcionales.md) que la historia hace cumplir |
| **Prioridad** | MoSCoW, la más alta de sus requisitos funcionales |
| **Puntos** | Estimación del esfuerzo relativo |
| **Criterios de aceptación** | Situaciones concretas en formato **Dado / Cuando / Entonces**. La historia está terminada cuando todos se cumplen, y cada criterio se convierte en al menos una prueba automática |

Los ejemplos usan los mismos datos que las reglas de negocio: la cliente Marta Rincón (celular 3104567890), la orden #0042 y sus prendas.

### Actores

| Actor | Quién es |
| --- | --- |
| **Dueña del taller** | La usuaria del sistema: registra, cobra, entrega y hace seguimiento |
| **Cliente del taller** | Deja prendas a arreglar y recibe los avisos; no usa el sistema directamente |

### Criterios INVEST

Cada historia se revisó contra estos criterios; la que no los cumplía se dividió:

| Letra | Criterio | Ejemplo en este documento |
| --- | --- | --- |
| **I** | Independiente | Buscar un cliente (HU-04) no depende de consultar su ficha (HU-05) |
| **N** | Negociable | HU-10 y HU-24 son Should: se pueden aplazar sin romper el flujo |
| **V** | Valiosa | Cada historia nace de una causa del árbol de problemas |
| **E** | Estimable | Todas tienen puntos |
| **S** | Pequeña (*small*) | Ninguna supera 5 puntos; el registro de la orden se dividió en HU-07, HU-08 y HU-09 |
| **T** | Verificable (*testable*) | Todas tienen criterios con datos concretos |

### Estimación

- **Escala:** puntos de historia con la serie de Fibonacci (1, 2, 3, 5, 8). Miden esfuerzo, complejidad e incertidumbre relativos, no horas.
- **Historia de referencia:** HU-06 · Corregir los datos de un cliente = **1 punto**. Las demás se estimaron comparándolas con ella.
- **Quién estimó:** el aprendiz, solo. Al ser un proyecto individual no hubo *planning poker* con un equipo; se declara así.
- **Revisión:** al cerrar el Sprint 3 se mide la velocidad real (puntos terminados) y se reestima lo que falta.

### Definición de "lista para desarrollar"

Una historia entra a un sprint de desarrollo cuando tiene: formato completo, origen, requisitos y reglas, criterios de aceptación verificables, estimación y su mockup, verificado contra sus criterios de aceptación con `generar_mockups.mjs` (Sprint 2).

## Resumen

| Épica | Historias | Must | Should | Could | Puntos |
| --- | --- | --- | --- | --- | --- |
| **EP-01 · Acceso** | HU-01 a HU-02 | 2 | — | — | 5 |
| **EP-02 · Clientes** | HU-03 a HU-06 | 4 | — | — | 8 |
| **EP-03 · Órdenes y prendas** | HU-07 a HU-16 | 6 | 3 | 1 | 24 |
| **EP-04 · Identificación de prendas** | HU-17 a HU-19 | 2 | 1 | — | 6 |
| **EP-05 · Estados y entrega** | HU-20 a HU-22 y HU-36 | 3 | 1 | — | 12 |
| **EP-06 · Pagos** | HU-23 a HU-27 | 2 | 3 | — | 11 |
| **EP-07 · Avisos** | HU-28 a HU-31 | 4 | — | — | 11 |
| **EP-08 · Seguimiento** | HU-32 a HU-35 | 3 | — | 1 | 8 |
| **Total** | **36** | **26** | **8** | **2** | **85** |

**Puntos por prioridad:** Must 66 · Should 16 · Could 3.

> **Riesgo de capacidad.** Los 66 puntos Must se desarrollan en los Sprints 3 y 4 (12 días). Aún no se conoce la velocidad real. Si al cerrar el Sprint 3 no alcanza, se recorta primero lo Could, después lo Should y, si aun así no alcanza, se renegocia el alcance con el instructor antes de sacrificar pruebas o documentación.

---

## EP-01 · Acceso

### HU-01 · Iniciar y cerrar sesión

> **Como** dueña del taller, **quiero** entrar al sistema con mi usuario y contraseña y poder cerrar la sesión, **para** que nadie más vea la información de mis clientes ni de mis cobros.

**Nació de:** C-01. La información que hoy vive solo en la memoria de la dueña (F-05) pasa a un sistema en internet (ADR-001), así que hay que protegerla. Fuente: F-01.

**Requisitos:** RF-01, RF-02 · **Reglas:** RN-01 · **Calidad:** RNF-20, RNF-21

**Prioridad:** Must · **Puntos:** 3

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-01.1** Acceso correcto | que tengo un usuario con contraseña válida | inicio sesión con esos datos | entro al panel del día de mi negocio |
| **CA-01.2** Datos incorrectos | que escribo mal la contraseña | intento iniciar sesión | veo "Usuario o contraseña incorrectos", sin que diga cuál de los dos falló, y no entro |
| **CA-01.3** Intentos repetidos | que fallé 5 veces en el último minuto | lo intento por sexta vez | el sistema me pide esperar antes de volver a intentarlo, aunque esta vez la contraseña sea correcta |
| **CA-01.4** Cerrar sesión | que tengo la sesión iniciada | cierro la sesión y uso el botón Atrás del navegador | veo la pantalla de inicio de sesión y ningún dato del taller |
| **CA-01.5** Sesión abandonada | que dejé la sesión abierta sin usarla durante más de 8 horas | vuelvo a usar el sistema | me pide iniciar sesión de nuevo |

### HU-02 · Cambiar mi contraseña

> **Como** dueña del taller, **quiero** cambiar mi contraseña, **para** protegerme si alguien más llegó a conocerla.

**Nació de:** C-01. La información que hoy vive en la memoria de la dueña pasa a un sistema en internet y hay que protegerla (F-05, ADR-001). La especificación original incluía el cambio de contraseña (F-01).

**Requisitos:** RF-03 · **Reglas:** — · **Calidad:** RNF-19

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-02.1** Cambio correcto | que conozco mi contraseña actual | escribo la actual y una nueva de al menos 8 caracteres dos veces igual | la contraseña cambia y la siguiente vez entro con la nueva |
| **CA-02.2** Contraseña actual incorrecta | que escribo mal la contraseña actual | intento cambiarla | no cambia y veo que la contraseña actual no es correcta |
| **CA-02.3** Contraseña corta | que la nueva contraseña tiene 6 caracteres | intento guardarla | no cambia y veo que debe tener al menos 8 caracteres |
| **CA-02.4** No coinciden | que la confirmación es distinta de la nueva contraseña | intento guardarla | no cambia y veo que las dos contraseñas no coinciden |

## EP-02 · Clientes

### HU-03 · Registrar un cliente

> **Como** dueña del taller, **quiero** registrar a un cliente con su nombre y su celular, **para** tener anotado quién me deja cada prenda y poder avisarle por WhatsApp.

**Nació de:** C-01.1. Hoy no se anota nada: ni cuaderno, ni recibo, ni ningún registro (F-05). El celular es el destino de los avisos (M-03).

**Requisitos:** RF-04 · **Reglas:** RN-02, RN-03, RN-04

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-03.1** Registro correcto | que Marta Rincón no está registrada | la registro con el celular 3104567890 | queda registrada y veo su ficha |
| **CA-03.2** Sin celular | que Marta Rincón no está registrada | intento registrarla sin celular | no se guarda y el campo del celular indica que es obligatorio |
| **CA-03.3** Número fijo | que Marta Rincón no está registrada | escribo el número 6014567890 | no se guarda y veo "Escribe un celular colombiano de 10 dígitos que empiece por 3" |
| **CA-03.4** Celular compartido | que Marta está registrada con el 3104567890 | registro a su hija Laura con el mismo celular | Laura queda registrada como otra cliente |

### HU-04 · Buscar un cliente

> **Como** dueña del taller, **quiero** encontrar a un cliente escribiendo parte de su nombre o su celular, **para** atenderlo rápido cuando llega al taller.

**Nació de:** C-01.2. No hay dónde consultar qué prendas hay, de quién son ni cuánto deben (F-01).

**Requisitos:** RF-05 · **Reglas:** RN-01

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-04.1** Por nombre, sin tildes | que está registrada "María Gómez" | busco "maria" | aparece María Gómez |
| **CA-04.2** Por celular | que Marta está registrada con el 3104567890 | busco 3104567890 | aparece Marta Rincón |
| **CA-04.3** Sin resultados | que no hay clientes llamados Pedro | busco "pedro" | veo que no hay resultados y la opción de registrar un cliente nuevo |
| **CA-04.4** Solo mi negocio | que otro negocio tiene una cliente llamada Marta | busco "marta" | solo aparecen las clientes de mi negocio |

### HU-05 · Consultar la ficha de un cliente

> **Como** dueña del taller, **quiero** ver en un solo lugar las órdenes de un cliente y cuánto me debe, **para** responderle al instante sin hacer cuentas de memoria.

**Nació de:** C-04. Hoy la dueña recuerda de memoria cuánto le debe cada cliente (F-05). También C-01.2.

**Requisitos:** RF-06 · **Reglas:** RN-27, RN-29

**Prioridad:** Must · **Puntos:** 3

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-05.1** Órdenes y deuda | que Marta tiene la orden #0040 Entregada con saldo de $12.000 y la #0042 En proceso con saldo de $21.000 | abro su ficha | veo sus datos, las dos órdenes con número, fecha de recepción, estado de avance y estado de pago, y que debe $33.000 en total |
| **CA-05.2** Las canceladas no suman | que Marta también tiene la orden #0041 Cancelada con saldo de $8.000 | abro su ficha | la #0041 aparece como Cancelada y el total que debe sigue siendo $33.000 |
| **CA-05.3** Cliente sin órdenes | que Laura no tiene órdenes | abro su ficha | veo que no tiene órdenes y que no debe nada |

### HU-06 · Corregir los datos de un cliente

> **Como** dueña del taller, **quiero** corregir el nombre o el celular de un cliente, **para** que los avisos le lleguen al número correcto.

**Nació de:** C-03. Hoy no se le avisa al cliente cuando su prenda está lista, y si su celular está mal registrado el aviso tampoco le llegará (M-03). Fuente: F-01.

**Requisitos:** RF-07 · **Reglas:** RN-02, RN-03

**Prioridad:** Must · **Puntos:** 1 · **Historia de referencia para estimar**

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-06.1** Nuevo celular | que Marta cambió de celular | lo corrijo por 3157654321 | se guarda y los avisos siguientes le llegan a ese número |
| **CA-06.2** Nombre vacío | que estoy corrigiendo a Marta | borro su nombre e intento guardar | no se guarda y veo que el nombre es obligatorio |
| **CA-06.3** Celular incompleto | que estoy corrigiendo a Marta | escribo un celular de 9 dígitos | no se guarda y veo cómo debe ser el celular |

## EP-03 · Órdenes y prendas

### HU-07 · Registrar una orden con sus prendas

> **Como** dueña del taller, **quiero** registrar en un solo paso la orden de un cliente con todas las prendas que trae, **para** que lo que dejó cada cliente no dependa de mi memoria.

**Nació de:** C-01 y C-01.1: hoy nada se anota (F-05). Y C-06.1: un cliente trae de 3 a 5 prendas y la dueña olvida cuáles son suyas (F-05).

**Requisitos:** RF-08, RF-16 · **Reglas:** RN-05, RN-06, RN-07, RN-10, RN-11, RN-12, RN-18, RN-26 · **Calidad:** RNF-13

**Prioridad:** Must · **Puntos:** 5

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-07.1** Orden completa | que Marta está registrada y hoy es 14 de septiembre | registro una orden con entrega el 20 de septiembre, un pantalón "subir basta 3 cm" de $15.000 y dos camisas "entallar" de $8.000 cada una | la orden queda En proceso con valor de $31.000 y sus tres prendas en Pendiente |
| **CA-07.2** Sin prendas | que estoy registrando una orden para Marta | intento guardarla sin prendas | no se guarda y veo que debe tener al menos una prenda |
| **CA-07.3** Entrega antes de hoy | que hoy es 14 de septiembre | pongo como entrega el 13 de septiembre | no se guarda y veo que la entrega no puede ser antes de la fecha de recepción |
| **CA-07.4** Entrega el mismo día | que hoy es 14 de septiembre | pongo como entrega el 14 de septiembre | la orden se guarda |
| **CA-07.5** Una prenda inválida | que registro tres prendas y una camisa tiene precio $0 | intento guardar | no se guarda ninguna parte de la orden y la camisa indica que el precio debe ser mayor que cero |
| **CA-07.6** Lista de tipos | que estoy agregando una prenda | abro la lista de tipos | veo pantalón, camisa, blusa, vestido, falda, chaqueta y la opción «Otro» |

### HU-08 · Obtener el número de la orden para marcar la bolsa

> **Como** dueña del taller, **quiero** ver bien grande el número de la orden al guardarla, **para** escribirlo en la bolsa donde meto las prendas del cliente.

**Nació de:** C-06. Las prendas por arreglar y las arregladas se guardan juntas en un rincón, sin identificar, y no hay presupuesto para una impresora de etiquetas (F-05).

**Requisitos:** RF-09 · **Reglas:** RN-08

**Prioridad:** Must · **Puntos:** 1

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-08.1** Número destacado | que la última orden del negocio es la #0041 | guardo una orden nueva | veo destacado "#0042" con la indicación de escribirlo en la bolsa |
| **CA-08.2** No se reutiliza | que la #0041 fue cancelada | guardo una orden nueva | su número es #0042, no #0041 |
| **CA-08.3** Siempre visible | que existe la orden #0042 | la abro otro día | el número aparece en la parte superior |

### HU-09 · Escribir un tipo de prenda que no está en la lista

> **Como** dueña del taller, **quiero** escribir el tipo de prenda cuando no está en la lista, **para** registrar cualquier prenda que me traigan.

**Nació de:** C-01.1. Para que ninguna prenda quede sin registrar, cualquier tipo de prenda debe poder anotarse: al elegir «Otro», se escribe el tipo (F-05).

**Requisitos:** RF-16 · **Reglas:** RN-10, RN-43

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-09.1** Tipo nuevo | que "Overol" no está en la lista | elijo «Otro» y escribo "Overol" | la prenda queda como Overol y, en la siguiente prenda, Overol aparece en la lista |
| **CA-09.2** Tipo repetido | que "Overol" ya está en la lista | elijo «Otro» y escribo "overol" | se usa el tipo Overol existente y la lista no lo muestra dos veces |
| **CA-09.3** «Otro» vacío | que elegí «Otro» | intento guardar sin escribir el tipo | no se guarda y veo que debo escribir el tipo de prenda |

### HU-10 · Registrar un cliente nuevo mientras registro su orden

> **Como** dueña del taller, **quiero** registrar a un cliente nuevo sin salir de la orden que estoy llenando, **para** no perder lo que ya escribí mientras el cliente espera.

**Nació de:** C-01. La orden se registra con el cliente enfrente, y que sea nuevo no debe obligar a empezar de cero. La especificación original permitía crearlo desde el formulario de la orden (F-01).

**Requisitos:** RF-10 · **Reglas:** RN-02, RN-03

**Prioridad:** Should · **Puntos:** 3

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-10.1** Cliente nuevo | que estoy llenando una orden con dos prendas y el cliente no está registrado | registro a Luis Pardo con el celular 3001112233 desde la orden | vuelvo a la orden con Luis seleccionado y las dos prendas siguen escritas |
| **CA-10.2** Datos inválidos | que estoy registrando al cliente nuevo desde la orden | escribo un celular inválido | el cliente no se registra y sigo en la orden con lo que ya había escrito |

### HU-11 · Agregar una prenda a una orden que ya existe

> **Como** dueña del taller, **quiero** agregar a una orden una prenda que olvidé registrar al recibirla, **para** no tener que crear otra orden.

**Nació de:** C-01.1. Una prenda que no quedó anotada vuelve a depender de la memoria. Estaba en la especificación original (F-01); la versión 1 permitía agregar prendas a órdenes entregadas, que quedaban entregadas con trabajo pendiente (F-02).

**Requisitos:** RF-11 · **Reglas:** RN-10, RN-11, RN-18, RN-22, RN-24

**Prioridad:** Should · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-11.1** Orden lista | que la #0042 está Lista para entregar | le agrego una falda "subir ruedo" de $10.000 | la falda queda Pendiente, la orden vuelve a En proceso, su valor sube $10.000 y se borra la fecha en que había quedado lista |
| **CA-11.2** Orden entregada | que la #0040 está Entregada | intento agregarle una prenda | no se permite |
| **CA-11.3** Orden cancelada | que la #0041 está Cancelada | intento agregarle una prenda | no se permite |

### HU-12 · Corregir la descripción o el precio de una prenda

> **Como** dueña del taller, **quiero** corregir la descripción o el precio de una prenda, **para** arreglar un error sin rehacer la orden.

**Nació de:** C-01.1 y C-04.1. La descripción se escribe al registrar la prenda y a veces hay que corregirla (F-05); en la versión 1, bajar un precio por debajo de lo pagado dejaba el saldo negativo (F-02).

**Requisitos:** RF-12 · **Reglas:** RN-11, RN-15, RN-16, RN-26, RN-27

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-12.1** Subir el precio | que la #0042 vale $31.000, no tiene pagos y su pantalón cuesta $15.000 | cambio el precio del pantalón a $18.000 | el valor de la orden y su saldo quedan en $34.000 |
| **CA-12.2** Por debajo de lo pagado | que la #0042 vale $31.000 y tiene $25.000 pagados | intento bajar el pantalón a $5.000 | no se guarda y veo que lo pagado superaría el valor de la orden y que primero debo anular el pago que sobra |
| **CA-12.3** Prenda entregada | que una camisa de la #0040 está Entregada | intento editarla | no se permite |

### HU-13 · Eliminar una prenda registrada por error

> **Como** dueña del taller, **quiero** eliminar una prenda que registré por error, **para** que la orden refleje lo que el cliente realmente dejó.

**Nació de:** C-04.1. Un saldo que se descuadra deja de ser confiable: en la versión 1, eliminar una prenda podía dejar el saldo negativo o una orden vacía (F-02).

**Requisitos:** RF-13 · **Reglas:** RN-06, RN-15, RN-16 · **Calidad:** RNF-10

**Prioridad:** Should · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-13.1** Eliminación correcta | que la #0042 tiene un pantalón de $15.000 y dos camisas de $8.000, sin pagos | elimino una camisa y lo confirmo | la orden queda con dos prendas y un valor de $23.000 |
| **CA-13.2** Me arrepiento | que pedí eliminar una camisa | respondo que no en la confirmación | nada cambia |
| **CA-13.3** Única prenda | que la orden tiene una sola prenda | intento eliminarla | no se permite y el sistema sugiere cancelar la orden |
| **CA-13.4** Prenda entregada | que la prenda está Entregada | intento eliminarla | no se permite |

### HU-14 · Consultar el detalle de una orden

> **Como** dueña del taller, **quiero** ver en una sola pantalla todo lo de una orden, **para** saber en qué va, qué fotos tiene y cuánto se debe sin buscar en varios lugares.

**Nació de:** C-01.2, no hay dónde consultar (F-01), y C-02, no se sabe en qué va cada prenda (F-01).

**Requisitos:** RF-14 · **Reglas:** RN-01, RN-18, RN-22, RN-23, RN-26, RN-27, RN-29, RN-41 · **Calidad:** RNF-22

**Prioridad:** Must · **Puntos:** 3

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-14.1** Orden en proceso | que la #0042 de Marta tiene una prenda Terminada, una En proceso y una Pendiente, un abono de $10.000 y entrega el 20 de septiembre | abro la orden | veo el cliente, el número, la fecha de recepción y la de entrega acordada, las tres prendas con su estado y sus fotos, el valor de $31.000, el abono, el saldo de $21.000, el estado de avance En proceso y el estado de pago Por cobrar |
| **CA-14.2** Orden entregada | que la #0040 quedó lista y ya se entregó | abro la orden | veo además la fecha en que quedó lista, la fecha de entrega real y los avisos enviados |
| **CA-14.3** Orden de otro negocio | que conozco el enlace de una orden de otro negocio | intento abrirla | el sistema responde como si la orden no existiera |

### HU-15 · Listar y buscar órdenes

> **Como** dueña del taller, **quiero** ver mis órdenes por estado y encontrar una por el número escrito en la bolsa, **para** ubicar rápido las prendas de un cliente.

**Nació de:** C-06, prendas juntas sin identificar, con el número escrito en la bolsa como solución (F-05), y C-02.

**Requisitos:** RF-15 · **Reglas:** RN-08, RN-18

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-15.1** Filtrar por estado | que hay órdenes En proceso, Listas para entregar y Entregadas | filtro por Lista para entregar | solo veo las órdenes listas |
| **CA-15.2** Buscar por número | que existe la #0042 | busco "42" o "#0042" | se abre la orden #0042 |
| **CA-15.3** Número inexistente | que no existe la orden #9999 | busco "#9999" | veo que no hay una orden con ese número |

### HU-16 · Renombrar o desactivar tipos de prenda

> **Como** dueña del taller, **quiero** renombrar o dejar de usar un tipo de prenda, **para** mantener ordenada la lista.

**Nació de:** C-01.1. La lista de tipos con la que se registra cada prenda es propia de cada negocio (ADR-002) y crece con lo que se escribe en «Otro» (RN-43, F-05).

**Requisitos:** RF-17 · **Reglas:** RN-01, RN-10, RN-43

**Prioridad:** Could · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-16.1** Renombrar | que el tipo "Overol" tiene 2 prendas | lo renombro como "Enterizo" | las dos prendas muestran Enterizo |
| **CA-16.2** Desactivar | que el tipo "Chaqueta" tiene prendas registradas | lo desactivo | ya no aparece al registrar prendas nuevas y las prendas que ya eran chaqueta lo conservan |

## EP-04 · Identificación de prendas

### HU-17 · Tomar fotos de las prendas

> **Como** dueña del taller, **quiero** tomarle foto a cada prenda cuando la recibo, **para** reconocer después cuáles son de cada cliente aunque estén todas en el mismo rincón.

**Nació de:** C-06 y C-06.1. Las prendas se guardan juntas en un rincón y la dueña olvida cuáles son de quién; sin presupuesto para etiquetas, la foto sirve para identificarlas (F-05).

**Requisitos:** RF-18 · **Reglas:** RN-17 · **Calidad:** RNF-03

**Prioridad:** Must · **Puntos:** 3

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-17.1** Con la cámara | que estoy registrando un vestido desde el celular | le tomo una foto con la cámara | la foto queda asociada al vestido |
| **CA-17.2** Desde la galería | que estoy registrando un vestido | elijo una foto de la galería | la foto queda asociada al vestido |
| **CA-17.3** Máximo tres | que el vestido ya tiene 3 fotos | intento agregar una cuarta | no se permite |
| **CA-17.4** Sin foto | que tengo prisa | guardo el vestido sin foto | se guarda y el sistema me sugiere tomarle una foto |
| **CA-17.5** Foto pesada | que la foto pesa 5 MB | la agrego | se guarda reducida a 1.600 px en su lado mayor y a no más de 400 KB |

### HU-18 · Ver las fotos de una orden para reconocer las prendas

> **Como** dueña del taller, **quiero** ver juntas las fotos de todas las prendas de una orden, **para** sacar del rincón las prendas correctas cuando el cliente viene a recogerlas.

**Nació de:** E-06. No se sabe con certeza qué prendas son de cada cliente (F-05).

**Requisitos:** RF-19 · **Reglas:** RN-17 · **Calidad:** RNF-25

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-18.1** Fotos agrupadas | que la #0042 tiene un pantalón con 2 fotos y una camisa con 1 | abro las fotos de la orden | veo las 3 fotos agrupadas por prenda, con su tipo y su descripción |
| **CA-18.2** Ampliar | que estoy viendo las fotos de la orden | toco una foto | se ve ampliada |
| **CA-18.3** Fotos privadas | que alguien sin sesión tiene el enlace de una foto | lo abre | no ve la foto |

### HU-19 · Eliminar una foto

> **Como** dueña del taller, **quiero** borrar una foto borrosa o equivocada, **para** que solo queden fotos que sirvan para reconocer la prenda.

**Nació de:** C-06. Las fotos existen para reconocer las prendas guardadas juntas en un rincón (F-05); una foto equivocada no cumple ese propósito. Estaba en la especificación original (F-01).

**Requisitos:** RF-20 · **Reglas:** RN-17 · **Calidad:** RNF-10

**Prioridad:** Should · **Puntos:** 1

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-19.1** Libera espacio para otra | que el vestido tiene 3 fotos | elimino una y lo confirmo | quedan 2 y puedo agregar otra |
| **CA-19.2** La prenda se conserva | que el vestido tiene 1 foto | la elimino y lo confirmo | el vestido sigue registrado, sin fotos |

## EP-05 · Estados y entrega

### HU-20 · Actualizar el estado de una prenda

> **Como** dueña del taller, **quiero** marcar en qué va cada prenda, **para** saber qué me falta y que la orden quede lista sola cuando termine todo.

**Nació de:** C-02, no se lleva el estado de avance (F-01), y C-02.1, en la versión 1 una orden quedaba lista con prendas sin terminar (F-02). El retoque al medirse la prenda sale de F-05.

**Requisitos:** RF-21, RF-22 · **Reglas:** RN-12, RN-13, RN-14, RN-15, RN-18, RN-19, RN-22, RN-24

**Prioridad:** Must · **Puntos:** 5

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-20.1** Avanzar | que un pantalón está Pendiente | lo marco En proceso | queda En proceso |
| **CA-20.2** Cambio no permitido | que un vestido está En proceso | reviso los estados a los que puede pasar | la opción Entregada no aparece |
| **CA-20.3** La orden queda lista sola | que la #0042 tiene dos prendas Terminadas y una En proceso | marco Terminada la tercera | la orden pasa a Lista para entregar y se registra la fecha y hora en que quedó lista |
| **CA-20.4** Retoque | que la #0042 está Lista y a Marta le quedó larga la basta del pantalón al medírselo | marco el pantalón En proceso | la orden vuelve a En proceso y se borra la fecha en que había quedado lista |
| **CA-20.5** Sin cambio manual | que abro cualquier orden | busco cómo cambiar directamente su estado de avance | esa opción no existe |
| **CA-20.6** Orden cancelada | que la #0041 está Cancelada | intento cambiar el estado de una de sus prendas | no se permite |

### HU-21 · Entregar la orden al cliente

> **Como** dueña del taller, **quiero** registrar la entrega cuando el cliente recoge sus prendas, **para** saber qué ya salió del taller y qué se quedó debiendo.

**Nació de:** E-02, prendas terminadas o entregadas sin cobrarse correctamente (F-01). Se puede entregar debiendo, confirmándolo (F-05).

**Requisitos:** RF-23, RF-24 · **Reglas:** RN-20, RN-21, RN-23, RN-29

**Prioridad:** Must · **Puntos:** 3

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-21.1** Entrega completa | que la #0042 tiene sus 3 prendas Terminadas y saldo de $0 | la entrego | las 3 prendas quedan Entregadas y la orden queda Entregada con la fecha y hora de entrega |
| **CA-21.2** Entrega parcial | que una orden tiene una camisa Terminada y un vestido En proceso | la entrego | la camisa queda Entregada y la orden sigue En proceso |
| **CA-21.3** Aviso de saldo | que la #0042 tiene todo Terminado y saldo de $12.000 | la entrego | veo "Marta debe $12.000. ¿Entregar de todos modos?" |
| **CA-21.4** Entrego debiendo | que estoy viendo el aviso de saldo | confirmo la entrega | la orden queda Entregada y Por cobrar, con saldo de $12.000 |
| **CA-21.5** No entrego | que estoy viendo el aviso de saldo | no confirmo | nada cambia |

### HU-22 · Cancelar una orden

> **Como** dueña del taller, **quiero** cancelar la orden de un cliente que desiste, **para** que no aparezca como trabajo pendiente ni como deuda.

**Nació de:** C-02 y C-04. Si un cliente desiste, su orden seguiría contando como trabajo pendiente y como deuda. Fuente: F-01 (v1 RN-11 a RN-14).

**Requisitos:** RF-25 · **Reglas:** RN-08, RN-24, RN-32 · **Calidad:** RNF-10

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-22.1** Cancelación | que la #0043 de Luis está En proceso con un abono de $5.000 | la cancelo y lo confirmo | queda Cancelada, el abono sigue registrado y la orden no suma al total por cobrar |
| **CA-22.2** Orden entregada | que la #0040 está Entregada | intento cancelarla | no se permite |
| **CA-22.3** Orden ya cancelada | que la #0043 está Cancelada | intento registrarle un pago o agregarle una prenda | no se permite |

### HU-36 · Devolver una prenda sin arreglar

> **Como** dueña del taller, **quiero** registrar que el cliente se llevó una prenda sin arreglar, **para** no cobrarle ese arreglo y seguir con las demás prendas de la orden.

**Nació de:** E-01. A veces el cliente llega en la fecha acordada y la prenda no está terminada porque se olvidó; si el arreglo no es rápido, el cliente vuelve otro día o se lleva la prenda sin arreglar (F-05). Sin esta historia, el saldo le cobraría un arreglo que no se hizo (C-04.1).

**Requisitos:** RF-41 · **Reglas:** RN-12, RN-16, RN-26, RN-44 · **Calidad:** RNF-10

**Prioridad:** Should · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-36.1** Devolución | que la #0042 vale $31.000, sin pagos, con un pantalón Terminado de $15.000 y dos camisas Pendientes de $8.000 | devuelvo una camisa sin arreglar y lo confirmo | la camisa queda Devuelta, el valor y el saldo quedan en $23.000 y la orden sigue En proceso |
| **CA-36.2** Me arrepiento | que pedí devolver una camisa sin arreglar | respondo que no en la confirmación | nada cambia |
| **CA-36.3** Prenda terminada | que el pantalón de la #0042 está Terminado | reviso sus opciones | la opción de devolver sin arreglar no aparece |
| **CA-36.4** Única prenda por resolver | que la #0043 tiene una sola prenda y está Pendiente | intento devolverla sin arreglar | no se permite y el sistema sugiere cancelar la orden |
| **CA-36.5** Lo pagado supera el valor | que la #0042 vale $31.000 y tiene $30.000 pagados | intento devolver una camisa de $8.000 | no se permite y veo que primero debo anular el pago que sobra |

## EP-06 · Pagos

### HU-23 · Registrar un pago o abono

> **Como** dueña del taller, **quiero** anotar cada pago o abono que me hace un cliente, **para** saber exactamente cuánto me debe sin llevarlo de memoria.

**Nació de:** C-04. La dueña recuerda de memoria cuánto le deben (F-05). C-04.1: en la versión 1, un saldo sumado y restado a mano se descuadró (F-02).

**Requisitos:** RF-26, RF-28 · **Reglas:** RN-25, RN-26, RN-27, RN-28, RN-29, RN-30 · **Calidad:** RNF-14

**Prioridad:** Must · **Puntos:** 3

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-23.1** Abono | que la #0042 vale $31.000 y no tiene pagos | registro $10.000 en efectivo | el saldo queda en $21.000 y el estado de pago es Por cobrar |
| **CA-23.2** Pago por Nequi | que la #0042 tiene saldo de $21.000 | registro $21.000 por Nequi | el saldo queda en $0 y la orden queda Pagada |
| **CA-23.3** Supera el saldo | que la #0042 tiene saldo de $21.000 | registro $25.000 | no se guarda y veo "El pago no puede superar el saldo pendiente de $21.000" |
| **CA-23.4** Valor cero | que la #0042 tiene saldo de $21.000 | registro $0 | no se guarda y veo que el valor debe ser mayor que cero |
| **CA-23.5** Doble toque | que la #0042 tiene saldo de $21.000 | toco guardar dos veces seguidas un pago de $10.000 | se registra un solo pago y el saldo queda en $11.000 |
| **CA-23.6** Después de entregar | que la #0040 está Entregada con saldo de $12.000 | registro $12.000 | se guarda y la orden queda Pagada |
| **CA-23.7** Orden cancelada | que la #0043 está Cancelada | intento registrarle un pago | no se permite |

### HU-24 · Registrar un abono al recibir la orden

> **Como** dueña del taller, **quiero** anotar el abono que me dan al dejar la ropa en el mismo registro de la orden, **para** no hacer dos pasos con el cliente enfrente.

**Nació de:** C-04. Lo que cada cliente debe, incluido lo que abonó al dejar la ropa, hoy se lleva de memoria (F-05). El abono inicial estaba en la especificación original (F-01).

**Requisitos:** RF-27 · **Reglas:** RN-25, RN-28 · **Calidad:** RNF-13

**Prioridad:** Should · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-24.1** Abono inicial | que estoy registrando una orden de $31.000 | agrego un abono inicial de $10.000 en efectivo y guardo | la orden queda guardada con un pago de $10.000 y saldo de $21.000 |
| **CA-24.2** Abono mayor que la orden | que estoy registrando una orden de $31.000 | agrego un abono inicial de $40.000 y guardo | no se guarda ni la orden ni el pago, y veo que el abono no puede superar $31.000 |

### HU-25 · Anular un pago mal registrado

> **Como** dueña del taller, **quiero** anular un pago que registré mal, **para** corregir el saldo sin perder el rastro del dinero.

**Nació de:** C-04.1 y E-03. Un saldo corregido a mano se descuadra y deja de saberse cuánto se ha recibido (F-01); en la versión 1, un pago equivocado no se podía corregir sin perder su rastro (F-02).

**Requisitos:** RF-29 · **Reglas:** RN-27, RN-31 · **Calidad:** RNF-10

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-25.1** Anulación | que la #0042 vale $31.000 y tiene un abono de $15.000 que en realidad era de $5.000 | lo anulo con el motivo "valor mal digitado" | deja de contar, el saldo vuelve a $31.000 y el pago sigue visible como anulado, con la fecha y el motivo |
| **CA-25.2** Sin motivo | que voy a anular un pago | intento hacerlo sin escribir el motivo | no se anula y veo que el motivo es obligatorio |
| **CA-25.3** No se borra | que un pago ya está anulado | busco cómo anularlo otra vez o eliminarlo | esas opciones no existen |

### HU-26 · Ver quién me debe

> **Como** dueña del taller, **quiero** ver las órdenes que me deben, de la mayor deuda a la menor, **para** saber a quién cobrarle primero.

**Nació de:** E-03, no se sabe cuánto falta por cobrar (F-01), y C-04.

**Requisitos:** RF-30 · **Reglas:** RN-29, RN-32

**Prioridad:** Should · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-26.1** Lista y total | que la #0040 está Entregada con saldo de $12.000, la #0042 En proceso con saldo de $21.000 y la #0041 Cancelada con saldo de $8.000 | abro la lista de órdenes por cobrar | veo primero la #0042 y después la #0040, con un total por cobrar de $33.000 |
| **CA-26.2** Las pagadas no aparecen | que una orden está Pagada | abro la lista de órdenes por cobrar | esa orden no aparece |

### HU-27 · Ver cuánto dinero he recibido

> **Como** dueña del taller, **quiero** saber cuánto dinero recibí hoy, esta semana o este mes, **para** tener claras mis cuentas.

**Nació de:** E-03 y E-03.1, confusión en la información financiera y decisiones sin información confiable (F-01).

**Requisitos:** RF-31 · **Reglas:** RN-09, RN-33

**Prioridad:** Should · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-27.1** Mes | que en septiembre hay pagos de $10.000, $21.000 y $15.000, este último anulado | consulto lo recibido en septiembre | veo $31.000 |
| **CA-27.2** Pago de noche | que registré un pago de $8.000 el 14 de septiembre a las 11:30 p. m. | consulto lo recibido el 14 de septiembre | el total incluye esos $8.000 |
| **CA-27.3** Rango | que hay pagos en distintos días de septiembre | elijo del 1 al 15 de septiembre | veo lo recibido solo en ese rango |

## EP-07 · Avisos

### HU-28 · Recibir un aviso cuando mi ropa está lista

> **Como** cliente del taller, **quiero** recibir un mensaje de WhatsApp cuando mi ropa esté lista, **para** ir a recogerla sin tener que llamar a preguntar.

**Nació de:** C-03, la comunicación es informal y no se avisa cuando la prenda está lista (F-01), y E-01.1, clientes insatisfechos. El aviso debe salir solo: "la idea es hacer la vida más fácil y automatizar" (F-05, ADR-003).

**Requisitos:** RF-32, RF-33 · **Reglas:** RN-37, RN-38, RN-40, RN-41, RN-42 · **Calidad:** RNF-04, RNF-17

**Prioridad:** Must · **Puntos:** 5

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-28.1** Sale solo | que el negocio tiene configurada la API oficial y a la #0042 de Marta le falta una prenda | la dueña marca Terminada esa última prenda | sin ninguna otra acción, Marta recibe un WhatsApp con el número de orden, la cantidad de prendas listas y su saldo, y el aviso queda registrado como enviado por la API oficial |
| **CA-28.2** No hace esperar | que WhatsApp tarda en responder | la dueña marca Terminada la última prenda | la pantalla responde en menos de 1 segundo |
| **CA-28.3** Datos del momento | que el aviso está en espera con saldo de $21.000 y Marta abona $10.000 antes de que salga | se envía el aviso | el mensaje dice que debe $11.000 |
| **CA-28.4** Reintentos sin duplicar | que la API falla dos veces y a la tercera responde | se reintenta el envío | Marta recibe un solo mensaje |
| **CA-28.5** Falla persistente | que la API falla en los 3 reintentos | termina el último intento | el aviso queda pendiente de envío asistido |

### HU-29 · Enviar con un toque los avisos pendientes

> **Como** dueña del taller, **quiero** enviar con un toque los avisos que no salieron solos, **para** que ningún cliente se quede sin saber que su ropa está lista.

**Nació de:** el respaldo definido en ADR-003 cuando no hay API configurada o esta falla, y C-03.

**Requisitos:** RF-34 · **Reglas:** RN-40, RN-41, RN-42

**Prioridad:** Must · **Puntos:** 3

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-29.1** Aviso pendiente | que el negocio no tiene configurada la API y la #0042 queda Lista | abro los avisos pendientes | veo el aviso a Marta con el mensaje ya redactado |
| **CA-29.2** Abrir WhatsApp | que estoy viendo el aviso a Marta | toco enviar | se abre WhatsApp con el celular 3104567890 y el mensaje escrito |
| **CA-29.3** Confirmo el envío | que abrí WhatsApp desde el aviso | vuelvo al sistema y confirmo que lo envié | el aviso queda registrado como enviado por envío asistido y sale de los pendientes |
| **CA-29.4** No lo envié | que abrí WhatsApp desde el aviso | vuelvo al sistema sin confirmar | el aviso sigue pendiente |

### HU-30 · No avisar una orden que ya no está lista

> **Como** dueña del taller, **quiero** que no se le avise a un cliente cuya orden dejó de estar lista, **para** no hacerlo venir por ropa que todavía no está.

**Nació de:** C-03 y C-02.1. El aviso solo sirve si la orden de verdad está lista: la versión 1 permitía enviarlo con la orden en cualquier estado (F-02), y una prenda terminada puede volver a En proceso al medírsela el cliente (F-05).

**Requisitos:** RF-35 · **Reglas:** RN-38, RN-39

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-30.1** Aviso en espera | que el aviso de la #0042 está en espera y el pantalón vuelve a En proceso | llega el momento de enviarlo | no se envía y queda registrado como descartado |
| **CA-30.2** Aviso asistido pendiente | que el aviso de la #0042 está pendiente de envío asistido y el pantalón vuelve a En proceso | abro los avisos pendientes | ese aviso ya no aparece |
| **CA-30.3** Vuelve a quedar lista | que el aviso de la #0042 se descartó | la orden vuelve a quedar Lista para entregar | se genera un aviso nuevo |

### HU-31 · Consultar los avisos de una orden

> **Como** dueña del taller, **quiero** ver qué avisos se enviaron de una orden, **para** responder con seguridad si un cliente dice que no le avisaron.

**Nació de:** C-03. La comunicación con el cliente es informal y no deja constancia de si se le avisó; la especificación original pedía un historial de notificaciones (F-01).

**Requisitos:** RF-36 · **Reglas:** RN-41

**Prioridad:** Must · **Puntos:** 1

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-31.1** Historial de avisos | que la #0042 tiene un aviso descartado y otro enviado | abro sus avisos | veo los dos con fecha y hora, canal, mensaje y resultado |

## EP-08 · Seguimiento

### HU-32 · Ver el panel del día

> **Como** dueña del taller, **quiero** ver al entrar lo que necesita mi atención, **para** no olvidar entregas, cobros ni prendas que nadie ha recogido.

**Nació de:** C-05, no hay seguimiento de vencidas ni sin reclamar (F-01), y E-01, olvidos y retrasos. Hoy "no se sabe cuántas" prendas esperan (F-05).

**Requisitos:** RF-37 · **Reglas:** RN-32, RN-34, RN-35, RN-40

**Prioridad:** Must · **Puntos:** 3

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-32.1** Cifras del día | que hay $33.000 por cobrar, 2 órdenes atrasadas, 1 orden sin reclamar con 2 prendas y 1 aviso pendiente | entro al sistema | veo esas cuatro cifras en el panel |
| **CA-32.2** Ir al detalle | que estoy en el panel | toco las órdenes atrasadas | veo la lista de órdenes atrasadas |
| **CA-32.3** Todo al día | que no hay nada atrasado, sin reclamar ni pendiente de aviso | entro al sistema | esos indicadores aparecen en cero |

### HU-33 · Ver las órdenes atrasadas

> **Como** dueña del taller, **quiero** ver las órdenes que pasaron su fecha de entrega sin terminarse, **para** priorizar ese trabajo.

**Nació de:** E-01, olvidos y retrasos en las entregas (F-01), y C-05.

**Requisitos:** RF-38 · **Reglas:** RN-09, RN-34

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-33.1** Más atrasada primero | que hoy es 16 de septiembre, la #0042 está En proceso con entrega el 15 y la #0044 En proceso con entrega el 12 | abro las órdenes atrasadas | veo la #0044 con 4 días de atraso y después la #0042 con 1 día |
| **CA-33.2** Lista no es atrasada | que la #0043 está Lista para entregar con entrega el 15 de septiembre | abro las órdenes atrasadas | la #0043 no aparece |
| **CA-33.3** Hora de Colombia | que son las 10:00 p. m. del 14 de septiembre y la #0045 está En proceso con entrega el 15 | abro las órdenes atrasadas | la #0045 no aparece |

### HU-34 · Ver las órdenes sin reclamar

> **Como** dueña del taller, **quiero** ver qué órdenes llevan más tiempo listas sin que las recojan, **para** contactar a esos clientes y cobrar ese trabajo.

**Nació de:** E-04. Hay prendas que no se recogen durante dos meses o más, o nunca, y no se sabe cuántas (F-05).

**Requisitos:** RF-39 · **Reglas:** RN-35, RN-36

**Prioridad:** Must · **Puntos:** 2

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-34.1** Orden sin reclamar | que el plazo es de 30 días, hoy es 16 de septiembre y la #0030 quedó lista el 1 de agosto con 2 camisas Terminadas | abro las órdenes sin reclamar | veo la #0030 con 46 días de espera y 2 prendas sin reclamar |
| **CA-34.2** Dentro del plazo | que la #0042 quedó lista el 1 de septiembre | abro las órdenes sin reclamar | la #0042 no aparece, porque lleva 15 días |
| **CA-34.3** Mayor espera primero | que la #0030 lleva 46 días de espera y la #0025 lleva 60 | abro las órdenes sin reclamar | veo primero la #0025 |

### HU-35 · Cambiar el plazo para considerar una orden sin reclamar

> **Como** dueña del taller, **quiero** ajustar después de cuántos días una orden lista se considera sin reclamar, **para** que la alerta se adapte a mis clientes.

**Nació de:** C-05. El seguimiento de las prendas sin reclamar necesita un plazo; se decidió usar 30 días por defecto, configurable por negocio (F-05).

**Requisitos:** RF-40 · **Reglas:** RN-35

**Prioridad:** Could · **Puntos:** 1

| Criterio | Dado | Cuando | Entonces |
| --- | --- | --- | --- |
| **CA-35.1** Nuevo plazo | que el plazo es de 30 días y la #0042 lleva 45 días de espera | cambio el plazo a 60 días | la #0042 deja de aparecer entre las sin reclamar |
| **CA-35.2** Fuera de rango | que estoy cambiando el plazo | escribo 0 o 400 | no se guarda y veo que debe estar entre 1 y 365 días |

---

## Cobertura de requisitos funcionales

Todo requisito funcional está en al menos una historia.

| Requisito | Historia | Requisito | Historia | Requisito | Historia | Requisito | Historia |
| --- | --- | --- | --- | --- | --- | --- | --- |
| RF-01 | HU-01 | RF-11 | HU-11 | RF-21 | HU-20 | RF-31 | HU-27 |
| RF-02 | HU-01 | RF-12 | HU-12 | RF-22 | HU-20 | RF-32 | HU-28 |
| RF-03 | HU-02 | RF-13 | HU-13 | RF-23 | HU-21 | RF-33 | HU-28 |
| RF-04 | HU-03 | RF-14 | HU-14 | RF-24 | HU-21 | RF-34 | HU-29 |
| RF-05 | HU-04 | RF-15 | HU-15 | RF-25 | HU-22 | RF-35 | HU-30 |
| RF-06 | HU-05 | RF-16 | HU-07, HU-09 | RF-26 | HU-23 | RF-36 | HU-31 |
| RF-07 | HU-06 | RF-17 | HU-16 | RF-27 | HU-24 | RF-37 | HU-32 |
| RF-08 | HU-07 | RF-18 | HU-17 | RF-28 | HU-23 | RF-38 | HU-33 |
| RF-09 | HU-08 | RF-19 | HU-18 | RF-29 | HU-25 | RF-39 | HU-34 |
| RF-10 | HU-10 | RF-20 | HU-19 | RF-30 | HU-26 | RF-40 | HU-35 |
| | | | | | | RF-41 | HU-36 |
