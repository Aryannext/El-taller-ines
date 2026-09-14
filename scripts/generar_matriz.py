#!/usr/bin/env python3
"""
Genera la matriz de trazabilidad a partir de los documentos de análisis, para
que nunca se desincronice de ellos.

    python scripts/generar_matriz.py

Escribe docs/02-requisitos/matriz-de-trazabilidad.md y su versión visual .html.
Termina con código 1 si un documento cita un código que no existe o si un
elemento obligatorio queda sin cubrir.
"""
from __future__ import annotations

import html
import re
import sys
import unicodedata
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
DOCS = RAIZ / "docs"
SALIDA_MD = DOCS / "02-requisitos" / "matriz-de-trazabilidad.md"
SALIDA_HTML = DOCS / "02-requisitos" / "matriz-de-trazabilidad.html"

# El orden importa: RNF antes que RN, FN antes que F.
CODIGO = re.compile(r"\b(?:ADR|RNF|RN|RF|HU|OE|FN|C|E|M|F)-\d+(?:\.\d+)?\b")
# Menciones a la especificación anterior, como "(v1 RN-06)" o "modifica v1 RN-04".
MENCION_V1 = re.compile(r"\([^)]*\bv1\b[^)]*\)|modifica v1 RN-\d+")

PENDIENTE_PRUEBA = "Sprint 3"


def leer(relativa: str) -> str:
    return (DOCS / relativa).read_text(encoding="utf-8")


def celdas(linea: str) -> list[str]:
    return [c.strip() for c in linea.strip().strip("|").split("|")]


def codigos(texto: str, prefijo: str | None = None) -> list[str]:
    texto = MENCION_V1.sub("", texto)
    encontrados = CODIGO.findall(texto)
    if prefijo:
        encontrados = [c for c in encontrados if c.split("-")[0] == prefijo]
    return list(dict.fromkeys(encontrados))


def orden(codigo: str) -> tuple:
    prefijo, numero = codigo.split("-", 1)
    return (prefijo, [int(p) for p in numero.split(".")])


def ordenar(codigos_: set[str] | list[str]) -> list[str]:
    return sorted(set(codigos_), key=orden)


def filas_con_codigo(texto: str, prefijo: str, columnas: int):
    patron = re.compile(rf"^\|\s*(?:\*\*)?({prefijo}-\d+(?:\.\d+)?)(?:\*\*)?\s*\|")
    for linea in texto.splitlines():
        m = patron.match(linea)
        if m:
            c = celdas(linea)
            if len(c) == columnas:
                yield m.group(1), c


# ─── Lectura de los documentos ─────────────────────────────────────────────────

