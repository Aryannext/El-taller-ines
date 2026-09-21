# PM-08 · Aviso real por WhatsApp

**Verifica:** RNF-06, CA-28.1 · **Cuándo:** Sprint 4, con HT-01 y HU-28 · **Entorno:** VPS con Evolution API y el WhatsApp del aprendiz conectado

## Objetivo

Comprobar que el aviso sale solo por el canal automático y llega de verdad a un celular. Las pruebas automáticas usan un canal falso y respuestas simuladas ([ADR-007](../../03-diseno/adr/ADR-007-avisos-por-evolution-api.md)), así que solo esta prueba demuestra el envío real.

## Preparación

1. HT-01 está terminado: Evolution API corre en el VPS y el WhatsApp del aprendiz está conectado a la instancia `taller`.
2. `EVOLUTION_INSTANCIA=taller` está en las variables de entorno del VPS, no en el repositorio (RNF-24).
3. Existe un cliente de prueba con un celular que tenga WhatsApp y que el aprendiz pueda revisar.
4. Ese cliente tiene una orden con 2 prendas: una Terminada y otra En proceso, y un abono que deja saldo.

## Pasos

| Paso | Qué se hace | Qué debe pasar |
| --- | --- | --- |
| 1 | Anotar el número de la orden, sus prendas y su saldo | — |
| 2 | Marcar Terminada la prenda que falta y no hacer nada más | La pantalla responde de inmediato |
| 3 | Esperar el mensaje en el celular del cliente de prueba | Llega un WhatsApp con el número de orden, la cantidad de prendas listas y el saldo anotado |
| 4 | Abrir los avisos de la orden | El aviso aparece como enviado por WhatsApp automático, con fecha y hora y el mensaje |
| 5 | Buscar en el código la dirección de envío de Evolution API y la de la API de Meta | Cada una aparece solo en su adaptador: `EvolutionApiCanal` y `WhatsAppCloudApiCanal` |

## Criterio de aprobación

- **CA-28.1:** los pasos 2 a 4 pasan sin ninguna acción adicional de la usuaria.
- **RNF-06:** el paso 5 encuentra cada servicio solo en su adaptador.

Si la sesión de WhatsApp está caída, se vuelve a conectar antes de repetir la prueba, y el intento fallido se anota en el informe.

## Registro

**Fecha:** 16 de septiembre de 2026, verificada el 21 · **Commit:** `de1b236` · **Versión de Evolution API:** v2.3.7

| Paso | Resultado | Hora | Captura |
| --- | --- | --- | --- |
| 1 | Órdenes #0001 (2 prendas, saldo $11.000) y #0002 (1 prenda, saldo $10.000). Reconstruido de la base, no anotado en su momento | — | — |
| 2 | La orden quedó Lista y el aviso se generó solo. No se anotó cómo respondió la pantalla | 10:01:43 y 10:04:31 | — |
| 3 | Los dos mensajes llegaron. El aprendiz los encontró el 21 de septiembre | 10:01:46 y 10:04:34 | En el chat del aprendiz consigo mismo; pendiente |
| 4 | Los dos avisos constan como `enviado` por `evolution_api`, con fecha, hora, mensaje e identificador de WhatsApp | — | — |
| 5 | La dirección de Evolution API aparece solo en `EvolutionApiCanal` y la de la API de Meta solo en `WhatsAppCloudApiCanal` | 21 sep | — |

**Mensaje recibido (texto exacto):**

> Hola Cristian, tu orden #0001 del taller está lista para recoger. Prendas listas: 2. Saldo pendiente: $11.000. Te esperamos.

> Hola Cristian, tu orden #0002 del taller está lista para recoger. Prendas listas: 1. Saldo pendiente: $10.000. Te esperamos.

**Resultado:** **Aprobado, con salvedades.** Entre generar el aviso y enviarlo pasaron 3 segundos, por la cola y sin ninguna acción de la usuaria (CA-28.1), y el paso 5 encuentra cada servicio solo en su adaptador (RNF-06).

Salvedades, para que el informe no diga más de lo que pasó:

- **El cliente de prueba tenía el mismo celular que el WhatsApp conectado**, así que el sistema se escribió a sí mismo. WhatsApp entrega esos mensajes en el chat de la persona consigo misma y sin notificación: por eso pasaron cinco días sin que nadie los viera. El recorrido completo —aviso, cola, Evolution API, servidores de WhatsApp, celular— quedó probado; la entrega a un número ajeno no.
- **La preparación no siguió el guion**: no consta que la orden tuviera una prenda Terminada y otra En proceso ni un abono. Los pasos 1 y 2 se reconstruyeron de la base.
- **Faltan las capturas.**

Antes de la sustentación conviene repetirla completa con el celular de otra persona, con su permiso: es la única forma de mostrar el aviso llegando como le llegaría a un cliente.
