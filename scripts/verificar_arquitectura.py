"""Comprueba que la arquitectura no deje reglas, historias ni clases por fuera.

- Cada regla de negocio (RN) tiene fila en «Dónde vive cada regla en el código».
- Cada historia de usuario (HU) tiene fila en «Historias y casos de uso».
- Cada clase nombrada en esas tablas existe en la estructura de carpetas.
- Cada pantalla citada existe en los mockups.
- Ninguna regla que el modelo de datos asigna a la aplicación queda ubicada solo en la base de datos.

Uso: python scripts/verificar_arquitectura.py
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
DOCS = RAIZ / "docs"
ARQUITECTURA = DOCS / "03-diseno" / "arquitectura" / "README.md"
MODELO = DOCS / "03-diseno" / "modelo-de-datos" / "README.md"
REGLAS = DOCS / "02-requisitos" / "reglas-de-negocio.md"
HISTORIAS = DOCS / "02-requisitos" / "historias-de-usuario.md"
MOCKUPS = DOCS / "03-diseno" / "mockups"

CLASE = re.compile(r"`([A-Z][A-Za-z]+)(?:::\w+)?`")


def celdas(linea: str) -> list[str]:
    return [c.strip() for c in linea.strip().strip("|").split("|")]


def filas_con_codigo(texto: str, prefijo: str) -> dict[str, list[str]]:
    return {celdas(l)[0].strip("*"): celdas(l)[1:] for l in texto.splitlines()
            if re.match(rf"^\| \*\*{prefijo}-\d+\*\* \|", l)}


def main() -> int:
    arquitectura = ARQUITECTURA.read_text(encoding="utf-8")
    reglas = set(re.findall(r"^### (RN-\d+)", REGLAS.read_text(encoding="utf-8"), re.M))
    historias = set(re.findall(r"^### (HU-\d+)", HISTORIAS.read_text(encoding="utf-8"), re.M))
    pantallas = {re.search(r'name="pantalla" content="([^"]+)"', p.read_text(encoding="utf-8")).group(1)
                 for p in MOCKUPS.glob("pt-*.html")}

    bloque = re.search(r"<!-- estructura:inicio -->(.*?)<!-- estructura:fin -->", arquitectura, re.S)
    if not bloque:
        print("Falta la estructura de carpetas entre sus marcadores")
        return 1
    en_estructura = set(re.findall(r"\b([A-Z]\w+)\.php", bloque.group(1)))  # clases; web.php no lo es

    filas_reglas = filas_con_codigo(arquitectura, "RN")
    filas_historias = filas_con_codigo(arquitectura, "HU")
    errores: list[str] = []

    def comparar(esperado: set[str], encontrado: set[str], nombre: str) -> None:
        if faltan := sorted(esperado - encontrado, key=lambda c: int(c.split("-")[1])):
            errores.append(f"{nombre} sin fila: {', '.join(faltan)}")
        if sobran := sorted(encontrado - esperado):
            errores.append(f"{nombre} que no existen: {', '.join(sobran)}")

    comparar(reglas, set(filas_reglas), "Reglas")
    comparar(historias, set(filas_historias), "Historias")

    nombradas: set[str] = set()
    for codigo, columnas in {**filas_reglas, **filas_historias}.items():
        for columna in columnas:
            for clase in CLASE.findall(columna):
                nombradas.add(clase)
                if clase not in en_estructura:
                    errores.append(f"{codigo}: `{clase}` no está en la estructura de carpetas")

    for codigo, (pantallas_citadas, *_) in filas_historias.items():
        for pantalla in re.findall(r"PT-\d+", pantallas_citadas):
            if pantalla not in pantallas:
                errores.append(f"{codigo}: la pantalla {pantalla} no existe en los mockups")

    for linea in MODELO.read_text(encoding="utf-8").splitlines():
        if m := re.match(r"^\| \*\*(RN-\d+)\*\* \|", linea):
            aplicacion = celdas(linea)[2]
            capa = filas_reglas.get(m.group(1), ["", ""])[0]
            if aplicacion != "—" and capa == "Base de datos":
                errores.append(f"{m.group(1)}: el modelo de datos la asigna a la aplicación, pero la arquitectura la deja solo en la base de datos")

    sin_uso = sorted(c for c in en_estructura - nombradas
                     if c not in {"AppServiceProvider", "Negocio", "Usuario", "Cliente", "TipoPrenda", "MetodoPago",
                                  "Orden", "Prenda", "Foto", "Pago", "Aviso", "ResultadoDeEnvio", "ReglaIncumplida",
                                  "AlmacenDeFotos", "WhatsAppCloudApiCanal", "WhatsAppAsistidoCanal"})

    print(f"{len(filas_reglas)} reglas · {len(filas_historias)} historias · {len(en_estructura)} clases en la estructura")
    if sin_uso:
        print("Clases de la estructura que ninguna tabla nombra: " + ", ".join(sin_uso))
    if errores:
        print("\nErrores:")
        for error in errores:
            print(f"- {error}")
        return 1
    print("La arquitectura cubre todas las reglas e historias, y cada clase nombrada tiene su lugar.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