def cargar() -> dict:
    problemas = leer("01-problema/arbol-de-problemas.md")
    objetivos = leer("01-problema/arbol-de-objetivos.md")
    especificos = leer("01-problema/objetivos-especificos.md")
    fuentes_doc = leer("02-requisitos/fuentes-de-requisitos.md")
    rf_doc = leer("02-requisitos/requisitos-funcionales.md")
    rnf_doc = leer("02-requisitos/requisitos-no-funcionales.md")
    rn_doc = leer("02-requisitos/reglas-de-negocio.md")
    hu_doc = leer("02-requisitos/historias-de-usuario.md")

    d: dict = {}
    d["causas"] = {c: {"texto": t[1]} for c, t in filas_con_codigo(problemas, "C", 3)}
    d["efectos"] = {c: {"texto": t[1]} for c, t in filas_con_codigo(problemas, "E", 3)}
    d["descartados"] = {c: t[1] for c, t in filas_con_codigo(problemas, "E", 4)}
    d["medios"] = {
        c: {"texto": t[1], "nace": codigos(t[2])} for c, t in filas_con_codigo(objetivos, "M", 4)
    }
    d["fines"] = {
        c: {"texto": t[1], "nace": codigos(t[2])} for c, t in filas_con_codigo(objetivos, "FN", 3)
    }
    d["fuentes"] = {c: t[1] for c, t in filas_con_codigo(fuentes_doc, "F", 4)}
    d["adrs"] = {"-".join(p.name.split("-")[:2]) for p in (DOCS / "03-diseno" / "adr").glob("ADR-*.md")}

    d["oes"] = {}
    for m in re.finditer(r"^### (OE-\d+) · (.+)$", especificos, flags=re.M):
        resto = especificos[m.end():]
        fila = next(l for l in resto.splitlines() if l.startswith("| M-"))
        d["oes"][m.group(1)] = {"titulo": m.group(2).strip(), "medios": codigos(celdas(fila)[0], "M")}

    d["rfs"] = {}
    modulo = ""
    for linea in rf_doc.splitlines():
        if linea.startswith("## "):
            modulo = linea[3:].strip()
        m = re.match(r"^\| \*\*(RF-\d+)\*\* \|", linea)
        if not m:
            continue
        c = celdas(linea)
        if "OE-01 a OE-06" in c[3]:
            objetivos_rf = [f"OE-0{i}" for i in range(1, 7)]
        else:
            objetivos_rf = codigos(c[3], "OE")
        d["rfs"][m.group(1)] = {
            "modulo": modulo,
            "texto": c[1],
            "prioridad": c[2],
            "objetivos": objetivos_rf,
            "reglas": codigos(c[4], "RN"),
            "fuentes": codigos(c[5]),
        }

    d["rnfs"] = {}
    caracteristica = ""
    for linea in rnf_doc.splitlines():
        if linea.startswith("## "):
            caracteristica = linea[3:].strip()
        m = re.match(r"^\| \*\*(RNF-\d+)\*\* \|", linea)
        if m:
            c = celdas(linea)
            d["rnfs"][m.group(1)] = {"caracteristica": caracteristica, "texto": c[1], "verificacion": c[4]}

    d["rns"] = {}
    partes = re.split(r"^### (RN-\d+) · (.+)$", rn_doc, flags=re.M)
    for i in range(1, len(partes), 3):
        cuerpo = partes[i + 2]
        d["rns"][partes[i]] = {
            "titulo": partes[i + 1].strip(),
            "tipo": re.search(r"\*\*Tipo:\*\* (\w+)", cuerpo).group(1),
            "origen": codigos(re.search(r"\*\*Origen:\*\*(.+)", cuerpo).group(1)),
        }

    d["hus"] = {}
    epica = ""
    actual = None
    for linea in hu_doc.splitlines():
        m = re.match(r"^## (EP-\d+) · (.+)$", linea)
        if m:
            epica = f"{m.group(1)} · {m.group(2).strip()}"
            continue
        m = re.match(r"^### (HU-\d+) · (.+)$", linea)
        if m:
            actual = d["hus"][m.group(1)] = {
                "titulo": m.group(2).strip(), "epica": epica, "actor": "", "nace": [],
                "rf": [], "rn": [], "rnf": [], "prioridad": "", "puntos": 0, "criterios": 0,
            }
            continue
        if actual is None:
            continue
        if linea.startswith("> **Como** "):
            actual["actor"] = re.match(r"> \*\*Como\*\* (.+?), \*\*quiero\*\*", linea).group(1)
        elif linea.startswith("**Nació de:**"):
            actual["nace"] = codigos(linea)
        elif linea.startswith("**Requisitos:**"):
            actual["rf"] = codigos(linea, "RF")
            actual["rn"] = codigos(linea, "RN")
            actual["rnf"] = codigos(linea, "RNF")
        elif linea.startswith("**Prioridad:**"):
            m = re.match(r"\*\*Prioridad:\*\* (\w+) · \*\*Puntos:\*\* (\d+)", linea)
            actual["prioridad"], actual["puntos"] = m.group(1), int(m.group(2))
        elif re.match(r"^\| \*\*CA-", linea):
            actual["criterios"] += 1
    return d


# ─── Relaciones derivadas ──────────────────────────────────────────────────────

