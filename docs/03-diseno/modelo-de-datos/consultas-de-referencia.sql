-- Consultas de referencia del modelo de datos
--
-- Muestran que lo que no se guarda (estado de avance, valor, saldo, total por cobrar, días de atraso y de espera)
-- se obtiene de lo que sí se guarda. Cada consulta indica las reglas que calcula y el resultado que debe dar con los
-- datos de ejemplo, que son las cifras de los mockups. scripts/verificar_modelo.py las ejecuta y compara.
--
-- La vista es una referencia de cálculo: la arquitectura (DOC-16) decide si el sistema usa una vista o calcula en la aplicación.
-- «Hoy» es un parámetro para que el resultado no dependa del día en que se ejecute; en el sistema es la fecha de Colombia (RN-09).

SET @negocio := 1;
SET @hoy := DATE('2026-09-16');

CREATE OR REPLACE VIEW resumen_ordenes AS
SELECT
  o.id,
  o.negocio_id,
  o.cliente_id,
  o.numero,
  o.fecha_entrega_acordada,
  o.lista_en,
  o.cancelada_en,
  CASE
    WHEN o.cancelada_en IS NOT NULL THEN 'Cancelada'
    WHEN SUM(p.estado IN ('pendiente', 'en_proceso')) > 0 THEN 'En proceso'
    WHEN SUM(p.estado = 'terminada') > 0 THEN 'Lista para entregar'
    ELSE 'Entregada'
  END AS estado,
  SUM(IF(p.estado <> 'devuelta', p.precio, 0)) AS valor,
  COALESCE(pg.pagado, 0) AS pagado,
  SUM(IF(p.estado <> 'devuelta', p.precio, 0)) - COALESCE(pg.pagado, 0) AS saldo,
  SUM(p.estado = 'terminada') AS prendas_terminadas,
  MAX(p.entregada_en) AS ultima_entrega_en
FROM ordenes o
JOIN prendas p ON p.orden_id = o.id
LEFT JOIN (
  SELECT orden_id, SUM(valor) AS pagado
  FROM pagos
  WHERE anulado_en IS NULL
  GROUP BY orden_id
) pg ON pg.orden_id = o.id
GROUP BY o.id, pg.pagado;

-- consulta: Estado, valor y saldo de cada orden
-- reglas: RN-18, RN-26, RN-27, RN-29
-- muestra: PT-05, PT-09, PT-22
-- espera: 30|Lista para entregar|16000|16000|Por cobrar / 39|Entregada|10000|0|Pagada / 40|Entregada|20000|12000|Por cobrar / 41|Cancelada|8000|8000|Cancelada / 42|En proceso|31000|21000|Por cobrar / 44|En proceso|23000|18000|Por cobrar / 45|En proceso|20000|0|Pagada / 46|Lista para entregar|20000|9000|Por cobrar
SELECT numero, estado, valor, saldo,
       CASE WHEN estado = 'Cancelada' THEN 'Cancelada' WHEN saldo > 0 THEN 'Por cobrar' ELSE 'Pagada' END AS estado_pago
FROM resumen_ordenes
WHERE negocio_id = @negocio
ORDER BY numero;

-- consulta: Número visible de una orden
-- reglas: RN-08
-- muestra: PT-07, PT-09
-- espera: #0042
SELECT CONCAT('#', LPAD(numero, 4, '0')) FROM ordenes WHERE negocio_id = @negocio AND numero = 42;

-- consulta: Siguiente número de orden en cada negocio
-- reglas: RN-08
-- muestra: PT-07
-- espera: 47|2
SELECT
  (SELECT COALESCE(MAX(numero), 0) + 1 FROM ordenes WHERE negocio_id = 1),
  (SELECT COALESCE(MAX(numero), 0) + 1 FROM ordenes WHERE negocio_id = 2);

-- consulta: Total por cobrar del negocio
-- reglas: RN-32
-- muestra: PT-02, PT-22
-- espera: 76000|5
SELECT SUM(saldo), COUNT(*)
FROM resumen_ordenes
WHERE negocio_id = @negocio AND estado <> 'Cancelada' AND saldo > 0;

-- consulta: Quién me debe, de la mayor deuda a la menor
-- reglas: RN-29, RN-32
-- muestra: PT-22
-- espera: 42|21000 / 44|18000 / 30|16000 / 40|12000 / 46|9000
SELECT numero, saldo
FROM resumen_ordenes
WHERE negocio_id = @negocio AND estado <> 'Cancelada' AND saldo > 0
ORDER BY saldo DESC;

-- consulta: Órdenes atrasadas con sus días de atraso
-- reglas: RN-09, RN-34
-- muestra: PT-02, PT-20
-- espera: 44|4 / 45|1
SELECT numero, DATEDIFF(@hoy, fecha_entrega_acordada) AS dias_de_atraso
FROM resumen_ordenes
WHERE negocio_id = @negocio AND estado = 'En proceso' AND fecha_entrega_acordada < @hoy
ORDER BY fecha_entrega_acordada;

