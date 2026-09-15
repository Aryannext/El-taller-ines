# Plan de pruebas

**Estado:** borrador · Sprint 2 · se valida con el instructor (F-04)

## Para qué sirve

Dice qué se prueba, cómo, con qué herramientas, cuándo y cómo se decide que el sistema está listo para entregarse. Sigue la estructura del plan de pruebas de la norma ISO/IEC/IEEE 29119-3, adaptada a un proyecto de una persona.

No inventa escenarios: parte de lo que ya está escrito y aprobado.

- Cada **criterio de aceptación** de las [historias de usuario](../02-requisitos/historias-de-usuario.md) es un caso de prueba con su mismo código.
- Cada **regla de negocio** se prueba con el ejemplo de su [especificación](../02-requisitos/reglas-de-negocio.md).
- Cada **requisito no funcional** se prueba contra la meta y la verificación de su [tabla](../02-requisitos/requisitos-no-funcionales.md).

Si una prueba necesita una situación que ningún documento describe, primero se corrige el documento y después se escribe la prueba.

`python scripts/generar_plan_de_pruebas.py` genera los casos de prueba y comprueba que ningún criterio, regla ni requisito no funcional quede sin prueba.

## Documentos del plan

| Documento | Qué contiene |
| --- | --- |
| **Este plan** | Estrategia, herramientas, entornos, datos, criterios de salida, defectos y cronograma |
| [Casos de prueba](casos-de-prueba/) | Un archivo por épica con sus criterios, y uno con las reglas de negocio. Se generan |
| [Pruebas manuales](pruebas-manuales/) | Los protocolos PM-01 a PM-08: preparación, pasos, criterio de aprobación y hoja de registro |
| **Informe de pruebas** (DOC-21, Sprint 4) | Los resultados. Se escribe al ejecutar las pruebas, con las hojas de registro llenas |

<!-- resumen:inicio -->

## Cobertura del plan

> **Sección generada** por `generar_plan_de_pruebas.py`.