def relacionar(d: dict) -> dict:
    r: dict = {}
    r["hu_por_rf"] = {k: [h for h, v in d["hus"].items() if k in v["rf"]] for k in d["rfs"]}
    r["hu_por_rn"] = {k: [h for h, v in d["hus"].items() if k in v["rn"]] for k in d["rns"]}
    r["hu_por_rnf"] = {k: [h for h, v in d["hus"].items() if k in v["rnf"]] for k in d["rnfs"]}
    r["rf_por_rn"] = {k: [f for f, v in d["rfs"].items() if k in v["reglas"]] for k in d["rns"]}
    r["medios_por_causa"] = {c: [m for m, v in d["medios"].items() if c in v["nace"]] for c in d["causas"]}
    r["oes_por_medio"] = {m: [o for o, v in d["oes"].items() if m in v["medios"]] for m in d["medios"]}
    r["rf_por_oe"] = {o: [f for f, v in d["rfs"].items() if o in v["objetivos"]] for o in d["oes"]}

    def medios_de_rf(f: str) -> list[str]:
        return ordenar({m for o in d["rfs"][f]["objetivos"] for m in d["oes"][o]["medios"]})

    r["medios_de_rf"] = {f: medios_de_rf(f) for f in d["rfs"]}
    r["causas_de_rf"] = {
        f: ordenar({c for m in r["medios_de_rf"][f] for c in d["medios"][m]["nace"]}) for f in d["rfs"]
    }
    return r


# ─── Validación ────────────────────────────────────────────────────────────────

def validar(d: dict, r: dict) -> tuple[list[str], list[tuple[str, list[str], bool]]]:
    conocidos = (
        set(d["causas"]) | set(d["efectos"]) | set(d["descartados"]) | set(d["medios"]) | set(d["fines"])
        | set(d["oes"]) | set(d["rfs"]) | set(d["rnfs"]) | set(d["rns"]) | set(d["hus"])
        | set(d["fuentes"]) | d["adrs"]
    )
    referencias: list[tuple[str, str]] = []
    for k, v in d["medios"].items():
        referencias += [(k, x) for x in v["nace"]]
    for k, v in d["fines"].items():
        referencias += [(k, x) for x in v["nace"]]
    for k, v in d["oes"].items():
        referencias += [(k, x) for x in v["medios"]]
    for k, v in d["rfs"].items():
        referencias += [(k, x) for x in v["objetivos"] + v["reglas"] + v["fuentes"]]
    for k, v in d["rns"].items():
        referencias += [(k, x) for x in v["origen"]]
    for k, v in d["hus"].items():
        referencias += [(k, x) for x in v["nace"] + v["rf"] + v["rn"] + v["rnf"]]
    errores = [f"{donde} cita {cod}, que no existe" for donde, cod in referencias if cod not in conocidos]

    cobertura = [
        ("Causas con al menos un medio", [c for c in d["causas"] if not r["medios_por_causa"][c]], True),
        ("Efectos con al menos un fin",
         [e for e in d["efectos"] if not any(e in v["nace"] for v in d["fines"].values())], True),
        ("Medios incluidos en un objetivo específico", [m for m in d["medios"] if not r["oes_por_medio"][m]], True),
        ("Objetivos específicos con al menos un requisito funcional", [o for o in d["oes"] if not r["rf_por_oe"][o]], True),
        ("Requisitos funcionales con al menos una historia", [f for f in d["rfs"] if not r["hu_por_rf"][f]], True),
        ("Reglas de negocio citadas por un requisito funcional", [n for n in d["rns"] if not r["rf_por_rn"][n]], True),
        ("Reglas de negocio citadas por una historia", [n for n in d["rns"] if not r["hu_por_rn"][n]], True),
        ("Historias con criterios de aceptación", [h for h, v in d["hus"].items() if not v["criterios"]], True),
        ("Historias que nombran la causa o el efecto del que nacen",
         [h for h, v in d["hus"].items() if not any(c.split("-")[0] in ("C", "E") for c in v["nace"])], True),
        ("Requisitos no funcionales exigidos en alguna historia", [n for n in d["rnfs"] if not r["hu_por_rnf"][n]], False),
    ]
    return errores, cobertura


# ─── Tablas ────────────────────────────────────────────────────────────────────

def lista(codigos_: list[str]) -> str:
    return ", ".join(ordenar(codigos_)) if codigos_ else "—"


