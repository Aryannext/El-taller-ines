-- Datos de ejemplo del modelo de datos
--
-- Son los mismos de los mockups (docs/03-diseno/mockups/README.md): «hoy» es miércoles 16 de septiembre de 2026.
-- Los nombres son inventados. El negocio 2 existe solo para comprobar que un negocio no ve los datos de otro (RN-01).
-- Es un subconjunto: faltan órdenes intermedias, como la #0043, que no aparecen en ninguna pantalla.
-- Las horas están en hora de Colombia (RN-09).

INSERT INTO negocios (id, nombre, dias_sin_reclamar, creado_en) VALUES
  (1, 'Taller de costura', 30, '2026-07-01 08:00:00'),
  (2, 'Otro taller de prueba', 30, '2026-07-01 08:00:00');

INSERT INTO usuarios (id, negocio_id, nombre, usuario, contrasena, creado_en) VALUES
  (1, 1, 'Dueña del taller', 'taller', 'hash-de-ejemplo-no-es-una-contrasena-real-1', '2026-07-01 08:00:00'),
  (2, 2, 'Usuaria del otro taller', 'otro', 'hash-de-ejemplo-no-es-una-contrasena-real-2', '2026-07-01 08:00:00');

INSERT INTO tipos_prenda (id, negocio_id, nombre, activo) VALUES
  (1, 1, 'Pantalón', TRUE), (2, 1, 'Camisa', TRUE), (3, 1, 'Blusa', TRUE), (4, 1, 'Vestido', TRUE),
  (5, 1, 'Falda', TRUE), (6, 1, 'Chaqueta', FALSE), (7, 1, 'Overol', TRUE),
  (8, 2, 'Pantalón', TRUE), (9, 2, 'Camisa', TRUE), (10, 2, 'Blusa', TRUE), (11, 2, 'Vestido', TRUE),
  (12, 2, 'Falda', TRUE), (13, 2, 'Chaqueta', TRUE);

INSERT INTO metodos_pago (id, negocio_id, nombre) VALUES
  (1, 1, 'Efectivo'), (2, 1, 'Nequi'), (3, 2, 'Efectivo');

INSERT INTO clientes (id, negocio_id, nombre, celular, creado_en) VALUES
  (1, 1, 'Marta Rincón', '3104567890', '2026-09-02 09:55:00'),
  (2, 1, 'Luis Pardo', '3001112233', '2026-09-08 10:55:00'),
  (3, 1, 'Sandra Ruiz', '3159876543', '2026-09-11 09:25:00'),
  (4, 1, 'Carmen Díaz', '3112345678', '2026-07-27 08:55:00'),
  (5, 1, 'Ana Beltrán', '3205550101', '2026-09-12 13:55:00'),
  (6, 1, 'María Gómez', '3012223344', '2026-08-20 09:55:00'),
  (7, 1, 'Mariana López', '3174008821', '2026-09-01 12:00:00'),
  (8, 2, 'Marta Suárez', '3108889999', '2026-09-15 07:55:00');

INSERT INTO ordenes (id, negocio_id, cliente_id, numero, fecha_entrega_acordada, recibida_en, lista_en, cancelada_en) VALUES
  (1, 1, 4, 30, '2026-07-31', '2026-07-27 09:00:00', '2026-08-01 17:00:00', NULL),
  (2, 1, 6, 39, '2026-08-25', '2026-08-20 10:00:00', '2026-08-24 15:00:00', NULL),
  (3, 1, 1, 40, '2026-09-08', '2026-09-02 10:00:00', '2026-09-08 16:00:00', NULL),
  (4, 1, 1, 41, '2026-09-09', '2026-09-04 15:00:00', NULL, '2026-09-05 09:00:00'),
  (5, 1, 1, 42, '2026-09-19', '2026-09-07 10:25:00', NULL, NULL),
  (6, 1, 2, 44, '2026-09-12', '2026-09-08 11:00:00', NULL, NULL),
  (7, 1, 3, 45, '2026-09-15', '2026-09-11 09:30:00', NULL, NULL),
  (8, 1, 5, 46, '2026-09-16', '2026-09-12 14:00:00', '2026-09-16 09:40:00', NULL),
  (9, 2, 8, 1, '2026-09-20', '2026-09-15 08:00:00', NULL, NULL);

