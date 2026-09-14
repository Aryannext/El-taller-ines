"""Valida el product backlog contra las historias de usuario y lo publica como issues de GitHub.

Uso:
    python scripts/publicar_backlog.py              # solo valida y muestra el resumen
    python scripts/publicar_backlog.py --publicar   # además crea o actualiza etiquetas, hitos e issues

Al actualizar un issue existente solo cambia título, etiquetas e hito: el cuerpo no se toca
para no borrar las casillas de criterios que ya se marcaron en GitHub.
"""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
from dataclasses import dataclass, field
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
DOCS = RAIZ / "docs"
HISTORIAS = DOCS / "02-requisitos" / "historias-de-usuario.md"
BACKLOG = DOCS / "00-scrum" / "product-backlog.md"

TIPOS = {
    "HU": ("historia de usuario", "1d76db", "Necesidad de la dueña o del cliente del taller"),
    "HT": ("habilitador técnico", "5319e7", "Trabajo técnico que exigen los RNF o los ADR"),
    "DOC": ("documento", "0e8a16", "Entregable de análisis, diseño o cierre"),
}
PRIORIDADES = {
    "Must": ("b60205", "MoSCoW: imprescindible para la entrega"),
    "Should": ("d93f0b", "MoSCoW: importante, se puede aplazar"),
    "Could": ("fbca04", "MoSCoW: deseable si sobra capacidad"),
}
COLOR_EPICA = "c5def5"
COLOR_PUNTOS = "ededed"

FILA_HITO = re.compile(r"^\| \*\*((?:Sprint \d+|Cierre) · [^*]+)\*\* \| (\d{4}-\d{2}-\d{2}) \|")
FILA_DOC = re.compile(r"^\| \*\*(DOC-\d+)\*\* \|")
FILA_DESARROLLO = re.compile(r"^\| (\d+) \| \*\*((?:HU|HT)-\d+)\*\* \|")
FILA_CAPACIDAD = re.compile(r"^\| \*\*(Sprint \d+)\*\* \|")
CODIGO = re.compile(r"\b(?:HU|HT|DOC)-\d+\b")


@dataclass
class Historia:
    codigo: str
    titulo: str
    epica: str
    como: str = ""
    nacio: str = ""
    requisitos: str = ""
    prioridad: str = ""
    puntos: int = 0
    criterios: list[tuple[str, str, str, str, str]] = field(default_factory=list)


@dataclass
class Elemento:
    codigo: str
    titulo: str
    sprint: str
    orden: int | None = None
    prioridad: str = ""
    puntos: int | None = None
    depende: list[str] = field(default_factory=list)
    estado: str = ""
    evidencia: str = ""
    detalle: str = ""

    @property
    def tipo(self) -> str:
        return self.codigo.split("-")[0]


def celdas(linea: str) -> list[str]:
    return [c.strip() for c in linea.strip().strip("|").split("|")]


def vacio(valor: str) -> str:
    return "" if valor == "—" else valor


def cargar_historias() -> dict[str, Historia]:
    historias: dict[str, Historia] = {}
    epica = ""
    actual: Historia | None = None
    for linea in HISTORIAS.read_text(encoding="utf-8").splitlines():
        if m := re.match(r"## (EP-\d+ · .+)", linea):
            epica, actual = m.group(1).strip(), None
        elif m := re.match(r"### (HU-\d+) · (.+)", linea):
            actual = Historia(m.group(1), m.group(2).strip(), epica)
            historias[actual.codigo] = actual
        elif linea.startswith("## "):
            actual = None
        elif actual is None:
            continue
        elif linea.startswith("> **Como**"):
            actual.como = linea[2:].strip()
        elif linea.startswith("**Nació de:**"):
            actual.nacio = linea.strip()
        elif linea.startswith("**Requisitos:**"):
            actual.requisitos = linea.strip()
        elif m := re.match(r"\*\*Prioridad:\*\* (\w+) · \*\*Puntos:\*\* (\d+)", linea):
            actual.prioridad, actual.puntos = m.group(1), int(m.group(2))
        elif linea.startswith("| **CA-"):
            c = celdas(linea)
            m = re.match(r"\*\*(CA-[\d.]+)\*\* (.+)", c[0])
            actual.criterios.append((m.group(1), m.group(2), c[1], c[2], c[3]))
    return historias


