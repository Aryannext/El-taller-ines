# Plan de sprints

**Inicio:** domingo 13 de septiembre de 2026 · **Entrega:** martes 13 de octubre de 2026 · **Duración:** 30 días

## Scrum en un equipo de una persona

Scrum supone un equipo de tres a nueve personas. Este proyecto es individual, así que se adapta y la adaptación se declara, en vez de fingir roles que no existen.

| Rol | Quién lo asume | Nota |
| --- | --- | --- |
| **Product Owner** | Instructor, como representante del cliente | La dueña del taller no está disponible durante el proyecto (ver [fuentes de requisitos](../02-requisitos/fuentes-de-requisitos.md)). **Pendiente de acordar con el instructor.** |
| **Scrum Master** | Aprendiz | Vela por que se cumplan los eventos y se retiren los impedimentos |
| **Equipo de desarrollo** | Aprendiz | Analiza, diseña, programa y prueba |

Se usa un asistente de IA como herramienta de apoyo en análisis, diseño y código. Se declara en la documentación; las decisiones y su defensa son del aprendiz.

## Eventos

| Evento | Adaptación |
| --- | --- |
| **Sprint** | Una semana |
| **Planificación** | Al inicio de cada sprint: objetivo y elementos del backlog que entran |
| **Daily** | Registro escrito de 3 líneas por día en el tablero: qué hice, qué haré, qué me bloquea |
| **Revisión** | Al cierre: demostración del incremento y actualización del backlog |
| **Retrospectiva** | Al cierre: qué funcionó, qué no, qué cambio para el siguiente sprint |

Las evidencias de cada evento quedan en esta carpeta, con su fecha real.

## Sprints

| Sprint | Fechas | Objetivo | Incremento esperado |
| --- | --- | --- | --- |
| **0 · Arranque** | 13–14 sep | Organizar el proyecto | Repositorio, estructura de documentación, tablero, backlog inicial, definición de terminado |
| **1 · Problema y requisitos** | 15–21 sep | Entender y especificar el problema | Árbol de problemas, proceso actual y propuesto, objetivos y alcance, reglas de negocio, RF y RNF medibles, historias de usuario con origen y criterios, matriz de trazabilidad |
| **2 · Diseño** | 22–28 sep | Diseñar antes de programar | Wireframes y mockups, casos de uso, arquitectura con ADR, modelo de datos normalizado con diccionario, diagramas de clases, secuencia y despliegue, plan de pruebas |
| **3 · Desarrollo I** | 29 sep – 5 oct | Núcleo del sistema | Acceso, clientes, órdenes, prendas y fotos, con pruebas |
| **4 · Desarrollo II** | 6–10 oct | Completar y desplegar | Estados, entrega, pagos, avisos, seguimiento, despliegue en VPS con respaldos, APK para Android, informe de pruebas |
| **Cierre** | 11–13 oct | Entregar y sustentar | Manuales en español e inglés, ensayo de sustentación |

Qué elemento entra en cada sprint, en qué orden y con cuántos puntos está en el [product backlog](product-backlog.md).

## Definición de terminado

Un elemento del backlog está terminado cuando:

- **Documento:** está en esta carpeta de documentación, trazado a su fuente y revisado contra la plantilla de su tipo.
- **Historia de usuario implementada:** cumple todos sus criterios de aceptación, tiene pruebas automáticas que pasan, respeta las convenciones de código y está integrada en `main`.
- **En ambos casos:** el aprendiz puede explicarlo sin leer.

## Riesgos del plan

| Riesgo | Impacto | Mitigación |
| --- | --- | --- |
| La dueña no puede validar requisitos, mockups ni recibir capacitación | Alto | Acordar con el instructor que actúe como Product Owner y valide; declarar la limitación |
| Conocimiento básico de Laravel | Medio | Aprender por módulo durante el Sprint 3, con el diseño ya cerrado |
| El desarrollo se come el tiempo de documentación | Alto | Sprints 1 y 2 sin código; el alcance de desarrollo se recorta antes que la documentación |
