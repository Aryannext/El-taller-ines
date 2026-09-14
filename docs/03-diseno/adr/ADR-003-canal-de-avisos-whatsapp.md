# ADR-003 · Avisos por WhatsApp: API oficial con envío asistido de respaldo

**Estado:** aceptada · **Fecha:** 13 de septiembre de 2026

## Contexto

- **Medio M-03 y objetivo OE-03:** el cliente debe enterarse cuando su orden está lista.
- **Propósito del sistema:** facilitarle el trabajo a la dueña. Enviar cada aviso a mano contradice ese propósito (F-05).
- **Canal de los clientes:** WhatsApp. Telegram, correo o SMS no son canales que usen los clientes del taller.
- **Idea de negocio:** el sistema es gratuito para las usuarias ([idea de negocio](../../01-problema/idea-de-negocio.md)).
- **Opciones de envío automático por WhatsApp:**
  - **API oficial de Meta (WhatsApp Cloud API).** Legal y estable. Para escribirle primero a un cliente exige plantillas de mensaje aprobadas por Meta. En producción cobra por mensaje según país y categoría; la tarifa vigente se consulta al activarla. Para desarrollo, Meta ofrece un número de prueba que envía a un grupo limitado de destinatarios verificados, sin costo.
  - **APIs no oficiales** (Evolution API, Baileys, whatsapp-web.js y similares). Gratuitas, pero automatizan WhatsApp Web contra los términos del servicio, y el número puede ser bloqueado.

## Decisión

1. **Un contrato para cualquier canal.** La aplicación define la interfaz `CanalDeAviso`, con una operación que recibe un aviso y devuelve su resultado. Ninguna parte del sistema conoce WhatsApp directamente.
2. **Canal automático:** `WhatsAppCloudApiCanal` envía la plantilla aprobada de "orden lista" por la API oficial. Las credenciales viven en variables de entorno, nunca en el repositorio.
3. **Canal de respaldo:** `WhatsAppAsistidoCanal` arma el mensaje y un enlace de WhatsApp que la usuaria abre y envía con un toque.
4. **Selección automática.** Si el negocio tiene la API configurada y el envío responde bien, el aviso sale solo. Si no está configurada o falla, el aviso queda disponible como envío asistido. En ambos casos se registran la fecha, el canal y el resultado.
5. **El aviso se dispara con un evento.** Cuando el estado de una orden pasa a lista, se emite el evento `OrdenQuedoLista`, y un listener crea y envía el aviso. La lógica de estados no sabe que existen los avisos.
6. **El envío va en cola** (colas de Laravel con la base de datos como almacén). La pantalla no espera a WhatsApp y un fallo temporal se reintenta.

## Alternativas consideradas

| Alternativa | Por qué no |
| --- | --- |
| Solo envío asistido | Deja a la usuaria haciendo a mano un paso que se puede automatizar; contradice el propósito del sistema |
| APIs no oficiales (Evolution API y similares) | Violan los términos de WhatsApp. Un bloqueo le quitaría a una emprendedora su canal con los clientes, y usarlas es difícil de defender ética y legalmente |
| Solo API oficial, sin respaldo | Si no hay presupuesto o la API falla, el cliente no se entera de nada |
| Otro canal (Telegram, correo, SMS) | Los clientes no los usan; el SMS además tiene costo por mensaje |

## Consecuencias

- **Automático y legal:** el aviso sale solo sin arriesgar el número de la usuaria.
- **Abierto a extensión:** agregar otro canal es escribir una clase nueva que cumpla `CanalDeAviso`, sin tocar la lógica de órdenes (principios abierto/cerrado e inversión de dependencias).
- **Demostrable sin costo:** en la sustentación se envía un aviso real con el número de prueba de Meta a un teléfono verificado.
- **Producción con costo:** activar el envío automático para una usuaria real requiere verificar el negocio ante Meta y cubrir el costo por mensaje. Se resuelve en el plan de negocio. Mientras tanto funciona el envío asistido.
- **Tiempo de aprobación:** Meta revisa las plantillas antes de permitir su uso. La plantilla se tramita al inicio del Sprint 3.
- **Fuera de esta entrega:** confirmar si el cliente recibió o leyó el mensaje (webhooks de estado). El resultado registrado es la aceptación del mensaje por la API.
