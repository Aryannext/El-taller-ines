# Alcance

**Estado:** borrador · Sprint 1 · se valida con el instructor (F-04)

## Criterio

Una función entra en la entrega del 13 de octubre de 2026 si cumple las dos condiciones:

1. **Nace de un medio del [árbol de objetivos](arbol-de-objetivos.md)**, y por tanto de una causa real del problema.
2. **Cabe en el plan de 30 días** sin recortar la documentación.

Lo que no cumple alguna de las dos queda fuera con su motivo, aunque estuviera en la especificación original (F-01) o en el prototipo (F-02).

## Dentro del alcance

| Módulo | Qué incluye | Objetivo | Nace de |
| --- | --- | --- | --- |
| **Acceso** | Inicio y cierre de sesión de la usuaria del taller, cambio de contraseña | Soporte de todos | Seguridad de la información del negocio |
| **Clientes** | Registrar, consultar, editar y buscar clientes por nombre o teléfono | OE-01 | M-01, M-01.2 |
| **Órdenes y prendas** | Crear una orden para un cliente con una o varias prendas: tipo, descripción del arreglo, precio y fecha de entrega; editar la descripción y el precio, y eliminar prendas, mientras no se hayan entregado | OE-01 | M-01.1 |
| **Identificación de prendas** | Tomar o subir fotos de cada prenda al registrarla; ver las fotos de todas las prendas de una orden; número de orden corto y fácil de copiar (por ejemplo, #0042) para escribirlo a mano en la bolsa | OE-06 | M-06, M-06.1 |
| **Estados** | Cambiar el estado de cada prenda; estado de la orden calculado a partir de sus prendas; entrega y cancelación de la orden; devolución de una prenda que el cliente se lleva sin arreglar | OE-02 | M-02, M-02.1 |
| **Pagos** | Registrar pagos y abonos por orden, anular un pago mal registrado sin borrarlo, saldo calculado, total por cobrar | OE-04 | M-04, M-04.1 |
| **Avisos** | Aviso automático por WhatsApp cuando la orden queda lista, mediante la API oficial; si el canal automático no está disponible, envío asistido con el mensaje redactado; registro de cada aviso con su canal y resultado | OE-03 | M-03 · [ADR-003](../03-diseno/adr/ADR-003-canal-de-avisos-whatsapp.md) |
| **Seguimiento** | Panel con órdenes vencidas, prendas sin reclamar con días de espera y total por cobrar | OE-05 | M-05 |
| **Base preparada para varios negocios** | Tabla de negocio y filtro por negocio en las tablas raíz, con un solo taller registrado | — | [ADR-002](../03-diseno/adr/ADR-002-un-taller-preparado-para-varios.md) |
| **Instalación en el celular** | APK para Android que abre el sistema a pantalla completa, e instalación desde el navegador en otros celulares | Soporte de todos | F-05 · RNF-35 · [ADR-006](../03-diseno/adr/ADR-006-instalacion-en-el-celular.md) |

## Fuera del alcance

| Función | Motivo | Estaba en |
| --- | --- | --- |
| **Varios negocios con registro propio** | No cabe en 30 días; los datos quedan preparados (ADR-002) | Idea de negocio |
| **APIs no oficiales de WhatsApp (Evolution API y similares)** | Violan los términos de WhatsApp y exponen el número de la usuaria a bloqueo ([ADR-003](../03-diseno/adr/ADR-003-canal-de-avisos-whatsapp.md)) | F-05 |
| **Envío automático en producción con el número real del taller** | Requiere verificar el negocio ante Meta y cubrir el costo por mensaje; se resuelve en el plan de negocio. En la entrega se demuestra con el número de prueba de Meta, y en uso real opera el envío asistido hasta activarlo | ADR-003 |
| **Confirmación de entrega o lectura del aviso** | Requiere recibir notificaciones de estado de Meta (webhooks); el resultado registrado es la aceptación del mensaje | ADR-003 |
| **Recordatorios automáticos a clientes con órdenes sin reclamar** | Cada mensaje automático por la API oficial tiene costo, y el medio M-05 se cumple mostrando las órdenes sin reclamar. Herramientas como CleanCloud lo ofrecen; se evalúa para una fase siguiente ([análisis de alternativas](../02-requisitos/analisis-de-alternativas.md)) | F-03 |
| **Registrar qué se hace con las prendas sin reclamar** | Hoy la dueña se queda con ellas para venderlas, usarlas o como tela de repuesto. El medio M-05 pide saber cuántas son y desde cuándo, y eso sí se cumple; qué hacer con ellas es una decisión del negocio ([proceso actual y propuesto](proceso-actual-y-propuesto.md)) | F-05 |
| **Impresora de etiquetas o códigos para las bolsas** | No hay presupuesto para el equipo (F-05). La foto de cada prenda y el número de orden cumplen el medio M-06 | F-05 |
| **Notas adicionales por prenda, aparte de la descripción** | La descripción del arreglo se escribe al registrar la prenda y se puede corregir; notas aparte no nacen de una causa del árbol. Se reconsidera si el instructor lo pide | F-01, F-02 |
| **Historial detallado de actividad** | El efecto que lo justificaba (E-05, desacuerdos con clientes) se descartó. Sí se conserva el rastro de pagos anulados y avisos enviados | F-01, F-02 |
| **App nativa y trabajo sin conexión** | El APK abre el mismo sistema web (ADR-006); una app nativa exigiría otra aplicación y una API. El taller tiene internet estable, así que no se trabaja sin conexión | F-02 |
| **Publicar el APK en Google Play** | Requiere una cuenta de desarrollador con pago y la revisión de Google; para la dueña basta con instalar el APK directamente. Se evalúa con la idea de negocio | ADR-006 |
| **Bot de Telegram y respaldos por Telegram** | Los respaldos se hacen en el servidor; no nace de una causa del árbol | F-02 |
| **Reportes financieros con gráficos** | El medio M-04 se cumple con el total por cobrar y lo recibido; los gráficos no atacan una causa | F-02 |
| **Conexión con Nequi u otras pasarelas de pago** | Los pagos por Nequi llegan a la cuenta personal de la dueña, no a una cuenta de negocio; el sistema solo registra el método del pago (RN-25) | F-05 |
| **Gestión de precios por tipo de arreglo** | Queda como observación por analizar ("cobra muy barato"); fijar precios es decisión del negocio | F-05 |
| **Varias usuarias con roles** | El taller lo atiende una persona | F-01 |

## Supuestos

- El taller tiene conexión a internet estable durante el horario de atención (según el aprendiz).
- La usuaria tiene un teléfono con navegador y WhatsApp.
- El servidor (VPS) estará disponible para el despliegue durante el Sprint 4.
- Habrá un dominio propio con HTTPS apuntando al VPS; el APK lo necesita para verificar el sitio (ADR-006).
- Meta aprueba la plantilla del aviso de "orden lista" a tiempo para el Sprint 4; si no, la demostración usa el envío asistido.

## Restricciones

- **Plazo:** entrega el 13 de octubre de 2026.
- **Equipo:** una persona, con conocimientos básicos de Laravel.
- **Validación:** la dueña no está disponible; valida el instructor como representante del cliente (F-04).
- **Costo:** sin servicios de pago para las usuarias, en coherencia con la idea de negocio. El costo por mensaje de la API oficial en producción no lo asumen las usuarias; su financiación se define en el plan de negocio.
- **Equipo físico:** sin presupuesto para impresora de etiquetas; la identificación se hace con fotos y el número de orden escrito a mano.
