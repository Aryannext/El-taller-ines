# ADR-001 · Laravel y MySQL como plataforma

**Estado:** aceptada · **Fecha:** 13 de septiembre de 2026

## Contexto

- **Requisito original:** la especificación de la versión 1 (F-01) pedía "un sistema web" disponible desde el navegador. La versión 1 se apartó de eso y terminó como app móvil sin conexión; esta decisión vuelve al requisito original.
- **Conexión:** según el aprendiz, el taller tiene internet estable. Ya no es necesario trabajar sin conexión, que fue lo que llevó a la versión 1 a una app solo local.
- **Conocimientos:** el aprendiz tiene bases de PHP, JavaScript y Python, sin experiencia previa en frameworks.
- **Evaluación:** el proyecto evalúa arquitectura MVC o Clean Architecture, SOLID, normalización de la base de datos y despliegue.
- **Infraestructura:** hay un VPS disponible para desplegar.
- **Ficha del proyecto formativo:** el ambiente de aprendizaje lista PHP, Laravel y MySQL.

## Decisión

Aplicación web con **Laravel** (patrón MVC) y **MySQL**, desplegada en el VPS y usable desde el navegador del teléfono y del computador.

## Alternativas consideradas

| Alternativa | A favor | Por qué no |
| --- | --- | --- |
| Django + MySQL | Python, panel de administración incluido | Patrón MTV: variante de MVC que exige explicar la diferencia; no está en la ficha |
| API en Node (Express) + Vue | Tecnologías modernas | Dos proyectos que construir, desplegar y defender en 30 días |
| Mantener la app móvil sin conexión (v1) | Funciona sin internet | Ya no hace falta; sin servidor no hay arquitectura cliente-servidor ni despliegue |

## Consecuencias

- **MVC explícito:** modelos, vistas y controladores son parte de la estructura de Laravel. La lógica de negocio va en clases de servicio y las validaciones en Form Requests, para que los controladores no acumulen responsabilidades (SOLID).
- **Base de datos versionada:** las migraciones dejan el esquema en el repositorio. El modelo normalizado se diseña en el Sprint 2 antes de escribirlas.
- **Curva de aprendizaje:** Laravel se aprende durante el Sprint 3, con el diseño ya cerrado.
- **Dependencia de internet:** si la conexión del taller falla, el sistema no está disponible. Se registra como riesgo y como requisito no funcional de disponibilidad.
