# Guía para la reunión con el instructor · Sprint 1

> **Ya no se usa para validar por etapas.** El 14 de septiembre de 2026 se supo que el instructor aprueba el producto terminado al final (F-04, DOC-14). Las preguntas y respuestas de defensa sirven para preparar la sustentación (DOC-24).

**Para:** DOC-12 · validación de requisitos y acuerdo del rol de Product Owner · **Cuándo:** entre el 15 y el 21 de septiembre de 2026, idealmente antes del viernes 18 · **Duración:** 40 minutos

## Qué tienes que conseguir

Sal de la reunión con tres cosas:

1. **Una respuesta sobre el rol:** si el instructor acepta actuar como Product Owner en representación del cliente, porque la dueña no está disponible.
2. **Un veredicto por documento:** aprobado, aprobado con cambios o no aprobado, con los cambios anotados.
3. **Las decisiones que dependen de él** (la lista de preguntas D1 a D10, más abajo).

Todo queda en el [acta de validación](actas/acta-validacion-sprint-1.md). Llévala abierta y llénala durante la reunión. Si la reunión cae al final de la semana, sirve además como **revisión del Sprint 1** con el Product Owner.

## Antes de la reunión

### 1. Pide la cita

> Buenos días, instructor Oscar. Quiero mostrarle el análisis del proyecto formativo que terminé en el Sprint 1 (árbol de problemas, requisitos, reglas de negocio, historias de usuario y backlog) para que lo valide antes de empezar el diseño. También quiero proponerle que actúe como Product Owner del proyecto, porque la dueña del taller no está disponible. ¿Tendría 40 minutos esta semana? Gracias. Cristian Cantillo Mejía, ficha 2480542.

### 2. Decide cómo le vas a mostrar los documentos

El repositorio es privado, así que el instructor no puede abrir los enlaces por su cuenta.

| Opción | Cómo | A favor | En contra |
| --- | --- | --- | --- |
| **Mostrarlos desde tu computador** | Abres GitHub o las páginas HTML y compartes pantalla o se los enseñas en persona | No hay que configurar nada | Después no puede revisarlos por su cuenta |
| **Invitarlo al repositorio** | Necesitas su usuario de GitHub; se le da permiso de solo lectura | Revisa cuando quiera y ve el tablero y los commits | Tiene que tener cuenta en GitHub |
| **Enviarle un PDF** | Se exportan los documentos principales | Lo abre en cualquier parte | Hay que volver a exportarlo con cada cambio |

Recomendación: muéstraselos desde tu computador en la reunión y pregúntale si quiere acceso al repositorio (pregunta D7).

### 3. Ten abiertas estas pestañas, en este orden

