# Objetivos

**Estado:** borrador · Sprint 1 · se valida con el instructor (F-04)

## Objetivo general

> **OG · Desarrollar un sistema web que permita a un taller de arreglo de ropa llevar un control confiable de sus órdenes de arreglo, entregas y cobros, reemplazando el registro de memoria por información consultable en todo momento.**
>
> Nace de P-01 ([árbol de problemas](arbol-de-problemas.md)) y del objetivo general del [árbol de objetivos](arbol-de-objetivos.md).

## Cómo están escritos

Cada objetivo específico nace de un medio del árbol de objetivos y cumple el criterio **SMART**:

| Letra | Significa | Cómo se cumple aquí |
| --- | --- | --- |
| **S** | Específico | Dice qué debe permitir el sistema y a quién |
| **M** | Medible | Tiene un indicador y una meta verificable con pruebas |
| **A** | Alcanzable | Está dentro del [alcance](alcance.md) de los 30 días |
| **R** | Relevante | Ataca una causa del árbol de problemas |
| **T** | Con plazo | Se cumple al 13 de octubre de 2026 |

**Qué se mide y cuándo.** En 30 días se puede verificar que el sistema **hace** lo que promete, con pruebas. No se puede medir todavía el **impacto** en el taller (menos pérdidas, menos olvidos), porque eso requiere que la dueña lo use durante semanas. Los indicadores de impacto se definen al final y se miden después de la implantación.

## Objetivos específicos

### OE-01 · Registro y consulta de prendas

**Al 13 de octubre de 2026, el sistema permitirá registrar cada prenda recibida con su cliente, el arreglo, el precio y la fecha de entrega acordada, y consultar en una sola pantalla las prendas de un cliente junto con lo que debe.**

| Nace de | Indicador | Meta | Cómo se verifica |
| --- | --- | --- | --- |
| M-01, M-01.1, M-01.2 | Datos obligatorios exigidos al registrar una prenda | El sistema no guarda una prenda sin cliente, arreglo, precio y fecha de entrega | Casos de prueba de las historias de usuario de registro |
| | Pasos para responder "¿qué prendas tiene este cliente y cuánto debe?" | Una búsqueda y una pantalla | Prueba de aceptación sobre la consulta del cliente |

### OE-02 · Estado de avance

**Al 13 de octubre de 2026, el sistema permitirá registrar el estado de cada prenda (pendiente, en proceso, terminada, entregada) y calculará el estado de la orden a partir del estado de sus prendas.**

| Nace de | Indicador | Meta | Cómo se verifica |
| --- | --- | --- | --- |
| M-02, M-02.1 | Órdenes que aparecen como listas con alguna prenda sin terminar | Cero | Pruebas automáticas de la regla de negocio que deriva el estado |

### OE-03 · Aviso al cliente

**Al 13 de octubre de 2026, el sistema preparará el aviso al cliente cuando todas las prendas de su orden estén terminadas y dejará constancia de cada aviso enviado.**

| Nace de | Indicador | Meta | Cómo se verifica |
| --- | --- | --- | --- |
| M-03 | Órdenes listas que ofrecen el aviso | Todas | Prueba de aceptación del flujo "orden lista" |
| | Avisos enviados con fecha registrada | Todos | Prueba de la constancia del aviso |

El aviso se envía por WhatsApp con un mensaje ya redactado que la usuaria confirma; el sistema no lo envía solo. El motivo está en el [alcance](alcance.md).

### OE-04 · Pagos, abonos y saldo

**Al 13 de octubre de 2026, el sistema permitirá registrar pagos y abonos por orden, calculará el saldo pendiente a partir del valor de las prendas y los pagos, y mostrará el total por cobrar del taller.**

| Nace de | Indicador | Meta | Cómo se verifica |
| --- | --- | --- | --- |
| M-04, M-04.1 | Diferencia entre el saldo mostrado y el valor de las prendas menos los pagos válidos | Cero en todos los casos de prueba | Pruebas automáticas de las reglas de saldo |
| | Abonos aceptados por encima del saldo pendiente | Cero | Prueba de la regla de validación del abono |

### OE-05 · Seguimiento de vencidas y sin reclamar

**Al 13 de octubre de 2026, el sistema permitirá consultar las órdenes cuya fecha de entrega ya pasó y las prendas terminadas que no se han recogido, con su cantidad y los días que llevan esperando.**

| Nace de | Indicador | Meta | Cómo se verifica |
| --- | --- | --- | --- |
| M-05 | Consulta de prendas sin reclamar con cantidad y días de espera | Disponible y coincide con los datos de prueba | Prueba de aceptación con datos preparados |

## Indicadores de impacto (después de la implantación)

Miden los fines del árbol de objetivos. No forman parte de la entrega del 13 de octubre porque requieren uso real del sistema.

| Fin | Indicador | Línea base hoy (F-05) |
| --- | --- | --- |
| FN-01 · Entregas a tiempo | Porcentaje de órdenes entregadas en la fecha acordada o antes | Desconocida: no hay registro |
| FN-02 · Nada entregado sin cobrar | Prendas entregadas con saldo no registrado | Desconocida: los saldos se llevan de memoria |
| FN-03 · Certeza del dinero | Total por cobrar consultable al instante | No existe |
| FN-04 · Prendas sin reclamar conocidas | Cantidad de prendas sin reclamar y su antigüedad | Desconocida: "dos meses o más, o nunca" |

Que la línea base sea "desconocida" es parte del problema: el sistema es el que empezará a producir esas cifras.
