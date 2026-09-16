# Diccionario de datos

**Generado** por `scripts/verificar_modelo.py` desde la base de datos real creada con [esquema.sql](esquema.sql) en MySQL 8.4.7. No se edita a mano: se cambia el esquema y se vuelve a generar.

**10 tablas** · **79 columnas** · **12 llaves foráneas** · **22 restricciones CHECK**. Juego de caracteres `utf8mb4` con collation `utf8mb4_0900_ai_ci`.

Claves: **PK** llave primaria · **FK** llave foránea · **UK** parte de una clave única.

## `negocios`

Taller que usa el sistema. En esta entrega hay uno solo (ADR-002)

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK | Identificador del negocio |
| `nombre` | `varchar(120)` | No | — | — | Nombre del taller |
| `dias_sin_reclamar` | `smallint unsigned` | No | 30 | — | Días en Lista para entregar después de los cuales una orden queda sin reclamar, entre 1 y 365 (RN-35) |
| `creado_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora de registro |
| `actualizado_en` | `datetime` | No | CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP | — | Fecha y hora del último cambio |

**Índices**

- `PRIMARY`: llave primaria sobre (id)

**Restricciones CHECK**

- `ck_negocios_dias_sin_reclamar`: `dias_sin_reclamar between 1 and 365`
- `ck_negocios_nombre`: `char_length(trim(nombre)) > 0`

## `usuarios`

Persona del negocio que usa el sistema; hoy, la dueña del taller

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK | Identificador de la usuaria |
| `negocio_id` | `bigint unsigned` | No | — | FK | Negocio al que pertenece; solo ve la información de ese negocio (RN-01) |
| `nombre` | `varchar(120)` | No | — | — | Nombre de la persona |
| `usuario` | `varchar(60)` | No | — | UK | Nombre con el que inicia sesión; único en todo el sistema |
| `contrasena` | `varchar(255)` | No | — | — | Hash de la contraseña, nunca el texto plano (RNF-19) |
| `token_recordar` | `varchar(100)` | Sí | — | — | Token de la sesión recordada en el dispositivo |
| `creado_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora de registro |
| `actualizado_en` | `datetime` | No | CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP | — | Fecha y hora del último cambio |

**Índices**

- `PRIMARY`: llave primaria sobre (id)
- `uq_usuarios_usuario`: único sobre (usuario)
- `ix_usuarios_negocio`: índice sobre (negocio_id)

**Llaves foráneas**

- `fk_usuarios_negocio`: (negocio_id) → `negocios` (id) · al eliminar en `negocios`: no se permite mientras tenga filas relacionadas

**Restricciones CHECK**

- `ck_usuarios_usuario`: `char_length(trim(usuario)) > 0`

## `clientes`

