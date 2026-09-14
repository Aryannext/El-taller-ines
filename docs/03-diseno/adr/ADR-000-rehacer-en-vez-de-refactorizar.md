# ADR-000 · Rehacer el sistema en vez de refactorizar el prototipo

**Estado:** aceptada · **Fecha:** 13 de septiembre de 2026

## Contexto

La versión 1 ([Costura-app](https://github.com/Aryannext/Costura-app)) es una app móvil en Vue 3 y Capacitor con SQLite local. Funciona y tiene pruebas automáticas, pero:

- **Se construyó antes del análisis y el diseño.** No hay mockups previos, y la documentación se escribió después del código.
- **El aprendiz no la construyó paso a paso** y no puede defender cada decisión.
- **Su arquitectura no corresponde a MVC ni a Clean Architecture.** Dos pantallas consultan la base directamente, y la lógica de acceso está mezclada con plugins del teléfono.
- **La base guarda dinero como número decimal flotante** y almacena datos derivados sin una decisión documentada.

El proyecto formativo evalúa el proceso de ingeniería: problema, requisitos, diseño y su justificación, no solo el software.

## Decisión

Construir una versión 2 desde cero, en un repositorio nuevo, siguiendo el orden del proceso: problema → requisitos → diseño → desarrollo → pruebas. La versión 1 se usa como fuente de requisitos (F-02), no como base de código.

## Alternativas consideradas

| Alternativa | Por qué no |
| --- | --- |
| Refactorizar la versión 1 y documentarla | La documentación seguiría describiendo decisiones tomadas sin análisis, y el aprendiz seguiría sin dominar el código |
| Cambiar de problema | Se pierde lo aprendido del dominio y hay que levantar un problema nuevo en 30 días |

## Consecuencias

- **A favor:** diseño antes que código, trazabilidad completa y un sistema que el aprendiz puede explicar.
- **En contra:** 30 días para todo el ciclo. Se mitiga recortando el alcance del desarrollo, nunca la documentación.
