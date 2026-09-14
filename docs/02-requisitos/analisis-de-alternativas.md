# Análisis de alternativas

**Estado:** completo · Sprint 1 · fuente **F-03** · información consultada el 14 de septiembre de 2026

## Para qué sirve

Antes de construir un sistema hay que responder una pregunta: **¿por qué el taller no usa algo que ya existe?** Este análisis revisa las alternativas reales, las compara con lo que el taller necesita y deja constancia de qué ideas se toman de ellas.

## Método

1. **Búsqueda** de software para talleres de arreglos, sastrerías y lavanderías, en español y en inglés, y de las herramientas que una emprendedora usaría sin comprar nada.
2. **Consulta de las páginas oficiales** de cada producto (precios, funciones y centro de ayuda). Donde la página no se pudo abrir, se usaron los textos del propio sitio mostrados por el buscador, y así se indica.
3. **Comparación con criterios que nacen de los medios** del [árbol de objetivos](../01-problema/arbol-de-objetivos.md), más el costo y el contexto colombiano, que vienen de la [idea de negocio](../01-problema/idea-de-negocio.md).

### Limitaciones

- **No se probaron las aplicaciones.** La comparación se basa en lo que cada fabricante publica.
- **"No indicado" no significa que no lo tenga:** significa que la información pública consultada no lo menciona.
- **Los precios cambian** y se muestran en la moneda en que los publica cada fabricante, sin convertir.

## Alternativas revisadas