def construir_tablas(d: dict, r: dict) -> list[dict]:
    tablas = []

    filas = []
    for c in ordenar(d["causas"]):
        medios = r["medios_por_causa"][c]
        oes = ordenar({o for m in medios for o in r["oes_por_medio"][m]})
        rfs = ordenar({f for o in oes for f in r["rf_por_oe"][o]})
        hus = ordenar({h for f in rfs for h in r["hu_por_rf"][f]})
        filas.append([c, d["causas"][c]["texto"], lista(medios), lista(oes), str(len(rfs)), str(len(hus))])
    tablas.append({
        "id": "problema",
        "titulo": "1. Del problema a los objetivos",
        "descripcion": "Cada causa del árbol de problemas, el medio que la resuelve, el objetivo específico que lo incluye y cuántos requisitos e historias lo desarrollan.",
        "encabezados": ["Causa", "Descripción", "Medios", "Objetivos", "Requisitos", "Historias"],
        "filas": filas,
    })

    filas = []
    for f in ordenar(d["rfs"]):
        v = d["rfs"][f]
        hus = r["hu_por_rf"][f]
        criterios = sum(d["hus"][h]["criterios"] for h in hus)
        filas.append([
            f, v["prioridad"], lista(r["causas_de_rf"][f]), lista(r["medios_de_rf"][f]),
            lista(v["objetivos"]) if v["objetivos"] else "Soporte", lista(v["reglas"]),
            lista(hus), str(criterios), PENDIENTE_PRUEBA,
        ])
    tablas.append({
        "id": "requisitos",
        "titulo": "2. Requisitos funcionales",
        "descripcion": "De qué causa, medio y objetivo nace cada requisito, qué reglas debe cumplir y qué historias lo desarrollan. \"Soporte\" marca los requisitos de acceso, que protegen la información sin nacer de un objetivo específico.",
        "encabezados": ["Requisito", "Prioridad", "Causas", "Medios", "Objetivos", "Reglas", "Historias", "Criterios", "Prueba"],
        "filas": filas,
    })

    filas = []
    for h in ordenar(d["hus"]):
        v = d["hus"][h]
        nace = [c for c in v["nace"] if c.split("-")[0] in ("C", "E")]
        filas.append([
            h, v["titulo"], v["epica"].split(" · ")[0], v["actor"].capitalize(), lista(nace),
            lista(v["rf"]), lista(v["rn"]), lista(v["rnf"]), v["prioridad"], str(v["puntos"]),
            str(v["criterios"]), PENDIENTE_PRUEBA,
        ])
    tablas.append({
        "id": "historias",
        "titulo": "3. Historias de usuario",
        "descripcion": "De qué causa o efecto nace cada historia y qué requisitos, reglas y requisitos no funcionales hace cumplir.",
        "encabezados": ["Historia", "Título", "Épica", "Actor", "Nace de", "Requisitos", "Reglas", "Calidad", "Prioridad", "Puntos", "Criterios", "Prueba"],
        "filas": filas,
    })

    filas = []
    for n in ordenar(d["rns"]):
        v = d["rns"][n]
        filas.append([n, v["titulo"], v["tipo"], lista(v["origen"]), lista(r["rf_por_rn"][n]), lista(r["hu_por_rn"][n]), PENDIENTE_PRUEBA])
    tablas.append({
        "id": "reglas",
        "titulo": "4. Reglas de negocio",
        "descripcion": "Origen de cada regla y qué requisitos e historias la hacen cumplir.",
        "encabezados": ["Regla", "Nombre", "Tipo", "Origen", "Requisitos", "Historias", "Prueba"],
        "filas": filas,
    })

    filas = []
    for n in ordenar(d["rnfs"]):
        v = d["rnfs"][n]
        filas.append([n, v["texto"], v["caracteristica"], lista(r["hu_por_rnf"][n]), v["verificacion"]])
    tablas.append({
        "id": "calidad",
        "titulo": "5. Requisitos no funcionales",
        "descripcion": "Los que aparecen en historias se prueban con ellas; los demás se verifican sobre el sistema completo, como indica su columna de verificación.",
        "encabezados": ["Requisito", "Descripción", "Característica", "Historias", "Verificación"],
        "filas": filas,
    })

    filas = []
    for e in ordenar(d["efectos"]):
        fines = [f for f, v in d["fines"].items() if e in v["nace"]]
        filas.append([e, d["efectos"][e]["texto"], lista(fines), "; ".join(d["fines"][f]["texto"] for f in ordenar(fines)) or "—"])
    tablas.append({
        "id": "fines",
        "titulo": "6. Efectos y fines",
        "descripcion": "Lo que el problema le cuesta al taller y lo que gana cuando se resuelve. Los fines se miden después de la implantación.",
        "encabezados": ["Efecto", "Descripción", "Fin", "Qué gana el taller"],
        "filas": filas,
    })
    return tablas