def cargar_backlog() -> tuple[dict[str, Elemento], dict[str, str], dict[str, tuple[int, int, int]]]:
    elementos: dict[str, Elemento] = {}
    hitos: dict[str, str] = {}
    capacidad: dict[str, tuple[int, int, int]] = {}
    detalles: dict[str, list[str]] = {}
    seccion: str | None = None

    for linea in BACKLOG.read_text(encoding="utf-8").splitlines():
        if m := re.match(r"### (HT-\d+) · ", linea):
            seccion = m.group(1)
            detalles[seccion] = []
            continue
        if linea.startswith("#"):
            seccion = None
        elif seccion:
            detalles[seccion].append(linea)

        if m := FILA_HITO.match(linea):
            hitos[m.group(1).strip()] = m.group(2)
        elif FILA_DOC.match(linea):
            c = celdas(linea)
            codigo = c[0].strip("*")
            elementos[codigo] = Elemento(codigo, c[1], vacio(c[2]), estado=c[3], evidencia=vacio(c[4]))
        elif FILA_DESARROLLO.match(linea):
            c = celdas(linea)
            codigo = c[1].strip("*")
            elementos[codigo] = Elemento(
                codigo, c[2], vacio(c[5]), orden=int(c[0]), prioridad=c[3], puntos=int(c[4]),
                depende=CODIGO.findall(c[6]),
            )
        elif FILA_CAPACIDAD.match(linea):
            c = celdas(linea)
            capacidad[c[0].strip("*")] = (int(c[3]), int(c[4]), int(c[5]))

    for codigo, lineas in detalles.items():
        if codigo in elementos:
            elementos[codigo].detalle = "\n".join(lineas).strip()
    return elementos, hitos, capacidad


def numero_de_sprint(sprint: str) -> int:
    m = re.match(r"Sprint (\d+)", sprint)
    return int(m.group(1)) if m else 99


def hito_de(sprint: str, hitos: dict[str, str]) -> str | None:
    return next((t for t in hitos if t.startswith(sprint + " ·")), None) if sprint else None


def validar(historias, elementos, hitos, capacidad) -> list[str]:
    errores: list[str] = []
    desarrollo = [e for e in elementos.values() if e.orden is not None]
    posicion = {e.codigo: e for e in desarrollo}

    faltan = sorted(set(historias) - {e.codigo for e in desarrollo if e.tipo == "HU"})
    if faltan:
        errores.append("Historias que no están en el backlog: " + ", ".join(faltan))

    if sorted(e.orden for e in desarrollo) != list(range(1, len(desarrollo) + 1)):
        errores.append("El orden del backlog de desarrollo no es 1, 2, 3… sin saltos ni repetidos")

    for e in desarrollo:
        if e.tipo == "HU":
            h = historias.get(e.codigo)
            if h is None:
                errores.append(f"{e.codigo} no existe en las historias de usuario")
                continue
            if e.titulo != h.titulo:
                errores.append(f"{e.codigo}: el título «{e.titulo}» no coincide con «{h.titulo}»")
            if e.prioridad != h.prioridad:
                errores.append(f"{e.codigo}: prioridad {e.prioridad} en el backlog y {h.prioridad} en la historia")
            if e.puntos != h.puntos:
                errores.append(f"{e.codigo}: {e.puntos} puntos en el backlog y {h.puntos} en la historia")
        elif not e.detalle:
            errores.append(f"{e.codigo} no tiene sección de detalle")
        if (e.prioridad == "Must") != bool(e.sprint):
            errores.append(f"{e.codigo}: lo Must va en un sprint y lo Should o Could queda sin sprint")
        for d in e.depende:
            previo = posicion.get(d)
            if previo is None:
                errores.append(f"{e.codigo} depende de {d}, que no está en el backlog de desarrollo")
            elif previo.orden >= e.orden:
                errores.append(f"{e.codigo} (orden {e.orden}) depende de {d}, que va después (orden {previo.orden})")
            elif numero_de_sprint(previo.sprint) > numero_de_sprint(e.sprint):
                errores.append(f"{e.codigo} está en un sprint anterior a su dependencia {d}")

    for e in elementos.values():
        if e.sprint and hito_de(e.sprint, hitos) is None:
            errores.append(f"{e.codigo}: el sprint «{e.sprint}» no tiene hito")
        if e.tipo == "DOC" and e.estado == "Terminado" and not e.evidencia:
            errores.append(f"{e.codigo} está terminado sin evidencia")

    for sprint, (total, de_hu, de_ht) in capacidad.items():
        items = [e for e in desarrollo if e.sprint == sprint]
        real = (
            sum(e.puntos for e in items),
            sum(e.puntos for e in items if e.tipo == "HU"),
            sum(e.puntos for e in items if e.tipo == "HT"),
        )
        if real != (total, de_hu, de_ht):
            errores.append(f"{sprint}: la tabla de capacidad dice {(total, de_hu, de_ht)} y los elementos suman {real}")
    return errores


