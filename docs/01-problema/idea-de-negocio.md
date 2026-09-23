# Idea de negocio · insumo

**Estado:** nota registrada el 13 de septiembre de 2026. Se desarrolla en el plan de negocio y se tiene en cuenta al definir el alcance.

## Decisión del aprendiz

El sistema será **gratuito** para mujeres que tienen un negocio pequeño de arreglo de ropa. No se cobrará a las usuarias.

## El nombre · resuelto el 23 de septiembre de 2026

El proyecto se llamó **El-taller-ines** desde el primer día, y desde el primer día quedó anotado como **nombre temporal** (commit `d04ba8a`, 13 de septiembre). Al cerrar el desarrollo se decidió el definitivo: **Puntada**.

**Por qué se cambió.** «El taller de Inés» nombra un solo negocio, y el sistema no es de un solo negocio: se entrega a uno, pero está pensado y construido para cualquier taller pequeño de arreglo de ropa (ADR-002), y la decisión de que sea gratuito apunta justamente a que lo usen muchas. Un nombre propio en el producto contradecía esa intención cada vez que alguien lo leía.

**Por qué «Puntada».** Es la unidad de todo lo que se cose: corta, del oficio, fácil de decir, y no se casa con ningún negocio ni con ninguna ciudad. Cabe bajo el ícono del celular sin abreviarse.

**Qué no cambió, y por qué.** El identificador de la app en Android (`online.proyectosena.taller`), la dirección del sistema (`/taller`) y la carpeta del servidor conservan su nombre: renombrarlos obligaría a reinstalar la app en los celulares y a tocar la configuración de Nginx y del cron con permisos de administrador, sin que la usuaria note ninguna diferencia. El nombre de un producto y el identificador técnico de su instalación son cosas distintas.

## Implicaciones a evaluar más adelante

| Tema | Pregunta abierta | Dónde se resuelve |
| --- | --- | --- |
| **Alcance** | ~~¿La versión que se entrega sirve a un solo taller o ya permite que varios negocios la usen?~~ **Resuelto:** un solo taller, con los datos preparados para varios | [ADR-002](../03-diseno/adr/ADR-002-un-taller-preparado-para-varios.md) |
| **Arquitectura y datos** | Qué catálogos personaliza cada negocio y cómo se prueba el aislamiento entre negocios | Modelo de datos · Sprint 2 |
| **Sostenibilidad** | Sin cobrar a las usuarias, ¿cómo se cubren el servidor, el mantenimiento y el costo por mensaje de la API oficial de WhatsApp ([ADR-003](../03-diseno/adr/ADR-003-canal-de-avisos-whatsapp.md))? (alianzas, programas de apoyo a emprendedoras, patrocinio) | Plan de negocio |
| **Impacto social** | Apoyo a mujeres emprendedoras del sector servicios en Florencia, en línea con el impacto social que pide la ficha del proyecto formativo | Plan de negocio |
