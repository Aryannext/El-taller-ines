"""Comprueba que las migraciones de Laravel produzcan exactamente el esquema del modelo de datos (RNF-31).

1. Crea dos bases en un MySQL 8.4: «referencia», con esquema.sql, y «migrada», con las migraciones del sistema.
2. Compara tablas, columnas, comentarios, índices, llaves foráneas y restricciones CHECK.
3. Ignora las tablas propias de Laravel (migraciones y cola), que no están en el modelo de datos.

Uso:
    python scripts/comparar_migraciones.py                      # MySQL temporal, como verificar_modelo.py
    python scripts/comparar_migraciones.py --servidor 127.0.0.1:3306 --usuario root --contrasena clave

El segundo modo es el de GitHub Actions, que ya tiene un servicio de MySQL 8.4. Sale con código 1 si hay diferencias.
"""

from __future__ import annotations

import argparse
import glob
import os
import re
import shutil
import subprocess
import sys
from pathlib import Path

import pymysql

sys.path.insert(0, str(Path(__file__).resolve().parent))
from verificar_modelo import ESQUEMA, ServidorTemporal, buscar_mysqld, sentencias  # noqa: E402

RAIZ = Path(__file__).resolve().parent.parent
SISTEMA = RAIZ / "sistema"
TABLAS_DE_LARAVEL = {"migrations", "jobs", "job_batches", "failed_jobs"}


class ServidorExistente:
    def __init__(self, servidor: str, usuario: str, contrasena: str):
        self.host, _, puerto = servidor.partition(":")
        self.puerto = int(puerto or 3306)
        self.usuario, self.contrasena = usuario, contrasena

    def __enter__(self) -> "ServidorExistente":
        return self

    def __exit__(self, *_) -> None:
        pass

    def conectar(self, base: str | None = None) -> pymysql.connections.Connection:
        return pymysql.connect(host=self.host, port=self.puerto, user=self.usuario, password=self.contrasena,
                               database=base, charset="utf8mb4", autocommit=True)


def buscar_php() -> str:
    candidatos = [os.environ.get("PHP"), shutil.which("php")]
    candidatos += sorted(glob.glob("C:/wamp64/bin/php/php8.4*/php.exe"), reverse=True)
    for candidato in candidatos:
        if candidato and Path(candidato).exists():
            return candidato
    raise SystemExit("No se encontró PHP 8.4. Indícalo con la variable PHP.")


def crear_referencia(servidor) -> None:
    with servidor.conectar() as conexion, conexion.cursor() as cursor:
        cursor.execute("DROP DATABASE IF EXISTS referencia")
        cursor.execute("CREATE DATABASE referencia CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci")
    with servidor.conectar("referencia") as conexion, conexion.cursor() as cursor:
        for sql in sentencias(ESQUEMA.read_text(encoding="utf-8")):
            cursor.execute(sql)


def crear_migrada(servidor, usuario: str, contrasena: str) -> None:
    with servidor.conectar() as conexion, conexion.cursor() as cursor:
        cursor.execute("DROP DATABASE IF EXISTS migrada")
        cursor.execute("CREATE DATABASE migrada CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci")
    entorno = {**os.environ, "DB_CONNECTION": "mysql", "DB_HOST": getattr(servidor, "host", "127.0.0.1"),
               "DB_PORT": str(servidor.puerto), "DB_DATABASE": "migrada", "DB_USERNAME": usuario,
               "DB_PASSWORD": contrasena}
    resultado = subprocess.run([buscar_php(), "artisan", "migrate:fresh", "--force", "--no-interaction"],
                               cwd=SISTEMA, env=entorno, capture_output=True, text=True, encoding="utf-8")
    if resultado.returncode != 0:
        print(resultado.stdout, resultado.stderr)
        raise SystemExit("Las migraciones fallaron")


