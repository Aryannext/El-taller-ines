"""Comprueba que la especificación técnica no contradiga al resto del diseño.

- Cada ruta usa un controlador de la estructura de la arquitectura, y su nombre y dirección no se repiten.
- Cada pantalla de los mockups tiene una ruta GET, salvo las declaradas sin ruta.
- Cada historia con controlador tiene al menos una ruta de ese controlador que la cita.
- Cada parámetro de ruta está en la tabla de parámetros.
- Cada campo validado apunta a una columna real, y un texto con `max` tiene el largo de su columna.
- Cada mensaje citado entre comillas en un criterio de aceptación existe en la especificación.
- Cada modelo de la tabla existe, y sus columnas de fecha y conversiones son columnas reales.
- Cada variable de entorno mencionada está en la tabla de variables.
- Cada clase nombrada existe en la arquitectura, y cada código citado existe.

Uso: python scripts/verificar_especificacion.py
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
DOCS = RAIZ / "docs"
ESPECIFICACION = DOCS / "04-especificacion-tecnica"
ARQUITECTURA = DOCS / "03-diseno" / "arquitectura" / "README.md"
ESQUEMA = DOCS / "03-diseno" / "modelo-de-datos" / "esquema.sql"
MOCKUPS = DOCS / "03-diseno" / "mockups"
HISTORIAS = DOCS / "02-requisitos" / "historias-de-usuario.md"
REGLAS = DOCS / "02-requisitos" / "reglas-de-negocio.md"
NO_FUNCIONALES = DOCS / "02-requisitos" / "requisitos-no-funcionales.md"
BACKLOG = DOCS / "00-scrum" / "product-backlog.md"
MANUALES = DOCS / "05-pruebas" / "pruebas-manuales"

CODIGO = re.compile(r"\b(?:CA-\d+\.\d+|RNF-\d+|RN-\d+|HU-\d+|HT-\d+|PM-\d+|PT-\d+|ADR-\d+|DOC-\d+)\b")
CLASE_EN_TEXTO = re.compile(r"`([A-Z][a-z]+(?:[A-Z][a-z]*)+)(?:::\w+\(\))?(?:@\w+)?`")
VARIABLE = re.compile(r"`([A-Z][A-Z0-9]*(?:_[A-Z0-9]+)+)`")
# Nombres de PHP, Laravel y de las pruebas que no son clases del sistema.
AJENAS = {"DateTimeImmutable", "HttpOnly", "RelojFijo", "CanalDeAvisoFalso", "NegocioInicialSeeder",
          "DatosDeLosMockupsSeeder", "VolumenDeTresAniosSeeder", "AislamientoEntreNegociosTest"}
NO_SON_VARIABLES = {"CREATED_AT", "UPDATED_AT"}


def celdas(linea: str) -> list[str]:
    return [c.strip() for c in linea.strip().strip("|").split("|")]


def sin_comillas(texto: str) -> str:
    return texto.strip().strip("`")


def columnas_del_esquema() -> tuple[dict[str, set[str]], dict[str, int]]:
    columnas: dict[str, set[str]] = {}
    largos: dict[str, int] = {}
    for tabla, cuerpo in re.findall(r"CREATE TABLE (\w+) \((.*?)\n\) ENGINE", ESQUEMA.read_text(encoding="utf-8"), re.S):
        columnas[tabla] = set()
        for columna, tipo, largo in re.findall(r"^\s{2}(\w+)\s+([A-Z]+)(?:\((\d+)\))?", cuerpo, re.M):
            if tipo in {"PRIMARY", "UNIQUE", "KEY", "CONSTRAINT"}:
                continue
            columnas[tabla].add(columna)
            if tipo in {"VARCHAR", "CHAR"}:
                largos[f"{tabla}.{columna}"] = int(largo)
    return columnas, largos


def plantilla_a_regex(mensaje: str) -> re.Pattern:
    partes = re.split(r"(\{\w+\})", mensaje)
    return re.compile("".join(".+?" if re.fullmatch(r"\{\w+\}", p) else re.escape(p) for p in partes))


def mensajes_de_celda(celda: str) -> list[str]:
    salida = []
    for parte in celda.split("<br>"):
        texto = re.sub(r"^(?:`[^`]+`(?:,\s*)?)+:\s*", "", parte.strip())
        if texto and texto != "—":
            salida.append(texto)
    return salida


def normalizar_mensaje(texto: str) -> str:
    return texto.strip().rstrip(".").strip()


def main() -> int:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")

    arquitectura = ARQUITECTURA.read_text(encoding="utf-8")
    estructura = re.search(r"<!-- estructura:inicio -->(.*?)<!-- estructura:fin -->", arquitectura, re.S).group(1)
    clases = set(re.findall(r"\b([A-Z]\w+)\.php", estructura))
    controladores_por_historia = {}
    for linea in arquitectura.splitlines():
        if m := re.match(r"^\| \*\*(HU-\d+)\*\* \|", linea):
            encontrados = re.findall(r"`([A-Z]\w+)`", celdas(linea)[2])
            controladores_por_historia[m.group(1)] = encontrados[0] if encontrados else None

    columnas, largos = columnas_del_esquema()
    pantallas = {re.search(r'name="pantalla" content="([^"]+)"', p.read_text(encoding="utf-8")).group(1)
                 for p in MOCKUPS.glob("pt-*.html")}
    historias_texto = HISTORIAS.read_text(encoding="utf-8")
    existentes = (
        set(re.findall(r"^\| \*\*(CA-\d+\.\d+)\*\*", historias_texto, re.M))
        | set(re.findall(r"^### (HU-\d+)", historias_texto, re.M))
        | set(re.findall(r"^### (RN-\d+)", REGLAS.read_text(encoding="utf-8"), re.M))
        | set(re.findall(r"^\| \*\*(RNF-\d+)\*\*", NO_FUNCIONALES.read_text(encoding="utf-8"), re.M))
        | set(re.findall(r"^### (HT-\d+)", BACKLOG.read_text(encoding="utf-8"), re.M))
        | set(re.findall(r"^\| \*\*(DOC-\d+)\*\*", BACKLOG.read_text(encoding="utf-8"), re.M))
        | {p.name[:5] for p in MANUALES.glob("PM-*.md")}
        | {"-".join(p.name.split("-")[:2]) for p in (DOCS / "03-diseno" / "adr").glob("ADR-*.md")}
        | pantallas
    )

    archivos = sorted(ESPECIFICACION.glob("*.md"))
    textos = {a.name: a.read_text(encoding="utf-8") for a in archivos}
    errores: list[str] = []

    # --- Rutas
    rutas_doc = textos["02-rutas.md"]
    rutas = []
    for linea in rutas_doc.splitlines():
        if re.match(r"^\| (GET|POST|PUT|DELETE) \|", linea):
            metodo, direccion, nombre, accion, pantalla, historias_ = celdas(linea)
            rutas.append({"metodo": metodo, "ruta": sin_comillas(direccion), "nombre": sin_comillas(nombre),
                          "accion": sin_comillas(accion), "pantallas": re.findall(r"PT-\d+", pantalla),
                          "historias": re.findall(r"HU-\d+", historias_)})
    nombres = [r["nombre"] for r in rutas if r["nombre"] != "—"]
    for repetido in sorted({n for n in nombres if nombres.count(n) > 1}):
        errores.append(f"Rutas: el nombre {repetido} se repite")
    pares = [(r["metodo"], r["ruta"]) for r in rutas]
    for repetido in sorted({p for p in pares if pares.count(p) > 1}):
        errores.append(f"Rutas: {repetido[0]} {repetido[1]} se repite")

    parametros = set(re.findall(r"^\| `\{(\w+)\}` \|", rutas_doc, re.M))
    acciones = set()
    for r in rutas:
        if "@" in r["accion"]:
            clase, metodo = r["accion"].split("@")
            acciones.add(r["accion"])
            if clase not in clases:
                errores.append(f"Rutas: {r['nombre']} usa {clase}, que no está en la arquitectura")
        elif r["accion"] not in {"vista", "Laravel"}:
            errores.append(f"Rutas: acción «{r['accion']}» no reconocida en {r['ruta']}")
        for parametro in re.findall(r"\{(\w+)\}", r["ruta"]):
            if parametro not in parametros:
                errores.append(f"Rutas: el parámetro {{{parametro}}} de {r['ruta']} no está en la tabla de parámetros")

    sin_ruta = set(re.findall(r"PT-\d+", (re.search(r"\*\*Pantallas sin ruta:\*\*(.+)", rutas_doc) or [None, ""])[1]))
    con_get = {p for r in rutas if r["metodo"] == "GET" for p in r["pantallas"]}
    for pantalla in sorted(pantallas - sin_ruta - con_get):
        errores.append(f"Rutas: la pantalla {pantalla} no tiene ruta GET")
    for historia, controlador in sorted(controladores_por_historia.items()):
        if controlador and not any(historia in r["historias"] and r["accion"].startswith(controlador + "@") for r in rutas):
            errores.append(f"Rutas: {historia} no tiene una ruta de {controlador}, el controlador que le asigna la arquitectura")

    # --- Validaciones y mensajes
    validaciones = textos["03-validaciones-y-mensajes.md"]
    mensajes: list[str] = []
    seccion_actual = None
    for linea in validaciones.splitlines():
        if m := re.match(r"^### (\w+)(?:@(\w+))?\s*$", linea):
            seccion_actual = m.group(0)[4:]
            clase = m.group(1)
            if clase not in clases:
                errores.append(f"Validaciones: {clase} no está en la arquitectura")
            if m.group(2) and seccion_actual not in acciones:
                errores.append(f"Validaciones: {seccion_actual} no es la acción de ninguna ruta")
        elif linea.startswith("**Rutas:**"):
            for nombre in re.findall(r"`([^`]+)`", linea):
                if nombre not in nombres:
                    errores.append(f"Validaciones, {seccion_actual}: la ruta {nombre} no existe")
        elif re.match(r"^\| `[^`]+`", linea) and len(celdas(linea)) == 5:
            campo, reglas, mensajes_celda, columna, _ = celdas(linea)
            mensajes += mensajes_de_celda(mensajes_celda)
            if columna != "—":
                tabla, _, nombre_columna = sin_comillas(columna).partition(".")
                if nombre_columna not in columnas.get(tabla, set()):
                    errores.append(f"Validaciones, {campo}: la columna {columna} no existe en esquema.sql")
                maximo = re.search(r"`max:(\d+)`", reglas)
                if maximo and "`string`" in reglas and f"{tabla}.{nombre_columna}" in largos:
                    if int(maximo.group(1)) != largos[f"{tabla}.{nombre_columna}"]:
                        errores.append(f"Validaciones, {campo}: max:{maximo.group(1)} y la columna {columna} "
                                       f"admite {largos[f'{tabla}.{nombre_columna}']}")
        elif re.match(r"^\| \*\*RN-\d+\*\* \|", linea):
            mensajes.append(celdas(linea)[3])
        elif re.match(r"^\| \*\*(PT-\d+|\d{3})\*\* \|", linea):
            mensajes.append(celdas(linea)[2])

    plantillas = [plantilla_a_regex(normalizar_mensaje(m)) for m in mensajes]
    for fila in re.findall(r"^\| \*\*(CA-\d+\.\d+)\*\*.*$", historias_texto, re.M):
        linea = next(l for l in historias_texto.splitlines() if l.startswith(f"| **{fila}**"))
        for cita in re.findall(r'"([^"]+)"', celdas(linea)[3]):
            if len(cita.split()) < 3:
                continue
            if not any(p.fullmatch(normalizar_mensaje(cita)) for p in plantillas):
                errores.append(f"Mensajes: «{cita}» de {fila} no está en la especificación")

    # --- Modelos
    datos = textos["04-datos-y-modelos.md"]
    modelos_tabla = set()
    for linea in datos.splitlines():
        m = re.match(r"^\| `(\w+)` \| `(\w+)` \|", linea)
        if not m or len(celdas(linea)) != 7:
            continue
        modelo, tabla = m.groups()
        modelos_tabla.add(modelo)
        _, _, creado, actualizado, _, clave, conversiones = celdas(linea)
        if modelo not in clases:
            errores.append(f"Modelos: {modelo} no está en la arquitectura")
        if tabla not in columnas:
            errores.append(f"Modelos: la tabla {tabla} de {modelo} no existe")
            continue
        for columna in [creado, actualizado, clave] + re.findall(r"`([a-z_]+)`", conversiones):
            columna = sin_comillas(columna)
            if columna != "—" and columna not in columnas[tabla]:
                errores.append(f"Modelos: {modelo} usa {columna}, que no es una columna de {tabla}")
    modelos_arquitectura = {"Negocio", "Usuario", "Cliente", "TipoPrenda", "MetodoPago", "Orden", "Prenda", "Foto", "Pago", "Aviso"}
    for faltante in sorted(modelos_arquitectura - modelos_tabla):
        errores.append(f"Modelos: {faltante} no está en la tabla de modelos")

    # --- Variables de entorno
    documentadas = set(re.findall(r"^\| `([A-Z][A-Z0-9_]+)` \|", textos["07-despliegue-y-operacion.md"], re.M))
    for nombre, texto in textos.items():
        for variable in sorted(set(VARIABLE.findall(texto)) - documentadas - NO_SON_VARIABLES):
            errores.append(f"{nombre}: la variable {variable} no está en la tabla de variables de entorno")

    # --- Clases y códigos citados
    for nombre, texto in textos.items():
        for clase in sorted(set(CLASE_EN_TEXTO.findall(texto)) - clases - AJENAS):
            errores.append(f"{nombre}: la clase {clase} no está en la arquitectura")
        for codigo in sorted(set(CODIGO.findall(texto)) - existentes):
            errores.append(f"{nombre}: cita {codigo}, que no existe")

    print(f"{len(rutas)} rutas · {len(mensajes)} mensajes · {len(modelos_tabla)} modelos · "
          f"{len(documentadas)} variables de entorno · {len(archivos)} documentos")
    if errores:
        print("\nErrores:")
        for error in errores:
            print(f"- {error}")
        return 1
    print("La especificación coincide con la arquitectura, el esquema, los mockups y las historias.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
