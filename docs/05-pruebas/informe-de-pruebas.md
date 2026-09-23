# Informe de pruebas

**Entregable:** DOC-21 · **Corte:** 22 de septiembre de 2026 · **Commit:** `9b711de` · **Estado:** en curso, se completa en el cierre

Dice qué se probó, con qué resultado y qué falta. Sigue el [plan de pruebas](plan-de-pruebas.md), que es quien define la estrategia, los criterios de salida y los protocolos.

> **Este informe se escribe mientras se prueba, no al final.** Las pruebas que faltan (PM-01 y PM-03) necesitan a otras personas, y de PM-05, PM-06 y PM-07 quedan pasos por ejecutar; sus filas están marcadas y se llenan con su hoja de registro.

## 1. Resumen

| Qué | Resultado |
| --- | --- |
| **Pruebas automáticas** | **286 pruebas, todas pasan** (1.557 comprobaciones), en poco más de un minuto |
| **Dónde corren** | En cada envío a GitHub, y en la máquina de desarrollo antes de cada commit |
| **Cobertura** | El flujo exige **80 % o más** de `app/Dominio` y `app/Aplicacion`; si baja, la corrida falla |
| **Criterios de aceptación** | **125 de 125 verificados**, desde que se construyeron las siete historias que faltaban |
| **Reglas de negocio** | **44 de 44 con prueba que pasa**, desde que se construyó HU-36 |
| **Historias Must** | **26 de 26 verificadas**, y también las 8 *Should* y las 2 *Could*: **las 36 del backlog** |
| **Pruebas manuales** | 6 de 8 ejecutadas: PM-02, PM-04 y PM-08 aprobadas; PM-05, PM-06 y PM-07 parciales; faltan PM-01 y PM-03 |
| **Defectos abiertos** | **Ninguno.** Los 10 encontrados se corrigieron; los cuatro últimos los halló PM-07, y uno de ellos lo causó la corrección de otro |
| **GitHub Actions en `main`** | En verde |

**Lo que falta para poder entregar:** PM-01 (usabilidad, necesita compañeros), PM-03 (instalación, necesita otra persona y otra máquina), lo que queda de PM-07 (la prueba de humo y dos revisiones a mano), las secciones A, B y C de PM-05 y la medición de Lighthouse de PM-06.

## 2. Pruebas automáticas

Cada criterio de aceptación y cada regla de negocio se prueban con el ejemplo escrito en su documento. Las pruebas se escribieron junto con cada historia, y ninguna historia se dio por terminada sin ellas.

| Medida | Valor |
| --- | --- |
| Pruebas que se ejecutan | 286, todas pasan |
| Comprobaciones dentro de ellas | 1.557 |
| Métodos escritos en `sistema/tests` | 275 |
| Métodos planeados en el plan | 167, **todos escritos**: al construir las siete historias que faltaban se escribieron los 15 que quedaban |
| Tiempo de la corrida completa | 62 segundos en la máquina de desarrollo |

**Qué más revisa cada envío, además de las pruebas:** secretos en el historial (gitleaks), dependencias con vulnerabilidades conocidas, estilo con Pint, análisis estático con Larastan y las reglas de capas con PHPat, vistas sin escapar, que las migraciones produzcan el esquema documentado, y los verificadores que contrastan los documentos entre sí.

## 3. Resultado por historia

| Prioridad | Historias | Con todos sus criterios verificados | Sin construir |
| --- | --- | --- | --- |
| **Must** | 26 | **26** | 0 |
| **Should** | 8 | **8** | 0 |
| **Could** | 2 | **2** | 0 |

Dos criterios de historias Must no tienen prueba automática porque **son manuales por diseño**, y quedaron verificados a mano:

| Criterio | Por qué es manual | Dónde se verificó |
| --- | --- | --- |
| **CA-17.1** Tomar una foto con la cámara | La cámara solo existe en un celular real | [PM-04](pruebas-manuales/PM-04-app-en-el-celular.md), aprobado |
| **CA-18.2** Ver una foto ampliada | Es comportamiento del navegador | [PM-05](pruebas-manuales/PM-05-pantallas-y-navegadores.md), sección D, aprobado |

### Ninguna historia quedó sin construir

El plan permitía recortar historias *Should* y *Could* si no alcanzaba la capacidad, y exigía declararlas con su motivo. Hasta el 22 de septiembre siete estaban declaradas así. El 23 se construyeron las siete, con sus pruebas, en este orden: HU-36, HU-13, HU-19, HU-24, HU-10, HU-35 y HU-16.

