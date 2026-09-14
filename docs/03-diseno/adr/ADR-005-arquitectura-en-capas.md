# ADR-005 · Arquitectura: MVC de Laravel con un núcleo de dominio separado

**Estado:** aceptada · **Fecha:** 14 de septiembre de 2026 · **Detalle:** [arquitectura del sistema](../arquitectura/README.md)

## Contexto

- **Qué se evalúa:** una arquitectura MVC o Clean Architecture, justificada, con código limpio y principios SOLID.
- **RNF-27:** las reglas de negocio deben estar separadas de la interfaz y de la base de datos, y una prueba automática debe comprobarlo.
- **RNF-28:** las 44 reglas deben tener pruebas automáticas; si cada prueba necesita navegador y base de datos, la suite se vuelve lenta y frágil.
- **Dependencias externas:** el aviso por WhatsApp puede salir por la API oficial o por envío asistido (ADR-003); las fotos se guardan en un disco privado (RNF-25); «hoy» es la fecha de Colombia (RN-09) y las pruebas necesitan fijarla.
- **Tamaño del problema:** 10 tablas, 36 historias y una sola usuaria por negocio. No hay otra aplicación que consuma los datos.
- **Restricciones:** una persona, 12 días de desarrollo, y Laravel se aprende durante el Sprint 3 (ADR-001).

## Decisión

El sistema se organiza en cinco partes con una regla de dependencias:

| Parte | Qué contiene | Puede usar |
| --- | --- | --- |
| **Http** | Rutas, controladores, validación de formularios (Form Requests) y vistas Blade | Aplicación |
| **Aplicación** | Un caso de uso por acción de las historias, y las consultas de las pantallas. Maneja transacciones, eventos y la cola | Dominio, Modelos y las interfaces |
| **Dominio** | Reglas del negocio en PHP puro: estados, cálculos de saldo, transiciones, objetos de valor e interfaces de lo externo | Nada fuera de sí mismo |
| **Modelos** | Modelos Eloquent, uno por tabla, con el filtro por negocio (ADR-002) | Laravel |
| **Infraestructura** | Implementaciones de las interfaces del dominio: canales de WhatsApp, almacén de fotos y reloj | Dominio y Laravel |

1. **Es MVC en los bordes.** La solicitud entra por un controlador, los datos se guardan con modelos Eloquent y la respuesta es una vista Blade, como en cualquier aplicación Laravel.
2. **El núcleo sigue la regla de dependencias de Clean Architecture.** El dominio no conoce Laravel, la base de datos ni WhatsApp. Los casos de uso le entregan datos simples y reciben decisiones: si una transición está permitida, cuánto es el saldo, en qué estado queda la orden.
3. **Interfaces solo donde hay algo que cambiar o simular.** `CanalDeAviso`, `AlmacenDeFotos` y `Reloj`. Los casos de uso las reciben por el contenedor de Laravel, y las pruebas les pasan versiones falsas.
4. **Sin repositorios.** Los casos de uso usan los modelos Eloquent directamente. Eloquent ya aísla el SQL, y la aplicación tiene una sola base de datos.
5. **Una sola implementación de cada regla calculada.** El estado de la orden, el saldo y los días de espera se calculan en el dominio, también en las listas. Las consultas cargan prendas y pagos por adelantado para no multiplicar las consultas (RNF-02). Si la medición de HT-06 no cumple RNF-01, se agrega un cálculo en SQL con un ADR nuevo.
6. **La regla de dependencias se vigila con pruebas de arquitectura** desde HT-02: por ejemplo, el dominio no puede usar clases de Laravel, y los controladores no pueden usar los modelos directamente.

## Alternativas consideradas

| Alternativa | A favor | Por qué no |
| --- | --- | --- |
| **MVC tradicional de Laravel**: reglas en controladores y modelos | Lo más rápido de empezar; es lo que muestran los tutoriales | Incumple RNF-27. Las reglas quedan repartidas entre controladores y modelos, y probar una regla exige una solicitud HTTP y la base de datos. Es el camino que llevó a la versión 1 a tener dos pantallas consultando la base directamente (ADR-000) |
| **Clean Architecture completa**: entidades propias, repositorios, mapeadores y un modelo de persistencia aparte | Independencia total del framework y de la base de datos | Duplica cada concepto: entidad, modelo Eloquent y mapeador entre ambos. Para 10 tablas y una base de datos, es trabajo que no cabe en 12 días y que no aporta: nada va a reemplazar a MySQL ni a Laravel en este proyecto |
| **Hexagonal con puertos para todo**, incluida la base de datos | Todas las dependencias intercambiables | Mismo costo que la anterior; los puertos se justifican donde hay alternativas reales, como WhatsApp |
| **API REST y aplicación de una sola página** (Vue o React) | Separación entre cliente y servidor | Dos aplicaciones que construir, asegurar y desplegar; ya descartado en ADR-001 |
| **Microservicios** | Escalan partes por separado | Un taller con 60 prendas al mes no lo necesita; multiplica el despliegue y el monitoreo |

## Consecuencias

- **Las reglas se prueban sin base de datos.** Las pruebas unitarias del dominio corren en milisegundos, y cubrir las 44 reglas (RNF-28) es realista.
- **Controladores delgados:** validan con un Form Request, llaman un caso de uso y devuelven una vista.
- **Abierto a extensión:** otro canal de aviso es una clase nueva que implementa `CanalDeAviso`, sin tocar los casos de uso.
- **Costo:** los casos de uso traducen entre modelos Eloquent y los datos simples que recibe el dominio. Es poco código, y es justo lo que mantiene el dominio independiente.
- **Disciplina vigilada:** sin las pruebas de arquitectura, la separación se erosiona con la prisa. Por eso entran en el primer habilitador técnico (HT-02).
- **Qué responder en la sustentación:** «Es MVC, como lo propone Laravel, con el núcleo organizado según la regla de dependencias de Clean Architecture. No es Clean Architecture completa, y el ADR-005 explica por qué».
