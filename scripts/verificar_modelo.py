"""Verifica el modelo de datos en un MySQL 8.4 temporal y genera su documentación.

1. Levanta un servidor MySQL temporal en una carpeta aparte y en un puerto libre (no toca el MySQL instalado).
2. Crea el esquema, carga los datos de ejemplo de los mockups y ejecuta las consultas de referencia,
   comparando cada resultado con el que esperan los mockups y las historias.
3. Comprueba que la base de datos rechace lo que las reglas prohíben y acepte lo que permiten.
4. Revisa que cada tabla y cada columna tenga su comentario y que las 44 reglas de negocio aparezcan
   en la tabla «Dónde se garantiza cada regla» del README.
5. Solo si todo cuadra, genera el diccionario de datos, el diagrama entidad-relación y el resumen de la verificación.

Uso:
    python scripts/verificar_modelo.py                 # busca mysqld en WAMP o en el PATH
    python scripts/verificar_modelo.py --mysqld RUTA   # indica el ejecutable de MySQL 8.4

Requiere pymysql. Sale con código 1 si algo no cuadra.
"""

from __future__ import annotations

import argparse
import glob
import os
import re
import shutil
import socket
import subprocess
import sys
import tempfile
import time
from decimal import Decimal
from pathlib import Path

import pymysql

RAIZ = Path(__file__).resolve().parent.parent
CARPETA = RAIZ / "docs" / "03-diseno" / "modelo-de-datos"
ESQUEMA = CARPETA / "esquema.sql"
DATOS = CARPETA / "datos-de-ejemplo.sql"
CONSULTAS = CARPETA / "consultas-de-referencia.sql"
DICCIONARIO = CARPETA / "diccionario-de-datos.md"
LEEME = CARPETA / "README.md"
REGLAS = RAIZ / "docs" / "02-requisitos" / "reglas-de-negocio.md"
BASE = "el_taller_ines"

