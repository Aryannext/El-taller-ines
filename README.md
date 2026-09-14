# Atelier Manager

Sistema de gestión para un taller de arreglos de costura en Florencia, Caquetá: clientes, órdenes de trabajo, prendas, pagos y avisos de entrega.

| | |
| --- | --- |
| **Programa** | Análisis y Desarrollo de Software (ADSO) · SENA |
| **Proyecto formativo** | Análisis y desarrollo de software a la medida para el sector servicios en el municipio de Florencia · ficha 2480542 |
| **Centro** | Centro Tecnológico de la Amazonia · Regional Caquetá |
| **Aprendiz** | Cristian Cantillo Mejía |
| **Instructor** | Oscar Eduardo Yanguas Arguello |
| **Tecnología** | Laravel + MySQL, desplegado en VPS ([ADR-001](docs/03-diseno/adr/ADR-001-laravel-mysql.md)) |
| **Metodología** | Scrum adaptado a un equipo de una persona ([plan](docs/00-scrum/plan-de-sprints.md)) |

## Antecedente

Esta es la segunda versión. La primera, [Costura-app](https://github.com/Aryannext/Costura-app), fue un prototipo móvil construido antes de hacer el análisis. Se conserva como antecedente y como fuente de requisitos, no como base de código. Por qué se rehízo: [ADR-000](docs/03-diseno/adr/ADR-000-rehacer-en-vez-de-refactorizar.md).

## Documentación

El orden de las carpetas es el orden del proceso: cada fase parte de lo que dejó la anterior.

| Carpeta | Contenido |
| --- | --- |
| [00-scrum](docs/00-scrum/) | Plan de sprints, backlog, definición de terminado, revisiones y retrospectivas |
| [01-problema](docs/01-problema/) | Contexto, árbol de problemas, proceso actual y propuesto, objetivos y alcance |
| [02-requisitos](docs/02-requisitos/) | Fuentes y técnicas, reglas de negocio, requisitos funcionales y no funcionales, historias de usuario, matriz de trazabilidad |
| [03-diseno](docs/03-diseno/) | Mockups, casos de uso, arquitectura y decisiones (ADR), modelo de datos y normalización, diagramas de clases, secuencia y despliegue |
| [04-especificacion-tecnica](docs/04-especificacion-tecnica/) | Especificación técnica, convenciones de código, entorno y despliegue |
| [05-pruebas](docs/05-pruebas/) | Plan de pruebas, casos de prueba e informe de resultados |
| [06-manuales](docs/06-manuales/) | Manual de usuario y manual técnico, en español e inglés |

El código de la aplicación irá en `sistema/` a partir del Sprint 3, cuando el diseño esté cerrado.
