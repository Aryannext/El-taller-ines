# Fuentes y técnicas de levantamiento de requisitos

Cada requisito, regla de negocio e historia de usuario cita la fuente de la que nació con su código (`F-01`, `F-02`…). Un requisito sin fuente no entra al backlog.

## Fuentes

| Código | Fuente | Técnica | Estado |
| --- | --- | --- | --- |
| **F-01** | Especificación de la versión 1 (`Costura.docx`, agosto de 2026): contexto, necesidad de la dueña, actores, requisitos e historias | Análisis documental | Disponible |
| **F-02** | Prototipo versión 1 ([Costura-app](https://github.com/Aryannext/Costura-app)) y su auditoría de septiembre de 2026 | Prototipado evolutivo y revisión | Disponible |
| **F-03** | Aplicaciones existentes de gestión de talleres y órdenes de servicio | Análisis de competencia (benchmarking) | Disponible · [análisis de alternativas](analisis-de-alternativas.md), consultado el 14 sep 2026 |
| **F-04** | Validación con el instructor como representante del cliente | Revisión y aceptación | Por acordar |
| **F-05** | Conocimiento directo del aprendiz, familiar de la dueña, sobre cómo funciona el taller | Observación informal del dominio | Disponible · ver limitaciones |

## Qué aporta el prototipo (F-02)

La versión 1 no se construyó con un análisis previo. Usarla y auditarla dejó requisitos que no estaban escritos y errores que la versión 2 debe evitar desde el diseño. Por ejemplo:

- **El saldo debe calcularse** desde las prendas y los pagos. Guardarlo y sumar o restar a mano lo descuadró.
- **El estado de una orden depende del estado de sus prendas.** Moverlo a mano permitía una orden "Lista" con prendas sin terminar.
- **Un pago mal registrado se anula, no se borra**, para no perder el rastro del dinero.
- **Las fechas de entrega se interpretan en hora local.** Hacerlo en UTC las mostraba un día antes.

Cada hallazgo se registra como regla de negocio o requisito con `F-02` como fuente.

## Limitación declarada

La dueña del taller no está disponible durante los 30 días del proyecto. En consecuencia:

- **No se realizan entrevistas nuevas.** Las necesidades del cliente provienen de F-01, que las documentó antes.
- **La validación de requisitos y mockups** la hace el instructor como representante del cliente (F-04), si lo acepta.
- **La implantación y la capacitación** con la usuaria final quedan fuera del periodo y se registran como trabajo posterior.

No se presentarán como entrevistas u observaciones actividades que no ocurrieron.

## Alcance de F-05

El aprendiz es familiar de la dueña y conoce el funcionamiento del taller de primera mano. Lo que aporta se registra como F-05 con dos reservas:

- **No es una entrevista estructurada.** Son hechos que el aprendiz conoce, no respuestas de la dueña a un guion.
- **Puede tener sesgo** por el vínculo familiar. Por eso todo lo que nace de F-05 pasa por la validación del instructor (F-04).

Hechos registrados de F-05:

| Fecha | Hecho | Usado en |
| --- | --- | --- |
| 13 sep 2026 | No se anota nada: clientes, prendas, arreglos, precios y abonos quedan solo en la memoria de la dueña | Árbol de problemas · C-01, C-01.1 |
| 13 sep 2026 | La dueña recuerda de memoria cuánto le debe cada cliente | Árbol de problemas · C-04 |
| 13 sep 2026 | Hay prendas que no se recogen durante dos meses o más, o nunca; no se sabe cuántas | Árbol de problemas · C-05, E-04 |
| 13 sep 2026 | No hay desacuerdos con los clientes: al recoger se mide el arreglo y, si falta algo, se termina | Árbol de problemas · E-05 descartado |
| 13 sep 2026 | El taller recibe entre 10 y 15 prendas por semana, sin un número fijo | Árbol de problemas · Magnitud |
| 13 sep 2026 | Los clientes suelen decir que la dueña cobra muy barato | Árbol de problemas · Observaciones por analizar |
| 13 sep 2026 | Las prendas por arreglar y las arregladas se guardan juntas en un rincón | Árbol de problemas · C-06 |
| 13 sep 2026 | Un cliente puede traer entre 3 y 5 prendas, y la dueña olvida cuáles son de quién | Árbol de problemas · C-06.1, E-06 |
| 13 sep 2026 | No hay presupuesto para una impresora de etiquetas; la foto de cada prenda sirve para identificarla | Alcance · identificación de prendas |
| 13 sep 2026 | La descripción del arreglo se escribe al registrar la prenda | Alcance · órdenes y prendas |
| 13 sep 2026 | Enviar los avisos a mano contradice el propósito de facilitar el trabajo: se busca automatizarlos | Alcance · avisos · ADR-003 |
| 13 sep 2026 | Sin impresora, el número de orden se escribe a mano en la bolsa donde van las prendas del cliente | Alcance · identificación de prendas |
| 13 sep 2026 | Una orden lista se considera sin reclamar a los 30 días | Reglas de negocio · RN-35 |
| 13 sep 2026 | Se puede entregar una orden que el cliente todavía debe, confirmándolo | Reglas de negocio · RN-21 |
| 13 sep 2026 | Las fotos de las prendas son opcionales, hasta 3 por prenda | Reglas de negocio · RN-17 |
| 13 sep 2026 | El taller recibe pagos en efectivo y por Nequi, en la cuenta personal de la dueña | Reglas de negocio · RN-25 · Alcance |
| 13 sep 2026 | Los clientes tienen celular colombiano; no hay números de otro país ni solo teléfono fijo | Reglas de negocio · RN-03 |
| 13 sep 2026 | Al elegir «Otro» como tipo de prenda, la usuaria debe poder escribir cuál es | Reglas de negocio · RN-43 · RF-16 |
| 13 sep 2026 | La prueba de usabilidad se hace con compañeros de formación | Requisitos no funcionales · RNF-12 |
| 13 sep 2026 | La copia de respaldo fuera del servidor se guarda en Google Drive | Requisitos no funcionales · RNF-15 |
