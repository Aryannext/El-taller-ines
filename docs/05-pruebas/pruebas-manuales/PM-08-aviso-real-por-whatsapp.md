# PM-08 · Aviso real por WhatsApp

**Verifica:** RNF-06, CA-28.1 · **Cuándo:** Sprint 4, con HT-01 y HU-28 · **Entorno:** VPS con el número de prueba de Meta

## Objetivo

Comprobar que el aviso sale solo por la API oficial de WhatsApp y llega de verdad al celular del cliente. Las pruebas automáticas usan un canal falso ([ADR-003](../../03-diseno/adr/ADR-003-canal-de-avisos-whatsapp.md)), así que solo esta prueba demuestra el envío real.

## Preparación

1. HT-01 está terminado: el número de prueba de Meta funciona y la plantilla del aviso está aprobada.
2. El celular del aprendiz está registrado en Meta como destinatario de prueba.
3. Las credenciales de la API están en las variables de entorno del VPS, no en el repositorio (RNF-24).
4. Existe un cliente de prueba con el celular del aprendiz.
5. Ese cliente tiene una orden con 2 prendas: una Terminada y otra En proceso, y un abono que deja saldo.

## Pasos

| Paso | Qué se hace | Qué debe pasar |
| --- | --- | --- |
| 1 | Anotar el número de la orden, sus prendas y su saldo | — |
| 2 | Marcar Terminada la prenda que falta y no hacer nada más | La pantalla responde de inmediato |
| 3 | Esperar el mensaje en el celular del aprendiz | Llega un WhatsApp con el número de orden, la cantidad de prendas listas y el saldo anotado |
| 4 | Abrir los avisos de la orden | El aviso aparece como enviado por la API oficial, con fecha y hora y el mensaje |
| 5 | Buscar en el código la dirección de la API de Meta | Solo aparece en `WhatsAppCloudApiCanal` |

## Criterio de aprobación

- **CA-28.1:** los pasos 2 a 4 pasan sin ninguna acción adicional de la usuaria.
- **RNF-06:** el paso 5 encuentra la API solo en el adaptador del canal.

Si Meta no habilitó el número de prueba o la plantilla a tiempo, se aplica la regla de suspensión del [plan](../plan-de-pruebas.md#cuándo-se-suspenden-las-pruebas) y se declara en el informe.

## Registro

**Fecha:** · **Commit:** · **Versión de la API de Meta:**

| Paso | Resultado | Hora | Captura |
| --- | --- | --- | --- |
| 1 | | | |
| 2 | | | |
| 3 | | | |
| 4 | | | |
| 5 | | | |

**Mensaje recibido (texto exacto):**

**Resultado:**