| Historia | Prioridad | Qué agregó |
| --- | --- | --- |
| **HU-36** Devolver una prenda sin arreglar | Should | Cerró **RN-44**, la última regla sin prueba, y construyó PT-12, la única pantalla diseñada que faltaba |
| **HU-13** Eliminar una prenda registrada por error | Should | Con HU-19 completó RNF-10: las cinco acciones irreversibles piden confirmación |
| **HU-19** Eliminar una foto | Should | La foto borrosa deja libre su lugar para otra |
| **HU-24** Registrar un abono al recibir la orden | Should | La orden y su abono se guardan juntos, sin dos pasos con el cliente enfrente |
| **HU-10** Registrar un cliente nuevo desde la orden | Should | Lo escrito en la orden ya no se pierde mientras se registra al cliente |
| **HU-35** Cambiar el plazo sin reclamar | Could | El plazo de 30 días pasó a ser del negocio |
| **HU-16** Renombrar o desactivar tipos de prenda | Could | La lista de tipos, que crece sola con «Otro», ahora se puede ordenar |

## 4. Reglas de negocio

Las 44 reglas tienen su caso de prueba definido con el ejemplo de su documento (RNF-28), y **las 44 tienen prueba escrita y pasa**. La última en cerrarse fue **RN-44** (devolver una prenda sin arreglar): su historia, HU-36, se construyó el 23 de septiembre justamente para no dejar una regla sin comprobar.

Durante este corte se corrigió un desajuste: el plan nombraba pruebas de **RN-32 a RN-36** que no existían con ese nombre. Al escribirlas apareció que RN-34, RN-35 y RN-36 vivían dentro de las consultas y no en el dominio, donde las pone la arquitectura. Se creó `ReglasDeSeguimiento`, las consultas la usan y las tres reglas quedaron con su prueba (`7c58cf6`).

## 5. Requisitos no funcionales

De los 35, **19 se comprueban solos en cada envío** y están en verde: RNF-02, 03, 04, 08, 13, 14, 17, 19, 20, 21, 22, 24, 25, 27, 28, 29, 30 y 31, más RNF-32, que se verificó al revisar el modelo de datos. Los demás dependen de una prueba manual o tienen una parte manual:

| Requisito | Cómo se verifica | Estado |
| --- | --- | --- |
| **RNF-01** Tiempo de respuesta | [PM-06](pruebas-manuales/PM-06-rendimiento.md): el servidor con el volumen de 3 años, y el tiempo de carga con Lighthouse | **Parcial.** El servidor aprobado: las cinco pantallas entre 8 y 18 veces por debajo del máximo de 500 ms. Falta la medición en el navegador |
| **RNF-05** Navegadores del taller | [PM-05](pruebas-manuales/PM-05-pantallas-y-navegadores.md), sección A | **Pendiente** |
| **RNF-06** Un adaptador por canal de WhatsApp | Pruebas de cada adaptador, más una revisión en [PM-08](pruebas-manuales/PM-08-aviso-real-por-whatsapp.md) | **Cumple** |
| **RNF-07** Diseño para el celular | PM-05, sección B | **Pendiente** |
| **RNF-09** Mensajes de error | Pruebas automáticas del texto exacto, más la lista de chequeo de PM-05, sección C | **Parcial.** La parte automática cumple. En esta ronda se agregaron las pantallas de error del sistema —404, 403, 419, 429, 500 y 503—, que antes salían con el texto crudo del servidor; falta la revisión de PM-05 |
| **RNF-10** Confirmación antes de una acción irreversible | Una prueba por acción | **Cumple.** Las cinco acciones tienen su confirmación probada: cancelar una orden, anular un pago, devolver una prenda sin arreglar, eliminar una prenda y eliminar una foto |
| **RNF-11** Contraste y accesibilidad | PM-05, con Lighthouse | **Pendiente** |
| **RNF-12** Usabilidad | [PM-01](pruebas-manuales/PM-01-usabilidad.md) con compañeros | **Pendiente** |
| **RNF-15** Respaldos y restauración | [PM-02](pruebas-manuales/PM-02-restauracion-de-respaldos.md) | **Aprobado.** Restauración en 7 segundos contra un máximo de 60 minutos |
| **RNF-17** Un fallo de WhatsApp no deja al cliente sin aviso | `EnviarAvisoTest` con un canal que siempre falla, más el reinicio de la cola en PM-07, sección B | **Cumple.** Los tres reintentos con espera creciente y el paso a envío asistido están probados; y si el trabajador se cae, el servicio lo levanta solo en menos de veinte segundos |
| **RNF-18** HTTPS | PM-07, sección A | **Cumple.** HTTP redirige con 301 y el certificado es válido hasta el 18 de diciembre |
| **RNF-26** Datos personales | PM-07, sección D | **Cumple desde esta ronda.** Los formularios piden solo nombre y celular, y la política de tratamiento de datos ya está publicada y se lee sin iniciar sesión |
| **RNF-34** Servidor sin cambios propios | PM-07, sección B | **Cumple.** El VPS corre el mismo commit de `main`, sin archivos modificados y configurado solo por variables de entorno |
| **RNF-16** Monitoreo | PM-07, sección E | **Parcial.** El monitor consulta el sistema cada 5 minutos desde el 21 de septiembre; falta anotar la disponibilidad del período |
| **RNF-23** Defensa ante ataques web | Pruebas automáticas de CSRF, HTML y SQL, más la lista de chequeo de PM-07 | **Parcial.** La parte automática cumple, con dos pruebas nuevas escritas en esta ronda. En PM-07 pasan ocho de los diez riesgos; faltan A01 y A03 a mano sobre el sistema desplegado |
| **RNF-33** Instalación en 30 minutos | [PM-03](pruebas-manuales/PM-03-instalacion-desde-el-manual.md) | **Pendiente.** En un ensayo previo del autor del manual tomó 12 min 35 s, pero la prueba exige otra persona |
| **RNF-35** App en el celular | [PM-04](pruebas-manuales/PM-04-app-en-el-celular.md) | **Aprobado con observaciones** |