INSERT INTO prendas (id, orden_id, tipo_prenda_id, descripcion_arreglo, precio, estado, entregada_en, devuelta_en, creado_en) VALUES
  (1, 1, 2, 'Entallar los costados', 8000, 'terminada', NULL, NULL, '2026-07-27 09:00:00'),
  (2, 1, 2, 'Acortar las mangas', 8000, 'terminada', NULL, NULL, '2026-07-27 09:00:00'),
  (3, 2, 3, 'Ajustar los costados', 10000, 'entregada', '2026-08-25 10:00:00', NULL, '2026-08-20 10:00:00'),
  (4, 3, 1, 'Ajustar la cintura', 20000, 'entregada', '2026-09-10 11:20:00', NULL, '2026-09-02 10:00:00'),
  (5, 4, 5, 'Subir el ruedo', 8000, 'pendiente', NULL, NULL, '2026-09-04 15:00:00'),
  (6, 5, 1, 'Subir basta 3 cm', 15000, 'terminada', NULL, NULL, '2026-09-07 10:25:00'),
  (7, 5, 2, 'Entallar los costados', 8000, 'terminada', NULL, NULL, '2026-09-07 10:25:00'),
  (8, 5, 2, 'Entallar y acortar mangas', 8000, 'pendiente', NULL, NULL, '2026-09-15 16:11:00'),
  (9, 6, 4, 'Ajustar cintura', 23000, 'en_proceso', NULL, NULL, '2026-09-08 11:00:00'),
  (10, 7, 5, 'Subir ruedo', 20000, 'en_proceso', NULL, NULL, '2026-09-11 09:30:00'),
  (11, 8, 6, 'Cambiar la cremallera', 20000, 'terminada', NULL, NULL, '2026-09-12 14:00:00'),
  (12, 9, 8, 'Subir basta', 12000, 'pendiente', NULL, NULL, '2026-09-15 08:00:00');

INSERT INTO fotos (id, prenda_id, posicion, ruta, ancho_px, alto_px, bytes, creado_en) VALUES
  (1, 6, 1, 'fotos/1/0042/prenda-6-1.jpg', 1200, 1600, 286000, '2026-09-07 10:25:00'),
  (2, 6, 2, 'fotos/1/0042/prenda-6-2.jpg', 1600, 1200, 241500, '2026-09-07 10:25:00'),
  (3, 7, 1, 'fotos/1/0042/prenda-7-1.jpg', 1200, 1600, 198300, '2026-09-07 10:25:00'),
  (4, 11, 1, 'fotos/1/0046/prenda-11-1.jpg', 1200, 1600, 305200, '2026-09-12 14:00:00');

INSERT INTO pagos (id, orden_id, metodo_pago_id, valor, pagado_en, anulado_en, motivo_anulacion) VALUES
  (1, 3, 1, 8000, '2026-09-02 10:00:00', NULL, NULL),
  (2, 2, 1, 10000, '2026-08-25 10:00:00', NULL, NULL),
  (3, 5, 1, 10000, '2026-09-07 10:25:00', NULL, NULL),
  (4, 6, 1, 5000, '2026-09-08 11:00:00', NULL, NULL),
  (5, 7, 2, 15000, '2026-09-11 09:35:00', '2026-09-11 09:40:00', 'Registrado en la orden equivocada'),
  (6, 7, 2, 20000, '2026-09-16 09:15:00', NULL, NULL),
  (7, 8, 1, 11000, '2026-09-12 14:00:00', NULL, NULL);

INSERT INTO avisos (id, orden_id, ciclo_lista_en, estado, canal, mensaje, intentos, generado_en, resuelto_en) VALUES
  (1, 1, '2026-08-01 17:00:00', 'enviado', 'asistido', 'Hola Carmen, tu orden #0030 del taller está lista para recoger: 2 prendas. Saldo pendiente: $16.000. Te esperamos.', 0, '2026-08-01 17:00:00', '2026-08-01 17:20:00'),
  (2, 2, '2026-08-24 15:00:00', 'enviado', 'asistido', 'Hola María, tu orden #0039 del taller está lista para recoger: 1 prenda. Saldo pendiente: $10.000. Te esperamos.', 0, '2026-08-24 15:00:00', '2026-08-24 15:05:00'),
  (3, 3, '2026-09-08 16:00:00', 'enviado', 'asistido', 'Hola Marta, tu orden #0040 del taller está lista para recoger: 1 prenda. Saldo pendiente: $12.000. Te esperamos.', 0, '2026-09-08 16:00:00', '2026-09-08 16:30:00'),
  (4, 5, '2026-09-15 16:10:00', 'descartado', NULL, NULL, 0, '2026-09-15 16:10:00', '2026-09-15 16:12:00'),
  (5, 8, '2026-09-16 09:40:00', 'pendiente_asistido', NULL, NULL, 0, '2026-09-16 09:40:00', NULL);
