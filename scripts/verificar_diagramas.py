"""Comprueba que los diagramas de diseño no contradigan la arquitectura ni el modelo de datos.

- Cada clase declarada en un diagrama de clases existe en la estructura de carpetas de la arquitectura.
- Cada participante de una secuencia con nombre de clase existe en esa estructura.
- En el diagrama de modelos (marcado con «%% verificar: modelos»), cada atributo es una columna real de esquema.sql.
- Los estados de un diagrama marcado con «%% verificar: tabla.columna» son exactamente los valores del ENUM de esa columna.
- Los estados de un diagrama marcado con «%% verificar: RN-18» son exactamente los estados de la orden de esa regla.

Uso: python scripts/verificar_diagramas.py
"""

from __future__ import annotations

import re
import sys
import unicodedata
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
DISENO = RAIZ / "docs" / "03-diseno"
DIAGRAMAS = DISENO / "diagramas" / "README.md"
ARQUITECTURA = DISENO / "arquitectura" / "README.md"
ESQUEMA = DISENO / "modelo-de-datos" / "esquema.sql"
REGLAS = RAIZ / "docs" / "02-requisitos" / "reglas-de-negocio.md"

MODELO_A_TABLA = {
    "Negocio": "negocios", "Usuario": "usuarios", "Cliente": "clientes", "TipoPrenda": "tipos_prenda",
    "MetodoPago": "metodos_pago", "Orden": "ordenes", "Prenda": "prendas", "Foto": "fotos",
    "Pago": "pagos", "Aviso": "avisos",
}
NO_SON_CLASES = {"MySQL"}


def normalizar(texto: str) -> str:
    sin_tildes = unicodedata.normalize("NFD", texto).encode("ascii", "ignore").decode()
    return re.sub(r"[^a-z]", "", sin_tildes.lower())


def columnas_y_enums() -> tuple[dict[str, set[str]], dict[str, set[str]]]:
    columnas: dict[str, set[str]] = {}
    enums: dict[str, set[str]] = {}
    for tabla, cuerpo in re.findall(r"CREATE TABLE (\w+) \((.*?)\n\) ENGINE", ESQUEMA.read_text(encoding="utf-8"), re.S):
        columnas[tabla] = set(re.findall(r"^\s{2}(\w+)\s+(?:BIGINT|INT|SMALLINT|TINYINT|VARCHAR|CHAR|DATETIME|DATE|BOOLEAN|ENUM|TEXT)", cuerpo, re.M))
        for columna, valores in re.findall(r"^\s{2}(\w+)\s+ENUM\(([^)]*)\)", cuerpo, re.M):
            enums[f"{tabla}.{columna}"] = set(re.findall(r"'([^']+)'", valores))
    return columnas, enums


def estados_de_rn18() -> set[str]:
    texto = REGLAS.read_text(encoding="utf-8")
    seccion = texto.split("### RN-18")[1].split("**Tipo:**")[0]
    return {normalizar(e) for e in re.findall(r"^\| \*\*([^*]+)\*\* \|", seccion, re.M)}


def main() -> int:
    texto = DIAGRAMAS.read_text(encoding="utf-8")
    estructura = re.search(r"<!-- estructura:inicio -->(.*?)<!-- estructura:fin -->", ARQUITECTURA.read_text(encoding="utf-8"), re.S)
    clases = set(re.findall(r"\b([A-Z]\w+)\.php", estructura.group(1)))
    columnas, enums = columnas_y_enums()
    errores: list[str] = []
    conteo = {"classDiagram": 0, "stateDiagram-v2": 0, "sequenceDiagram": 0, "flowchart": 0}

    for numero, bloque in enumerate(re.findall(r"```mermaid\r?\n(.*?)```", texto, re.S), start=1):
        tipo = bloque.split()[0]
        conteo[tipo] = conteo.get(tipo, 0) + 1
        verificar = (m.group(1).strip() if (m := re.search(r"%% verificar: (.+)", bloque)) else "")
        lugar = f"Diagrama {numero} ({tipo})"

        if tipo == "classDiagram":
            declaradas = re.findall(r"^\s*class (\w+)", bloque, re.M)
            errores += [f"{lugar}: la clase {c} no está en la estructura de la arquitectura" for c in declaradas if c not in clases]
            if verificar == "modelos":
                for modelo, tabla in MODELO_A_TABLA.items():
                    cuerpo = re.search(rf"class {modelo} \{{(.*?)\}}", bloque, re.S)
                    if not cuerpo:
                        errores.append(f"{lugar}: falta el modelo {modelo}")
                        continue
                    for linea in cuerpo.group(1).splitlines():
                        linea = linea.strip()
                        if not linea or "(" in linea or linea.startswith("<<"):
                            continue
                        atributo = linea.split()[-1]
                        if atributo not in columnas[tabla]:
                            errores.append(f"{lugar}: {modelo}.{atributo} no es una columna de {tabla}")

        elif tipo == "stateDiagram-v2":
            estados = {e for par in re.findall(r"(\w+|\[\*\])\s*-->\s*(\w+|\[\*\])", bloque) for e in par if e != "[*]"}
            if verificar in enums:
                esperados = {normalizar(v) for v in enums[verificar]}
                obtenidos = {normalizar(e) for e in estados}
                if obtenidos != esperados:
                    errores.append(f"{lugar}: estados {sorted(obtenidos)} y {verificar} permite {sorted(esperados)}")
            elif verificar == "RN-18":
                obtenidos = {normalizar(e) for e in estados}
                if obtenidos != estados_de_rn18():
                    errores.append(f"{lugar}: estados {sorted(obtenidos)} y RN-18 define {sorted(estados_de_rn18())}")
            elif not verificar:
                errores.append(f"{lugar}: falta la marca «%% verificar:»")

        elif tipo == "sequenceDiagram":
            for etiqueta in re.findall(r"^\s*participant \w+ as (.+)$", bloque, re.M):
                etiqueta = etiqueta.strip()
                if re.fullmatch(r"[A-Z][a-z]+[A-Z]\w*", etiqueta) and etiqueta not in NO_SON_CLASES and etiqueta not in clases:
                    errores.append(f"{lugar}: el participante {etiqueta} no está en la estructura de la arquitectura")

    resumen = " · ".join(f"{n} {t}" for t, n in conteo.items() if n)
    print(f"Diagramas revisados: {resumen}")
    if errores:
        print("\nErrores:")
        for error in errores:
            print(f"- {error}")
        return 1
    print("Los diagramas coinciden con la arquitectura, el esquema de la base de datos y las reglas.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