## 6. Pruebas manuales

| Prueba | Fecha | Resultado |
| --- | --- | --- |
| **PM-01** Usabilidad | — | **Pendiente.** Necesita 3 compañeros de formación; el aprendiz no puede reemplazarlos |
| **PM-02** Restauración de respaldos | 21 sep | **Aprobado.** 7 segundos; conteos idénticos; una orden de control abrió con sus prendas, su saldo y sus 3 fotos |
| **PM-03** Instalación siguiendo el manual | — | **Pendiente.** Necesita otra persona y otra máquina; se hace en el cierre, con DOC-23 |
| **PM-04** App en el celular | 22 sep | **Aprobada con observaciones.** Los 10 pasos pasan en un Redmi Note 14 5G con Android 15 |
| **PM-05** Pantallas y navegadores | 21 sep | **Parcial.** Solo la sección D, en Brave, Chrome, Edge y Android |
| **PM-06** Rendimiento | 21 sep | **Parcial.** Aprobado en el servidor; falta la medición de Lighthouse en el navegador |
| **PM-07** Seguridad y operación | 22 sep | **Parcial.** Las secciones A (cifrado), B (código y configuración, con la cola levantándose sola) y D (datos personales) pasan completas; C pasa en ocho de diez riesgos. Encontró los dos defectos de abajo |
| **PM-08** Aviso real por WhatsApp | 16 sep, verificado el 21 | **Aprobado con salvedades.** El aviso salió solo en 3 segundos; el mensaje llegó al número del aprendiz, no de un tercero |

### Observaciones y salvedades declaradas

- **PM-04:** con Brave como navegador predeterminado, el APK muestra la barra de Brave unos 2 segundos mientras comprueba el sitio; con Chrome abre directo. No es un defecto del APK ni del enlace con el sitio, que Android valida (`verified`). Además, el paso del envío asistido se probó pasando un aviso a pendiente a mano, porque el negocio sí tiene WhatsApp automático.
- **PM-08:** el aviso real se envió al celular del aprendiz. Enviar a un tercero exige su consentimiento (RNF-26), así que queda declarado.
- **PM-06:** la medición del servidor está hecha; la del navegador (Lighthouse) la debe correr el aprendiz en su equipo.

## 7. Defectos

Ninguno abierto. Los que se encontraron al probar se corrigieron y quedaron cubiertos por una prueba o por el protocolo que los descubrió.