| Qué se prueba | Casos | Automáticos | Automáticos y manuales | Manuales o por revisión |
| --- | --- | --- | --- | --- |
| [Criterios de aceptación](casos-de-prueba/) | 125 | 118 | 5 | 2 |
| [Reglas de negocio](casos-de-prueba/09-reglas-de-negocio.md) | 44 | 44 | 0 | 0 |
| [Requisitos no funcionales](#cómo-se-prueba-cada-requisito-no-funcional) | 35 | 19 | 3 | 13 |

**Pruebas automáticas planeadas:** 167 métodos en 51 clases · **Escritas en `sistema/tests/`:** 102 de 167.

| Épica | Historias | Casos |
| --- | --- | --- |
| [EP-01 · Acceso](casos-de-prueba/01-acceso.md) | 2 | 9 |
| [EP-02 · Clientes](casos-de-prueba/02-clientes.md) | 4 | 14 |
| [EP-03 · Órdenes y prendas](casos-de-prueba/03-ordenes-y-prendas.md) | 10 | 32 |
| [EP-04 · Identificación de prendas](casos-de-prueba/04-identificacion-de-prendas.md) | 3 | 10 |
| [EP-05 · Estados y entrega](casos-de-prueba/05-estados-y-entrega.md) | 4 | 19 |
| [EP-06 · Pagos](casos-de-prueba/06-pagos.md) | 5 | 17 |
| [EP-07 · Avisos](casos-de-prueba/07-avisos.md) | 4 | 13 |
| [EP-08 · Seguimiento](casos-de-prueba/08-seguimiento.md) | 4 | 11 |
| [Reglas de negocio](casos-de-prueba/09-reglas-de-negocio.md) | — | 44 |

| Prueba manual | Verifica |
| --- | --- |
| [PM-01 · Prueba de usabilidad](pruebas-manuales/PM-01-usabilidad.md) | RNF-12 |
| [PM-02 · Restauración de respaldos](pruebas-manuales/PM-02-restauracion-de-respaldos.md) | RNF-15 |
| [PM-03 · Instalación siguiendo el manual](pruebas-manuales/PM-03-instalacion-desde-el-manual.md) | RNF-33 |
| [PM-04 · App en el celular](pruebas-manuales/PM-04-app-en-el-celular.md) | RNF-35, CA-17.1, CA-17.2, CA-29.2 |
| [PM-05 · Pantallas y navegadores](pruebas-manuales/PM-05-pantallas-y-navegadores.md) | RNF-05, RNF-07, RNF-09, RNF-11, CA-01.4, CA-10.1, CA-18.2 |
| [PM-06 · Rendimiento con el volumen de 3 años](pruebas-manuales/PM-06-rendimiento.md) | RNF-01 |
| [PM-07 · Seguridad y operación del despliegue](pruebas-manuales/PM-07-seguridad-y-despliegue.md) | RNF-16, RNF-18, RNF-23, RNF-26, RNF-34 |
| [PM-08 · Aviso real por WhatsApp](pruebas-manuales/PM-08-aviso-real-por-whatsapp.md) | RNF-06, CA-28.1 |

<!-- resumen:fin -->

## Alcance

### Qué se prueba

- Todas las historias de usuario, a través de sus criterios de aceptación.
- Todas las reglas de negocio, en la capa donde la [arquitectura](../03-diseno/arquitectura/README.md#dónde-vive-cada-regla-en-el-código) las ubica.
- Todos los requisitos no funcionales, incluidos la instalación en el celular, los respaldos y la instalación desde el manual.
- La regla de dependencias entre capas ([ADR-005](../03-diseno/adr/ADR-005-arquitectura-en-capas.md)) y el aislamiento entre negocios ([ADR-002](../03-diseno/adr/ADR-002-un-taller-preparado-para-varios.md)).

### Qué no se prueba

| Qué | Por qué |
| --- | --- |
| Laravel, MySQL y la API de WhatsApp en sí mismos | Son herramientas probadas por sus autores. Se prueba el código propio que las usa: el adaptador del canal con respuestas simuladas y un envío real con el número de prueba de Meta (PM-08) |
| Muchas usuarias usando el sistema al mismo tiempo | Hay un taller con una usuaria (ADR-002). RNF-01 mide la rapidez con el volumen de 3 años, no con carga concurrente |
| iPhone y Safari | RNF-05 fija Chrome para Android y Chrome y Edge de escritorio. La instalación desde el navegador en otro celular sí se prueba (PM-04) |
| La disponibilidad de un mes completo (RNF-16) | El sistema se despliega en el Sprint 4 y se entrega pocos días después. El informe reporta la disponibilidad medida en ese período y lo declara |
| Lo que está fuera del [alcance](../01-problema/alcance.md) | Publicación en Google Play, trabajo sin conexión y el destino de las prendas sin reclamar no se construyen |

## Estrategia

### La prueba se escribe antes que el código

En los Sprints 3 y 4, cada historia se construye así:

1. Se escribe la prueba de cada criterio y de cada regla que toca la historia, y se ve fallar.
2. Se programa lo mínimo para que pase.
3. Se ordena el código sin cambiar su comportamiento, con las pruebas en verde.

Es el ciclo rojo, verde y refactorizar del desarrollo guiado por pruebas. Ver fallar la prueba primero demuestra que prueba algo: una prueba que nunca falló puede estar pasando por error.

Si el tiempo no alcanza para seguir el ciclo completo, la regla mínima no cambia: ninguna historia pasa a Terminado sin las pruebas de sus criterios.

### Niveles de prueba

Se sigue la pirámide de pruebas: muchas pruebas pequeñas y rápidas en la base, menos pruebas grandes arriba y solo lo indispensable a mano.

| Nivel | Qué comprueba | Cómo | Dónde vive | Base de datos |
| --- | --- | --- | --- | --- |
| **Documentación** | Que los documentos no se contradigan ni dejen algo sin cubrir | Los scripts de verificación del repositorio | `scripts/` | MySQL temporal, solo para el modelo |
| **Unitaria** | Las reglas del dominio | PHPUnit sin Laravel ni base de datos | `tests/Unit/Dominio/` | No |
| **Integración** | Casos de uso, transacciones, bloqueos, eventos y la cola | PHPUnit con Laravel y MySQL, con reloj y canal de aviso falsos | `tests/Feature/` | Sí |
| **Funcionalidad** | Los criterios de aceptación de principio a fin | Solicitudes HTTP al sistema y revisión de la respuesta | `tests/Feature/` | Sí |
| **Arquitectura** | La regla de dependencias entre capas (RNF-27) | PHPat dentro de Larastan | `tests/Arquitectura/` | No |
| **Aislamiento** | Que ningún negocio vea datos de otro (RN-01, RNF-22) | Recorre todas las rutas con datos de otro negocio | `tests/Feature/Aislamiento/` | Sí |
| **Sistema** | Lo que solo se ve en el sistema desplegado, en un navegador o en un celular real | Protocolos de prueba manual con hoja de registro | VPS y dispositivos reales | La del VPS |
| **Aceptación** | Que el sistema sirva a quien lo usa | Prueba de usabilidad (PM-01) y aprobación del producto terminado por el instructor (DOC-14) | — | — |

Las rutas de las pruebas son relativas a `sistema/`, donde vive el código desde el Sprint 3.

### Las pruebas de la documentación ya empezaron

Probar no empieza con el código. Desde el Sprint 1, cada documento tiene un script que lo contrasta con los demás; son pruebas estáticas, que revisan sin ejecutar el sistema.

| Script | Qué comprueba |
| --- | --- |
| `generar_matriz.py` | Que todo código citado exista y que cada causa llegue hasta una historia con criterios |
| `publicar_backlog.py` | Que el backlog coincida con las historias en títulos, puntos, dependencias y capacidad |
| `generar_mockups.mjs` | Que cada historia tenga su pantalla |
| `verificar_modelo.py` | Las consultas de referencia con su resultado esperado y las restricciones del esquema, contra MySQL 8.4 |
| `verificar_arquitectura.py` | Que cada regla e historia tenga una clase que la implemente |
| `verificar_diagramas.py` | Que clases, atributos y estados coincidan con el modelo de datos y las reglas |
| `generar_casos_de_uso.py` | Que los casos de uso cubran las historias y sus diagramas no tengan líneas cruzadas |
| `generar_plan_de_pruebas.py` | Que cada criterio, regla y requisito no funcional tenga su prueba en este plan |

Estos scripts ya encontraron defectos reales antes de escribir código:

- Una historia de los mockups era imposible: la orden #0042 aparecía lista con una prenda Pendiente.
- La especificación de un caso de uso citaba una regla equivocada.

Corregirlos en un documento costó minutos; en el código habría costado horas.

### Cómo se prueba cada tipo de requisito

| Qué | Caso de prueba | Nivel | Detalle |
| --- | --- | --- | --- |
| **Criterio de aceptación** | Uno por criterio, con su mismo código | Funcionalidad, salvo las excepciones de abajo | [Casos de prueba por épica](casos-de-prueba/) |
| **Regla de negocio** | Uno con el ejemplo de la regla | El de la capa donde vive la regla | [Reglas de negocio](casos-de-prueba/09-reglas-de-negocio.md) |
| **Requisito no funcional** | Según su verificación | Cualquiera | [Tabla de requisitos no funcionales](#cómo-se-prueba-cada-requisito-no-funcional) |

El nivel de una regla sale de la capa donde vive:

| Capa | Nivel | Clase de prueba |
| --- | --- | --- |
| **Dominio** | Unitaria | `tests/Unit/Dominio/<carpeta>/<Clase>Test.php` |
| **Aplicación** | Integración | `tests/Feature/<carpeta>/<Clase>Test.php`, la misma de los criterios de ese caso de uso |
| **Http** | Funcionalidad | `tests/Feature/Http/<Clase>Test.php` |
| **Modelos** | Aislamiento | `tests/Feature/Aislamiento/AislamientoEntreNegociosTest.php` |
| **Base de datos** | Integración | `tests/Feature/BaseDeDatos/EsquemaTest.php` |
| **Infraestructura** | Integración | `tests/Feature/Infraestructura/<Clase>Test.php` |

### Criterios con otra forma de prueba

Por defecto, un criterio se prueba con una prueba de funcionalidad automática en la clase de prueba del caso de uso de su historia. Estos criterios se apartan de esa regla:

| Criterio | Nivel | Forma | Clase de prueba | Protocolo | Por qué |
| --- | --- | --- | --- | --- | --- |
| **CA-01.4** | Funcionalidad | Automática y manual | — | PM-05 | La prueba automática comprueba que la sesión se cierra y que las páginas se envían sin guardarse en caché; lo que muestra el botón Atrás depende de cada navegador |
| **CA-10.1** | Funcionalidad | Automática y manual | — | PM-05 | La prueba automática comprueba que el borrador de la orden se conserva; que las prendas sigan escritas en la pantalla solo se ve en el navegador |
| **CA-17.1** | Sistema | Manual | — | PM-04 | La cámara solo existe en un celular real |
| **CA-17.2** | Funcionalidad | Automática y manual | — | PM-04 | La subida de la foto se prueba con una imagen generada; elegirla de la galería se prueba en el celular |
| **CA-18.2** | Sistema | Manual | — | PM-05 | Ampliar una foto es un comportamiento de la pantalla |
| **CA-28.1** | Funcionalidad | Automática y manual | — | PM-08 | La prueba automática usa el canal falso; que el WhatsApp llegue de verdad se prueba con el número de prueba de Meta |
| **CA-28.3** | Integración | Automática | `EnviarAviso` | — | Ocurre en la cola, sin pantalla |
| **CA-28.4** | Integración | Automática | `EnviarAviso` | — | Ocurre en la cola, sin pantalla |
| **CA-28.5** | Integración | Automática | `EnviarAviso` | — | Ocurre en la cola, sin pantalla |
| **CA-29.2** | Funcionalidad | Automática y manual | — | PM-04 | La prueba automática revisa el enlace con el celular y el mensaje; que se abra WhatsApp se prueba en el celular |
| **CA-29.3** | Funcionalidad | Automática | `ConfirmarEnvioAsistido` | — | La confirmación es otro caso de uso distinto del que lista los avisos |
| **CA-29.4** | Funcionalidad | Automática | `ConfirmarEnvioAsistido` | — | La confirmación es otro caso de uso distinto del que lista los avisos |
| **CA-30.1** | Integración | Automática | — | — | Ocurre en la cola, sin pantalla |
| **CA-30.2** | Funcionalidad | Automática | `AvisosPorEnviar` | — | Se ve en la lista de avisos pendientes, no en el envío |
| **CA-30.3** | Integración | Automática | `GenerarAviso` | — | El aviso nuevo lo crea el oyente del evento, no el envío |

## Cómo se prueba cada requisito no funcional

| Requisito | Forma | Cómo se prueba | Cuándo | Evidencia |
| --- | --- | --- | --- | --- |
| **RNF-01** | Manual | Con el volumen de 3 años, un script mide 100 solicitudes a cada pantalla de uso diario en el VPS y calcula el percentil 95; Lighthouse con perfil móvil mide la carga en 4G simulada | Sprint 4, con HT-06 | PM-06 |
| **RNF-02** | Automática | `ConsultasPorPaginaTest` cuenta las consultas del panel, el detalle de orden, la búsqueda de clientes y las listas de seguimiento con órdenes de 1 y de 10 prendas | Sprints 3 y 4, al crear cada pantalla | GitHub Actions |
| **RNF-03** | Automática | `AlmacenLocalPrivadoTest` guarda una imagen de 5 MB y mide el lado mayor y el peso de lo guardado (CA-17.5) | Sprint 3, con HU-17 | GitHub Actions |
| **RNF-04** | Automática | Con un canal que tarda 10 s, marcar la última prenda Terminada responde en menos de 1 s y el envío queda en la cola sin ejecutarse (CA-28.2) | Sprint 4, con HU-28 | GitHub Actions |
| **RNF-05** | Manual | Recorrido de las historias Must en Chrome para Android, Chrome de escritorio y Edge de escritorio | Sprint 4 | PM-05 |
| **RNF-06** | Automática y manual | `WhatsAppCloudApiCanalTest` comprueba con respuestas simuladas que solo se llama a la API oficial; una búsqueda en el código confirma que ninguna otra clase la llama; un envío real al número de prueba de Meta | Sprint 4, con HT-01 y HU-28 | PM-08 |
| **RNF-07** | Manual | Cada pantalla a 360 px: sin desplazamiento horizontal y con controles de al menos 44 × 44 px, medidos con un script sobre el navegador | Sprints 3 y 4 | PM-05 |
| **RNF-08** | Automática | `DineroTest` prueba el formato de los pesos; las pruebas de los criterios comparan fechas y horas con el formato exacto de las pantallas | Sprint 3 | GitHub Actions |
| **RNF-09** | Automática y manual | Las pruebas de los criterios comparan el texto exacto de cada mensaje; una lista de chequeo revisa los demás mensajes de validación | Sprint 4 | PM-05 |
| **RNF-10** | Automática | Una prueba por acción irreversible (cancelar orden, anular pago, eliminar prenda, eliminar foto y devolver sin arreglar): sin la confirmación, nada cambia | Sprints 3 y 4, con cada acción | GitHub Actions |
| **RNF-11** | Manual | Auditoría de Lighthouse en cada pantalla y revisión del contraste de los textos | Sprint 4 | PM-05 |
| **RNF-12** | Manual | Prueba de usabilidad con 3 compañeros de formación | Sprint 4 | PM-01 |
| **RNF-13** | Automática | `RegistrarOrdenTest`, `RegistrarPagoTest` y `EntregarOrdenTest` fuerzan un error a mitad de la operación y comprueban que la base quedó como estaba | Sprints 3 y 4 | GitHub Actions |
| **RNF-14** | Automática | Se envía dos veces la misma solicitud con el mismo token, para una orden y para un pago (CA-23.5) | Sprints 3 y 4 | GitHub Actions |
| **RNF-15** | Manual | Restauración completa desde la copia de Google Drive en otra máquina, con cronómetro | Sprint 4, con HT-05 | PM-02 |
| **RNF-16** | Manual | Monitor externo cada 5 minutos desde el despliegue; se reporta la disponibilidad del período medido | Sprint 4, con HT-04 | PM-07 |
| **RNF-17** | Automática | Con un canal que siempre falla, el aviso se intenta 3 veces con espera creciente y queda para envío asistido (CA-28.5) | Sprint 4, con HU-28 | GitHub Actions |
| **RNF-18** | Manual | Una solicitud por HTTP responde con redirección a HTTPS y el certificado es válido | Sprint 4, con HT-04 | PM-07 |
| **RNF-19** | Automática | `CambiarContrasenaTest` comprueba que la contraseña se guarda con hash y exige 8 caracteres (CA-02.1, CA-02.3) | Sprint 3, con HU-02 | GitHub Actions |
| **RNF-20** | Automática | Seis intentos fallidos de inicio de sesión en un minuto (CA-01.3) | Sprint 3, con HU-01 | GitHub Actions |
| **RNF-21** | Automática | Se adelanta el tiempo 8 horas y 1 minuto y la sesión ya no sirve (CA-01.5) | Sprint 3, con HU-01 | GitHub Actions |
| **RNF-22** | Automática | `AislamientoEntreNegociosTest` pide cada ruta con datos de otro negocio y espera la respuesta de «no existe»; falla si aparece una ruta que no está en su lista | Sprints 3 y 4, desde HT-03 | GitHub Actions |
| **RNF-23** | Automática y manual | Pruebas automáticas de CSRF, de un nombre de cliente con código HTML y de una búsqueda con inyección SQL; lista de chequeo OWASP Top 10 sobre el sistema desplegado | Sprint 4 | PM-07 |
| **RNF-24** | Automática | gitleaks revisa el repositorio en GitHub Actions en cada envío | Sprint 3, con HT-02 | GitHub Actions |
| **RNF-25** | Automática | Una foto pedida sin sesión (CA-18.3) y desde otro negocio, en `AislamientoEntreNegociosTest`, no se entrega | Sprint 3, con HU-18 | GitHub Actions |
| **RNF-26** | Revisión | Los formularios solo piden nombre y celular del cliente y la política de tratamiento de datos está publicada | Sprint 4 | PM-07 |
| **RNF-27** | Automática | Las reglas de PHPat en `tests/Arquitectura/ReglasDeCapas.php` impiden que una capa dependa de otra más externa, como el dominio de Laravel; `ControladoresSinConsultasTest` impide que un controlador consulte la base de datos | Sprint 3, con HT-02 | GitHub Actions |
| **RNF-28** | Automática | Reporte de cobertura de `app/Dominio` y `app/Aplicacion`; este script cuenta las reglas con prueba escrita | Sprints 3 y 4 | GitHub Actions y resumen de este plan |
| **RNF-29** | Automática | Pint en modo de revisión y Larastan en nivel 5 | Sprint 3, con HT-02 | GitHub Actions |
| **RNF-30** | Automática | Pruebas, estilo y análisis estático en cada envío | Sprint 3, con HT-02 | Historial de GitHub Actions |
| **RNF-31** | Automática | GitHub Actions construye la base vacía solo con las migraciones y compara sus columnas con `esquema.sql` | Sprint 3, con HT-02 | GitHub Actions |
| **RNF-32** | Revisión | Ya verificado en el [modelo de datos](../03-diseno/modelo-de-datos/README.md) con `verificar_modelo.py`; se repite si cambia el esquema | Sprint 2, terminado | Modelo de datos |
| **RNF-33** | Manual | Instalación en otra máquina siguiendo solo el manual técnico, con cronómetro | Cierre, con DOC-23 | PM-03 |
| **RNF-34** | Manual | El VPS corre el mismo commit de `main`, sin archivos modificados, configurado solo con variables de entorno | Sprint 4, con HT-04 | PM-07 |
| **RNF-35** | Manual | Instalación del APK en un Android real, foto con la cámara, apertura de WhatsApp e instalación desde el navegador en otro celular | Sprint 4, con HT-07 | PM-04 |

> **Sobre RNF-30.** El repositorio es público desde el 14 de septiembre de 2026, así que GitHub Actions no tiene costo. Aun así, GitHub no inicia los trabajos mientras la cuenta tenga un bloqueo de facturación. En ese caso, `python scripts/calidad.py` corre los mismos pasos en la máquina de desarrollo antes de cada commit, y su resultado es la evidencia. La regla de suspensión de este plan obliga a arreglar un `main` en rojo antes de seguir.

## Herramientas

| Herramienta | Para qué | Por qué esta |
| --- | --- | --- |
| **PHPUnit** | Pruebas unitarias, de integración y de funcionalidad | Viene con Laravel y sus pruebas son clases de PHP como el resto del código. Pest es una alternativa válida, pero agrega otra forma de escribir que habría que aprender en un plazo corto |
| **PHPat** | Reglas de arquitectura | Corre dentro de Larastan, que ya se ejecuta por RNF-29; no agrega otro paso |
| **Laravel Pint y Larastan** | Estilo y análisis estático (RNF-29) | Son las herramientas oficiales o recomendadas por la comunidad de Laravel |
| **PCOV** | Cobertura de líneas (RNF-28) | Solo mide cobertura, así que es más rápido que Xdebug en GitHub Actions |
| **GitHub Actions con MySQL 8.4** | Ejecutar todo en cada envío (RNF-30) | Gratuito para el repositorio y con la misma versión de MySQL del VPS |
| **gitleaks** | Buscar secretos en el repositorio (RNF-24) | Revisa también el historial de commits, no solo los archivos actuales |
| **Lighthouse** | Accesibilidad (RNF-11) y carga en 4G simulada (RNF-01) | Viene incluido en Chrome y Edge |
| **Script de Node con Edge** | Medir cada pantalla a 360 px (RNF-07) | Se adapta el script que ya genera las capturas de los mockups |
| **UptimeRobot, plan gratuito** | Monitor externo (RNF-16) | Revisa cada 5 minutos sin costo. La cuenta la crea el aprendiz |
| **Celular Android real** | Cámara, APK y WhatsApp (RNF-35) | Un emulador no prueba la cámara ni la instalación real |

### Por qué MySQL y no SQLite

Laravel suele correr sus pruebas con SQLite en memoria porque es más rápido. Aquí no se hace, por tres razones:

- La búsqueda sin tildes y el tipo de prenda que no se repite aunque cambien las mayúsculas (RN-43, CA-04.1, CA-09.2) dependen de la intercalación `utf8mb4_0900_ai_ci` de MySQL.
- La llave foránea compuesta de [ADR-004](../03-diseno/adr/ADR-004-negocio-en-ordenes.md) y el bloqueo de filas de RN-08 y RN-28 no se comportan igual en SQLite.
- Una prueba que pasa en SQLite podría fallar en el VPS.

Por eso las pruebas usan MySQL 8.4, la misma versión de WAMP y del VPS, en una base separada llamada `taller_pruebas`.

## Entornos

| Entorno | Para qué | Base de datos | Configuración |
| --- | --- | --- | --- |
| **Local, en WAMP** | Escribir y correr las pruebas mientras se programa | `taller_pruebas` en MySQL 8.4 | `.env.testing`, sin credenciales reales |
| **GitHub Actions** | Correr todo en cada envío | Servicio de MySQL 8.4 | Variables de prueba del flujo de trabajo |
| **VPS** | Pruebas de sistema: PM-02, PM-06, PM-07 y PM-08 | La del sistema desplegado | Variables de entorno del servidor (RNF-34) |
| **Celulares y navegadores** | PM-01, PM-04 y PM-05 | La del VPS | — |

El taller todavía no usa el sistema, así que las pruebas de sistema se hacen en la instalación del VPS con datos de ejemplo, que se borran antes de la entrega. La restauración de respaldos (PM-02) se hace en otra máquina para no tocar el VPS.

## Datos de prueba

- **Cada prueba automática arma sus propios datos** a partir de su criterio o del ejemplo de su regla, con fábricas de modelos. Empieza con la base vacía y no depende de otra prueba ni del orden en que corren.
- **Los ejemplos de las historias no forman un solo conjunto de datos.** El CA-32.1 habla de $33.000 por cobrar y el panel de los mockups muestra $76.000. Cada prueba construye solo lo que describe su criterio.
- **La hora nunca es la real.** Un reloj falso fija «hoy» en la fecha del criterio: el 14 de septiembre de 2026 para CA-07.3 y el 16 de septiembre para CA-33.1. Una prueba que depende del día en que se ejecuta pasa un día y falla otro.
- **Las pruebas de aislamiento crean dos negocios**, cada uno con sus clientes, órdenes, fotos y pagos.
- **Las pruebas manuales usan los datos de los mockups**, cargados desde los [datos de ejemplo del modelo](../03-diseno/modelo-de-datos/datos-de-ejemplo.sql). Un seeder corre sus fechas para que «hoy» sea el día de la prueba sin cambiar las distancias: la #0044 sigue con 4 días de atraso y la #0030 con 46 días sin reclamar. Así cada pantalla se compara con la captura de su mockup.
- **El volumen de 3 años** lo crea el generador de HT-06: 500 clientes, 750 órdenes, 2.200 prendas y 1.500 pagos.
- **Los celulares son ficticios** en las pruebas automáticas, como el 3104567890 de los ejemplos. Nunca se envía un mensaje a un tercero real: en PM-04 y PM-08 el cliente de prueba usa un celular del aprendiz (RNF-26).
- **Las fotos se generan en la prueba**, sin fotos de personas.

## Dobles de prueba

Un doble de prueba reemplaza una pieza real que es lenta, externa o impredecible. Las interfaces de la arquitectura existen para poder hacer este cambio sin tocar los casos de uso.

| Doble | Reemplaza | Variantes | Se usa en |
| --- | --- | --- | --- |
| **`RelojFijo`** | `RelojDeColombia` | Cualquier fecha y hora, y avanzar el tiempo | Toda regla con fechas: RN-07, RN-09, RN-33 a RN-36, CA-27.2 y CA-33.3 |
| **`CanalDeAvisoFalso`** | `WhatsAppCloudApiCanal` | Anota los mensajes, tarda 10 s, falla N veces y luego acepta, o siempre falla | RNF-04, RNF-17 y los criterios de HU-28 a HU-30 |
| **Respuestas HTTP simuladas** | La API de WhatsApp de Meta | Mensaje aceptado, error del servidor y número inválido | `WhatsAppCloudApiCanalTest` (RNF-06) |
| **Disco falso** | El disco privado de las fotos | — | Las pruebas de fotos. La reducción usa el `AlmacenLocalPrivado` real sobre el disco falso, para medir la imagen de verdad (RNF-03) |

El canal falso devuelve el mismo `ResultadoDeEnvio` que los canales reales. Es el principio de sustitución de Liskov descrito en la arquitectura: si el canal falso se comportara distinto, las pruebas no dirían nada del canal real.

### Errores que harían pasar una prueba sin probar nada

| Error | Qué pasaría | Qué se hace |
| --- | --- | --- |
| Laravel desactiva la protección CSRF al correr pruebas | Una prueba de CSRF pasaría aunque la protección no estuviera activa | La prueba de RNF-23 la activa de forma explícita |
| Usar la cola `sync` en las pruebas | El envío correría dentro de la solicitud y no se probaría que la pantalla no espera a WhatsApp | La prueba de RNF-04 usa la conexión `database`, la misma del VPS, y PM-07 revisa que el VPS no use `sync` |
| Guardar fechas en UTC | Un pago a las 11:30 p. m. contaría al día siguiente; GitHub Actions corre en UTC | La aplicación fija America/Bogota; CA-27.2 y CA-33.3 prueban justo ese borde |
| Falta la extensión de imágenes de PHP | La reducción de fotos fallaría o la prueba se saltaría sin avisar | GitHub Actions instala GD, y la prueba de RNF-03 falla si no está, en vez de saltarse |

## Convenciones

- **Una clase de prueba por clase del sistema:** `RegistrarPago` se prueba en `tests/Feature/Pagos/RegistrarPagoTest.php`.
- **Cada método empieza con el código que prueba:** `test_ca_23_3_supera_el_saldo`, `test_rn_28_...` o `test_rnf_14_...`. Así se ve en el código de dónde sale cada prueba, y el script cuenta cuáles ya están escritas. Los nombres de los casos de prueba ya traen el método que les corresponde.
- **Preparar, actuar y comprobar:** cada prueba arma los datos del criterio, ejecuta una sola acción y comprueba el resultado esperado.
- **Los mensajes se comparan con su texto exacto**, tal como lo dice el criterio (RNF-09).
- **Un defecto se corrige escribiendo primero la prueba que lo reproduce**, con el número de su issue: `test_defecto_75_...`. Esa prueba queda para siempre como prueba de regresión.

## Criterios de entrada y salida

### Para empezar a probar una historia

- La historia cumple la definición de «lista para desarrollar», que incluye su mockup verificado contra sus criterios (DOC-13).
- HT-02 está terminado: GitHub Actions ya corre las pruebas.
- Si la historia agrega rutas, HT-03 está terminado.

### Para que una historia quede terminada

Se suma a la [definición de terminado](../00-scrum/plan-de-sprints.md#definición-de-terminado):

- Las pruebas de todos sus criterios y de las reglas que toca están escritas y pasan en GitHub Actions.
- La parte manual de sus criterios quedó anotada en la hoja de registro de su protocolo.
- Sus rutas nuevas están en la prueba de aislamiento.
- Pint y Larastan no reportan errores, y la cobertura del dominio no baja del 80 %.

### Para entregar

| Condición | Meta |
| --- | --- |
| Criterios de las historias Must | 100 % verificados |
| Criterios de las historias Should y Could | 100 % de las implementadas; las que se recorten se declaran en el informe con su motivo, junto con las reglas que solo ellas usan |
| Reglas de negocio | Todas con una prueba que pasa (RNF-28) |
| Cobertura de líneas de `app/Dominio` y `app/Aplicacion` | 80 % o más |
| Defectos críticos o altos abiertos | Ninguno |
| Protocolos de prueba manual | Ejecutados y registrados; PM-03 se ejecuta en el cierre |
| GitHub Actions en `main` | En verde |

### Cuándo se suspenden las pruebas

| Situación | Qué se hace |
| --- | --- |
| GitHub Actions está en rojo en `main` | No se empieza otra historia hasta dejarlo en verde |
| No hay dominio con HTTPS al terminar el Sprint 3 | El APK no se puede probar (ADR-006): PM-04 se aplaza, se prueba la instalación desde el navegador y se declara en el informe |
| Meta no habilita el número de prueba o la plantilla | CA-28.1 se prueba con el canal falso y el envío asistido; el envío real queda pendiente y se declara |
| No se consiguen 3 compañeros para PM-01 | Se hace con quienes estén disponibles y se declara. Nunca la reemplaza el aprendiz, que ya conoce el sistema |

## Defectos

Un defecto es cualquier diferencia entre lo que hace el sistema y lo que dice un criterio, una regla o un requisito no funcional.

- **Dónde se registra:** un issue de GitHub con la etiqueta `defecto`, en el mismo tablero del proyecto.
- **Qué contiene:** los pasos para reproducirlo, lo esperado con el código que lo exige (CA, RN o RNF), lo obtenido, una captura si aplica y la severidad.
- **Cómo avanza:** abierto → en corrección, con la prueba que lo reproduce → verificado, cuando esa prueba pasa en GitHub Actions → cerrado.

| Severidad | Qué significa | Ejemplo | Cuándo se corrige |
| --- | --- | --- | --- |
| **Crítica** | Datos perdidos, equivocados o expuestos | Un negocio ve las órdenes de otro; un saldo queda negativo | Antes que cualquier otra cosa |
| **Alta** | Una historia Must no se puede completar | No se puede registrar un pago | En el mismo sprint |
| **Media** | Se puede completar, pero con un rodeo | El filtro de órdenes se pierde al volver del detalle | Antes de la entrega, si hay tiempo |
| **Baja** | Apariencia o redacción | Un botón desalineado a 360 px | Se anota y se corrige si sobra tiempo |

## Cronograma

| Cuándo | Qué se prueba | Quién | Evidencia |
| --- | --- | --- | --- |
| **Sprint 2** · hasta el 28 sep | La documentación, con los scripts de verificación | Aprendiz | Commits |
| **Sprint 3** · 29 sep – 5 oct | HT-02: GitHub Actions, arquitectura y gitleaks. HT-03: aislamiento. Las pruebas de cada historia al construirla. PM-05 sobre las pantallas que ya existan | Aprendiz | GitHub Actions |
| **Sprint 4** · 6 – 10 oct | Las pruebas de cada historia. PM-08 con HT-01 y HU-28, PM-07 con HT-04, PM-02 con HT-05, PM-06 con HT-06, PM-04 con HT-07, PM-05 completo y PM-01 a más tardar el 9 de octubre | Aprendiz y 3 compañeros | Hojas de registro e informe de pruebas (DOC-21) |
| **Cierre** · 11 – 13 oct | PM-03 con el manual técnico (DOC-23) y una ejecución completa de las pruebas antes de la sustentación | Aprendiz y quien preste la otra máquina | Anexo del informe de pruebas |

## Riesgos

| Riesgo | Impacto | Mitigación |
| --- | --- | --- |
| Las pruebas consumen el tiempo de desarrollo | Alto | Son parte de la definición de terminado. Si no alcanza, se recortan primero las historias Could y Should, no las pruebas |
| Pruebas que pasan sin probar nada | Alto | Cada prueba se ve fallar antes de escribir el código; los errores conocidos están en la tabla de dobles de prueba |
| El sistema se comporta distinto en local, en GitHub Actions y en el VPS | Medio | PHP 8.4 y MySQL 8.4 en los tres, y zona horaria fija en la aplicación |
| Dependencias de terceros: Meta, el dominio y los compañeros | Medio | Se piden lo antes posible y cada una tiene su plan B en «Cuándo se suspenden las pruebas» |
| El aprendiz prueba su propio trabajo | Medio | Los criterios se escribieron antes que el código, los scripts contrastan cada documento con los demás, PM-01 lo hacen otras personas y el instructor aprueba el producto terminado |

## Pendiente

- El instructor aprueba el plan junto con el producto terminado (DOC-14).
- Los resultados van en el informe de pruebas (DOC-21).