# ─── Salida en Markdown ────────────────────────────────────────────────────────

def celda_md(texto: str) -> str:
    return texto.replace("|", "\\|")


def generar_md(d: dict, tablas: list[dict], errores: list[str], cobertura: list) -> str:
    total_criterios = sum(v["criterios"] for v in d["hus"].values())
    out = [
        "# Matriz de trazabilidad",
        "",
        "> **Archivo generado.** Se crea con `python scripts/generar_matriz.py` a partir de los documentos de análisis. "
        "No se edita a mano: se corrige el documento de origen y se vuelve a generar. "
        "Hay una [versión con buscador](matriz-de-trazabilidad.html).",
        "",
        "## Cómo se lee",
        "",
        "La cadena completa va del problema a la prueba:",
        "",
        "**Causa** (árbol de problemas) → **Medio** (árbol de objetivos) → **Objetivo específico** → "
        "**Requisito funcional** → **Regla de negocio** → **Historia de usuario** → **Criterios de aceptación** → **Prueba**",
        "",
        f"La columna **Prueba** se completa en el Sprint 3, cuando cada criterio de aceptación se convierta en una prueba automática.",
        "",
        "## Inventario",
        "",
        "| Causas | Medios | Objetivos | Requisitos funcionales | Reglas | Historias | Criterios | Requisitos no funcionales |",
        "| --- | --- | --- | --- | --- | --- | --- | --- |",
        f"| {len(d['causas'])} | {len(d['medios'])} | {len(d['oes'])} | {len(d['rfs'])} | {len(d['rns'])} | {len(d['hus'])} | {total_criterios} | {len(d['rnfs'])} |",
        "",
        "## Verificación",
        "",
        "| Comprobación | Resultado | Pendientes |",
        "| --- | --- | --- |",
        f"| Todos los códigos citados existen | {'Cumple' if not errores else 'No cumple'} | {celda_md('; '.join(errores)) or '—'} |",
    ]
    for descripcion, faltantes, obligatorio in cobertura:
        if not faltantes:
            resultado = "Cumple"
        else:
            resultado = "No cumple" if obligatorio else "Informativo"
        out.append(f"| {descripcion} | {resultado} | {lista(faltantes)} |")
    out.append("")
    for t in tablas:
        out += [f"## {t['titulo']}", "", t["descripcion"], ""]
        out.append("| " + " | ".join(t["encabezados"]) + " |")
        out.append("| " + " | ".join("---" for _ in t["encabezados"]) + " |")
        for fila in t["filas"]:
            out.append("| " + " | ".join(celda_md(c) for c in fila) + " |")
        out.append("")
    return "\n".join(out)


# ─── Salida en HTML ────────────────────────────────────────────────────────────