def resumen(elementos) -> None:
    desarrollo = [e for e in elementos.values() if e.orden is not None]
    por_sprint: dict[str, list[Elemento]] = {}
    for e in desarrollo:
        por_sprint.setdefault(e.sprint or "Sin sprint", []).append(e)
    docs = [e for e in elementos.values() if e.tipo == "DOC"]
    print(f"Documentos: {len(docs)} ({sum(e.estado == 'Terminado' for e in docs)} terminados)")
    for sprint, items in por_sprint.items():
        hu = sum(e.tipo == "HU" for e in items)
        ht = len(items) - hu
        print(f"{sprint}: {sum(e.puntos for e in items)} puntos · {hu} historias · {ht} habilitadores")
    print(f"Total de desarrollo: {sum(e.puntos for e in desarrollo)} puntos en {len(desarrollo)} elementos")


# --- Publicación en GitHub ---------------------------------------------------------------


def gh(*args: str, entrada: str | None = None) -> str:
    r = subprocess.run(["gh", *args], input=entrada, capture_output=True, text=True, encoding="utf-8")
    if r.returncode != 0:
        raise SystemExit(f"Falló gh {' '.join(args[:2])}:\n{r.stderr.strip()}")
    return r.stdout


def etiquetas_de(e: Elemento, historias: dict[str, Historia]) -> list[str]:
    nombres = [TIPOS[e.tipo][0]]
    if e.prioridad:
        nombres.append(e.prioridad)
    if e.puntos:
        nombres.append(f"{e.puntos} punto" if e.puntos == 1 else f"{e.puntos} puntos")
    if e.tipo == "HU":
        nombres.append(historias[e.codigo].epica)
    return nombres


def cuerpo(e: Elemento, historias: dict[str, Historia], numeros: dict[str, int], url: str) -> str:
    partes: list[str] = []
    if e.tipo == "HU":
        h = historias[e.codigo]
        partes += [h.como, "", h.nacio, "", h.requisitos, ""]
    elif e.tipo == "HT":
        partes += [e.detalle, ""]
    else:
        partes += [f"**Entregable:** {e.titulo}", ""]

    datos = []
    if e.orden:
        datos.append(f"**Orden en el backlog:** {e.orden}")
    if e.prioridad:
        datos.append(f"**Prioridad:** {e.prioridad}")
    if e.puntos:
        datos.append(f"**Puntos:** {e.puntos}")
    datos.append(f"**Sprint:** {e.sprint or 'sin asignar'}")
    if e.depende:
        datos.append("**Depende de:** " + ", ".join(f"#{numeros[d]} ({d})" if d in numeros else d for d in e.depende))
    if e.evidencia:
        datos.append(f"**Evidencia:** {e.evidencia}")
    partes.append(" · ".join(datos))

    if e.tipo == "HU":
        partes += ["", "### Criterios de aceptación", ""]
        partes += [
            f"- [ ] **{c}** {titulo} · **Dado** {dado}, **cuando** {cuando}, **entonces** {entonces}"
            for c, titulo, dado, cuando, entonces in historias[e.codigo].criterios
        ]

    fuente = "02-requisitos/historias-de-usuario.md" if e.tipo == "HU" else "00-scrum/product-backlog.md"
    partes += ["", f"Fuente: [{fuente.split('/')[-1]}]({url}/blob/main/docs/{fuente})"]
    return "\n".join(partes)


