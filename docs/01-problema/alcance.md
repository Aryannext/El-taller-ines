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
| **Órdenes y prendas** | Crear una orden para un cliente con una o varias prendas: arreglo, tipo, precio y fecha de entrega; editar y eliminar prendas no entregadas | OE-01 | M-01.1 |
| **Estados** | Cambiar el estado de cada prenda; estado de la orden calculado a partir de sus prendas; entrega y cancelación de la orden | OE-02 | M-02, M-02.1 |
| **Pagos** | Registrar pagos y abonos por orden, anular un pago mal registrado sin borrarlo, saldo calculado, total por cobrar | OE-04 | M-04, M-04.1 |
| **Avisos** | Botón para avisar por WhatsApp cuando la orden está lista, con el mensaje redactado, y registro de cada aviso | OE-03 | M-03 |
| **Seguimiento** | Panel con órdenes vencidas, prendas sin reclamar con días de espera y total por cobrar | OE-05 | M-05 |
| **Base preparada para varios negocios** | Tabla de negocio y filtro por negocio en las tablas raíz, con un solo taller registrado | — | [ADR-002](../03-diseno/adr/ADR-002-un-taller-preparado-para-varios.md) |

## Fuera del alcance

| Función | Motivo | Estaba en |
| --- | --- | --- |
| **Varios negocios con registro propio** | No cabe en 30 días; los datos quedan preparados (ADR-002) | Idea de negocio |
| **Envío automático de avisos por WhatsApp** | La API oficial de WhatsApp cobra por mensaje y exige verificar el negocio ante Meta; choca con un sistema gratuito para las usuarias. El aviso asistido cumple el medio M-03 | F-01 |
| **Fotografías de las prendas** | No nace de ninguna causa del árbol de problemas | F-01, F-02 |
| **Observaciones o notas por prenda** | No nace de ninguna causa del árbol; la descripción del arreglo cubre lo necesario. Se reconsidera si el instructor lo pide | F-01, F-02 |
| **Historial detallado de actividad** | El efecto que lo justificaba (E-05, desacuerdos con clientes) se descartó. Sí se conserva el rastro de pagos anulados y avisos enviados | F-01, F-02 |
| **App móvil nativa y trabajo sin conexión** | El taller tiene internet estable; el sistema web funciona en el navegador del teléfono (ADR-001) | F-02 |
| **Bot de Telegram y respaldos por Telegram** | Los respaldos se hacen en el servidor; no nace de una causa del árbol | F-02 |
| **Reportes financieros con gráficos** | El medio M-04 se cumple con el total por cobrar y lo recibido; los gráficos no atacan una causa | F-02 |
| **Gestión de precios por tipo de arreglo** | Queda como observación por analizar ("cobra muy barato"); fijar precios es decisión del negocio | F-05 |
| **Varias usuarias con roles** | El taller lo atiende una persona | F-01 |

## Supuestos

- El taller tiene conexión a internet estable durante el horario de atención (según el aprendiz).
- La usuaria tiene un teléfono con navegador y WhatsApp.
- El servidor (VPS) estará disponible para el despliegue durante el Sprint 4.

## Restricciones

- **Plazo:** entrega el 13 de octubre de 2026.
- **Equipo:** una persona, con conocimientos básicos de Laravel.
- **Validación:** la dueña no está disponible; valida el instructor como representante del cliente (F-04).
- **Costo:** sin servicios de pago para las usuarias, en coherencia con la idea de negocio.
