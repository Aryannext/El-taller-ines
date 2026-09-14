# Fuentes y técnicas de levantamiento de requisitos

Cada requisito, regla de negocio e historia de usuario cita la fuente de la que nació con su código (`F-01`, `F-02`…). Un requisito sin fuente no entra al backlog.

## Fuentes

| Código | Fuente | Técnica | Estado |
| --- | --- | --- | --- |
| **F-01** | Especificación de la versión 1 (`Costura.docx`, agosto de 2026): contexto, necesidad de la dueña, actores, requisitos e historias | Análisis documental | Disponible |
| **F-02** | Prototipo versión 1 ([Costura-app](https://github.com/Aryannext/Costura-app)) y su auditoría de septiembre de 2026 | Prototipado evolutivo y revisión | Disponible |
| **F-03** | Aplicaciones existentes de gestión de talleres y órdenes de servicio | Análisis de competencia (benchmarking) | Por hacer en el Sprint 1 |
| **F-04** | Validación con el instructor como representante del cliente | Revisión y aceptación | Por acordar |

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
