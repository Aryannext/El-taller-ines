-- Esquema de la base de datos de El-taller-ines
-- MySQL 8.4 · InnoDB · utf8mb4_0900_ai_ci (compara textos sin distinguir mayúsculas ni tildes)
--
-- Es la referencia del modelo de datos (DOC-17). Las migraciones de Laravel del Sprint 3 deben producir este mismo esquema.
-- Los comentarios de cada tabla y columna generan el diccionario de datos (scripts/verificar_modelo.py).
-- Las fechas y horas se guardan en hora de Colombia (RN-09): la conexión fija time_zone = '-05:00' y Colombia no cambia de horario.

SET NAMES utf8mb4;

CREATE TABLE negocios (
  id                BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT COMMENT 'Identificador del negocio',
  nombre            VARCHAR(120)      NOT NULL COMMENT 'Nombre del taller',
  dias_sin_reclamar SMALLINT UNSIGNED NOT NULL DEFAULT 30 COMMENT 'Días en Lista para entregar después de los cuales una orden queda sin reclamar, entre 1 y 365 (RN-35)',
  creado_en         DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de registro',
  actualizado_en    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora del último cambio',
  PRIMARY KEY (id),
  CONSTRAINT ck_negocios_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0),
  CONSTRAINT ck_negocios_dias_sin_reclamar CHECK (dias_sin_reclamar BETWEEN 1 AND 365)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Taller que usa el sistema. En esta entrega hay uno solo (ADR-002)';