# Cada prueba se ejecuta en una transacción que se revierte. «fallar»: la última sentencia debe ser rechazada.
PRUEBAS = [
    ("RN-02", "Cliente con el nombre vacío", "fallar",
     ["INSERT INTO clientes (negocio_id, nombre, celular) VALUES (1, '   ', '3104567890')"]),
    ("RN-03", "Celular fijo 6014567890", "fallar",
     ["INSERT INTO clientes (negocio_id, nombre, celular) VALUES (1, 'Luis Pardo', '6014567890')"]),
    ("RN-03", "Celular de 9 dígitos", "fallar",
     ["INSERT INTO clientes (negocio_id, nombre, celular) VALUES (1, 'Luis Pardo', '310456789')"]),
    ("RN-04", "Dos clientes con el mismo celular", "pasar",
     ["INSERT INTO clientes (negocio_id, nombre, celular) VALUES (1, 'Laura Rincón', '3104567890')"]),
    ("RN-01", "Orden de un negocio con un cliente de otro", "fallar",
     ["INSERT INTO ordenes (negocio_id, cliente_id, numero, fecha_entrega_acordada, recibida_en) "
      "VALUES (1, 8, 47, '2026-09-20', '2026-09-16 10:00:00')"]),
    ("RN-07", "Entrega antes de la recepción", "fallar",
     ["INSERT INTO ordenes (negocio_id, cliente_id, numero, fecha_entrega_acordada, recibida_en) "
      "VALUES (1, 1, 47, '2026-09-15', '2026-09-16 10:00:00')"]),
    ("RN-07", "Entrega el mismo día de la recepción", "pasar",
     ["INSERT INTO ordenes (negocio_id, cliente_id, numero, fecha_entrega_acordada, recibida_en) "
      "VALUES (1, 1, 47, '2026-09-16', '2026-09-16 10:00:00')"]),
    ("RN-08", "Número de orden repetido en el mismo negocio", "fallar",
     ["INSERT INTO ordenes (negocio_id, cliente_id, numero, fecha_entrega_acordada, recibida_en) "
      "VALUES (1, 1, 42, '2026-09-20', '2026-09-16 10:00:00')"]),
    ("RN-08", "El mismo número en otro negocio", "pasar",
     ["INSERT INTO ordenes (negocio_id, cliente_id, numero, fecha_entrega_acordada, recibida_en) "
      "VALUES (2, 8, 42, '2026-09-20', '2026-09-16 10:00:00')"]),
    ("RN-10", "Prenda sin descripción del arreglo", "fallar",
     ["INSERT INTO prendas (orden_id, tipo_prenda_id, descripcion_arreglo, precio) VALUES (5, 2, '', 8000)"]),
    ("RN-11", "Prenda con precio $0", "fallar",
     ["INSERT INTO prendas (orden_id, tipo_prenda_id, descripcion_arreglo, precio) VALUES (5, 2, 'Entallar', 0)"]),
    ("RN-12", "Estado de prenda que no existe", "fallar",
     ["UPDATE prendas SET estado = 'lista' WHERE id = 8"]),
    ("RN-17", "Cuarta foto de una prenda", "fallar",
     ["INSERT INTO fotos (prenda_id, posicion, ruta, ancho_px, alto_px, bytes) VALUES (6, 4, 'fotos/prueba-4.jpg', 1200, 1600, 200000)"]),
    ("RN-17", "Dos fotos en el mismo lugar", "fallar",
     ["INSERT INTO fotos (prenda_id, posicion, ruta, ancho_px, alto_px, bytes) VALUES (6, 1, 'fotos/prueba-1.jpg', 1200, 1600, 200000)"]),
    ("RN-17", "Tercera foto de una prenda", "pasar",
     ["INSERT INTO fotos (prenda_id, posicion, ruta, ancho_px, alto_px, bytes) VALUES (6, 3, 'fotos/prueba-3.jpg', 1200, 1600, 200000)"]),
    ("RN-17", "Eliminar una prenda elimina sus fotos", "pasar",
     ["DELETE FROM prendas WHERE id = 7"]),
    ("RNF-03", "Foto de más de 1.600 px en su lado mayor", "fallar",
     ["INSERT INTO fotos (prenda_id, posicion, ruta, ancho_px, alto_px, bytes) VALUES (8, 1, 'fotos/prueba-grande.jpg', 4000, 3000, 200000)"]),
    ("RN-23", "Prenda Entregada sin fecha de entrega", "fallar",
     ["UPDATE prendas SET estado = 'entregada' WHERE id = 6"]),
    ("RN-25", "Pago de $0", "fallar",
     ["INSERT INTO pagos (orden_id, metodo_pago_id, valor) VALUES (5, 1, 0)"]),
    ("RN-31", "Anular un pago sin motivo", "fallar",
     ["UPDATE pagos SET anulado_en = '2026-09-16 11:00:00' WHERE id = 3"]),
    ("RN-31", "Anular un pago con motivo", "pasar",
     ["UPDATE pagos SET anulado_en = '2026-09-16 11:00:00', motivo_anulacion = 'Valor mal digitado' WHERE id = 3"]),
    ("RN-31", "Borrar una orden que tiene prendas y pagos", "fallar",
     ["DELETE FROM ordenes WHERE id = 5"]),
    ("RN-35", "Plazo sin reclamar de 400 días", "fallar",
     ["UPDATE negocios SET dias_sin_reclamar = 400 WHERE id = 1"]),
    ("RN-35", "Plazo sin reclamar de 0 días", "fallar",
     ["UPDATE negocios SET dias_sin_reclamar = 0 WHERE id = 1"]),
    ("RN-38", "Segundo aviso para la misma vez que la orden quedó lista", "fallar",
     ["INSERT INTO avisos (orden_id, ciclo_lista_en) VALUES (8, '2026-09-16 09:40:00')"]),
    ("RN-41", "Aviso marcado como enviado sin canal ni mensaje", "fallar",
     ["UPDATE avisos SET estado = 'enviado', resuelto_en = '2026-09-16 10:00:00' WHERE id = 5"]),
    ("RN-43", "Tipo «overol» repetido sin distinguir mayúsculas", "fallar",
     ["INSERT INTO tipos_prenda (negocio_id, nombre) VALUES (1, 'overol')"]),
    ("RN-43", "Tipo «PANTALON» repetido sin distinguir tildes", "fallar",
     ["INSERT INTO tipos_prenda (negocio_id, nombre) VALUES (1, 'PANTALON')"]),
    ("RN-43", "Tipo nuevo «Enterizo» escrito con «Otro»", "pasar",
     ["INSERT INTO tipos_prenda (negocio_id, nombre) VALUES (1, 'Enterizo')"]),
    ("RN-44", "Prenda Devuelta sin fecha de devolución", "fallar",
     ["UPDATE prendas SET estado = 'devuelta' WHERE id = 8"]),
    ("RN-44", "Prenda Devuelta con su fecha", "pasar",
     ["UPDATE prendas SET estado = 'devuelta', devuelta_en = '2026-09-16 10:00:00' WHERE id = 8"]),
    ("RNF-14", "El mismo formulario de pago enviado dos veces", "fallar",
     ["INSERT INTO pagos (orden_id, metodo_pago_id, valor, token_formulario) VALUES (5, 1, 1000, '0b8f2a4e-7c1d-4e5a-9f3b-2d6c8e1a4b7f')",
      "INSERT INTO pagos (orden_id, metodo_pago_id, valor, token_formulario) VALUES (5, 1, 1000, '0b8f2a4e-7c1d-4e5a-9f3b-2d6c8e1a4b7f')"]),
]

