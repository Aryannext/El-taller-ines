# ADR-004 · El negocio se guarda también en las órdenes, con llave foránea compuesta

**Estado:** aceptada · **Fecha:** 14 de septiembre de 2026 · **Modifica:** el punto 3 de [ADR-002](ADR-002-un-taller-preparado-para-varios.md), solo para la tabla `ordenes`

## Contexto

- **RN-08:** cada orden recibe un número consecutivo **dentro de su negocio** que nunca se reutiliza. Dos negocios pueden tener cada uno su orden #0042; un mismo negocio, no.
- **ADR-002:** la columna `negocio_id` va solo en las tablas raíz. Las demás heredan el negocio por su relación: una orden pertenece a un cliente y el cliente a un negocio.
- **RNF-32:** el modelo debe estar en tercera forma normal, y toda excepción necesita un ADR.
- **El problema:** una restricción `UNIQUE` solo puede usar columnas de su propia tabla. Si `ordenes` no tiene el negocio, la base de datos no puede impedir dos órdenes con el mismo número en el mismo negocio, y la unicidad queda solo en manos del código.
- **Concurrencia:** dos solicitudes casi simultáneas, como un doble toque en guardar o dos pestañas abiertas, pueden calcular el mismo número si nada en la base lo impide.

## Decisión

1. **`ordenes` tiene `negocio_id`** y la restricción `UNIQUE (negocio_id, numero)`.
2. **El negocio de la orden no puede contradecir al de su cliente.** La orden referencia al cliente con una llave foránea compuesta, `(negocio_id, cliente_id) → clientes (negocio_id, id)`: la base rechaza una orden de un negocio con un cliente de otro.
3. **El número se asigna dentro de una transacción** que bloquea la fila del negocio (`SELECT … FOR UPDATE`) y toma el mayor número del negocio más uno. Como las órdenes nunca se eliminan (se cancelan, RN-24), un número nunca se reutiliza.
4. **Las demás tablas dependientes siguen como en ADR-002:** prendas, fotos, pagos y avisos heredan el negocio a través de la orden.

## Alternativas consideradas

| Alternativa | Por qué no |
| --- | --- |
| Garantizar la unicidad solo en el código | La base no detendría un número repetido por concurrencia o por un error futuro; RN-08 quedaría sin respaldo en los datos |
| Guardar el último número usado en la tabla de negocios | Sigue sin existir una restricción única sobre las órdenes, y guarda un dato que se puede calcular |
| Una tabla aparte de numeración por negocio | La orden llegaría al negocio por dos caminos (numeración y cliente) que podrían no coincidir |
| Un número único para todo el sistema | No cumple RN-08: el segundo negocio no empezaría en #0001 |

## Consecuencias

- **Redundancia controlada:** `negocio_id` en `ordenes` depende del cliente, lo que formalmente es una dependencia transitiva. La llave foránea compuesta impide que ese dato quede inconsistente, así que no puede generar las anomalías que la tercera forma normal busca evitar.
- **Consultas más simples:** el filtro por negocio de las órdenes, que son la tabla más consultada del panel y las listas, no necesita unir con `clientes` (RNF-01).
- **Índice adicional:** `clientes` necesita la clave única `(negocio_id, id)` como destino de la llave compuesta.
- **Se prueba en el Sprint 3:** una prueba automática intenta crear una orden con un cliente de otro negocio y otra con un número repetido. [verificar_modelo.py](../../../scripts/verificar_modelo.py) ya lo comprueba sobre el esquema de referencia.