CREATE TABLE usuarios (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador de la usuaria',
  negocio_id     BIGINT UNSIGNED NOT NULL COMMENT 'Negocio al que pertenece; solo ve la información de ese negocio (RN-01)',
  nombre         VARCHAR(120)    NOT NULL COMMENT 'Nombre de la persona',
  usuario        VARCHAR(60)     NOT NULL COMMENT 'Nombre con el que inicia sesión; único en todo el sistema',
  contrasena     VARCHAR(255)    NOT NULL COMMENT 'Hash de la contraseña, nunca el texto plano (RNF-19)',
  token_recordar VARCHAR(100)    NULL COMMENT 'Token de la sesión recordada en el dispositivo',
  creado_en      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de registro',
  actualizado_en DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora del último cambio',
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_usuario (usuario),
  KEY ix_usuarios_negocio (negocio_id),
  CONSTRAINT fk_usuarios_negocio FOREIGN KEY (negocio_id) REFERENCES negocios (id),
  CONSTRAINT ck_usuarios_usuario CHECK (CHAR_LENGTH(TRIM(usuario)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Persona del negocio que usa el sistema; hoy, la dueña del taller';

CREATE TABLE clientes (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador del cliente',
  negocio_id     BIGINT UNSIGNED NOT NULL COMMENT 'Negocio que atiende al cliente (RN-01)',
  nombre         VARCHAR(120)    NOT NULL COMMENT 'Nombre del cliente; se busca sin distinguir mayúsculas ni tildes (RN-02, RF-05)',
  celular        CHAR(10)        NOT NULL COMMENT 'Celular colombiano de 10 dígitos que empieza por 3, destino de los avisos (RN-03); puede repetirse (RN-04)',
  creado_en      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de registro',
  actualizado_en DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora del último cambio',
  PRIMARY KEY (id),
  UNIQUE KEY uq_clientes_negocio_id (negocio_id, id),
  KEY ix_clientes_negocio_nombre (negocio_id, nombre),
  KEY ix_clientes_negocio_celular (negocio_id, celular),
  CONSTRAINT fk_clientes_negocio FOREIGN KEY (negocio_id) REFERENCES negocios (id),
  CONSTRAINT ck_clientes_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0),
  CONSTRAINT ck_clientes_celular CHECK (REGEXP_LIKE(celular, '^3[0-9]{9}$'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Persona que lleva prendas a arreglar. No usa el sistema; recibe los avisos';

CREATE TABLE tipos_prenda (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador del tipo',
  negocio_id     BIGINT UNSIGNED NOT NULL COMMENT 'Negocio dueño de la lista de tipos (ADR-002)',
  nombre         VARCHAR(60)     NOT NULL COMMENT 'Nombre del tipo; no se repite en el negocio sin distinguir mayúsculas ni tildes (RN-43)',
  activo         BOOLEAN         NOT NULL DEFAULT TRUE COMMENT 'Si aparece al registrar prendas nuevas; desactivar no cambia las prendas existentes (RF-17)',
  creado_en      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de registro',
  actualizado_en DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora del último cambio',
  PRIMARY KEY (id),
  UNIQUE KEY uq_tipos_prenda_negocio_nombre (negocio_id, nombre),
  CONSTRAINT fk_tipos_prenda_negocio FOREIGN KEY (negocio_id) REFERENCES negocios (id),
  CONSTRAINT ck_tipos_prenda_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Lista de tipos de prenda de cada negocio; empieza con seis y crece con «Otro» (RF-16, RN-43)';

CREATE TABLE metodos_pago (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador del método',
  negocio_id     BIGINT UNSIGNED NOT NULL COMMENT 'Negocio que usa el método (RN-25)',
  nombre         VARCHAR(40)     NOT NULL COMMENT 'Nombre del método; el taller usa Efectivo y Nequi (RN-25)',
  activo         BOOLEAN         NOT NULL DEFAULT TRUE COMMENT 'Si se ofrece al registrar pagos nuevos',
  creado_en      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de registro',
  actualizado_en DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora del último cambio',
  PRIMARY KEY (id),
  UNIQUE KEY uq_metodos_pago_negocio_nombre (negocio_id, nombre),
  CONSTRAINT fk_metodos_pago_negocio FOREIGN KEY (negocio_id) REFERENCES negocios (id),
  CONSTRAINT ck_metodos_pago_nombre CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Métodos de pago que acepta cada negocio. Solo se registra el método: no hay conexión con Nequi';

CREATE TABLE ordenes (
  id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador de la orden',
  negocio_id             BIGINT UNSIGNED NOT NULL COMMENT 'Negocio de la orden; siempre coincide con el de su cliente por la llave foránea compuesta (ADR-004)',
  cliente_id             BIGINT UNSIGNED NOT NULL COMMENT 'Cliente que dejó las prendas en una misma visita (RN-05)',
  numero                 INT UNSIGNED    NOT NULL COMMENT 'Número consecutivo dentro del negocio; se muestra como #0042 y nunca se reutiliza (RN-08)',
  fecha_entrega_acordada DATE            NOT NULL COMMENT 'Fecha de entrega acordada con el cliente; puede ser el mismo día de la recepción, no antes (RN-07)',
  recibida_en            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora en que se registró la orden (RN-09)',
  lista_en               DATETIME        NULL COMMENT 'Fecha y hora en que la orden quedó Lista para entregar; se borra si vuelve a En proceso y se conserva al entregar (RN-22)',
  cancelada_en           DATETIME        NULL COMMENT 'Fecha y hora de la cancelación; si tiene valor la orden está Cancelada y no se reabre (RN-24)',
  token_formulario       CHAR(36)        NULL COMMENT 'Identificador del envío del formulario; impide registrar la misma orden dos veces (RNF-14)',
  actualizado_en         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora del último cambio',
  PRIMARY KEY (id),
  UNIQUE KEY uq_ordenes_negocio_numero (negocio_id, numero),
  UNIQUE KEY uq_ordenes_token_formulario (token_formulario),
  KEY ix_ordenes_negocio_cliente (negocio_id, cliente_id),
  KEY ix_ordenes_cliente (cliente_id),
  KEY ix_ordenes_negocio_entrega (negocio_id, fecha_entrega_acordada),
  KEY ix_ordenes_negocio_lista (negocio_id, lista_en),
  CONSTRAINT fk_ordenes_negocio FOREIGN KEY (negocio_id) REFERENCES negocios (id),
  CONSTRAINT fk_ordenes_cliente FOREIGN KEY (negocio_id, cliente_id) REFERENCES clientes (negocio_id, id),
  CONSTRAINT ck_ordenes_numero CHECK (numero > 0),
  CONSTRAINT ck_ordenes_entrega CHECK (fecha_entrega_acordada >= CAST(recibida_en AS DATE))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Prendas que un cliente deja en una misma visita; lo que va en una bolsa. Su estado, valor y saldo se calculan (RN-18, RN-26, RN-27)';

CREATE TABLE prendas (
  id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador de la prenda',
  orden_id            BIGINT UNSIGNED NOT NULL COMMENT 'Orden a la que pertenece (RN-06)',
  tipo_prenda_id      BIGINT UNSIGNED NOT NULL COMMENT 'Tipo de prenda de la lista del mismo negocio (RN-10, RN-43)',
  descripcion_arreglo VARCHAR(255)    NOT NULL COMMENT 'Qué arreglo lleva, escrito al registrarla (RN-10)',
  precio              INT UNSIGNED    NOT NULL COMMENT 'Precio del arreglo en pesos colombianos, entero y mayor que cero (RN-11)',
  estado              ENUM('pendiente', 'en_proceso', 'terminada', 'entregada', 'devuelta') NOT NULL DEFAULT 'pendiente' COMMENT 'Estado de la prenda; toda prenda nueva empieza Pendiente (RN-12)',
  entregada_en        DATETIME        NULL COMMENT 'Fecha y hora de entrega de la prenda; la entrega real de la orden es la de su última prenda (RN-20, RN-23)',
  devuelta_en         DATETIME        NULL COMMENT 'Fecha y hora en que el cliente se la llevó sin arreglar; su precio deja de contar (RN-44)',
  creado_en           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de registro',
  actualizado_en      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora del último cambio',
  PRIMARY KEY (id),
  KEY ix_prendas_orden_estado (orden_id, estado),
  KEY ix_prendas_tipo (tipo_prenda_id),
  CONSTRAINT fk_prendas_orden FOREIGN KEY (orden_id) REFERENCES ordenes (id),
  CONSTRAINT fk_prendas_tipo FOREIGN KEY (tipo_prenda_id) REFERENCES tipos_prenda (id),
  CONSTRAINT ck_prendas_descripcion CHECK (CHAR_LENGTH(TRIM(descripcion_arreglo)) > 0),
  CONSTRAINT ck_prendas_precio CHECK (precio > 0),
  CONSTRAINT ck_prendas_entregada CHECK ((estado = 'entregada') = (entregada_en IS NOT NULL)),
  CONSTRAINT ck_prendas_devuelta CHECK ((estado = 'devuelta') = (devuelta_en IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Pieza de ropa de una orden, con su arreglo, su precio y su estado';

CREATE TABLE fotos (
  id        BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT COMMENT 'Identificador de la foto',
  prenda_id BIGINT UNSIGNED   NOT NULL COMMENT 'Prenda de la foto; al eliminar la prenda se eliminan sus fotos (RN-17)',
  posicion  TINYINT UNSIGNED  NOT NULL COMMENT 'Lugar de la foto en la prenda, de 1 a 3: así una prenda no tiene más de tres (RN-17)',
  ruta      VARCHAR(255)      NOT NULL COMMENT 'Ubicación del archivo en el almacenamiento privado, fuera de la carpeta pública (RNF-25)',
  ancho_px  SMALLINT UNSIGNED NOT NULL COMMENT 'Ancho de la imagen guardada, en píxeles (RNF-03)',
  alto_px   SMALLINT UNSIGNED NOT NULL COMMENT 'Alto de la imagen guardada, en píxeles (RNF-03)',
  bytes     INT UNSIGNED      NOT NULL COMMENT 'Tamaño del archivo guardado; no más de 400 KB (RNF-03)',
  creado_en DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora en que se tomó o se subió',
  PRIMARY KEY (id),
  UNIQUE KEY uq_fotos_prenda_posicion (prenda_id, posicion),
  UNIQUE KEY uq_fotos_ruta (ruta),
  CONSTRAINT fk_fotos_prenda FOREIGN KEY (prenda_id) REFERENCES prendas (id) ON DELETE CASCADE,
  CONSTRAINT ck_fotos_posicion CHECK (posicion BETWEEN 1 AND 3),
  CONSTRAINT ck_fotos_lado_mayor CHECK (GREATEST(ancho_px, alto_px) BETWEEN 1 AND 1600),
  CONSTRAINT ck_fotos_bytes CHECK (bytes BETWEEN 1 AND 409600)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Foto de una prenda para reconocerla entre las demás del rincón (RN-17, M-06.1). Guarda la ubicación del archivo, no la imagen';

CREATE TABLE pagos (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Identificador del pago',
  orden_id         BIGINT UNSIGNED NOT NULL COMMENT 'Orden que se paga (RN-25)',
  metodo_pago_id   BIGINT UNSIGNED NOT NULL COMMENT 'Método de pago de la lista del mismo negocio (RN-25)',
  valor            INT UNSIGNED    NOT NULL COMMENT 'Valor en pesos, entero y mayor que cero; al registrarlo no supera el saldo (RN-25, RN-28)',
  pagado_en        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del pago, que es la del registro (RF-26, RN-33)',
  anulado_en       DATETIME        NULL COMMENT 'Fecha y hora de la anulación; un pago anulado no cuenta en el saldo y no se borra (RN-31)',
  motivo_anulacion VARCHAR(255)    NULL COMMENT 'Motivo de la anulación, obligatorio al anular (RN-31)',
  token_formulario CHAR(36)        NULL COMMENT 'Identificador del envío del formulario; impide registrar el mismo pago dos veces (RNF-14)',
  actualizado_en   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora del último cambio',
  PRIMARY KEY (id),
  UNIQUE KEY uq_pagos_token_formulario (token_formulario),
  KEY ix_pagos_orden (orden_id),
  KEY ix_pagos_metodo (metodo_pago_id),
  KEY ix_pagos_pagado_en (pagado_en),
  CONSTRAINT fk_pagos_orden FOREIGN KEY (orden_id) REFERENCES ordenes (id),
  CONSTRAINT fk_pagos_metodo FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago (id),
  CONSTRAINT ck_pagos_valor CHECK (valor > 0),
  CONSTRAINT ck_pagos_anulacion CHECK ((anulado_en IS NULL) = (motivo_anulacion IS NULL)),
  CONSTRAINT ck_pagos_motivo CHECK (motivo_anulacion IS NULL OR CHAR_LENGTH(TRIM(motivo_anulacion)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Dinero que el cliente entrega por una orden, sea el total o un abono (RN-25)';

CREATE TABLE avisos (
  id                  BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT 'Identificador del aviso',
  orden_id            BIGINT UNSIGNED  NOT NULL COMMENT 'Orden que quedó lista (RN-37)',
  ciclo_lista_en      DATETIME         NOT NULL COMMENT 'Valor de ordenes.lista_en al generar el aviso: identifica cada vez que la orden quedó lista (RN-38)',
  estado              ENUM('en_cola', 'enviado', 'pendiente_asistido', 'descartado') NOT NULL DEFAULT 'en_cola' COMMENT 'Resultado del aviso (RN-39, RN-40, RN-41)',
  canal               ENUM('api_oficial', 'evolution_api', 'asistido') NULL COMMENT 'Canal por el que salió el aviso (RN-40, ADR-003, ADR-007)',
  mensaje             TEXT             NULL COMMENT 'Texto enviado, armado con los datos de la orden en el momento del envío (RN-42)',
  intentos            TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Intentos de envío por la API oficial, máximo 3 (RNF-17)',
  id_mensaje_whatsapp VARCHAR(100)     NULL COMMENT 'Identificador que devuelve la API oficial al aceptar el mensaje',
  generado_en         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora en que se generó (RN-37)',
  resuelto_en         DATETIME         NULL COMMENT 'Fecha y hora en que se envió o se descartó (RN-41)',
  actualizado_en      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha y hora del último cambio',
  PRIMARY KEY (id),
  UNIQUE KEY uq_avisos_orden_ciclo (orden_id, ciclo_lista_en),
  KEY ix_avisos_estado (estado),
  CONSTRAINT fk_avisos_orden FOREIGN KEY (orden_id) REFERENCES ordenes (id),
  CONSTRAINT ck_avisos_intentos CHECK (intentos <= 3),
  CONSTRAINT ck_avisos_enviado CHECK (estado <> 'enviado' OR (canal IS NOT NULL AND mensaje IS NOT NULL AND resuelto_en IS NOT NULL)),
  CONSTRAINT ck_avisos_descartado CHECK (estado <> 'descartado' OR resuelto_en IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
  COMMENT='Mensaje al cliente para informarle que su orden está lista, con su canal y su resultado (RN-37 a RN-42)';