| Alternativa | Tipo | País o mercado | Precio publicado | Fuente |
| --- | --- | --- | --- | --- |
| **Memoria de la dueña** | Situación actual | — | Gratis | F-05 |
| **Hoja de cálculo** (Google Sheets o Excel) | Herramienta general | — | Gratis con una cuenta de Google | Conocimiento general |
| **WhatsApp Business** | App de mensajería para negocios | Global, en español | App gratuita | [Centro de ayuda de WhatsApp](https://faq.whatsapp.com/641572844337957/?locale=en_US&category=5245246) |
| **Alegra POS** | Punto de venta y facturación electrónica | Colombia | Desde $25.900 COP al mes (plan Emprendedor) | [Precios de Alegra POS](https://www.alegra.com/colombia/pos/precios/) |
| **TailorMate** | App para sastrerías | India | Gratis hasta 5 órdenes y 5 clientes; planes de ₹200 a ₹1.000 al mes | [tailormateapp.com](https://www.tailormateapp.com/) |
| **Orderry** | Software de órdenes de servicio con versión para sastrerías | Global, con versión en español | Desde USD 39 al mes (plan Hobby, 100 órdenes cada 30 días) | [Orderry para sastrerías](https://orderry.com/tailor-shop-software/) · [precios](https://orderry.com/pricing/) |
| **CleanCloud** | Punto de venta para lavanderías y tintorerías | Global | Desde USD 99 al mes; los SMS se cobran aparte | [Precios de CleanCloud](https://cleancloudapp.com/pricing) · [Capterra](https://www.capterra.com/p/133390/CleanCloud/) |
| **GTG Arreglos** | Software para talleres de arreglos de ropa | España | De €30 a €70 al mes | [Gesturas · GTG Arreglos](https://www.gesturas.com/gtg-arreglos/) |
| **tailorbird** | Software de pedidos para talleres | México | $599 MXN al mes (plan Finch) | [tailorbird.app](https://tailorbird.app/) (textos del sitio mostrados por el buscador; la página bloqueó la consulta directa) |

## Comparación

Cada criterio nace de un medio del árbol de objetivos. Valores: **Sí**, **Parcial** (lo cubre a medias o a mano), **No**, **No indicado**.

| Criterio | Nace de | Memoria | Hoja de cálculo | WhatsApp Business | Alegra POS | TailorMate | Orderry | CleanCloud | GTG Arreglos | tailorbird |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| **Registrar una orden con varias prendas, precio y fecha** | M-01 | No | Parcial: a mano, sin validaciones | No | No indicado | Sí | Sí | Sí | Sí | Sí |
| **Estado de avance de cada orden o prenda** | M-02 | No | Parcial: columna manual | Parcial: etiquetas por conversación | No indicado | Sí | Sí | No indicado | Sí | Sí |
| **Aviso al cliente cuando está lista** | M-03 | No | No | Parcial: mensaje manual con respuestas rápidas | No indicado | Sí, WhatsApp y SMS; no indica si es automático | SMS y correo automáticos; WhatsApp no indicado | SMS y correo; SMS con costo adicional | WhatsApp semiautomático | WhatsApp con mensaje listo para enviar |
| **Abonos y saldo pendiente** | M-04 | No | Parcial: fórmulas hechas a mano | No | No indicado | Sí | Sí | Parcial: facturación y cuentas de cliente | No indicado | Sí |
| **Seguimiento de órdenes sin reclamar** | M-05 | No | Parcial: filtros manuales | No | No indicado | No indicado | No indicado | Sí: recordatorio si no recogen | No indicado | No indicado |
| **Fotos de las prendas** | M-06.1 | No | No | Parcial: fotos sueltas en el chat | No indicado | Parcial: galería de trabajos | Sí | No indicado | No indicado | No indicado |
| **Número o código para identificar la orden** | M-06 | No | Parcial | No | No indicado | No indicado | Sí: códigos de barras y etiquetas | Sí: códigos de barras | Sí: tickets | Sí: folio |
| **Sin costo para la usuaria** | Idea de negocio | Sí | Sí | Sí | No | Solo 5 órdenes | No | No | No | No |
| **Pensado para Colombia** (español, pesos colombianos) | Contexto | — | Se adapta | Sí | Sí | No | Parcial: en español, precio en dólares | No indicado | No: España | Parcial: español, precio en pesos mexicanos |

## Hallazgos

### 1. El problema es real y reconocido

Existen productos dedicados justamente a talleres de arreglos: GTG Arreglos y tailorbird dicen haber nacido en uno. Todos resuelven lo mismo que el árbol de problemas identificó: registrar órdenes, seguir su estado, cobrar y avisar. **Esto valida el análisis**: el taller no tiene un problema inventado.

### 2. Las herramientas gratuitas no controlan el trabajo

- **WhatsApp Business** es gratis y el taller ya usa WhatsApp, pero organiza conversaciones, no órdenes. No calcula saldos ni sabe qué prendas están terminadas.
- **Una hoja de cálculo** es gratis, pero todo depende de escribir bien cada fila y cada fórmula. Repite el error C-04.1: un saldo sumado y restado a mano se descuadra.
- **Alegra POS** está hecho para Colombia, pero su plan de precios gira alrededor de facturar ventas; la información publicada no menciona órdenes de servicio, estados ni avisos.

### 3. Las especializadas cuestan cada mes y están pensadas para otros mercados

- **Costo mensual:** van de unos USD 39 a USD 99 (Orderry, CleanCloud), de €30 a €70 (GTG Arreglos) o $599 MXN (tailorbird). Choca con la idea de un sistema gratuito para emprendedoras pequeñas ([idea de negocio](../01-problema/idea-de-negocio.md)).
- **Otros mercados:** GTG Arreglos está hecho para España (incluye la integración con Verifactu, el sistema de facturación español), tailorbird para México y TailorMate para India. Ninguna publica soporte para pesos colombianos o Nequi.
- **Avisos:** donde el aviso por WhatsApp está indicado, es semiautomático o con un mensaje listo para enviar. Los avisos automáticos publicados son por SMS o correo, y en CleanCloud el SMS tiene costo adicional.

### 4. Conclusión

Se justifica construir el sistema. No porque falten soluciones en el mercado, sino porque ninguna de las revisadas reúne las cuatro condiciones de este caso: **gratuito para la usuaria**, **pensado para Colombia**, **aviso automático por WhatsApp** y **seguimiento de lo que el taller hoy no puede medir** (saldos, prendas sin reclamar y prendas sin identificar).

## Qué se toma de las alternativas

Revisarlas no solo justifica el proyecto: confirma decisiones y deja ideas.

| Idea observada | Dónde | Cómo se usa en El-taller-ines |
| --- | --- | --- |
| Estados recibido, en proceso, listo y entregado | TailorMate, Orderry | Confirma los estados de prenda de RN-12 |
| Número o folio visible de la orden | tailorbird, GTG Arreglos | Confirma RN-08 y HU-08; sin impresora, el número se escribe a mano en la bolsa |
| Abono registrado al crear la orden | Orderry | Confirma HU-24 |
| Mensaje de WhatsApp prearmado que la usuaria envía | GTG Arreglos, tailorbird | Es el envío asistido de respaldo (ADR-003); el automático por la API oficial va más allá |
| Recordatorio automático a quien no recoge su orden | CleanCloud | **Fuera de esta entrega:** cada mensaje automático tiene costo, y el medio M-05 se cumple con la lista de órdenes sin reclamar. Queda para una fase siguiente |
| Códigos de barras y etiquetas | Orderry, CleanCloud | **Fuera del alcance:** no hay presupuesto para impresora ni lector (F-05); se reemplaza con fotos y el número de orden |

## Fuentes consultadas

- WhatsApp Help Center · [About WhatsApp Business](https://faq.whatsapp.com/641572844337957/?locale=en_US&category=5245246) · [How to use labels](https://faq.whatsapp.com/3398508707096369/?cms_platform=android) · [How to use quick replies](https://faq.whatsapp.com/1791149784551042/?cms_platform=android)
- Alegra · [Precios del sistema POS](https://www.alegra.com/colombia/pos/precios/)
- TailorMate · [tailormateapp.com](https://www.tailormateapp.com/)
- Orderry · [Software para sastrerías](https://orderry.com/tailor-shop-software/) · [Precios](https://orderry.com/pricing/)
- CleanCloud · [Precios](https://cleancloudapp.com/pricing) · [Ficha en Capterra](https://www.capterra.com/p/133390/CleanCloud/)
- Gesturas · [GTG Arreglos](https://www.gesturas.com/gtg-arreglos/) · [Comunicación por WhatsApp](https://www.gesturas.com/gtg-arreglos-comunicacion-por-whatsapp/)
- tailorbird · [tailorbird.app](https://tailorbird.app/)
