# Product backlog

**Estado:** borrador · Sprint 1 · lo prioriza el Product Owner (instructor, pendiente de acordar) · se refina en cada revisión de sprint

## Qué es y cómo se ordena

El product backlog es la lista única y ordenada de todo lo que hay que hacer para entregar el producto. En este proyecto el producto no es solo el sistema: también es su documentación, así que el backlog tiene tres tipos de elemento.

| Tipo | Código | Qué es | Dónde está el detalle |
| --- | --- | --- | --- |
| **Documento** | DOC-xx | Un entregable de análisis, diseño o cierre | En la carpeta `docs/` que indica cada uno |
| **Historia de usuario** | HU-xx | Una necesidad de la dueña o del cliente del taller | [Historias de usuario](../02-requisitos/historias-de-usuario.md) |
| **Habilitador técnico** | HT-xx | Trabajo que ninguna historia pide por sí sola, pero sin el cual no se cumplen los requisitos no funcionales o las decisiones de arquitectura | [Más abajo](#habilitadores-técnicos) |

Los elementos de desarrollo se ordenan con cuatro criterios, en este orden:

1. **Prioridad MoSCoW:** todo lo Must va antes que lo Should, y lo Should antes que lo Could.
2. **Dependencia:** un elemento va después de aquello sin lo cual no se puede construir ni probar. Cada fila dice de qué depende.
3. **Riesgo:** lo que depende de terceros o puede fallar tarde va lo antes posible (HT-01, HT-04).
4. **Valor:** entre elementos equivalentes, primero el que ataca la causa principal del [árbol de problemas](../01-problema/arbol-de-problemas.md).

El script `scripts/publicar_backlog.py` revisa este documento contra las historias de usuario (títulos, prioridad, puntos, dependencias y sumas de capacidad) antes de publicarlo en GitHub.

## Hitos

Cada sprint es un hito en GitHub con su fecha de cierre.

| Hito | Fin | Objetivo |
| --- | --- | --- |
| **Sprint 0 · Arranque** | 2026-09-14 | Organizar el proyecto |
| **Sprint 1 · Problema y requisitos** | 2026-09-21 | Entender y especificar el problema |
| **Sprint 2 · Diseño** | 2026-09-28 | Diseñar antes de programar |
| **Sprint 3 · Desarrollo I** | 2026-10-05 | Acceso, clientes, órdenes, prendas y fotos, con pruebas |
| **Sprint 4 · Desarrollo II** | 2026-10-10 | Estados, entrega, pagos, avisos, seguimiento y despliegue |
| **Cierre · Entrega y sustentación** | 2026-10-13 | Manuales y sustentación |

## Documentación

Los documentos del Sprint 1 se adelantaron: se terminaron el 13 y el 14 de septiembre, antes del inicio formal del sprint. Se registran en el sprint al que pertenecen según el [plan](plan-de-sprints.md), con el commit como evidencia.

| Código | Entregable | Sprint | Estado | Evidencia |
| --- | --- | --- | --- | --- |
| **DOC-01** | Estructura del repositorio, plan de sprints, fuentes de requisitos, ADR-000 y ADR-001 | Sprint 0 | Terminado | e2c27ae |
| **DOC-02** | Árbol de problemas | Sprint 1 | Terminado | b178e20 |
| **DOC-03** | Árbol de objetivos e idea de negocio | Sprint 1 | Terminado | 0f61680 |
| **DOC-04** | Objetivos específicos, alcance, ADR-002 y ADR-003 | Sprint 1 | Terminado | 869a2e2, babe5d6 |
| **DOC-05** | Reglas de negocio | Sprint 1 | Terminado | fce38fa, 13c3f42 |
| **DOC-06** | Requisitos funcionales y no funcionales | Sprint 1 | Terminado | 88e1285 |
| **DOC-07** | Historias de usuario con criterios de aceptación | Sprint 1 | Terminado | dad6951 |
| **DOC-08** | Matriz de trazabilidad | Sprint 1 | Terminado | f19470b |
| **DOC-09** | Análisis de alternativas | Sprint 1 | Terminado | 5923187 |
| **DOC-10** | Product backlog y tablero | Sprint 1 | Terminado | 1df92bb, 0178975, https://github.com/users/Aryannext/projects/1 |
| **DOC-11** | Proceso actual y proceso propuesto | Sprint 1 | Terminado | c93bde0 |
| **DOC-12** | Acuerdo con el instructor sobre cómo se valida: aprueba el producto al final | Sprint 1 | Terminado | 313db91 |
| **DOC-13** | Wireframes y mockups | Sprint 2 | Terminado | 93cd7ee |
| **DOC-14** | Aprobación final del producto y su documentación con el instructor | Cierre | Pendiente | — |
| **DOC-15** | Diagrama y especificación de casos de uso | Sprint 2 | Terminado | b0509b3 |
| **DOC-16** | Arquitectura del sistema y su ADR | Sprint 2 | Terminado | ea08f64 |
| **DOC-17** | Modelo de datos normalizado y diccionario de datos | Sprint 2 | Terminado | 166d0e0 |
| **DOC-18** | Diagramas de clases, secuencia, estados, componentes y despliegue | Sprint 2 | Terminado | 0e744d7 |
| **DOC-19** | Especificación técnica | Sprint 2 | Terminado | 4d62070 |
| **DOC-20** | Plan de pruebas | Sprint 2 | Terminado | bc02a90 |
| **DOC-21** | Informe de pruebas, con la prueba de usabilidad y la restauración de respaldos | Sprint 4 | En curso | 0860099 |
| **DOC-22** | Manual de usuario en español e inglés | Cierre | Terminado | 6d98103 |
| **DOC-23** | Manual técnico y de instalación en español e inglés | Cierre | Terminado | 0f2f466 |
| **DOC-24** | Presentación y ensayo de la sustentación | Cierre | Pendiente | — |

## Desarrollo

Una historia entra a un sprint solo si cumple la definición de "lista para desarrollar" de las [historias de usuario](../02-requisitos/historias-de-usuario.md#definición-de-lista-para-desarrollar), que incluye su mockup verificado contra sus criterios (DOC-13). El instructor no aprueba cada historia antes de construirla: aprueba el producto terminado (DOC-14). Los habilitadores se estiman con la misma escala y la misma historia de referencia (HU-06 = 1 punto).

| Orden | Código | Elemento | Prioridad | Puntos | Sprint | Depende de |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | **HT-01** | Conectar WhatsApp por Evolution API para los avisos | Must | 1 | Sprint 3 | — |
| 2 | **HT-02** | Proyecto base con calidad automática | Must | 3 | Sprint 3 | — |
| 3 | **HT-03** | Aislamiento de los datos por negocio | Must | 3 | Sprint 3 | HT-02 |
| 4 | **HU-01** | Iniciar y cerrar sesión | Must | 3 | Sprint 3 | HT-03 |
| 5 | **HU-02** | Cambiar mi contraseña | Must | 2 | Sprint 3 | HU-01 |
| 6 | **HU-03** | Registrar un cliente | Must | 2 | Sprint 3 | HU-01 |
| 7 | **HU-04** | Buscar un cliente | Must | 2 | Sprint 3 | HU-03 |
| 8 | **HU-06** | Corregir los datos de un cliente | Must | 1 | Sprint 3 | HU-03 |
| 9 | **HU-07** | Registrar una orden con sus prendas | Must | 5 | Sprint 3 | HU-03 |
| 10 | **HU-08** | Obtener el número de la orden para marcar la bolsa | Must | 1 | Sprint 3 | HU-07 |
| 11 | **HU-09** | Escribir un tipo de prenda que no está en la lista | Must | 2 | Sprint 3 | HU-07 |
| 12 | **HU-14** | Consultar el detalle de una orden | Must | 3 | Sprint 3 | HU-07 |
| 13 | **HU-15** | Listar y buscar órdenes | Must | 2 | Sprint 3 | HU-07 |
| 14 | **HU-12** | Corregir la descripción o el precio de una prenda | Must | 2 | Sprint 3 | HU-14 |
| 15 | **HU-17** | Tomar fotos de las prendas | Must | 3 | Sprint 3 | HU-07 |
| 16 | **HU-18** | Ver las fotos de una orden para reconocer las prendas | Must | 2 | Sprint 3 | HU-14, HU-17 |
| 17 | **HU-05** | Consultar la ficha de un cliente | Must | 3 | Sprint 3 | HU-03, HU-07 |
| 18 | **HT-04** | Despliegue en el VPS con HTTPS | Must | 3 | Sprint 4 | HT-02 |
| 19 | **HU-20** | Actualizar el estado de una prenda | Must | 5 | Sprint 4 | HU-14 |
| 20 | **HU-23** | Registrar un pago o abono | Must | 3 | Sprint 4 | HU-14 |
| 21 | **HU-25** | Anular un pago mal registrado | Must | 2 | Sprint 4 | HU-23 |
| 22 | **HU-21** | Entregar la orden al cliente | Must | 3 | Sprint 4 | HU-20, HU-23 |
| 23 | **HU-22** | Cancelar una orden | Must | 2 | Sprint 4 | HU-20 |
| 24 | **HU-28** | Recibir un aviso cuando mi ropa está lista | Must | 5 | Sprint 4 | HT-01, HU-20 |
| 25 | **HU-30** | No avisar una orden que ya no está lista | Must | 2 | Sprint 4 | HU-28 |
| 26 | **HU-29** | Enviar con un toque los avisos pendientes | Must | 3 | Sprint 4 | HU-28 |
| 27 | **HU-31** | Consultar los avisos de una orden | Must | 1 | Sprint 4 | HU-28 |
| 28 | **HU-32** | Ver el panel del día | Must | 3 | Sprint 4 | HU-20, HU-23 |
| 29 | **HU-33** | Ver las órdenes atrasadas | Must | 2 | Sprint 4 | HU-20 |
| 30 | **HU-34** | Ver las órdenes sin reclamar | Must | 2 | Sprint 4 | HU-20 |
| 31 | **HT-05** | Respaldos y restauración probada | Must | 2 | Sprint 4 | HT-04 |
| 32 | **HT-06** | Datos de volumen y medición de rendimiento | Must | 2 | Sprint 4 | HT-04, HU-32 |
| 33 | **HT-07** | Instalación en el celular: PWA y APK para Android | Must | 3 | Sprint 4 | HT-04, HU-17, HU-29 |
| 34 | **HU-26** | Ver quién me debe | Should | 2 | Sprint 4 | HU-23 |
| 35 | **HU-24** | Registrar un abono al recibir la orden | Should | 2 | — | HU-07, HU-23 |
| 36 | **HU-13** | Eliminar una prenda registrada por error | Should | 2 | — | HU-12 |
| 37 | **HU-36** | Devolver una prenda sin arreglar | Should | 2 | — | HU-20, HU-23 |
| 38 | **HU-10** | Registrar un cliente nuevo mientras registro su orden | Should | 3 | — | HU-07 |
| 39 | **HU-11** | Agregar una prenda a una orden que ya existe | Should | 2 | Sprint 4 | HU-20 |
| 40 | **HU-27** | Ver cuánto dinero he recibido | Should | 2 | Sprint 4 | HU-25 |
| 41 | **HU-19** | Eliminar una foto | Should | 1 | — | HU-17 |
| 42 | **HU-35** | Cambiar el plazo para considerar una orden sin reclamar | Could | 1 | — | HU-34 |
| 43 | **HU-16** | Renombrar o desactivar tipos de prenda | Could | 2 | — | HU-09 |

### Por qué este orden

- **HT-01 va primero** aunque vale un punto: dependía de terceros. Con la API oficial la plantilla requería aprobación de Meta; ADR-007 la cambió por Evolution API cuando ese trámite no se pudo completar.
- **El Sprint 3 construye el registro** (C-01, C-06): sin clientes, órdenes, prendas y fotos no hay nada que cambiar de estado, cobrar ni avisar.
- **HU-05 va al final del Sprint 3** porque la ficha del cliente muestra sus órdenes; su saldo se completa cuando existan los pagos (HU-23) y sus criterios se vuelven a probar entonces.
- **HT-04 abre el Sprint 4:** desplegar temprano descubre los problemas del servidor cuando todavía quedan días. Desde ahí, cada historia terminada se despliega el mismo día.
- **Los pagos (HU-23) van antes de entregar (HU-21)** porque entregar con saldo pide confirmación (RN-21), y el saldo necesita los pagos.
- **HT-07 cierra el Sprint 4:** el APK abre el sistema desplegado, así que necesita el dominio con HTTPS (HT-04) y las pantallas de fotos y avisos ya construidas.
- **HU-26 encabeza lo Should:** ataca el efecto E-03, no saber cuánto falta por cobrar, y reutiliza el saldo ya construido.
- **HU-36 va junto a HU-13:** las dos corrigen qué prendas cuentan en el valor de la orden. Se agregó el 14 de septiembre, al analizar el proceso actual.
- **HU-11 se adelantó al Sprint 4** el 22 de septiembre, antes que HU-26: en PM-04 el aprendiz buscó cómo agregar una prenda a una orden en proceso y no había forma. Es el caso de C-01.1, la prenda que no quedó anotada, y reutiliza el formulario de PT-06.
- **HU-26 y HU-27 entraron al Sprint 4** el 22 de septiembre, juntas porque comparten PT-22: «Dinero» aparecía en gris en la barra de navegación y la tarjeta «Por cobrar» del panel no llevaba a ninguna parte.

## Capacidad y compromiso

Todavía no hay velocidad medida, así que el compromiso de cada sprint de desarrollo es lo Must y nada más. Lo Should y lo Could no tienen sprint: entran solo si sobra capacidad.

| Sprint | Fechas | Días | Puntos | De historias | De habilitadores |
| --- | --- | --- | --- | --- | --- |
| **Sprint 3** | 29 sep – 5 oct | 7 | 40 | 33 | 7 |
| **Sprint 4** | 6 – 10 oct | 5 | 43 | 33 | 10 |

- **El Sprint 4 tiene menos días y más puntos.** Es un riesgo aceptado a propósito: el Sprint 3 carga el aprendizaje de Laravel y crea los patrones (formularios, validaciones, pruebas, filtro por negocio) que el Sprint 4 repite. El 14 de septiembre se agregó HT-07 (APK), que subió el Sprint 4 de 40 a 43 puntos, porque las usuarias trabajan desde el celular (ADR-006).
- **Punto de control el jueves 1 de octubre.** Al terminar el tercer día del Sprint 3 se cuentan los puntos terminados. Si son menos de 15, a ese ritmo el sprint cerraría con unos 35 de 40, y el recorte se decide ese mismo día, no al cierre.
- **Orden del recorte:** primero no entra nada Should ni Could; después el aprendiz decide qué Must sale y lo declara para la aprobación final (DOC-14), empezando por lo que no rompe el flujo principal (por ejemplo, HU-33 se ve parcialmente en el panel de HU-32, y HU-31 solo consulta). Nunca se recortan pruebas ni documentación.
- **Burndown:** el registro diario anota los puntos que faltan del sprint. Con esos datos se dibuja la gráfica en la revisión.

## Habilitadores técnicos

### HT-01 · Conectar WhatsApp por Evolution API para los avisos

**Nace de:** ADR-003 · ADR-007 · RNF-06. El alta en la API oficial de Meta no se logró completar (ADR-007).

**Terminado cuando:**

- [ ] Evolution API corre en el VPS, solo en el servidor y con su clave, sin guardar conversaciones.
- [ ] El WhatsApp del aprendiz está conectado a la instancia `taller`.
- [ ] Un aviso real de una orden lista llega al celular del aprendiz (PM-08).
- [ ] Las credenciales viven fuera del repositorio (RNF-24).

**Si la sesión se cae:** los avisos quedan para el envío asistido de HU-29 hasta volver a escanear el QR.

### HT-02 · Proyecto base con calidad automática

**Nace de:** ADR-001 · RNF-24, RNF-27, RNF-29, RNF-30, RNF-31, RNF-34.

**Terminado cuando:**

- [ ] Laravel está en `sistema/` con MySQL 8.4 y la zona horaria de Colombia (RN-09).
- [ ] Laravel Pint y Larastan en nivel 5 corren sin errores.
- [ ] GitHub Actions ejecuta pruebas, Pint y Larastan en cada envío y marca en rojo si algo falla.
- [ ] Una prueba de arquitectura verifica las capas definidas en el diseño (RNF-27).
- [ ] La base de datos se construye completa solo con migraciones.
- [ ] `.env.example` documenta cada variable y no hay secretos en el repositorio.

### HT-03 · Aislamiento de los datos por negocio

**Nace de:** ADR-002 · RN-01 · RNF-22.

**Terminado cuando:**

- [ ] Existen la tabla de negocios y la columna de negocio en las tablas raíz del modelo de datos.
- [ ] Un filtro global aplica el negocio de la sesión a toda consulta de esas tablas y lo asigna al crear registros.
- [ ] Una prueba automática con dos negocios comprueba que pedir un dato del otro responde como si no existiera. Cada historia que agrega una ruta la suma a esta prueba.

### HT-04 · Despliegue en el VPS con HTTPS

**Nace de:** ADR-001 · RNF-16, RNF-18, RNF-34.

**Terminado cuando:**

- [ ] El sistema corre en el VPS con el mismo código del repositorio, configurado solo con variables de entorno.
- [ ] Toda solicitud por HTTP se redirige a HTTPS.
- [ ] La cola de trabajos de los avisos (ADR-003) corre como servicio y se reinicia sola si se detiene.
- [ ] Un monitor externo revisa el sistema cada 5 minutos.

### HT-05 · Respaldos y restauración probada

**Nace de:** RNF-15.

**Terminado cuando:**

- [ ] Hay un respaldo diario automático de la base de datos y las fotos, conservado 14 días.
- [ ] Hay una copia semanal automática en Google Drive.
- [ ] Una restauración completa se probó en una hora o menos y quedó registrada para el informe de pruebas (DOC-21).

### HT-06 · Datos de volumen y medición de rendimiento

**Nace de:** RNF-01, RNF-02.

**Terminado cuando:**

- [ ] Un generador de datos crea el volumen de referencia de 3 años: 500 clientes, 750 órdenes, 2.200 prendas y 1.500 pagos.
- [ ] El panel, el detalle de orden, la búsqueda de clientes y las listas de seguimiento responden en 500 ms o menos en el percentil 95, medido en el VPS.
- [ ] Ninguna de esas páginas pasa de 15 consultas, con órdenes de 1 y de 10 prendas.

### HT-07 · Instalación en el celular: PWA y APK para Android

**Nace de:** ADR-006 · RNF-35 · F-05.

**Terminado cuando:**

- [ ] El sistema tiene manifiesto con nombre, íconos y los colores de los mockups, y Chrome en Android ofrece instalarlo.
- [ ] Sin conexión se abre una página que explica que se necesita internet, en vez del error del navegador.
- [ ] `assetlinks.json` está publicado en el dominio del VPS.
- [ ] Un APK firmado se genera con Bubblewrap; la llave de firma queda fuera del repositorio (RNF-24) y respaldada.
- [ ] El APK se instala en un celular Android real, abre a pantalla completa sin barra del navegador, toma una foto (HU-17) y abre WhatsApp desde el envío asistido (HU-29).
- [ ] El manual de usuario explica cómo instalarlo (DOC-22).

**Qué necesita de afuera:** un dominio propio con HTTPS apuntando al VPS (HT-04).

## Tablero

El backlog vive en GitHub como issues del repositorio, uno por elemento, con estas etiquetas e hitos:

| En GitHub | Qué indica |
| --- | --- |
| **Hito** | El sprint en que se compromete el elemento; sin hito, todavía no tiene sprint |
| **Etiqueta de tipo** | historia de usuario, habilitador técnico o documento |
| **Etiqueta de prioridad** | Must, Should o Could |
| **Etiqueta de puntos** | 1, 2, 3 o 5 puntos |
| **Etiqueta de épica** | EP-01 a EP-08, solo en historias |
| **Casillas** | Cada criterio de aceptación o condición de terminado; se marcan al cumplirse |

El [tablero de GitHub Projects](https://github.com/users/Aryannext/projects/1) (privado) reúne esos issues con cuatro campos propios: **Sprint** (una iteración por hito, con sus fechas reales), **Prioridad**, **Puntos** y **Orden**. Tiene tres vistas:

| Vista | Qué muestra |
| --- | --- |
| **Backlog** | Tabla con todos los elementos en el orden de este documento |
| **Sprint actual** | Tablero por columnas filtrado al sprint en curso; es la vista del día a día |
| **Todo el proyecto** | Tablero por columnas con todos los elementos |

Las columnas son:

| Columna | Entra cuando |
| --- | --- |
| **Backlog** | El elemento existe y no está en el sprint actual |
| **Por hacer** | La planificación del sprint lo incluye |
| **En curso** | Se empieza a trabajar. **Máximo un elemento a la vez:** en un equipo de una persona, tener varios abiertos esconde lo que está bloqueado |
| **Terminado** | Cumple la definición de terminado del [plan de sprints](plan-de-sprints.md#definición-de-terminado); el issue se cierra con el commit que lo termina |

Cada sprint tiene un issue de registro diario donde se escriben las tres líneas del daily (qué hice, qué haré, qué me bloquea) y los puntos que faltan, con su fecha real.