CSS = """
:root {
  --fondo: #f6f4ef; --papel: #fffdf8; --tinta: #23211d; --tinta-suave: #5d5850;
  --borde: #e3ddd1; --acento: #2e5f7a; --acento-fondo: #e4eef3;
  --ok: #3f6b2f; --ok-fondo: #e7efdc; --mal: #9c3d2e; --mal-fondo: #f8e9e4; --info: #8a6d1f; --info-fondo: #fbf3dc;
}
* { box-sizing: border-box; }
body { margin: 0; background: var(--fondo); color: var(--tinta);
  font: 14px/1.45 "Source Sans 3", system-ui, -apple-system, "Segoe UI", sans-serif; }
.pagina { max-width: 1400px; margin: 0 auto; padding: 28px 20px 48px; display: flex; flex-direction: column; gap: 22px; }
.eyebrow { font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--tinta-suave); }
h1 { margin: 2px 0 6px; font: 700 32px/1.1 "Source Serif 4", Georgia, serif; }
h2 { margin: 0 0 4px; font: 600 20px/1.3 "Source Serif 4", Georgia, serif; }
p { margin: 0; color: var(--tinta-suave); max-width: 75ch; }
.cadena { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; font-size: 13px; }
.cadena span { background: var(--papel); border: 1px solid var(--borde); border-radius: 999px; padding: 3px 10px; }
.cadena i { color: var(--tinta-suave); font-style: normal; }
.cifras { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; }
.cifra { background: var(--papel); border: 1px solid var(--borde); border-radius: 10px; padding: 10px 12px; }
.cifra b { display: block; font: 700 24px/1.1 "Source Serif 4", Georgia, serif; font-variant-numeric: tabular-nums; }
.cifra span { font-size: 12px; color: var(--tinta-suave); }
.buscador { position: sticky; top: 0; z-index: 5; background: var(--fondo); padding: 10px 0; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
.buscador input { flex: 1; min-width: 220px; font: inherit; font-size: 16px; padding: 10px 14px; border: 1px solid var(--borde); border-radius: 10px; background: var(--papel); }
.buscador input:focus { outline: 2px solid var(--acento); outline-offset: 1px; }
.buscador small { color: var(--tinta-suave); }
section { display: flex; flex-direction: column; gap: 8px; }
.tabla { overflow-x: auto; background: var(--papel); border: 1px solid var(--borde); border-radius: 10px; }
table { border-collapse: collapse; width: 100%; font-size: 13px; }
th { position: sticky; top: 0; background: #efe9dd; text-align: left; font-weight: 600; padding: 8px 10px; white-space: nowrap; }
td { padding: 7px 10px; border-top: 1px solid var(--borde); vertical-align: top; }
td:first-child { font-weight: 700; color: var(--acento); white-space: nowrap; }
td.num { text-align: right; font-variant-numeric: tabular-nums; }
tr.oculta { display: none; }
.estado { display: inline-block; border-radius: 999px; padding: 1px 9px; font-size: 12px; font-weight: 600; white-space: nowrap; }
.estado.ok { background: var(--ok-fondo); color: var(--ok); }
.estado.mal { background: var(--mal-fondo); color: var(--mal); }
.estado.info { background: var(--info-fondo); color: var(--info); }
.vacio { padding: 12px; color: var(--tinta-suave); display: none; }
mark { background: #fde68a; padding: 0 1px; border-radius: 2px; }
"""

JS = """
const entrada = document.getElementById('buscar');
const contador = document.getElementById('contador');
const normalizar = t => t.normalize('NFD').replace(/[\\u0300-\\u036f]/g, '').toLowerCase();
function filtrar() {
  const q = normalizar(entrada.value.trim());
  let visibles = 0;
  document.querySelectorAll('section[data-tabla]').forEach(sec => {
    let n = 0;
    sec.querySelectorAll('tbody tr').forEach(tr => {
      const coincide = !q || normalizar(tr.textContent).includes(q);
      tr.classList.toggle('oculta', !coincide);
      if (coincide) n++;
    });
    sec.querySelector('.vacio').style.display = n ? 'none' : 'block';
    visibles += n;
  });
  contador.textContent = q ? visibles + ' filas coinciden' : '';
}
entrada.addEventListener('input', filtrar);
const inicial = new URLSearchParams(location.search).get('q');
if (inicial) { entrada.value = inicial; filtrar(); }
"""