ETIQUETAS_RELACION = {
    ("negocios", "usuarios"): "tiene",
    ("negocios", "clientes"): "atiende",
    ("negocios", "tipos_prenda"): "define",
    ("negocios", "metodos_pago"): "acepta",
    ("negocios", "ordenes"): "numera",
    ("clientes", "ordenes"): "deja",
    ("ordenes", "prendas"): "agrupa",
    ("tipos_prenda", "prendas"): "clasifica",
    ("prendas", "fotos"): "tiene",
    ("ordenes", "pagos"): "recibe",
    ("metodos_pago", "pagos"): "clasifica",
    ("ordenes", "avisos"): "genera",
}


# --- Servidor temporal --------------------------------------------------------------------------


def buscar_mysqld(ruta: str | None) -> str:
    candidatos = [ruta, os.environ.get("MYSQLD")]
    candidatos += sorted(glob.glob("C:/wamp64/bin/mysql/mysql8.4*/bin/mysqld.exe"), reverse=True)
    candidatos.append(shutil.which("mysqld"))
    for candidato in candidatos:
        if candidato and Path(candidato).exists():
            return candidato
    raise SystemExit("No se encontró mysqld 8.4. Indícalo con --mysqld o con la variable MYSQLD.")


def puerto_libre() -> int:
    with socket.socket() as s:
        s.bind(("127.0.0.1", 0))
        return s.getsockname()[1]


