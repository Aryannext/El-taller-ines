"""Corre en la máquina de desarrollo los mismos pasos de GitHub Actions (RNF-30).

Sirve antes de cada commit y reemplaza al flujo mientras GitHub no pueda ejecutarlo. Pasos:

1. Secretos en el historial de Git (RNF-24): patrones de claves y tokens, en lugar de gitleaks.
2. Vulnerabilidades de las dependencias con composer audit (RNF-23).
3. Estilo con Pint (RNF-29).
4. Análisis estático y reglas de capas con PHPStan, Larastan y PHPat (RNF-27, RNF-29).
5. Vistas sin impresión sin escapar (RNF-23).
6. En un MySQL 8.4 temporal: las migraciones producen esquema.sql (RNF-31) y las pruebas pasan (RNF-28).
7. Los verificadores de la documentación.

Uso:
    python scripts/calidad.py                  # todo
    python scripts/calidad.py --solo-codigo    # sin los verificadores de la documentación

Sale con código 1 si algún paso falla. Corre todos los pasos aunque uno falle, para ver todo lo pendiente.
"""

from __future__ import annotations

import argparse
import os
import re
import shutil
import subprocess
import sys
import time
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
SISTEMA = RAIZ / "sistema"
sys.path.insert(0, str(RAIZ / "scripts"))

from comparar_migraciones import buscar_php, comparar, crear_migrada, crear_referencia, estructura  # noqa: E402
from verificar_modelo import ServidorTemporal, buscar_mysqld  # noqa: E402

SECRETOS = re.compile(
    r"APP_KEY=base64:[A-Za-z0-9+/=]{20,}|ghp_[A-Za-z0-9]{20,}|github_pat_|EAA[A-Za-z0-9]{30,}"
    r"|BEGIN [A-Z ]*PRIVATE KEY|AKIA[0-9A-Z]{16}|xox[bap]-|sk-[A-Za-z0-9]{20,}"
    r"|(?:DB_PASSWORD|WHATSAPP_TOKEN|USUARIA_INICIAL_CONTRASENA)=\S+"
)
DOCUMENTACION = ["publicar_backlog.py", "verificar_arquitectura.py", "verificar_diagramas.py",
                 "verificar_especificacion.py", "generar_plan_de_pruebas.py"]


def ejecutar(comando: list[str], cwd: Path, entorno: dict | None = None) -> tuple[bool, str]:
    resultado = subprocess.run(comando, cwd=cwd, env=entorno or os.environ.copy(), capture_output=True,
                               text=True, encoding="utf-8", errors="replace")
    return resultado.returncode == 0, (resultado.stdout + resultado.stderr).strip()


def secretos_en_el_historial() -> tuple[bool, str]:
    ok, salida = ejecutar(["git", "log", "-p", "--all"], RAIZ)
    if not ok:
        return False, salida
    encontrados = [linea.strip() for linea in salida.splitlines()
                   if linea.startswith("+") and SECRETOS.search(linea)]
    return not encontrados, "\n".join(encontrados[:10]) or "Sin secretos en el historial"


def composer_audit() -> tuple[bool, str]:
    composer = shutil.which("composer")
    if not composer:
        return False, "No se encontró Composer en el PATH"
    return ejecutar([composer, "audit", "--abandoned=report", "--no-interaction"], SISTEMA)


def vistas_sin_escapar() -> tuple[bool, str]:
    encontrados = [f"{archivo.relative_to(SISTEMA)}:{numero}" for archivo in (SISTEMA / "resources" / "views").rglob("*.php")
                   for numero, linea in enumerate(archivo.read_text(encoding="utf-8").splitlines(), start=1)
                   if "{!!" in linea]
    return not encontrados, "\n".join(encontrados) or "Ninguna vista usa {!!"


