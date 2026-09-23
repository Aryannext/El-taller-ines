"""Levanta el sistema con un MySQL temporal y datos mínimos, para revisar pantallas en el navegador sin WAMP.

1. Inicia un MySQL 8.4 temporal (como verificar_modelo.py) y aplica las migraciones en la base «taller».
2. Crea un negocio, la usuaria «taller» con la contraseña «clave-de-prueba» y clientes de los mockups.
3. Sirve el sistema en http://127.0.0.1:8000 hasta que se detenga.

Solo para desarrollo: la contraseña es pública y los datos se borran al terminar.
Si el proceso se cierra sin limpiar (por ejemplo, al cerrarlo desde el panel del navegador),
la siguiente ejecución detiene el MySQL que quedó abierto y borra su carpeta.

Uso: python scripts/servidor_de_prueba.py
"""

from __future__ import annotations

import json
import os
import shutil
import signal
import subprocess
import sys
import tempfile
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
SISTEMA = RAIZ / "sistema"
sys.path.insert(0, str(RAIZ / "scripts"))

from comparar_migraciones import buscar_php  # noqa: E402
from verificar_modelo import ServidorTemporal, buscar_mysqld  # noqa: E402

REGISTRO = Path(tempfile.gettempdir()) / "puntada-servidor-de-prueba.json"
CONTRASENA = "clave-de-prueba"
CLIENTES = [
    ("Marta Rincón", "3104567890"),
    ("María Gómez", "3012223344"),
    ("Mariana López", "3174008821"),
    ("Luis Pardo", "3001112233"),
]


def limpiar_ejecucion_anterior() -> None:
    if not REGISTRO.exists():
        return
    anterior = json.loads(REGISTRO.read_text(encoding="utf-8"))
    # Windows reutiliza los números de proceso: solo se detiene si ese número sigue siendo mysqld o php
    for pid, programa in ((anterior.get("mysqld"), "mysqld.exe"), (anterior.get("php"), "php.exe")):
        if pid:
            subprocess.run(["taskkill", "/F", "/T", "/FI", f"PID eq {pid}", "/FI", f"IMAGENAME eq {programa}"],
                           capture_output=True)
    shutil.rmtree(anterior.get("datos", ""), ignore_errors=True)
    REGISTRO.unlink(missing_ok=True)


def cargar_datos(servidor: ServidorTemporal, php: str) -> None:
    hash_contrasena = subprocess.run([php, "-r", f"echo password_hash('{CONTRASENA}', PASSWORD_BCRYPT);"],
                                     capture_output=True, text=True, check=True).stdout
    with servidor.conectar("taller") as conexion, conexion.cursor() as cursor:
        cursor.execute("INSERT INTO negocios (nombre) VALUES ('Taller de costura')")
        negocio = cursor.lastrowid
        cursor.execute("INSERT INTO usuarios (negocio_id, nombre, usuario, contrasena) VALUES (%s, 'Inés', 'taller', %s)",
                       (negocio, hash_contrasena))
        cursor.executemany("INSERT INTO clientes (negocio_id, nombre, celular) VALUES (%s, %s, %s)",
                           [(negocio, nombre, celular) for nombre, celular in CLIENTES])
        # La misma lista inicial de NegocioInicialSeeder (RF-16)
        cursor.executemany("INSERT INTO tipos_prenda (negocio_id, nombre) VALUES (%s, %s)",
                           [(negocio, nombre) for nombre in ("Pantalón", "Camisa", "Blusa", "Vestido", "Falda", "Chaqueta")])


def main() -> int:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    # Convierte el cierre del proceso en una salida ordenada, para que el MySQL temporal se detenga
    for senal in (signal.SIGTERM, getattr(signal, "SIGBREAK", None)):
        if senal is not None:
            signal.signal(senal, lambda *_: (_ for _ in ()).throw(KeyboardInterrupt))

    limpiar_ejecucion_anterior()
    php = buscar_php()
    with ServidorTemporal(buscar_mysqld(None)) as servidor:
        with servidor.conectar() as conexion, conexion.cursor() as cursor:
            cursor.execute("CREATE DATABASE taller CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci")
        entorno = {**os.environ, "DB_CONNECTION": "mysql", "DB_HOST": "127.0.0.1", "DB_PORT": str(servidor.puerto),
                   "DB_DATABASE": "taller", "DB_USERNAME": "root", "DB_PASSWORD": "", "SESSION_DRIVER": "file"}
        subprocess.run([php, "artisan", "migrate", "--force", "--no-interaction"], cwd=SISTEMA, env=entorno,
                       check=True, capture_output=True)
        cargar_datos(servidor, php)

        web = subprocess.Popen([php, "artisan", "serve", "--host=127.0.0.1", "--port=8000"], cwd=SISTEMA, env=entorno)
        REGISTRO.write_text(json.dumps({"mysqld": servidor.proceso.pid, "php": web.pid, "datos": servidor.datos}),
                            encoding="utf-8")
        print(f"Sistema en http://127.0.0.1:8000 · usuario «taller» · contraseña «{CONTRASENA}»", flush=True)
        try:
            web.wait()
        except KeyboardInterrupt:
            web.terminate()
        finally:
            REGISTRO.unlink(missing_ok=True)
    return 0


if __name__ == "__main__":
    sys.exit(main())