-- consulta: Órdenes sin reclamar con sus días de espera y prendas
-- reglas: RN-35, RN-36
-- muestra: PT-02, PT-21
-- espera: 30|46|2
SELECT r.numero, DATEDIFF(@hoy, DATE(r.lista_en)) AS dias_de_espera, r.prendas_terminadas
FROM resumen_ordenes r
JOIN negocios n ON n.id = r.negocio_id
WHERE r.negocio_id = @negocio
  AND r.estado = 'Lista para entregar'
  AND DATEDIFF(@hoy, DATE(r.lista_en)) > n.dias_sin_reclamar
ORDER BY r.lista_en;

-- consulta: Órdenes listas para entregar
-- reglas: RN-18
-- muestra: PT-02, PT-08
-- espera: 2
SELECT COUNT(*) FROM resumen_ordenes WHERE negocio_id = @negocio AND estado = 'Lista para entregar';

-- consulta: Avisos por enviar con un toque
-- reglas: RN-40
-- muestra: PT-02, PT-18
-- espera: 46
SELECT o.numero
FROM avisos a
JOIN ordenes o ON o.id = a.orden_id
WHERE o.negocio_id = @negocio AND a.estado = 'pendiente_asistido';

-- consulta: Dinero recibido hoy
-- reglas: RN-09, RN-33
-- muestra: PT-02
-- espera: 20000
SELECT COALESCE(SUM(pg.valor), 0)
FROM pagos pg
JOIN ordenes o ON o.id = pg.orden_id
WHERE o.negocio_id = @negocio AND pg.anulado_en IS NULL AND DATE(pg.pagado_en) = @hoy;

-- consulta: Dinero recibido en septiembre, pagos válidos y anulados
-- reglas: RN-31, RN-33
-- muestra: PT-22
-- espera: 54000|5|1
SELECT SUM(IF(pg.anulado_en IS NULL, pg.valor, 0)), SUM(pg.anulado_en IS NULL), SUM(pg.anulado_en IS NOT NULL)
FROM pagos pg
JOIN ordenes o ON o.id = pg.orden_id
WHERE o.negocio_id = @negocio AND pg.pagado_en >= '2026-09-01' AND pg.pagado_en < '2026-10-01';

-- consulta: Ficha de Marta Rincón: órdenes y total que debe
-- reglas: RN-27, RN-29, RN-32
-- muestra: PT-05
-- espera: 3|33000
SELECT COUNT(*), SUM(IF(r.estado <> 'Cancelada', r.saldo, 0))
FROM resumen_ordenes r
JOIN clientes c ON c.id = r.cliente_id
WHERE c.negocio_id = @negocio AND c.nombre = 'Marta Rincón';

-- consulta: Fecha de entrega real de las órdenes entregadas
-- reglas: RN-20, RN-23
-- muestra: PT-05
-- espera: 39|2026-08-25 10:00 / 40|2026-09-10 11:20
SELECT numero, DATE_FORMAT(ultima_entrega_en, '%Y-%m-%d %H:%i')
FROM resumen_ordenes
WHERE negocio_id = @negocio AND estado = 'Entregada'
ORDER BY numero;

-- consulta: Buscar «maria» sin tildes ni mayúsculas
-- reglas: RN-01
-- muestra: PT-03
-- espera: María Gómez / Mariana López
SELECT nombre FROM clientes WHERE negocio_id = @negocio AND nombre LIKE CONCAT('%', 'maria', '%') ORDER BY nombre;

-- consulta: Buscar «marta» solo en el propio negocio
-- reglas: RN-01
-- muestra: PT-03
-- espera: Marta Rincón
SELECT nombre FROM clientes WHERE negocio_id = @negocio AND nombre LIKE CONCAT('%', 'marta', '%') ORDER BY nombre;

-- consulta: Tipos de prenda que se ofrecen al registrar
-- reglas: RN-43
-- muestra: PT-06, PT-23
-- espera: Blusa / Camisa / Falda / Overol / Pantalón / Vestido
SELECT nombre FROM tipos_prenda WHERE negocio_id = @negocio AND activo ORDER BY nombre;

-- consulta: Una devolución sin arreglar baja el valor y el saldo
-- reglas: RN-26, RN-27, RN-44
-- muestra: PT-12
-- espera: 23000|13000
SELECT
  SUM(IF(p.estado <> 'devuelta' AND p.id <> 8, p.precio, 0)),
  SUM(IF(p.estado <> 'devuelta' AND p.id <> 8, p.precio, 0)) - (SELECT SUM(valor) FROM pagos WHERE orden_id = 5 AND anulado_en IS NULL)
FROM prendas p
WHERE p.orden_id = 5;
