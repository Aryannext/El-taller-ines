# Presentación y ensayo de la sustentación

**Entregable:** DOC-24 · **Fecha:** 23 de septiembre de 2026 · **Estado:** listo para ensayar

Este documento es todo lo que se necesita para sustentar: qué se dice, en qué orden, con qué tiempo, qué se muestra en vivo, qué hacer si algo falla y qué responder cuando pregunten. No hay que leer ningún otro documento el día de la sustentación.

**Las diapositivas están en [presentacion.html](presentacion.html).** Se abre con doble clic: no necesita internet, ni servidor, ni programa aparte, y se proyecta desde cualquier computador.

| Tecla | Qué hace |
| --- | --- |
| **→** o espacio | Avanza · **←** vuelve |
| **N** | Muestra u oculta **lo que se dice** en esa lámina, debajo de la diapositiva. Para ensayar, no para proyectar |
| **T** | Cronómetro. Avisa en rojo si la lámina va más de dos minutos tarde respecto al plan · **R** lo reinicia |
| **G** | Índice, para saltar a cualquier lámina si preguntan por algo |
| **F** | Pantalla completa |

Para el PDF de respaldo: imprimir con `Ctrl+P`, hoja horizontal y sin márgenes; cada lámina sale en su página.

> Son **15 láminas**, numeradas igual aquí y allá. La 6 es la de «Demostración»: queda en pantalla mientras se muestra el sistema, y sus notas traen los doce pasos por si se pierde el hilo.

## Contenido