1. [Árbol de problemas](../01-problema/arbol-de-problemas.html) y [árbol de objetivos](../01-problema/arbol-de-objetivos.html)
2. [Proceso actual y propuesto](../01-problema/proceso-actual-y-propuesto.html)
3. [Alcance](../01-problema/alcance.md)
4. [Historias de usuario](../02-requisitos/historias-de-usuario.md), en HU-07
5. [Matriz de trazabilidad](../02-requisitos/matriz-de-trazabilidad.html)
6. [Product backlog](product-backlog.md) y el [tablero](https://github.com/users/Aryannext/projects/1)
7. [Acta de validación](actas/acta-validacion-sprint-1.md)

### 4. Repasa hasta poder explicarlo sin leer

La definición de terminado del proyecto exige que puedas explicar cada documento sin leerlo. Si puedes decir estas cinco frases con tus palabras, estás listo:

- **El problema:** el taller no controla órdenes, entregas ni cobros porque todo vive en la memoria de la dueña: nada se anota, las prendas de todos van juntas a un rincón y las deudas se recuerdan.
- **La solución:** un sistema web donde cada orden queda registrada con fotos y número, el estado de la orden se calcula de sus prendas, el saldo se calcula de los pagos y el aviso por WhatsApp sale solo.
- **Por qué construirlo:** ninguna de las 9 alternativas revisadas es gratuita, pensada para Colombia, con aviso automático por WhatsApp y con seguimiento de lo que el taller hoy no puede medir.
- **Cómo se sabe que cada historia sirve:** cada una dice de qué causa nació, y un script genera la matriz de trazabilidad y falla si algo queda sin cubrir.
- **Cómo se van a cumplir los 30 días:** los Sprints 3 y 4 comprometen 40 y 43 puntos Must, con un punto de control el 1 de octubre y un orden de recorte acordado.

## Agenda

| Min | Tema | Qué muestras | Qué necesitas que diga |
| --- | --- | --- | --- |
| 0–3 | Contexto y limitación | Proyecto rehecho desde cero; la dueña no está disponible | Que entiende la limitación (D2) |
| 3–6 | Rol de Product Owner | Qué implica, en cuatro revisiones cortas | Si acepta (D1) |
| 6–12 | El problema | Árbol de problemas, árbol de objetivos, proceso actual y propuesto | Veredicto de esos documentos |
| 12–17 | Qué entra y qué no | Alcance y análisis de alternativas | Veredicto del alcance (D3, D4, D10) |
| 17–25 | Requisitos | Reglas de negocio, RF y RNF en cifras; HU-07 completa; matriz | Veredicto de requisitos e historias |
| 25–31 | Plan | Backlog, capacidad por sprint y tablero | Acuerdo sobre el recorte (D9) |
| 31–37 | Lo que viene | Sprint 2: mockups, arquitectura, modelo de datos | D5, D6, D7, D8 |
| 37–40 | Cierre | Leer en voz alta lo anotado en el acta | Confirmación y fecha de la próxima revisión |

## Qué decir en cada tema

### Contexto y limitación

- La versión 1 fue una app construida antes de hacer el análisis, y no podías defender cada decisión. Por eso se rehízo desde cero siguiendo el orden problema → requisitos → diseño → desarrollo ([ADR-000](../03-diseno/adr/ADR-000-rehacer-en-vez-de-refactorizar.md)).
- La dueña no está disponible en estos 30 días. No se inventaron entrevistas: las fuentes son la especificación original (F-01), el prototipo (F-02), el análisis de alternativas (F-03) y tu conocimiento directo como familiar (F-05), cada hecho con su fecha. Por eso necesitas que él valide (F-04).

### Rol de Product Owner

Explícale que el rol es liviano. Consiste en:

- **Validar** hoy los requisitos y el backlog.
- **Revisar los mockups** antes del 28 de septiembre (DOC-14).
- **Ver el avance** al cierre de los Sprints 3 y 4 (5 y 10 de octubre).
- **Decidir qué sale** si en el punto de control del 1 de octubre no alcanza el tiempo.

### El problema

- **Árbol de problemas:** un problema central, 11 causas y sus efectos, cada una con su fuente. Hay un efecto descartado (desacuerdos con clientes) porque no ocurre: se registró para mostrar que se evaluó.
- **Magnitud:** entre 10 y 15 prendas por semana, ninguna anotada, y no se sabe cuántas están sin reclamar.
- **Proceso actual y propuesto:** muéstrale una fase en rojo y en verde. El rojo marca dónde nace cada causa; el verde, qué historia y qué regla la resuelve.

### Qué entra y qué no

- Cada función entra solo si nace de un medio del árbol de objetivos y cabe en 30 días.
- Menciona tres cosas que quedaron fuera y por qué: el registro de varios negocios (los datos quedan preparados, [ADR-002](../03-diseno/adr/ADR-002-un-taller-preparado-para-varios.md)), las APIs no oficiales de WhatsApp (riesgo de bloqueo del número) y la impresora de etiquetas (no hay presupuesto; se usan fotos y el número escrito a mano).
- **Alternativas:** 9 revisadas con precios publicados. Las especializadas cuestan entre USD 39 y 99 al mes, o están hechas para España, México o India.

### Requisitos

- **En cifras:** 44 reglas de negocio, 41 requisitos funcionales (31 Must, 8 Should y 2 Could) y 35 no funcionales, cada uno con métrica y forma de verificarlo.
- **Un requisito que salió del proceso:** al reconstruir el proceso actual apareció que a veces el cliente se lleva una prenda sin arreglar. Eso generó RN-44 y HU-36 (Should), para no cobrar un arreglo que no se hizo.
- **Una historia completa:** abre HU-07 y muéstrale en orden «Como / quiero / para», «Nació de», los requisitos y reglas que cumple, los puntos y los criterios Dado / Cuando / Entonces con datos concretos.
- **La matriz:** busca «C-06» en el buscador de la matriz y muéstrale la cadena completa hasta sus historias.

### Plan

- **Backlog:** 24 documentos, 36 historias y 7 habilitadores técnicos, publicados como 67 issues en GitHub.
- **Capacidad:** los Sprints 3 y 4 comprometen 40 y 43 puntos, solo con lo Must. El Sprint 4 tiene menos días y además el APK; es un riesgo aceptado y escrito.
- **App en el celular:** las usuarias trabajan desde el celular, así que el sistema se entrega también como APK para Android que abre el mismo sistema web ([ADR-006](../03-diseno/adr/ADR-006-instalacion-en-el-celular.md)).
- **Punto de control el 1 de octubre:** si van menos de 15 puntos terminados, se decide el recorte ese mismo día.

## Preguntas que le tienes que hacer

Anota cada respuesta en el acta con el mismo código.

| Código | Pregunta | Por qué importa |
| --- | --- | --- |
| **D1** | ¿Acepta actuar como Product Owner en representación del cliente? ¿Por qué medio prefiere las revisiones? | Sin validador, los requisitos quedan sin aprobar |
| **D2** | ¿Acepta las fuentes de requisitos sin entrevista a la dueña, con las limitaciones declaradas? ¿Pide alguna evidencia adicional? | Es la principal debilidad del análisis; mejor saberlo ahora |
| **D3** | ¿Aprueba el alcance, en especial lo que quedó fuera? | Define qué se construye |
| **D4** | ¿Acepta que el aviso automático se demuestre con el número de prueba gratuito de Meta, sin activarlo en producción? | Activarlo en producción cuesta por mensaje ([ADR-003](../03-diseno/adr/ADR-003-canal-de-avisos-whatsapp.md)) |
| **D5** | ¿Espera MVC o Clean Architecture? ¿Acepta MVC de Laravel con una capa de servicios para la lógica de negocio? | La arquitectura se decide en el Sprint 2 |
| **D6** | ¿Con qué herramienta y formato quiere los mockups, y cuándo puede revisarlos? | DOC-14 debe cerrarse antes del 28 de septiembre |
| **D7** | ¿En qué formato se entrega la documentación: repositorio, PDF o una plantilla del SENA? ¿Quiere acceso al repositorio? ¿Los manuales van también en inglés? | Evita rehacer formatos al final |
| **D8** | ¿Cómo quiere que se declare el uso de asistentes de IA? | El plan ya lo declara; conviene hacerlo como él espera |
| **D9** | ¿Está de acuerdo con el orden de recorte: primero lo Could y lo Should, y lo Must se renegocia con él, nunca las pruebas ni la documentación? | Deja acordado qué pasa si el tiempo no alcanza |
| **D10** | Hoy la dueña se queda con las prendas que nadie reclama. ¿Vale la pena definir un plazo de guarda como regla, o se deja fuera? | Es la observación abierta del proceso actual |

## Preguntas que te puede hacer

| Si pregunta | Responde con | Dónde está |
| --- | --- | --- |
| ¿Por qué no hablaste con la dueña? | No está disponible en el periodo. Se declaró como limitación, no se inventaron entrevistas y por eso se le pide a él validar | [Fuentes de requisitos](../02-requisitos/fuentes-de-requisitos.md) |
| ¿No eres parcial por ser familiar de la dueña? | Puede haber sesgo, y está escrito. Por eso cada hecho que aportaste tiene fecha y pasa por su validación | Fuentes · alcance de F-05 |
| ¿Por qué rehiciste la app? | Se construyó antes del análisis, no correspondía a MVC ni a Clean Architecture y no podías defenderla | ADR-000 |
| ¿Por qué Laravel? | La especificación original pedía un sistema web, el taller tiene internet, Laravel es MVC explícito y está en la ficha del programa | ADR-001 |
| ¿Por qué no usar una app que ya existe? | Ninguna de las 9 revisadas reúne gratuidad, Colombia, WhatsApp automático y seguimiento de saldos y prendas sin reclamar | [Análisis de alternativas](../02-requisitos/analisis-de-alternativas.md) |
| ¿Cómo sabes que las historias están bien? | Cada una dice de qué causa nace, y un script genera la matriz y falla si falta cobertura | Matriz de trazabilidad |
| ¿Por qué WhatsApp oficial si cuesta? | Las APIs no oficiales violan los términos y pueden bloquear el número. Hay envío asistido de respaldo, y la demostración usa el número de prueba gratuito | ADR-003 |
| ¿Por qué preparar varios negocios si es un solo taller? | Agregarlo después obligaría a cambiar casi todas las tablas. Solo las tablas raíz llevan el negocio, así el modelo sigue en tercera forma normal | ADR-002 |
| ¿Te alcanza el tiempo? | 40 y 43 puntos Must en los sprints de desarrollo, punto de control el 1 de octubre y un orden de recorte escrito | [Product backlog](product-backlog.md) |
| ¿Qué hizo la inteligencia artificial? | Es una herramienta de apoyo declarada en el plan. Las decisiones del problema y del alcance salieron de tus respuestas, y cada decisión técnica tiene un ADR que puedes explicar | [Plan de sprints](plan-de-sprints.md) |

Si te pregunta algo que no sabes, no improvises. Anótalo en el acta como pendiente y respóndelo por escrito después.

## Lo que evalúan y en qué va

Úsalo si te pregunta cómo va el proyecto frente a lo que se evalúa.

| Lo que se evalúa | Dónde está | Estado |
| --- | --- | --- |
| Desglose del problema | Árbol de problemas, árbol de objetivos, proceso actual y propuesto | Hecho en el Sprint 1 |
| Ingeniería de requisitos | Fuentes, análisis de alternativas, reglas de negocio, RF y RNF | Hecho en el Sprint 1 |
| Historias de usuario con criterios y origen | Historias de usuario y matriz de trazabilidad | Hecho en el Sprint 1 |
| Scrum | Plan de sprints, backlog y tablero | Plan, backlog y tablero hechos; revisiones y retrospectivas desde el cierre del Sprint 1 |
| Arquitectura justificada | ADR-001 (plataforma); ADR de arquitectura por capas | Plataforma decidida; arquitectura en el Sprint 2 |
| Mockups | DOC-13 y DOC-14 | Sprint 2 |
| Diagramas UML y especificación técnica | DOC-15, DOC-18 y DOC-19 | Sprint 2 |
| Base de datos normalizada | DOC-17 | Sprint 2 |
| Código limpio, SOLID y pruebas | Requisitos RNF-27 a RNF-31 | Sprints 3 y 4 |

## Después de la reunión

1. **El mismo día,** termina de llenar el acta mientras lo recuerdas.
2. **Envíale un resumen por correo** y pídele que responda confirmando. Esa respuesta es la evidencia de la validación.
3. **Pásame el acta.** Con ella:
   - aplico los cambios en los documentos;
   - cambio el estado de F-04 en las fuentes de requisitos;
   - creo un issue por cada cambio solicitado;
   - cierro DOC-12 con el acta como evidencia.

### Correo de confirmación

> Asunto: Resumen de la validación del Sprint 1 · El-taller-ines
>
> Instructor Oscar, gracias por la reunión de hoy. Le resumo lo acordado para confirmar que quedó bien registrado:
>
> - Rol de Product Owner: [lo que respondió]
> - Documentos aprobados: [lista]
> - Cambios solicitados: [lista]
> - Decisiones: [D1 a D10 en una línea cada una]
> - Próxima revisión: mockups, el [fecha]
>
> Si algo no corresponde a lo que conversamos, le agradezco me lo indique.
>
> Cristian Cantillo Mejía · ficha 2480542