| Defecto | Dónde salió | Severidad | Corrección |
| --- | --- | --- | --- |
| Las miniaturas del detalle de la orden no llevaban a las fotos grandes: había que escribir la dirección a mano | PM-05 | Media | Las miniaturas son un enlace a las fotos de su prenda (`fc48f20`), con prueba automática |
| Las fotos subidas desde el servidor respondían error 500 | Operación tras PM-04 | Alta | La carpeta se había creado como `root`. Se corrigió el dueño y quedó escrito en el manual técnico |
| `restaurar.sh` no aceptaba un MySQL en otro puerto | PM-02 | Media | Se agregó `MYSQL_PORT` (`492b6d5`) |
| El respaldo se quedaba esperando una entrada que nadie escribía | PM-02 | Media | Se corrigió la llamada dentro del contenedor (`492b6d5`) |
| Una prueba de fotos fallaba según la máquina | Corrida en otra máquina | Baja | Se fijó la calidad de la imagen y se adaptaron las medidas (`f439917`, `765bdb6`) |
| El plan nombraba pruebas de RN-32 a RN-36 que no existían | Revisión con el portal | Media | Se escribieron y las reglas de seguimiento se movieron al dominio (`7c58cf6`) |
| **El sistema no enviaba ninguna cabecera de seguridad**: ni política de contenido, ni `X-Frame-Options`, ni `Referrer-Policy`, ni `nosniff` | PM-07, A05 | Media | Las envía el contenedor, sin cambiarles las cabeceras a los otros proyectos del dominio (`38ed8fb`) |
| **La política de tratamiento de datos no existía**, aunque su ruta estaba especificada desde el Sprint 2 | PM-07, D | Media | Se escribió y se publicó, enlazada desde el inicio de sesión, con su prueba automática (`f086f13`) |
| **Los errores salían con la página cruda del servidor**: un «404 Not Found» en inglés, sin decir qué hacer, contra lo que pide RNF-09 | PM-07, A05 | Baja | Se escribieron las pantallas de error del taller (404, 403, 419, 429, 500 y 503), cada una con su explicación y su salida, y su prueba |
| **La política de contenido dejó sin iconos a toda la app.** La corrección anterior de RNF-23 no permitía imágenes `data:`, y los iconos son SVG escritos dentro de la hoja de estilos | Al revisar en el navegador la pantalla de error recién desplegada | Media | Se permitió `data:` en `img-src`, que no deja ejecutar código, y se escribió una prueba que compara la política con lo que la hoja de estilos carga de verdad (`9b711de`) |

**Comportamiento revisado y declarado sin defecto:** en PM-05, al cerrar sesión y usar «Atrás» y «Adelante», el navegador muestra la pantalla de inicio de sesión y no datos del taller. Se comprobó en los registros del servidor que toda página con datos responde con una redirección al inicio de sesión (CA-01.4).

## 8. Criterios de salida

Del [plan de pruebas](plan-de-pruebas.md#para-entregar):

| Condición | Meta | Hoy |
| --- | --- | --- |
| Criterios de las historias Must | 100 % verificados | **Cumple:** 26 de 26 historias |
| Criterios de las Should y Could implementadas | 100 %, y las recortadas se declaran | **Cumple:** las 10 están construidas y verificadas; no quedó ninguna recortada |
| Reglas de negocio | Todas con una prueba que pasa | **Cumple:** 44 de 44 |
| Cobertura de `Dominio` y `Aplicacion` | 80 % o más | **Cumple:** el flujo falla si baja |
| Defectos críticos o altos abiertos | Ninguno | **Cumple** |
| Protocolos manuales | Ejecutados y registrados | **No cumple todavía:** faltan PM-01 y PM-03, y completar PM-05, PM-06 y PM-07 |
| GitHub Actions en `main` | En verde | **Cumple** |

## 9. Qué falta antes de la entrega

| Pendiente | Quién lo hace | Cuándo |
| --- | --- | --- |
| **PM-01 · Usabilidad** con 3 compañeros; si no se consiguen, se hace con quienes estén y se declara | Aprendiz y compañeros | Antes del 9 de octubre |
| **PM-03 · Instalación** siguiendo el manual técnico, en otra máquina | Un compañero, con el aprendiz observando | Cierre, 11 al 13 de octubre |
| **Terminar PM-07:** la prueba de humo y las dos revisiones a mano (A01 y A03) | Aprendiz | Antes del cierre |
| **PM-05 · Secciones A, B y C** en los tres navegadores | Aprendiz | Antes del cierre |
| **PM-06 · Lighthouse** en el navegador, para cerrar el tiempo de carga | Aprendiz | Antes del cierre |
| **Decidir sobre `Strict-Transport-Security`:** vale para todo el dominio, no solo para el sistema | Dueño del dominio | Antes del cierre |
| **Una corrida completa** de todas las pruebas antes de la sustentación | Aprendiz | Cierre |

## 10. Dónde está la evidencia

- **Registros de cada prueba manual:** en [pruebas-manuales/](pruebas-manuales/), al final de cada protocolo.
- **Casos de prueba** de cada criterio y regla, con el nombre de su prueba: en [casos-de-prueba/](casos-de-prueba/).
- **Corridas automáticas:** en GitHub Actions, una por envío.
- **Trazabilidad completa:** el [portal del proyecto](https://aryannext.github.io/puntada/) enlaza cada criterio con su prueba y con el código que lo cumple.