def base_de_datos(mysqld: str | None) -> list[tuple[str, bool, str]]:
    """Esquema y pruebas comparten un mismo MySQL temporal para no levantarlo dos veces."""
    resultados = []
    with ServidorTemporal(buscar_mysqld(mysqld)) as servidor:
        try:
            crear_referencia(servidor)
            crear_migrada(servidor, "root", "")
            with servidor.conectar() as conexion:
                diferencias = comparar(estructura(conexion, "referencia"), estructura(conexion, "migrada"))
            resultados.append(("Las migraciones producen esquema.sql (RNF-31)", not diferencias,
                               "\n".join(diferencias) or "Sin diferencias"))
        except SystemExit as error:
            resultados.append(("Las migraciones producen esquema.sql (RNF-31)", False, str(error)))

        with servidor.conectar() as conexion, conexion.cursor() as cursor:
            cursor.execute("CREATE DATABASE taller_pruebas CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci")
        entorno = {**os.environ, "DB_HOST": "127.0.0.1", "DB_PORT": str(servidor.puerto),
                   "DB_USERNAME": "root", "DB_PASSWORD": ""}
        comando = [buscar_php(), "artisan", "test"]
        if any(any((SISTEMA / "app" / carpeta).rglob("*.php")) for carpeta in ("Dominio", "Aplicacion")):
            comando += ["--coverage", "--min=80"]
            entorno["XDEBUG_MODE"] = "coverage"
        ok, salida = ejecutar(comando, SISTEMA, entorno)
        resultados.append(("Pruebas y cobertura de la lógica de negocio (RNF-28)", ok, salida))
    return resultados


def main() -> int:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    parser = argparse.ArgumentParser()
    parser.add_argument("--solo-codigo", action="store_true", help="omite los verificadores de la documentación")
    parser.add_argument("--mysqld", help="ruta de mysqld 8.4 para el servidor temporal")
    opciones = parser.parse_args()
    php = buscar_php()

    pasos = [
        ("Secretos en el historial (RNF-24)", secretos_en_el_historial),
        ("Vulnerabilidades de las dependencias (RNF-23)", composer_audit),
        ("Estilo de código (RNF-29)", lambda: ejecutar([php, "vendor/bin/pint", "--test"], SISTEMA)),
        ("Análisis estático y reglas de capas (RNF-27, RNF-29)",
         lambda: ejecutar([php, "vendor/bin/phpstan", "analyse", "--no-progress", "--memory-limit=1G"], SISTEMA)),
        ("Vistas sin impresión sin escapar (RNF-23)", vistas_sin_escapar),
    ]
    resultados: list[tuple[str, bool, str, float]] = []
    for nombre, paso in pasos:
        inicio = time.monotonic()
        ok, salida = paso()
        resultados.append((nombre, ok, salida, time.monotonic() - inicio))
        print(f"{'OK   ' if ok else 'FALLA'} {nombre}", flush=True)

    inicio = time.monotonic()
    for nombre, ok, salida in base_de_datos(opciones.mysqld):
        resultados.append((nombre, ok, salida, time.monotonic() - inicio))
        print(f"{'OK   ' if ok else 'FALLA'} {nombre}", flush=True)

    if not opciones.solo_codigo:
        for script in DOCUMENTACION:
            inicio = time.monotonic()
            ok, salida = ejecutar([sys.executable, str(RAIZ / "scripts" / script)], RAIZ,
                                  {**os.environ, "PYTHONIOENCODING": "utf-8"})
            nombre = f"Documentación: {script}"
            resultados.append((nombre, ok, salida, time.monotonic() - inicio))
            print(f"{'OK   ' if ok else 'FALLA'} {nombre}", flush=True)

    fallas = [r for r in resultados if not r[1]]
    for nombre, _, salida, _ in fallas:
        print(f"\n--- {nombre} ---\n{salida[-3000:]}")
    total = sum(r[3] for r in resultados)
    print(f"\n{len(resultados) - len(fallas)} de {len(resultados)} pasos pasan · {total:.0f} s")
    return 1 if fallas else 0


if __name__ == "__main__":
    sys.exit(main())