def estructura(conexion, base: str) -> dict[str, dict]:
    tablas = {}
    with conexion.cursor() as c:
        c.execute("SELECT TABLE_NAME, TABLE_COMMENT, ENGINE, TABLE_COLLATION FROM information_schema.TABLES "
                  "WHERE TABLE_SCHEMA=%s", (base,))
        for nombre, comentario, motor, intercalacion in c.fetchall():
            if nombre in TABLAS_DE_LARAVEL:
                continue
            tabla = {"tabla": (comentario, motor, intercalacion)}
            c.execute("""SELECT ORDINAL_POSITION, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA,
                                COLLATION_NAME, COLUMN_COMMENT
                         FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s
                         ORDER BY ORDINAL_POSITION""", (base, nombre))
            tabla["columnas"] = c.fetchall()
            c.execute("""SELECT INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)
                         FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s
                         GROUP BY INDEX_NAME, NON_UNIQUE ORDER BY INDEX_NAME""", (base, nombre))
            tabla["indices"] = c.fetchall()
            c.execute("""SELECT k.CONSTRAINT_NAME, GROUP_CONCAT(k.COLUMN_NAME ORDER BY k.ORDINAL_POSITION),
                                k.REFERENCED_TABLE_NAME, GROUP_CONCAT(k.REFERENCED_COLUMN_NAME ORDER BY k.ORDINAL_POSITION),
                                r.UPDATE_RULE, r.DELETE_RULE
                         FROM information_schema.KEY_COLUMN_USAGE k
                         JOIN information_schema.REFERENTIAL_CONSTRAINTS r
                           ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                         WHERE k.TABLE_SCHEMA=%s AND k.TABLE_NAME=%s AND k.REFERENCED_TABLE_NAME IS NOT NULL
                         GROUP BY k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, r.UPDATE_RULE, r.DELETE_RULE
                         ORDER BY k.CONSTRAINT_NAME""", (base, nombre))
            tabla["foraneas"] = c.fetchall()
            c.execute("""SELECT tc.CONSTRAINT_NAME, cc.CHECK_CLAUSE
                         FROM information_schema.TABLE_CONSTRAINTS tc
                         JOIN information_schema.CHECK_CONSTRAINTS cc
                           ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
                         WHERE tc.TABLE_SCHEMA=%s AND tc.TABLE_NAME=%s AND tc.CONSTRAINT_TYPE='CHECK'
                         ORDER BY tc.CONSTRAINT_NAME""", (base, nombre))
            tabla["chequeos"] = c.fetchall()
            tablas[nombre] = tabla
    return tablas


def comparar(referencia: dict, migrada: dict) -> list[str]:
    diferencias = []
    for nombre in sorted(set(referencia) - set(migrada)):
        diferencias.append(f"Falta la tabla {nombre} en las migraciones")
    for nombre in sorted(set(migrada) - set(referencia)):
        diferencias.append(f"Las migraciones crean {nombre}, que no está en esquema.sql")
    for nombre in sorted(set(referencia) & set(migrada)):
        for parte in ("tabla", "columnas", "indices", "foraneas", "chequeos"):
            esperado, obtenido = referencia[nombre][parte], migrada[nombre][parte]
            if esperado == obtenido:
                continue
            if parte == "tabla":
                diferencias.append(f"{nombre}: tabla {obtenido}, se esperaba {esperado}")
                continue
            for fila in sorted(set(esperado) - set(obtenido), key=str):
                diferencias.append(f"{nombre}.{parte}: falta o difiere {fila}")
            for fila in sorted(set(obtenido) - set(esperado), key=str):
                diferencias.append(f"{nombre}.{parte}: sobra o difiere {fila}")
    return diferencias


def main() -> int:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    parser = argparse.ArgumentParser()
    parser.add_argument("--servidor", help="host:puerto de un MySQL 8.4 existente")
    parser.add_argument("--usuario", default="root")
    parser.add_argument("--contrasena", default="")
    parser.add_argument("--mysqld", help="ruta de mysqld 8.4 para el servidor temporal")
    opciones = parser.parse_args()

    servidor = (ServidorExistente(opciones.servidor, opciones.usuario, opciones.contrasena) if opciones.servidor
                else ServidorTemporal(buscar_mysqld(opciones.mysqld)))
    with servidor:
        usuario, contrasena = (opciones.usuario, opciones.contrasena) if opciones.servidor else ("root", "")
        crear_referencia(servidor)
        crear_migrada(servidor, usuario, contrasena)
        with servidor.conectar() as conexion:
            diferencias = comparar(estructura(conexion, "referencia"), estructura(conexion, "migrada"))
            tablas = len(estructura(conexion, "referencia"))
        with servidor.conectar() as conexion, conexion.cursor() as cursor:
            cursor.execute("DROP DATABASE referencia")
            cursor.execute("DROP DATABASE migrada")

    if diferencias:
        print(f"Las migraciones no producen esquema.sql ({len(diferencias)} diferencias):")
        for diferencia in diferencias:
            print(f"- {diferencia}")
        return 1
    print(f"Las migraciones producen exactamente esquema.sql: {tablas} tablas con sus columnas, índices, llaves y restricciones.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
