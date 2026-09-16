# ADR-007 · Avisos automáticos por Evolution API

**Estado:** aceptada · **Fecha:** 16 de septiembre de 2026 · **Modifica:** [ADR-003](ADR-003-canal-de-avisos-whatsapp.md), en su canal automático

## Contexto

- **HT-01 no se pudo completar.** La API oficial exige una app de Meta conectada a un portafolio comercial. El 15 de septiembre la red del aprendiz bloqueaba developers.facebook.com, y el 16, ya con acceso, el proceso pidió crear el portafolio, una Página de Facebook y avanzar por pasos de verificación que no se lograron terminar.
- **Plazo:** la entrega es el 13 de octubre. HU-28 ya está construida y probada con un canal falso, pero sin un canal automático real el aviso depende siempre del envío asistido (HU-29).
- **La arquitectura lo permite sin cambiar la lógica.** Los avisos pasan por la interfaz `CanalDeAviso` (ADR-003, ADR-005): un canal nuevo es una clase más en la infraestructura.
- **Evolution API** es un servidor de código abierto que se conecta a WhatsApp como WhatsApp Web, escaneando un código QR, y expone una API HTTP para enviar mensajes.

## Decisión

1. **Canal automático:** `EvolutionApiCanal` envía el texto de `MensajeDeAviso` por Evolution API. No usa plantillas.
2. **Evolution API corre en el VPS**, en contenedores propios dentro de `despliegue/docker-compose.yml`, con su base PostgreSQL. Solo escucha en el mismo servidor y exige su clave; no tiene salida pública.
3. **No guarda conversaciones.** Evolution API se configura para no almacenar mensajes, chats, contactos ni historial: solo la sesión del número conectado.
4. **Número conectado:** el WhatsApp personal del aprendiz, para las pruebas y la sustentación. Lo decidió el aprendiz, que conoce y acepta el riesgo de bloqueo.
5. **Se conserva lo demás de ADR-003:** la interfaz `CanalDeAviso`, la cola con reintentos, el envío asistido de respaldo y la constancia de cada aviso. `WhatsAppCloudApiCanal` sigue en el código: si Evolution API no está configurada y la API oficial sí, se usa la oficial.

## Alternativas consideradas

| Alternativa | Por qué no |
| --- | --- |
| Seguir con la API oficial | No se logró completar el alta en Meta dentro del plazo |
| Solo envío asistido | Contradice el propósito del sistema: el aviso debe salir solo (F-05) |
| Otro canal (Telegram, correo, SMS) | Los clientes no los usan; el SMS además cuesta |

## Consecuencias

- **Riesgo de bloqueo:** automatizar WhatsApp Web va contra los términos del servicio y el número puede ser bloqueado. Se mitiga con pocos mensajes, solo a clientes con órdenes, y con el envío asistido como respaldo si el canal deja de responder.
- **No apto tal cual para una usuaria real.** Para producción se recomienda volver a la API oficial o usar un número dedicado al taller; queda como decisión del plan de negocio.
- **RNF-06 cambia:** ya no exige «solo la API oficial», sino que ningún componente fuera de los adaptadores de canal conozca un servicio de WhatsApp.
- **Constancia:** los avisos enviados por este canal quedan con `canal = evolution_api` (RN-41).
- **Operación:** si la sesión se cierra en el celular, el canal falla, los avisos quedan para envío asistido y hay que volver a escanear el QR.