1. [Las tres ideas que tienen que quedar](#1-las-tres-ideas-que-tienen-que-quedar)
2. [El reparto del tiempo](#2-el-reparto-del-tiempo)
3. [Las diapositivas, una por una](#3-las-diapositivas-una-por-una)
4. [El guion de la demostración](#4-el-guion-de-la-demostración)
5. [Si algo falla](#5-si-algo-falla)
6. [Preguntas de defensa y sus respuestas](#6-preguntas-de-defensa-y-sus-respuestas)
7. [Lista de chequeo](#7-lista-de-chequeo)
8. [Registro del ensayo](#8-registro-del-ensayo)

## 1. Las tres ideas que tienen que quedar

Si el jurado olvida todo lo demás, debe quedarse con esto:

1. **El sistema resuelve un problema real y medido**, no un ejercicio: un taller donde todo se lleva de memoria, y por eso se pierden prendas, se olvidan cobros y los clientes no saben cuándo volver.
2. **Está terminado y funcionando en producción**, con su dominio, su HTTPS, sus respaldos y su app instalada en un celular de verdad. No es una maqueta.
3. **Cada decisión está documentada y probada.** De cualquier línea de código se puede llegar a la causa del problema que la justifica, y al revés.

La frase de cierre, si hay que resumir en una sola: **«Del árbol de problemas a la línea de código, y de vuelta, todo está trazado y probado.»**

## 2. El reparto del tiempo

Para una sustentación de **20 minutos** más preguntas. Si dan 30, se alarga la demostración y las decisiones técnicas; si dan 15, se recorta la sección 6 de las diapositivas y se va directo a la demostración.

| Minuto | Qué | Diapositivas |
| --- | --- | --- |
| 0 – 3 | El problema y de dónde salió | 1 – 3 |
| 3 – 5 | Qué se entrega | 4 – 5 |
| 5 – 13 | **Demostración en vivo** | 6, y el sistema en pantalla |
| 13 – 16 | Cómo está hecho y por qué | 7 – 10 |
| 16 – 18 | Cómo se sabe que funciona | 11 – 12 |
| 18 – 20 | Lo que quedó fuera, lo que sigue y el cierre | 13 – 15 |

**La demostración es el corazón: ocupa casi la mitad.** El resto sostiene lo que ahí se ve.

## 3. Las diapositivas, una por una

Cada una trae **qué se muestra**, **qué se dice** (no de memoria: la idea) y **de dónde sale el dato**, por si preguntan.

### 1 · Portada

**Se muestra:** el nombre del sistema, el aprendiz, la ficha, la fecha, y el número de orden `#0042` de fondo como marca del sistema.

**Se dice:** quién es y qué construyó, en una frase: «un sistema para que un taller de arreglo de ropa deje de llevar sus órdenes de memoria».

### 2 · El taller y el problema

**Se muestra:** la foto o el dibujo del rincón con las bolsas de ropa, y las tres consecuencias del árbol de problemas.

**Se dice:** el taller recibe prendas, las arregla y las entrega. Nada queda anotado: ni qué prenda es de quién, ni qué arreglo se pidió, ni cuánto se acordó, ni quién ya pagó. De ahí salen tres consecuencias reales: prendas que se confunden o se pierden, cobros que se olvidan, y clientes que no saben cuándo volver por su ropa.

**De dónde sale:** [árbol de problemas](../01-problema/arbol-de-problemas.md), causas C-01.1 a C-06.

### 3 · Del problema a los objetivos

**Se muestra:** el árbol de objetivos resumido: el objetivo general arriba y los seis específicos debajo.

**Se dice:** cada causa se convirtió en un medio, y cada medio en un objetivo específico con su indicador. No se inventó ninguna función: las seis salen del árbol.

| Objetivo | Qué promete |
| --- | --- |
| **OE-01** | Registrar cada prenda con su cliente, arreglo, precio y fecha, y consultarlo |
| **OE-02** | Saber en qué va cada prenda |
| **OE-03** | Avisarle al cliente cuando su ropa está lista |
| **OE-04** | Saber quién debe cuánto |
| **OE-05** | Ver lo atrasado y lo que nadie reclama |
| **OE-06** | Reconocer de quién es cada prenda |

**De dónde sale:** [objetivos específicos](../01-problema/objetivos-especificos.md).

### 4 · Qué se entrega

**Se muestra:** los módulos del sistema en una sola imagen: Acceso, Clientes, Órdenes y prendas, Fotos, Estados, Pagos, Avisos, Seguimiento, Dinero, App en el celular.

**Se dice:** esto es lo que está construido, desplegado y probado. Nueve módulos y una app instalable.

**De dónde sale:** [alcance](../01-problema/alcance.md).

### 5 · Cómo se trabajó

**Se muestra:** la línea de tiempo: Sprint 0 arranque, 1 problema y requisitos, 2 diseño, 3 y 4 desarrollo, cierre.

**Se dice:** Scrum adaptado a un equipo de una persona, con sprints de una semana, backlog priorizado con MoSCoW y estimación en puntos. Nada se programó sin su historia, sus criterios y su mockup. Más de 130 commits, todos con su mensaje explicando el porqué.

**De dónde sale:** [plan de sprints](../00-scrum/plan-de-sprints.md) y [product backlog](../00-scrum/product-backlog.md).

### 6 · Demostración

**Se muestra:** una lámina azul que solo dice «Demostración», para que el proyector no quede con algo viejo en pantalla mientras se muestra el sistema. Sus notas traen los doce pasos del guion.

**Se dice:** «Ahora el sistema, desde el celular, tal como lo usa la dueña.» Y se pasa al guion de la sección 4. No se vuelve a las diapositivas hasta que termine.

### 7 · Cómo está hecho

**Se muestra:** el diagrama de la arquitectura en capas: Dominio ← Aplicación ← Http / Infraestructura.

**Se dice:** las reglas del negocio viven en el dominio, aisladas del framework; los casos de uso las orquestan; los controladores solo reciben y responden. **Esa separación no es una promesa: una prueba la hace cumplir.** Si alguien escribe una consulta a la base de datos dentro de un controlador, la corrida falla.

**De dónde sale:** [arquitectura](../03-diseno/arquitectura/README.md), [ADR-005](../03-diseno/adr/ADR-005-arquitectura-en-capas.md), y las pruebas `ControladoresSinConsultasTest` y las reglas de capas con PHPat.

### 8 · Las decisiones que hubo que tomar

**Se muestra:** cuatro decisiones con su alternativa descartada.

| Decisión | Se escogió | Se descartó, y por qué |
| --- | --- | --- |
| **ADR-003** · Canal de avisos | API oficial de WhatsApp, con envío asistido cuando no está disponible | Las APIs no oficiales: violan los términos y exponen el número de la dueña a que se lo bloqueen |
| **ADR-002** · Un taller o varios | Un taller, con los datos preparados para varios | Multi-negocio completo: no cabía en el plazo, y dejarlo a medias es peor |
| **ADR-006** · App en el celular | Aplicación web instalable, más un APK firmado | App nativa: otro lenguaje, otra base de código, mismo resultado para la usuaria |
| **ADR-000** · Qué hacer con el prototipo | Rehacer desde el análisis | Seguir con el prototipo: no tenía requisitos ni pruebas detrás |

**Se dice:** cada una está escrita con su contexto, sus opciones y sus consecuencias, el día que se tomó. La de WhatsApp es la más importante: era más fácil usar una API no oficial y habría funcionado en la demostración, pero pone en riesgo el número de la dueña, que es su herramienta de trabajo.

### 9 · Los datos y su cuidado

**Se muestra:** el modelo de datos resumido y las tres medidas de protección.

**Se dice:** diez tablas normalizadas hasta la tercera forma normal, con su diccionario y con una sola excepción, documentada en [ADR-004](../03-diseno/adr/ADR-004-negocio-en-ordenes.md): el negocio se repite en las órdenes para poder filtrar sin recorrer tres tablas. De los clientes solo se guarda **nombre y celular**: nada más, porque nada más hace falta. La política de tratamiento de datos está publicada en el sistema y se lee sin iniciar sesión, como exige la Ley 1581 de 2012. Y cada orden, cliente y pago pertenece a un negocio: una prueba comprueba que pedir el dato de otro negocio responde «no existe».

**De dónde sale:** [modelo de datos](../03-diseno/modelo-de-datos/README.md), [seguridad](../04-especificacion-tecnica/06-seguridad.md), `AislamientoEntreNegociosTest`.

### 10 · Dónde vive el sistema

**Se muestra:** el diagrama de despliegue: el servidor, los contenedores y el celular.

**Se dice:** corre en un servidor propio con dominio y HTTPS. Seis contenedores: el sistema, el trabajador de avisos, el programador de tareas, la base de datos y el canal de WhatsApp. Respaldo diario automático, con restauración probada en 7 segundos. Si el trabajador de avisos se cae, el servicio lo levanta solo: **eso se probó tumbándolo a propósito**.

**De dónde sale:** [despliegue y operación](../04-especificacion-tecnica/07-despliegue-y-operacion.md), PM-02 y PM-07.

### 11 · Cómo se sabe que funciona

**Se muestra:** los números, grandes y pocos.

| | |
| --- | --- |
| **286** | pruebas automáticas, todas pasan |
| **1.557** | comprobaciones dentro de ellas |
| **36 / 36** | historias del backlog, construidas y verificadas |
| **44 / 44** | reglas de negocio con prueba |
| **8** | protocolos de prueba manual |
| **10** | defectos encontrados; **0** abiertos |

**Se dice:** las pruebas no se escribieron al final: cada historia se dio por terminada solo con ellas. Corren en cada envío al repositorio, junto con el análisis estático, el estilo, la revisión de dependencias con vulnerabilidades y la búsqueda de secretos en el historial. Si algo de eso falla, el cambio no entra.

**Y lo más honesto:** los diez defectos que se encontraron están anotados con su causa y su corrección. Dos de ellos los encontró la prueba de seguridad ejecutada a mano, y uno lo causó la corrección de otro. Por eso las pruebas manuales existen.

**De dónde sale:** [informe de pruebas](../05-pruebas/informe-de-pruebas.md).

### 12 · La documentación es navegable

**Se muestra:** el portal abierto, en la página de una historia de usuario, señalando cómo cada código es un enlace.

**Se dice:** la documentación no son veinte archivos sueltos. Es un sitio donde cada causa, requisito, regla, historia, criterio, caso de uso, pantalla, decisión y prueba tiene su página, y cada código es un enlace: de la historia a su regla, de la regla a la línea del código que la hace cumplir, y de ahí a la prueba que la comprueba. Tiene además un recorrido guiado que va del problema a la prueba en once pasos.

**Se muestra en vivo, si el tiempo alcanza:** el recorrido guiado, que es exactamente esta idea.

**De dónde sale:** [aryannext.github.io/puntada](https://aryannext.github.io/puntada/).

### 13 · Lo que quedó fuera, y por qué

**Se muestra:** lo que se dejó fuera del alcance con su motivo, y lo que falta por probar.

**Se dice:** hay que distinguir dos cosas. Lo que quedó **fuera del alcance** —multi-negocio con registro propio, el envío automático con el número real del taller, la impresora de etiquetas— está declarado desde el Sprint 1 con su motivo, no explicado después. Y del **backlog no quedó nada sin construir**: las 36 historias están hechas, incluidas las siete *Should* y *Could* que estuvieron recortadas hasta el último día y se construyeron al final, con sus pruebas. La que más importaba era HU-36, porque era la única que dejaba una regla de negocio sin comprobar.

**Se dice también, sin que lo pregunten:** lo que sí falta son dos pruebas manuales que necesitan a otras personas —la de usabilidad con compañeros y la de instalación por alguien más—, y están agendadas.

### 14 · Lo que sigue

**Se muestra:** cuatro pasos.

1. **Implantación:** la dueña usa el sistema con sus clientes reales, con acompañamiento la primera semana.
2. **Medir el impacto:** los indicadores no se pueden medir en 30 días; se miden después, con el sistema en uso.
3. **Abrir el registro:** que cualquier taller se una entrando con Google, sin que nadie tenga que crear cuentas a mano. Los datos ya están preparados para varios negocios (ADR-002) y el aislamiento ya está probado; lo que falta es la puerta de entrada.
4. **Sostenerlo:** el sistema es gratuito para la usuaria. El plan de negocio resuelve cómo se cubren el servidor y el costo por mensaje de la API oficial.

> **Si preguntan por qué el registro con Google no se hizo ya:** porque el botón es la parte fácil. Después del botón hay que crearle el negocio a quien entra, sembrarle sus tipos de prenda y sus métodos de pago, y probar que no ve los datos de nadie más. Es la historia más grande del proyecto, estaba declarada fuera del alcance desde el Sprint 1, y dejarla a medias habría sido peor que no hacerla. La misma respuesta sirve para la **ayuda interactiva dentro de la app**: se construye después de PM-01, que es justamente la prueba que mide si el sistema se entiende **sin** ayuda.

### 15 · Cierre

**Se muestra:** las tres direcciones, grandes y legibles desde el fondo del salón.

- **El sistema:** proyectosena.online/taller
- **La documentación:** aryannext.github.io/puntada
- **El código:** github.com/Aryannext/puntada

**Se dice:** la frase de cierre de la sección 1, y gracias.

## 4. El guion de la demostración

**Ocho minutos.** Se hace desde el celular con la app instalada, proyectando la pantalla; si no se puede proyectar el celular, desde el navegador del computador, que es el mismo sistema.

**Antes de empezar:** sesión ya iniciada y la app abierta en **Hoy**. Nadie quiere ver a alguien escribiendo una contraseña.

| # | Qué se hace | Qué se dice mientras tanto | Cuánto |
| --- | --- | --- | --- |
| 1 | Mostrar **Hoy** | «Esta es la primera pantalla del día: lo que hay que entregar hoy, lo atrasado, lo que falta cobrar y los avisos pendientes. Antes esto vivía en la memoria.» | 40 s |
| 2 | **Clientes → Registrar**: nombre y celular de un cliente de demostración | «Del cliente solo se pide nombre y celular. Nada más hace falta, y nada más se guarda.» | 40 s |
| 3 | **Nueva orden** para ese cliente: una prenda, su arreglo, su precio, la fecha de entrega | «Cada prenda tiene su arreglo, su precio y su fecha. El sistema no deja poner una fecha de entrega anterior al día en que se recibe la orden: esa es una regla de negocio, RN-07, y tiene su prueba.» | 90 s |
| 4 | **Tomar la foto** de la prenda con la cámara | «Esta es la respuesta al problema de las bolsas: la foto y el número de orden. Hasta tres fotos por prenda.» | 50 s |
| 5 | Mostrar el **número de orden** que queda, y decir que se escribe en la bolsa | «#0043. Eso es lo que se escribe en la bolsa con marcador.» | 20 s |
| 6 | **Registrar un abono** sobre esa orden | «El saldo se calcula solo: nunca se escribe a mano lo que alguien debe.» | 40 s |
| 7 | Poner la prenda **En proceso** y luego **Terminada** | «Cuando todas las prendas de la orden quedan terminadas, la orden pasa a Lista sola. Eso dispara el aviso.» | 50 s |
| 8 | Mostrar el **aviso** que apareció en Hoy y abrirlo en WhatsApp | «El mensaje ya está redactado con el nombre, el número de orden y el saldo. Con la API oficial sale solo; mientras el negocio se verifica ante Meta, la dueña lo envía de un toque y el sistema lo registra.» | 70 s |
| 9 | **Entregar la orden** con saldo pendiente | «Aquí el sistema pregunta: la orden tiene saldo. Entregar con deuda es una decisión de la dueña, no un descuido, y queda registrada.» | 50 s |
| 10 | **Dinero → Quién me debe** y **Cuánto he recibido** | «Quién debe y cuánto, de mayor a menor. Y el dinero que entró hoy, esta semana o este mes, con su método de pago.» | 50 s |
| 11 | **Atrasadas** y **Sin reclamar** | «Lo que se pasó de fecha y lo que lleva más de 30 días sin que nadie lo recoja, con los días de espera.» | 40 s |
| 12 | **Cerrar sesión** o dejarlo en Hoy | — | 10 s |

**Los datos de demostración.** Se registra un cliente nuevo en vivo —queda más creíble— sobre un sistema que ya tiene órdenes de ejemplo para que Hoy no se vea vacío. Los clientes de ejemplo son inventados y así se dice.

**Lo que no se demuestra en vivo, y por qué:** el envío automático por la API oficial de WhatsApp con el número real del taller. Requiere verificar el negocio ante Meta y tiene costo por mensaje; está fuera del alcance declarado. Se demuestra el envío asistido, que es lo que la dueña usa hoy.

## 5. Si algo falla

Se prepara antes, no se improvisa.

| Si pasa esto | Se hace esto |
| --- | --- |
| **No hay internet en el salón** | Se usa el celular con datos móviles y se proyecta su pantalla. Si tampoco, se muestra el recorrido de capturas del manual de usuario: son del sistema real |
| **El proyector no toma el celular** | Se hace todo desde el navegador del computador: es el mismo sistema, misma sesión, mismos datos |
| **El sistema no responde** | Se abre `proyectosena.online/taller/up`, que dice si está vivo, y se explica que el monitor externo revisa cada 5 minutos. Mientras tanto, se sigue con las diapositivas y se vuelve a intentar |
| **Un paso de la demostración da error** | Se dice qué pasó, sin disimular, y se continúa. Un error encontrado en vivo se registra como defecto: así se han encontrado los diez que están anotados |
| **Se acaba el tiempo** | Se salta a la diapositiva 14 y se dice la frase de cierre. Nunca se corre la demostración a las carreras |
| **Preguntan algo que no se sabe** | «No lo sé de memoria; está en la documentación y lo busco.» Y se busca en el portal, en vivo. Eso demuestra el portal mejor que cualquier explicación |

## 6. Preguntas de defensa y sus respuestas

### Sobre el problema y el proyecto

**¿Por qué este proyecto y no otro?**
Porque el problema existe y se puede ver: un taller de arreglo de ropa donde todo se lleva de memoria. Las causas están levantadas en el árbol de problemas, y cada función del sistema nace de una de ellas.

**¿Cómo sabe que el sistema resuelve el problema?**
Hoy se puede afirmar que el sistema **hace** lo que promete, con pruebas por cada criterio. El **impacto** —menos prendas perdidas, menos cobros olvidados— no se puede medir en 30 días: necesita que la dueña lo use durante semanas. Por eso los indicadores de impacto están definidos y se miden después de la implantación. Decir hoy que ya se redujeron las pérdidas sería mentir.

**¿La dueña participó?**
El levantamiento salió de su forma de trabajar. La validación del producto terminado la hace el instructor como Product Owner, según lo acordado en el Sprint 1, porque la dueña no estaba disponible para revisiones semanales.

**¿Cuánto costaría mantenerlo?**
El servidor y el dominio están pagos. El único costo variable es el mensaje de la API oficial de WhatsApp, y mientras eso no esté activo el envío asistido no cuesta nada. El sistema es gratuito para la usuaria; cómo se sostiene es parte del plan de negocio.

### Sobre la técnica

**¿Por qué Laravel y MySQL?**
Está en [ADR-001](../03-diseno/adr/ADR-001-laravel-mysql.md): es el entorno que la formación cubre, tiene todo lo que el proyecto necesita —sesiones, cola de trabajos, migraciones, pruebas— sin sumar piezas, y se despliega en un servidor común sin nada exótico.

**¿Por qué arquitectura en capas si es un sistema pequeño?**
Porque las reglas del negocio son el corazón del sistema y no debían quedar mezcladas con el framework. Y no es decorativa: hay pruebas que fallan si alguien cruza una capa.

**¿Cómo se prueba que un negocio no ve los datos de otro?**
Con una prueba que recorre todas las rutas con parámetros y comprueba que, pidiendo el dato de otro negocio, el sistema responde «no existe». Además, el filtro por negocio está en el modelo, no en cada consulta, para que no se pueda olvidar.

**¿Qué pasa si WhatsApp falla?**
Tres reintentos con espera creciente y, si aún falla, el aviso pasa a envío asistido: el mensaje queda redactado y la dueña lo manda con un toque. Está probado con un canal que siempre falla.

**¿Y si se cae el trabajador que envía los avisos?**
El servicio lo vuelve a levantar solo. Se probó tumbándolo a propósito: volvió en menos de veinte segundos, y los avisos que estaban en espera no se perdieron, porque la cola vive en la base de datos.

**¿Y si se daña el servidor?**
Hay respaldo diario automático, guardado fuera del servidor. La restauración está probada: 7 segundos, contra un máximo de una hora que fija el requisito.

**¿Cómo se guardan las contraseñas?**
Con hash, nunca en texto. Hay límite de intentos, la sesión se cierra sola a las 8 horas, y todo eso tiene su prueba.

**¿Cumple la ley de datos personales?**
Se piden solo nombre y celular. La política de tratamiento de datos está publicada y se lee sin iniciar sesión, según la Ley 1581 de 2012.

**¿Qué es lo que más le costó?**
Responder con la verdad, y hay de dónde escoger: el canal de WhatsApp, porque la opción fácil era la que ponía en riesgo el número de la dueña; o la prueba de seguridad, donde la propia corrección de un defecto dejó al sistema sin iconos y solo se vio revisando en el navegador.

### Sobre el trabajo propio

**¿Usó herramientas de inteligencia artificial?**
Sí, como asistente de programación y redacción. Las decisiones, la priorización, las pruebas manuales y la validación son del aprendiz, y todo el recorrido está en el historial del repositorio commit por commit. La definición de terminado del proyecto exige algo que ninguna herramienta puede hacer por uno: **poder explicarlo sin leer**. Esa es la prueba, y es la que se está pasando ahora mismo.

**¿Qué haría distinto?**
Ejecutar las pruebas manuales antes, no al final. Las dos últimas encontraron defectos que llevaban horas en producción sin que nadie los notara.

## 7. Lista de chequeo

### La víspera

- [ ] El sistema responde: abrir `proyectosena.online/taller/up`
- [ ] Hay órdenes de ejemplo para que **Hoy** no se vea vacío
- [ ] La app del celular abre y la sesión está iniciada
- [ ] El celular queda cargado, sin modo avión y sin notificaciones privadas a la vista
- [ ] El portal de documentación abre: probar el recorrido guiado
- [ ] `presentacion.html` abre bien en el computador que se va a usar, y está exportada a PDF por si acaso
- [ ] Las tres direcciones del cierre, escritas en un papel por si falla todo

### El día

- [ ] Llegar con el celular conectado al proyector **antes** de empezar
- [ ] Silenciar las notificaciones del celular y del computador
- [ ] Abrir de una vez: las diapositivas, el sistema en **Hoy** y el portal, en pestañas separadas
- [ ] Respirar y hablar despacio: la demostración está guionada, no hay que improvisar

## 8. Registro del ensayo

El ensayo es parte del entregable: no basta con tener las diapositivas.

| Ensayo | Fecha | Duración | Qué salió mal | Qué se corrigió |
| --- | --- | --- | --- | --- |
| **1.º** | | | | |
| **2.º** | | | | |
| **Final, ante alguien más** | | | | |

**Cómo se ensaya:** de corrido, con reloj, en voz alta y con la demostración de verdad —no imaginándola—. El tercero, delante de otra persona que pueda interrumpir con preguntas.

**Criterio para darlo por listo:** dos ensayos seguidos dentro del tiempo, sin leer este documento y sin que un paso de la demostración falle.