Persona que lleva prendas a arreglar. No usa el sistema; recibe los avisos

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK, UK | Identificador del cliente |
| `negocio_id` | `bigint unsigned` | No | — | FK, UK | Negocio que atiende al cliente (RN-01) |
| `nombre` | `varchar(120)` | No | — | — | Nombre del cliente; se busca sin distinguir mayúsculas ni tildes (RN-02, RF-05) |
| `celular` | `char(10)` | No | — | — | Celular colombiano de 10 dígitos que empieza por 3, destino de los avisos (RN-03); puede repetirse (RN-04) |
| `creado_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora de registro |
| `actualizado_en` | `datetime` | No | CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP | — | Fecha y hora del último cambio |

**Índices**

- `PRIMARY`: llave primaria sobre (id)
- `uq_clientes_negocio_id`: único sobre (negocio_id, id)
- `ix_clientes_negocio_celular`: índice sobre (negocio_id, celular)
- `ix_clientes_negocio_nombre`: índice sobre (negocio_id, nombre)

**Llaves foráneas**

- `fk_clientes_negocio`: (negocio_id) → `negocios` (id) · al eliminar en `negocios`: no se permite mientras tenga filas relacionadas

**Restricciones CHECK**

- `ck_clientes_celular`: `regexp_like(celular,'^3[0-9]{9}$'`
- `ck_clientes_nombre`: `char_length(trim(nombre)) > 0`

## `tipos_prenda`

Lista de tipos de prenda de cada negocio; empieza con seis y crece con «Otro» (RF-16, RN-43)

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK | Identificador del tipo |
| `negocio_id` | `bigint unsigned` | No | — | FK, UK | Negocio dueño de la lista de tipos (ADR-002) |
| `nombre` | `varchar(60)` | No | — | UK | Nombre del tipo; no se repite en el negocio sin distinguir mayúsculas ni tildes (RN-43) |
| `activo` | `tinyint(1)` | No | 1 | — | Si aparece al registrar prendas nuevas; desactivar no cambia las prendas existentes (RF-17) |
| `creado_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora de registro |
| `actualizado_en` | `datetime` | No | CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP | — | Fecha y hora del último cambio |

**Índices**

- `PRIMARY`: llave primaria sobre (id)
- `uq_tipos_prenda_negocio_nombre`: único sobre (negocio_id, nombre)

**Llaves foráneas**

- `fk_tipos_prenda_negocio`: (negocio_id) → `negocios` (id) · al eliminar en `negocios`: no se permite mientras tenga filas relacionadas

**Restricciones CHECK**

- `ck_tipos_prenda_nombre`: `char_length(trim(nombre)) > 0`

## `metodos_pago`

Métodos de pago que acepta cada negocio. Solo se registra el método: no hay conexión con Nequi

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK | Identificador del método |
| `negocio_id` | `bigint unsigned` | No | — | FK, UK | Negocio que usa el método (RN-25) |
| `nombre` | `varchar(40)` | No | — | UK | Nombre del método; el taller usa Efectivo y Nequi (RN-25) |
| `activo` | `tinyint(1)` | No | 1 | — | Si se ofrece al registrar pagos nuevos |
| `creado_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora de registro |
| `actualizado_en` | `datetime` | No | CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP | — | Fecha y hora del último cambio |

**Índices**

- `PRIMARY`: llave primaria sobre (id)
- `uq_metodos_pago_negocio_nombre`: único sobre (negocio_id, nombre)

**Llaves foráneas**

- `fk_metodos_pago_negocio`: (negocio_id) → `negocios` (id) · al eliminar en `negocios`: no se permite mientras tenga filas relacionadas

**Restricciones CHECK**

- `ck_metodos_pago_nombre`: `char_length(trim(nombre)) > 0`

## `ordenes`

Prendas que un cliente deja en una misma visita; lo que va en una bolsa. Su estado, valor y saldo se calculan (RN-18, RN-26, RN-27)

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK | Identificador de la orden |
| `negocio_id` | `bigint unsigned` | No | — | FK, UK | Negocio de la orden; siempre coincide con el de su cliente por la llave foránea compuesta (ADR-004) |
| `cliente_id` | `bigint unsigned` | No | — | FK | Cliente que dejó las prendas en una misma visita (RN-05) |
| `numero` | `int unsigned` | No | — | UK | Número consecutivo dentro del negocio; se muestra como #0042 y nunca se reutiliza (RN-08) |
| `fecha_entrega_acordada` | `date` | No | — | — | Fecha de entrega acordada con el cliente; puede ser el mismo día de la recepción, no antes (RN-07) |
| `recibida_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora en que se registró la orden (RN-09) |
| `lista_en` | `datetime` | Sí | — | — | Fecha y hora en que la orden quedó Lista para entregar; se borra si vuelve a En proceso y se conserva al entregar (RN-22) |
| `cancelada_en` | `datetime` | Sí | — | — | Fecha y hora de la cancelación; si tiene valor la orden está Cancelada y no se reabre (RN-24) |
| `token_formulario` | `char(36)` | Sí | — | UK | Identificador del envío del formulario; impide registrar la misma orden dos veces (RNF-14) |
| `actualizado_en` | `datetime` | No | CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP | — | Fecha y hora del último cambio |

**Índices**

- `PRIMARY`: llave primaria sobre (id)
- `uq_ordenes_negocio_numero`: único sobre (negocio_id, numero)
- `uq_ordenes_token_formulario`: único sobre (token_formulario)
- `ix_ordenes_cliente`: índice sobre (cliente_id)
- `ix_ordenes_negocio_cliente`: índice sobre (negocio_id, cliente_id)
- `ix_ordenes_negocio_entrega`: índice sobre (negocio_id, fecha_entrega_acordada)
- `ix_ordenes_negocio_lista`: índice sobre (negocio_id, lista_en)

**Llaves foráneas**

- `fk_ordenes_cliente`: (negocio_id, cliente_id) → `clientes` (negocio_id, id) · al eliminar en `clientes`: no se permite mientras tenga filas relacionadas
- `fk_ordenes_negocio`: (negocio_id) → `negocios` (id) · al eliminar en `negocios`: no se permite mientras tenga filas relacionadas

**Restricciones CHECK**

- `ck_ordenes_entrega`: `fecha_entrega_acordada >= cast(recibida_en as date`
- `ck_ordenes_numero`: `numero > 0`

## `prendas`

Pieza de ropa de una orden, con su arreglo, su precio y su estado

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK | Identificador de la prenda |
| `orden_id` | `bigint unsigned` | No | — | FK | Orden a la que pertenece (RN-06) |
| `tipo_prenda_id` | `bigint unsigned` | No | — | FK | Tipo de prenda de la lista del mismo negocio (RN-10, RN-43) |
| `descripcion_arreglo` | `varchar(255)` | No | — | — | Qué arreglo lleva, escrito al registrarla (RN-10) |
| `precio` | `int unsigned` | No | — | — | Precio del arreglo en pesos colombianos, entero y mayor que cero (RN-11) |
| `estado` | `enum('pendiente','en_proceso','terminada','entregada','devuelta')` | No | pendiente | — | Estado de la prenda; toda prenda nueva empieza Pendiente (RN-12) |
| `entregada_en` | `datetime` | Sí | — | — | Fecha y hora de entrega de la prenda; la entrega real de la orden es la de su última prenda (RN-20, RN-23) |
| `devuelta_en` | `datetime` | Sí | — | — | Fecha y hora en que el cliente se la llevó sin arreglar; su precio deja de contar (RN-44) |
| `creado_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora de registro |
| `actualizado_en` | `datetime` | No | CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP | — | Fecha y hora del último cambio |

**Índices**

- `PRIMARY`: llave primaria sobre (id)
- `ix_prendas_orden_estado`: índice sobre (orden_id, estado)
- `ix_prendas_tipo`: índice sobre (tipo_prenda_id)

**Llaves foráneas**

- `fk_prendas_orden`: (orden_id) → `ordenes` (id) · al eliminar en `ordenes`: no se permite mientras tenga filas relacionadas
- `fk_prendas_tipo`: (tipo_prenda_id) → `tipos_prenda` (id) · al eliminar en `tipos_prenda`: no se permite mientras tenga filas relacionadas

**Restricciones CHECK**

- `ck_prendas_descripcion`: `char_length(trim(descripcion_arreglo)) > 0`
- `ck_prendas_devuelta`: `estado = 'devuelta') = (devuelta_en is not null`
- `ck_prendas_entregada`: `estado = 'entregada') = (entregada_en is not null`
- `ck_prendas_precio`: `precio > 0`

## `fotos`

Foto de una prenda para reconocerla entre las demás del rincón (RN-17, M-06.1). Guarda la ubicación del archivo, no la imagen

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK | Identificador de la foto |
| `prenda_id` | `bigint unsigned` | No | — | FK, UK | Prenda de la foto; al eliminar la prenda se eliminan sus fotos (RN-17) |
| `posicion` | `tinyint unsigned` | No | — | UK | Lugar de la foto en la prenda, de 1 a 3: así una prenda no tiene más de tres (RN-17) |
| `ruta` | `varchar(255)` | No | — | UK | Ubicación del archivo en el almacenamiento privado, fuera de la carpeta pública (RNF-25) |
| `ancho_px` | `smallint unsigned` | No | — | — | Ancho de la imagen guardada, en píxeles (RNF-03) |
| `alto_px` | `smallint unsigned` | No | — | — | Alto de la imagen guardada, en píxeles (RNF-03) |
| `bytes` | `int unsigned` | No | — | — | Tamaño del archivo guardado; no más de 400 KB (RNF-03) |
| `creado_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora en que se tomó o se subió |

**Índices**

- `PRIMARY`: llave primaria sobre (id)
- `uq_fotos_prenda_posicion`: único sobre (prenda_id, posicion)
- `uq_fotos_ruta`: único sobre (ruta)

**Llaves foráneas**

- `fk_fotos_prenda`: (prenda_id) → `prendas` (id) · al eliminar en `prendas`: se eliminan también estas filas

**Restricciones CHECK**

- `ck_fotos_bytes`: `bytes between 1 and 409600`
- `ck_fotos_lado_mayor`: `greatest(ancho_px,alto_px) between 1 and 1600`
- `ck_fotos_posicion`: `posicion between 1 and 3`

## `pagos`

Dinero que el cliente entrega por una orden, sea el total o un abono (RN-25)

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK | Identificador del pago |
| `orden_id` | `bigint unsigned` | No | — | FK | Orden que se paga (RN-25) |
| `metodo_pago_id` | `bigint unsigned` | No | — | FK | Método de pago de la lista del mismo negocio (RN-25) |
| `valor` | `int unsigned` | No | — | — | Valor en pesos, entero y mayor que cero; al registrarlo no supera el saldo (RN-25, RN-28) |
| `pagado_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora del pago, que es la del registro (RF-26, RN-33) |
| `anulado_en` | `datetime` | Sí | — | — | Fecha y hora de la anulación; un pago anulado no cuenta en el saldo y no se borra (RN-31) |
| `motivo_anulacion` | `varchar(255)` | Sí | — | — | Motivo de la anulación, obligatorio al anular (RN-31) |
| `token_formulario` | `char(36)` | Sí | — | UK | Identificador del envío del formulario; impide registrar el mismo pago dos veces (RNF-14) |
| `actualizado_en` | `datetime` | No | CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP | — | Fecha y hora del último cambio |

**Índices**

- `PRIMARY`: llave primaria sobre (id)
- `uq_pagos_token_formulario`: único sobre (token_formulario)
- `ix_pagos_metodo`: índice sobre (metodo_pago_id)
- `ix_pagos_orden`: índice sobre (orden_id)
- `ix_pagos_pagado_en`: índice sobre (pagado_en)

**Llaves foráneas**

- `fk_pagos_metodo`: (metodo_pago_id) → `metodos_pago` (id) · al eliminar en `metodos_pago`: no se permite mientras tenga filas relacionadas
- `fk_pagos_orden`: (orden_id) → `ordenes` (id) · al eliminar en `ordenes`: no se permite mientras tenga filas relacionadas

**Restricciones CHECK**

- `ck_pagos_anulacion`: `anulado_en is null) = (motivo_anulacion is null`
- `ck_pagos_motivo`: `motivo_anulacion is null) or (char_length(trim(motivo_anulacion)) > 0`
- `ck_pagos_valor`: `valor > 0`

## `avisos`

Mensaje al cliente para informarle que su orden está lista, con su canal y su resultado (RN-37 a RN-42)

| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |
| --- | --- | --- | --- | --- | --- |
| `id` | `bigint unsigned` | No | auto_increment | PK | Identificador del aviso |
| `orden_id` | `bigint unsigned` | No | — | FK, UK | Orden que quedó lista (RN-37) |
| `ciclo_lista_en` | `datetime` | No | — | UK | Valor de ordenes.lista_en al generar el aviso: identifica cada vez que la orden quedó lista (RN-38) |
| `estado` | `enum('en_cola','enviado','pendiente_asistido','descartado')` | No | en_cola | — | Resultado del aviso (RN-39, RN-40, RN-41) |
| `canal` | `enum('api_oficial','evolution_api','asistido')` | Sí | — | — | Canal por el que salió el aviso (RN-40, ADR-003, ADR-007) |
| `mensaje` | `text` | Sí | — | — | Texto enviado, armado con los datos de la orden en el momento del envío (RN-42) |
| `intentos` | `tinyint unsigned` | No | 0 | — | Intentos de envío por la API oficial, máximo 3 (RNF-17) |
| `id_mensaje_whatsapp` | `varchar(100)` | Sí | — | — | Identificador que devuelve la API oficial al aceptar el mensaje |
| `generado_en` | `datetime` | No | CURRENT_TIMESTAMP | — | Fecha y hora en que se generó (RN-37) |
| `resuelto_en` | `datetime` | Sí | — | — | Fecha y hora en que se envió o se descartó (RN-41) |
| `actualizado_en` | `datetime` | No | CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP | — | Fecha y hora del último cambio |

**Índices**

- `PRIMARY`: llave primaria sobre (id)
- `uq_avisos_orden_ciclo`: único sobre (orden_id, ciclo_lista_en)
- `ix_avisos_estado`: índice sobre (estado)

**Llaves foráneas**

- `fk_avisos_orden`: (orden_id) → `ordenes` (id) · al eliminar en `ordenes`: no se permite mientras tenga filas relacionadas

**Restricciones CHECK**

- `ck_avisos_descartado`: `estado <> 'descartado') or (resuelto_en is not null`
- `ck_avisos_enviado`: `estado <> 'enviado') or ((canal is not null) and (mensaje is not null) and (resuelto_en is not null`
- `ck_avisos_intentos`: `intentos <= 3`