def publicar(historias, elementos, hitos) -> None:
    repo_info = json.loads(gh("repo", "view", "--json", "nameWithOwner,url"))
    repo, url = repo_info["nameWithOwner"], repo_info["url"]

    gestionadas: dict[str, tuple[str, str]] = {n: (c, d) for n, c, d in TIPOS.values()}
    gestionadas |= {n: v for n, v in PRIORIDADES.items()}
    for h in historias.values():
        gestionadas[h.epica] = (COLOR_EPICA, "Épica de las historias de usuario")
    for p in {e.puntos for e in elementos.values() if e.puntos}:
        gestionadas[f"{p} punto" if p == 1 else f"{p} puntos"] = (COLOR_PUNTOS, "Puntos de historia (Fibonacci)")
    for nombre, (color, descripcion) in gestionadas.items():
        gh("label", "create", nombre, "--color", color, "--description", descripcion, "--force", "-R", repo)
    print(f"Etiquetas: {len(gestionadas)}")

    existentes_hitos = {m["title"] for m in json.loads(gh("api", f"repos/{repo}/milestones?state=all&per_page=100"))}
    for titulo, fecha in hitos.items():
        if titulo not in existentes_hitos:
            gh("api", f"repos/{repo}/milestones", "-f", f"title={titulo}", "-f", f"due_on={fecha}T12:00:00Z")
            print(f"Hito creado: {titulo}")

    lista = json.loads(gh("issue", "list", "-R", repo, "--state", "all", "--limit", "500",
                          "--json", "number,title,state,labels,milestone"))
    existentes = {i["title"].split(" · ")[0]: i for i in lista}
    numeros = {codigo: i["number"] for codigo, i in existentes.items()}

    orden = sorted((e for e in elementos.values() if e.tipo == "DOC"), key=lambda e: int(e.codigo[4:]))
    orden += sorted((e for e in elementos.values() if e.orden is not None), key=lambda e: e.orden)

    creados = actualizados = cerrados = 0
    for e in orden:
        titulo = f"{e.codigo} · {e.titulo}"
        etiquetas = etiquetas_de(e, historias)
        hito = hito_de(e.sprint, hitos)
        issue = existentes.get(e.codigo)

        if issue is None:
            args = ["issue", "create", "-R", repo, "--title", titulo, "--body-file", "-"]
            for nombre in etiquetas:
                args += ["--label", nombre]
            if hito:
                args += ["--milestone", hito]
            salida = gh(*args, entrada=cuerpo(e, historias, numeros, url))
            numero = int(salida.strip().rsplit("/", 1)[-1])
            numeros[e.codigo] = numero
            issue = {"number": numero, "state": "OPEN"}
            creados += 1
        else:
            actuales = {l["name"] for l in issue["labels"]}
            hito_actual = (issue.get("milestone") or {}).get("title")
            sobran = [n for n in actuales if n in gestionadas and n not in etiquetas]
            faltan = [n for n in etiquetas if n not in actuales]
            args = ["issue", "edit", str(issue["number"]), "-R", repo]
            if issue["title"] != titulo:
                args += ["--title", titulo]
            for nombre in faltan:
                args += ["--add-label", nombre]
            for nombre in sobran:
                args += ["--remove-label", nombre]
            if hito != hito_actual:
                args += ["--milestone", hito] if hito else ["--remove-milestone"]
            if len(args) > 5:
                gh(*args)
                actualizados += 1

        if e.estado == "Terminado" and issue["state"] == "OPEN":
            gh("issue", "close", str(issue["number"]), "-R", repo, "--comment", f"Terminado. Evidencia: {e.evidencia}")
            cerrados += 1

    print(f"Issues creados: {creados} · actualizados: {actualizados} · cerrados: {cerrados}")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--publicar", action="store_true", help="crea o actualiza etiquetas, hitos e issues en GitHub")
    opciones = parser.parse_args()

    historias = cargar_historias()
    elementos, hitos, capacidad = cargar_backlog()
    errores = validar(historias, elementos, hitos, capacidad)
    resumen(elementos)
    if errores:
        print("\nErrores:")
        for error in errores:
            print(f"- {error}")
        return 1
    print("El backlog cuadra con las historias de usuario.")

    if opciones.publicar:
        publicar(historias, elementos, hitos)
    return 0


if __name__ == "__main__":
    sys.exit(main())
