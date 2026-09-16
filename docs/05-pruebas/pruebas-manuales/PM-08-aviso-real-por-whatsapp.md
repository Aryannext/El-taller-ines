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

**Fecha:** · **Commit:** · **Versión de Evolution API:**

| Paso | Resultado | Hora | Captura |
| --- | --- | --- | --- |
| 1 | | | |
| 2 | | | |
| 3 | | | |
| 4 | | | |
| 5 | | | |

**Mensaje recibido (texto exacto):**

**Resultado:**