class ServidorTemporal:
    def __init__(self, mysqld: str):
        self.mysqld = mysqld

    def __enter__(self) -> "ServidorTemporal":
        self.datos = tempfile.mkdtemp(prefix="modelo-mysql-")
        subprocess.run([self.mysqld, "--no-defaults", "--initialize-insecure", f"--datadir={self.datos}"],
                       check=True, capture_output=True)
        self.puerto = puerto_libre()
        self.proceso = subprocess.Popen(
            [self.mysqld, "--no-defaults", f"--datadir={self.datos}", f"--port={self.puerto}",
             "--bind-address=127.0.0.1", "--mysqlx=OFF", "--default-time-zone=-05:00"],
            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        for _ in range(120):
            try:
                self.conectar().close()
                return self
            except pymysql.err.OperationalError:
                time.sleep(0.5)
        raise SystemExit("El servidor MySQL temporal no respondió")

    def conectar(self, base: str | None = None, autocommit: bool = True) -> pymysql.connections.Connection:
        return pymysql.connect(host="127.0.0.1", port=self.puerto, user="root", password="", database=base,
                               charset="utf8mb4", autocommit=autocommit)

    def version(self) -> str:
        with self.conectar() as conexion, conexion.cursor() as cursor:
            cursor.execute("SELECT VERSION()")
            return cursor.fetchone()[0]

    def __exit__(self, *_) -> None:
        try:
            with self.conectar() as conexion, conexion.cursor() as cursor:
                cursor.execute("SHUTDOWN")
            self.proceso.wait(timeout=60)
        except Exception:
            self.proceso.kill()
        shutil.rmtree(self.datos, ignore_errors=True)


# --- Carga y consultas --------------------------------------------------------------------------


def sentencias(texto: str) -> list[str]:
    sin_comentarios = "\n".join(l for l in texto.splitlines() if not l.strip().startswith("--"))
    return [s.strip() for s in re.split(r";\s*(?:\n|$)", sin_comentarios) if s.strip()]


def bloques_de_consultas(texto: str) -> list[tuple[dict[str, str], str]]:
    bloques, meta, lineas = [], {}, []
    for linea in texto.splitlines():
        if m := re.match(r"--\s*(consulta|reglas|muestra|espera):\s*(.*)", linea):
            meta[m.group(1)] = m.group(2).strip()
            continue
        if linea.strip().startswith("--"):
            continue
        lineas.append(linea)
        if linea.rstrip().endswith(";"):
            sentencia = "\n".join(lineas).strip().rstrip(";").strip()
            if sentencia:
                bloques.append((meta, sentencia))
            meta, lineas = {}, []
    return bloques


def formato(valor) -> str:
    if valor is None:
        return "NULL"
    if isinstance(valor, Decimal):
        return str(int(valor)) if valor == valor.to_integral_value() else str(valor)
    return str(valor)


def ejecutar_consultas(conexion) -> list[dict]:
    resultados = []
    with conexion.cursor() as cursor:
        for meta, sql in bloques_de_consultas(CONSULTAS.read_text(encoding="utf-8")):
            cursor.execute(sql)
            if "consulta" not in meta:
                continue
            obtenido = " / ".join("|".join(formato(v) for v in fila) for fila in cursor.fetchall())
            resultados.append({**meta, "obtenido": obtenido, "cuadra": obtenido == meta.get("espera")})
    return resultados


def motivo_del_error(error: pymysql.err.MySQLError) -> str:
    mensaje = str(error.args[1]) if len(error.args) > 1 else str(error)
    for patron in (r"CONSTRAINT `(\w+)`", r"constraint '(\w+)'", r"for key '(?:\w+\.)?(\w+)'", r"column '(\w+)'"):
        if m := re.search(patron, mensaje):
            return m.group(1)
    return f"error {error.args[0]}"


def ejecutar_pruebas(servidor: ServidorTemporal) -> list[dict]:
    resultados = []
    with servidor.conectar(BASE, autocommit=False) as conexion:
        for regla, caso, debe, lista in PRUEBAS:
            error, indice = None, -1
            with conexion.cursor() as cursor:
                for indice, sql in enumerate(lista):
                    try:
                        cursor.execute(sql)
                    except pymysql.err.MySQLError as e:
                        error = e
                        break
            conexion.rollback()
            if debe == "fallar":
                cuadra = error is not None and indice == len(lista) - 1
                resultado = f"Rechazada por `{motivo_del_error(error)}`" if error else "Aceptada"
            else:
                cuadra = error is None
                resultado = "Aceptada" if error is None else f"Rechazada por `{motivo_del_error(error)}`"
            resultados.append({"regla": regla, "caso": caso, "debe": debe, "resultado": resultado, "cuadra": cuadra})
    return resultados


# --- Documentación -------------------------------------------------------------------------------


def filas(cursor, sql: str, *parametros) -> list[tuple]:
    cursor.execute(sql, parametros)
    return list(cursor.fetchall())


def leer_estructura(conexion) -> list[dict]:
    orden = re.findall(r"CREATE TABLE (\w+)", ESQUEMA.read_text(encoding="utf-8"))
    tablas = []
    with conexion.cursor() as c:
        for nombre in orden:
            comentario = filas(c, "SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s", BASE, nombre)[0][0]
            columnas = filas(c, """SELECT COLUMN_NAME, COLUMN_TYPE, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, COLUMN_COMMENT
                                   FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s ORDER BY ORDINAL_POSITION""", BASE, nombre)
            indices = filas(c, """SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ')
                                  FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s
                                  GROUP BY INDEX_NAME, NON_UNIQUE ORDER BY INDEX_NAME = 'PRIMARY' DESC, NON_UNIQUE, INDEX_NAME""", BASE, nombre)
            foraneas = filas(c, """SELECT k.CONSTRAINT_NAME, GROUP_CONCAT(k.COLUMN_NAME ORDER BY k.ORDINAL_POSITION SEPARATOR ', '),
                                          k.REFERENCED_TABLE_NAME, GROUP_CONCAT(k.REFERENCED_COLUMN_NAME ORDER BY k.ORDINAL_POSITION SEPARATOR ', '),
                                          r.DELETE_RULE
                                   FROM information_schema.KEY_COLUMN_USAGE k
                                   JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                                     ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                                   WHERE k.TABLE_SCHEMA=%s AND k.TABLE_NAME=%s AND k.REFERENCED_TABLE_NAME IS NOT NULL
                                   GROUP BY k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, r.DELETE_RULE ORDER BY k.CONSTRAINT_NAME""", BASE, nombre)
            chequeos = filas(c, """SELECT tc.CONSTRAINT_NAME, cc.CHECK_CLAUSE
                                   FROM information_schema.TABLE_CONSTRAINTS tc
                                   JOIN information_schema.CHECK_CONSTRAINTS cc
                                     ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
                                   WHERE tc.TABLE_SCHEMA=%s AND tc.TABLE_NAME=%s AND tc.CONSTRAINT_TYPE='CHECK'
                                   ORDER BY tc.CONSTRAINT_NAME""", BASE, nombre)
            tablas.append({"nombre": nombre, "comentario": comentario, "columnas": columnas,
                           "indices": indices, "foraneas": foraneas, "chequeos": chequeos})
    return tablas


def limpiar_check(clausula: str) -> str:
    clausula = clausula.replace("`", "").replace("_utf8mb4", "").replace("\\'", "'")
    return re.sub(r"\s+", " ", clausula).strip("() ")


def celda(texto: str) -> str:
    return str(texto).replace("|", "\\|")


def generar_diccionario(tablas: list[dict], version: str) -> str:
    partes = [
        "# Diccionario de datos",
        "",
        f"**Generado** por `scripts/verificar_modelo.py` desde la base de datos real creada con [esquema.sql](esquema.sql) en MySQL {version}. No se edita a mano: se cambia el esquema y se vuelve a generar.",
        "",
        f"**{len(tablas)} tablas** · **{sum(len(t['columnas']) for t in tablas)} columnas** · "
        f"**{sum(len(t['foraneas']) for t in tablas)} llaves foráneas** · **{sum(len(t['chequeos']) for t in tablas)} restricciones CHECK**. "
        "Juego de caracteres `utf8mb4` con collation `utf8mb4_0900_ai_ci`.",
        "",
        "Claves: **PK** llave primaria · **FK** llave foránea · **UK** parte de una clave única.",
        "",
    ]
    for t in tablas:
        primarias = {c for n, _, cols in t["indices"] if n == "PRIMARY" for c in cols.split(", ")}
        unicas = {c for n, no_unico, cols in t["indices"] if n != "PRIMARY" and not no_unico for c in cols.split(", ")}
        foraneas = {c for _, cols, *_ in t["foraneas"] for c in cols.split(", ")}
        partes += [f"## `{t['nombre']}`", "", t["comentario"], "",
                   "| Columna | Tipo | Nulo | Por defecto | Clave | Descripción |", "| --- | --- | --- | --- | --- | --- |"]
        for nombre, tipo, _, nulo, defecto, extra, comentario in t["columnas"]:
            claves = [k for k, conjunto in (("PK", primarias), ("FK", foraneas), ("UK", unicas)) if nombre in conjunto]
            extra = (extra or "").replace("DEFAULT_GENERATED", "").strip()
            por_defecto = " ".join(x for x in (str(defecto) if defecto is not None else "", extra) if x) or "—"
            partes.append(f"| `{nombre}` | `{celda(tipo)}` | {'Sí' if nulo == 'YES' else 'No'} | {celda(por_defecto)} | "
                          f"{', '.join(claves) or '—'} | {celda(comentario)} |")
        partes += ["", "**Índices**", ""]
        partes += [f"- `{n}`: {'llave primaria' if n == 'PRIMARY' else 'único' if not no_unico else 'índice'} sobre ({cols})"
                   for n, no_unico, cols in t["indices"]]
        if t["foraneas"]:
            partes += ["", "**Llaves foráneas**", ""]
            # En InnoDB, NO ACTION y RESTRICT significan lo mismo: no se puede eliminar la fila referenciada.
            al_eliminar = {"NO ACTION": "no se permite mientras tenga filas relacionadas", "RESTRICT": "no se permite mientras tenga filas relacionadas",
                           "CASCADE": "se eliminan también estas filas", "SET NULL": "la columna queda vacía"}
            partes += [f"- `{n}`: ({cols}) → `{ref}` ({ref_cols}) · al eliminar en `{ref}`: {al_eliminar.get(regla, regla)}"
                       for n, cols, ref, ref_cols, regla in t["foraneas"]]
        if t["chequeos"]:
            partes += ["", "**Restricciones CHECK**", ""]
            partes += [f"- `{n}`: `{celda(limpiar_check(clausula))}`" for n, clausula in t["chequeos"]]
        partes.append("")
    return "\n".join(partes)


def generar_diagrama(tablas: list[dict]) -> str:
    lineas = ["```mermaid", "erDiagram"]
    for t in tablas:
        nulos = {nombre: nulo == "YES" for nombre, _, _, nulo, *_ in t["columnas"]}
        for _, cols, ref, _, _ in t["foraneas"]:
            if ref == "clientes" and "negocio_id" in cols:
                pass  # la llave compuesta hacia clientes se dibuja como la relación cliente–orden
            opcional = any(nulos[c] for c in cols.split(", "))
            etiqueta = ETIQUETAS_RELACION.get((ref, t["nombre"]), "se relaciona con")
            lineas.append(f"    {ref} {'|o' if opcional else '||'}--o{{ {t['nombre']} : \"{etiqueta}\"")
    for t in tablas:
        primarias = {c for n, _, cols in t["indices"] if n == "PRIMARY" for c in cols.split(", ")}
        unicas = {c for n, no_unico, cols in t["indices"] if n != "PRIMARY" and not no_unico for c in cols.split(", ")}
        foraneas = {c for _, cols, *_ in t["foraneas"] for c in cols.split(", ")}
        lineas.append(f"    {t['nombre']} {{")
        for nombre, _, tipo_base, *_ in t["columnas"]:
            claves = [k for k, conjunto in (("PK", primarias), ("FK", foraneas), ("UK", unicas)) if nombre in conjunto]
            lineas.append(f"        {tipo_base} {nombre}{' ' + ', '.join(claves) if claves else ''}")
        lineas.append("    }")
    lineas.append("```")
    return "\n".join(lineas)


def generar_verificacion(consultas: list[dict], pruebas: list[dict], version: str) -> str:
    partes = [
        f"Resultado de `python scripts/verificar_modelo.py` sobre MySQL {version}: "
        f"**{sum(c['cuadra'] for c in consultas)} de {len(consultas)} consultas** dan las cifras de los mockups y "
        f"**{sum(p['cuadra'] for p in pruebas)} de {len(pruebas)} pruebas** de restricciones se comportan como exigen las reglas.",
        "",
        "### Consultas de referencia",
        "",
        "| Consulta | Reglas | Pantallas | Resultado |",
        "| --- | --- | --- | --- |",
    ]
    partes += [f"| {celda(c['consulta'])} | {c.get('reglas', '—')} | {c.get('muestra', '—')} | `{celda(c['obtenido'])}` |" for c in consultas]
    partes += ["", "### La base de datos rechaza lo que las reglas prohíben", "",
               "| Regla | Caso | Debe | Resultado |", "| --- | --- | --- | --- |"]
    partes += [f"| {p['regla']} | {p['caso']} | {'Rechazarse' if p['debe'] == 'fallar' else 'Aceptarse'} | {p['resultado']} |" for p in pruebas]
    return "\n".join(partes)


def reemplazar_bloque(texto: str, nombre: str, contenido: str) -> str:
    patron = re.compile(rf"<!-- {nombre}:inicio -->.*?<!-- {nombre}:fin -->", re.S)
    if not patron.search(texto):
        raise SystemExit(f"README.md no tiene los marcadores <!-- {nombre}:inicio --> y <!-- {nombre}:fin -->")
    return patron.sub(lambda _: f"<!-- {nombre}:inicio -->\n\n{contenido}\n\n<!-- {nombre}:fin -->", texto)


# --- Principal -----------------------------------------------------------------------------------


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--mysqld", help="ruta del ejecutable mysqld 8.4")
    opciones = parser.parse_args()

    errores: list[str] = []
    with ServidorTemporal(buscar_mysqld(opciones.mysqld)) as servidor:
        version = servidor.version()
        with servidor.conectar() as conexion, conexion.cursor() as cursor:
            cursor.execute(f"CREATE DATABASE {BASE} CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci")
        with servidor.conectar(BASE) as conexion:
            with conexion.cursor() as cursor:
                for archivo in (ESQUEMA, DATOS):
                    for sql in sentencias(archivo.read_text(encoding="utf-8")):
                        cursor.execute(sql)
            consultas = ejecutar_consultas(conexion)
            tablas = leer_estructura(conexion)
        pruebas = ejecutar_pruebas(servidor)

    print(f"MySQL {version} · {len(tablas)} tablas")
    for c in consultas:
        print(f"[{'ok' if c['cuadra'] else 'NO'}] {c['consulta']}")
        if not c["cuadra"]:
            errores.append(f"«{c['consulta']}»: espera {c.get('espera')} y obtuvo {c['obtenido']}")
    for p in pruebas:
        print(f"[{'ok' if p['cuadra'] else 'NO'}] {p['regla']} · {p['caso']}: {p['resultado']}")
        if not p["cuadra"]:
            errores.append(f"{p['regla']} · {p['caso']}: debía {'rechazarse' if p['debe'] == 'fallar' else 'aceptarse'} y fue {p['resultado']}")

    for t in tablas:
        if not t["comentario"]:
            errores.append(f"La tabla {t['nombre']} no tiene comentario")
        errores += [f"La columna {t['nombre']}.{c[0]} no tiene comentario" for c in t["columnas"] if not c[6]]

    leeme = LEEME.read_text(encoding="utf-8")
    reglas = set(re.findall(r"^### (RN-\d+)", REGLAS.read_text(encoding="utf-8"), re.M))
    en_tabla = set(re.findall(r"^\| \*\*(RN-\d+)\*\* \|", leeme, re.M))
    if faltan := sorted(reglas - en_tabla, key=lambda r: int(r[3:])):
        errores.append("Reglas sin fila en «Dónde se garantiza cada regla»: " + ", ".join(faltan))
    if sobran := sorted(en_tabla - reglas):
        errores.append("Filas de reglas que no existen: " + ", ".join(sobran))

    if errores:
        print("\nErrores:")
        for error in errores:
            print(f"- {error}")
        return 1

    DICCIONARIO.write_text(generar_diccionario(tablas, version), encoding="utf-8")
    leeme = reemplazar_bloque(leeme, "diagrama", generar_diagrama(tablas))
    leeme = reemplazar_bloque(leeme, "verificacion", generar_verificacion(consultas, pruebas, version))
    LEEME.write_text(leeme, encoding="utf-8")
    print(f"\nTodo cuadra: {len(consultas)} consultas, {len(pruebas)} pruebas, {len(reglas)} reglas ubicadas. Documentación generada.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