def generar_html(d: dict, tablas: list[dict], errores: list[str], cobertura: list) -> str:
    e = html.escape
    total_criterios = sum(v["criterios"] for v in d["hus"].values())
    cifras = [
        (len(d["causas"]), "causas"), (len(d["medios"]), "medios"), (len(d["oes"]), "objetivos específicos"),
        (len(d["rfs"]), "requisitos funcionales"), (len(d["rns"]), "reglas de negocio"),
        (len(d["hus"]), "historias"), (total_criterios, "criterios"), (len(d["rnfs"]), "requisitos no funcionales"),
    ]
    filas_ver = [("Todos los códigos citados existen", errores, True)] + cobertura
    ver = []
    for descripcion, faltantes, obligatorio in filas_ver:
        if not faltantes:
            estado = '<span class="estado ok">Cumple</span>'
        elif obligatorio:
            estado = '<span class="estado mal">No cumple</span>'
        else:
            estado = '<span class="estado info">Informativo</span>'
        pendientes = "; ".join(faltantes) if descripcion.startswith("Todos") else lista(faltantes)
        ver.append(f"<tr><td>{e(descripcion)}</td><td>{estado}</td><td>{e(pendientes or '—')}</td></tr>")

    secciones = []
    for t in tablas:
        cab = "".join(f"<th>{e(h)}</th>" for h in t["encabezados"])
        cuerpo = []
        for fila in t["filas"]:
            tds = "".join(
                f'<td class="num">{e(c)}</td>' if c.isdigit() else f"<td>{e(c)}</td>" for c in fila
            )
            cuerpo.append(f"<tr>{tds}</tr>")
        secciones.append(
            f'<section data-tabla id="{t["id"]}"><h2>{e(t["titulo"])}</h2><p>{e(t["descripcion"])}</p>'
            f'<div class="tabla"><table><thead><tr>{cab}</tr></thead><tbody>{"".join(cuerpo)}</tbody></table>'
            f'<div class="vacio">Ninguna fila coincide con la búsqueda.</div></div></section>'
        )

    return f"""<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Matriz de trazabilidad · El-taller-ines</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap">
<style>{CSS}</style>
</head>
<body>
<div class="pagina">
  <header>
    <div class="eyebrow">El-taller-ines · Sprint 1 · Generado desde los documentos</div>
    <h1>Matriz de trazabilidad</h1>
    <div class="cadena"><span>Causa</span><i>→</i><span>Medio</span><i>→</i><span>Objetivo</span><i>→</i><span>Requisito</span><i>→</i><span>Regla</span><i>→</i><span>Historia</span><i>→</i><span>Criterios</span><i>→</i><span>Prueba (Sprint 3)</span></div>
  </header>
  <div class="cifras">{"".join(f'<div class="cifra"><b>{n}</b><span>{e(t)}</span></div>' for n, t in cifras)}</div>
  <section>
    <h2>Verificación</h2>
    <div class="tabla"><table><thead><tr><th>Comprobación</th><th>Resultado</th><th>Pendientes</th></tr></thead><tbody>{"".join(ver)}</tbody></table></div>
  </section>
  <div class="buscador"><input id="buscar" type="search" placeholder="Busca un código o una palabra: RN-27, C-06, fotos, WhatsApp…" aria-label="Buscar en la matriz"><small id="contador"></small></div>
  {"".join(secciones)}
</div>
<script>{JS}</script>
</body>
</html>
"""


def main() -> int:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    d = cargar()
    r = relacionar(d)
    errores, cobertura = validar(d, r)
    tablas = construir_tablas(d, r)
    SALIDA_MD.write_text(generar_md(d, tablas, errores, cobertura) + "\n", encoding="utf-8")
    SALIDA_HTML.write_text(generar_html(d, tablas, errores, cobertura), encoding="utf-8")

    print(f"Causas {len(d['causas'])} · medios {len(d['medios'])} · objetivos {len(d['oes'])} · "
          f"RF {len(d['rfs'])} · RN {len(d['rns'])} · HU {len(d['hus'])} · RNF {len(d['rnfs'])}")
    falla = False
    for error in errores:
        print("ERROR:", error)
        falla = True
    for descripcion, faltantes, obligatorio in cobertura:
        if faltantes:
            print(("FALTA: " if obligatorio else "Nota: ") + f"{descripcion}: {lista(faltantes)}")
            falla = falla or obligatorio
    print("Matriz generada." if not falla else "Matriz generada con pendientes.")
    return 1 if falla else 0


if __name__ == "__main__":
    sys.exit(main())
