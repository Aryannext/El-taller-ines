"""Genera los diagramas de casos de uso y comprueba que coincidan con su especificación.

Cada diagrama se dibuja por columnas fijas: actor principal, casos de uso, casos relacionados y actor externo.
El script comprueba que ninguna línea se cruce con otra ni atraviese un caso. Los nombres de los casos se toman
de su especificación, así el dibujo y el texto no pueden decir cosas distintas.

Comprueba además:
- que cada caso de uso tenga especificación completa y aparezca como propio en un solo diagrama;
- que las 36 historias de usuario estén cubiertas;
- que las pantallas existan en los mockups y las clases en la estructura de la arquitectura;
- que las relaciones «extend» y de generalización dibujadas coincidan con las especificaciones, en ambos sentidos;
- que cada actor dibujado participe en el caso según su especificación;
- que los códigos RN y CA citados existan.

Genera los SVG en docs/03-diseno/casos-de-uso/diagramas/ y la tabla de trazabilidad del README.

Uso: python scripts/generar_casos_de_uso.py
"""

from __future__ import annotations

import math
import re
import sys
from dataclasses import dataclass
from pathlib import Path
from xml.sax.saxutils import escape

RAIZ = Path(__file__).resolve().parent.parent
DOCS = RAIZ / "docs"
CARPETA = DOCS / "03-diseno" / "casos-de-uso"
SVGS = CARPETA / "diagramas"
LEEME = CARPETA / "README.md"
ARQUITECTURA = DOCS / "03-diseno" / "arquitectura" / "README.md"
HISTORIAS = DOCS / "02-requisitos" / "historias-de-usuario.md"
REGLAS = DOCS / "02-requisitos" / "reglas-de-negocio.md"
MOCKUPS = DOCS / "03-diseno" / "mockups"

# --- Geometría (px) ----------------------------------------------------------------------------

FILA = 110
ARRIBA = 100
X_ACTOR = 90
X_COLUMNA = {1: 400, 2: 750}
RX, RY = 124, 40
MITAD = {"paquete": (125, 32), "sistema": (80, 34), "actor": (32, 52)}
MARGEN_LIMITE = 36

FUENTE = "'Source Sans 3', 'Segoe UI', Arial, sans-serif"
C = {
    "papel": "#fffdf8", "tinta": "#23211d", "suave": "#5d5850", "linea": "#4a453d",
    "codigo": "#2a44a8", "referencia": "#f1ede4", "referencia_borde": "#8f877a", "limite": "#faf7f0",
}

DUENA, CLIENTE, WHATSAPP = "Dueña del taller", "Cliente del taller", "WhatsApp Cloud API"
CAMPOS_OBLIGATORIOS = ["Actor principal", "Historias", "Pantallas", "Implementa", "Precondición",
                       "Disparador", "Postcondición", "Relaciones"]


@dataclass
class Nodo:
    id: str
    tipo: str  # actor, sistema, caso, referencia, paquete
    col: int  # 0 actor izquierdo · 1 y 2 casos · 3 actor derecho
    fila: float
    texto: str = ""
    detalle: str = ""


@dataclass
class Relacion:
    origen: str
    destino: str
    tipo: str  # asociacion, extend, generalizacion


@dataclass
class Diagrama:
    numero: str
    archivo: str
    limite: str
    documento: str | None
    nodos: list[Nodo]
    relaciones: list[Relacion]


def actor(id_: str, nombre: str, col: int, fila: float) -> Nodo:
    return Nodo(id_, "actor", col, fila, nombre)


def sistema(id_: str, nombre: str, col: int, fila: float) -> Nodo:
    return Nodo(id_, "sistema", col, fila, nombre)


def caso(codigo: str, col: int, fila: float) -> Nodo:
    return Nodo(codigo, "caso", col, fila)


def ref(codigo: str, col: int, fila: float) -> Nodo:
    return Nodo(codigo, "referencia", col, fila)


def paquete(id_: str, nombre: str, fila: float, detalle: str) -> Nodo:
    return Nodo(id_, "paquete", 1, fila, nombre, detalle)


def asoc(a: str, b: str) -> Relacion:
    return Relacion(a, b, "asociacion")


def ext(a: str, b: str) -> Relacion:
    return Relacion(a, b, "extend")


def gen(a: str, b: str) -> Relacion:
    return Relacion(a, b, "generalizacion")


DIAGRAMAS = [
    Diagrama("00", "00-vista-general", "vista general", None, [
        actor("duena", DUENA, 0, 3.5),
        paquete("acceso", "Acceso y ajustes", 0, "CU-01 a CU-05 · diagrama 01"),
        paquete("clientes", "Clientes", 1, "CU-06 a CU-09 · diagrama 02"),
        paquete("ordenes", "Órdenes y prendas", 2, "CU-10 a CU-17 · diagrama 03"),
        paquete("fotos", "Fotos", 3, "CU-18 a CU-20 · diagrama 04"),
        paquete("estados", "Estados y entrega", 4, "CU-21 a CU-25 · diagrama 05"),
        paquete("pagos", "Pagos", 5, "CU-26 a CU-29 · diagrama 06"),
        paquete("avisos", "Avisos", 6, "CU-30 a CU-32 · diagramas 07 y 09"),
        paquete("seguimiento", "Seguimiento", 7, "CU-33 a CU-35 · diagrama 08"),
        actor("cliente", CLIENTE, 3, 5.1),
        sistema("whatsapp", WHATSAPP, 3, 6.9),
    ], [asoc("duena", p) for p in ("acceso", "clientes", "ordenes", "fotos", "estados", "pagos", "avisos", "seguimiento")]
       + [asoc("cliente", "avisos"), asoc("whatsapp", "avisos")]),

    Diagrama("01", "01-duena-acceso-y-ajustes", "Acceso y ajustes", "duena-del-taller/01-acceso-y-ajustes.md", [
        actor("duena", DUENA, 0, 2),
        caso("CU-01", 1, 0), caso("CU-02", 1, 1), caso("CU-03", 1, 2), caso("CU-04", 1, 3), caso("CU-05", 1, 4),
    ], [asoc("duena", c) for c in ("CU-01", "CU-02", "CU-03", "CU-04", "CU-05")]),

    Diagrama("02", "02-duena-clientes", "Clientes", "duena-del-taller/02-clientes.md", [
        actor("duena", DUENA, 0, 1),
        caso("CU-06", 1, 0), caso("CU-07", 1, 1), caso("CU-08", 1, 2), caso("CU-09", 2, 2),
    ], [asoc("duena", "CU-06"), asoc("duena", "CU-07"), asoc("duena", "CU-08"),
        ext("CU-07", "CU-06"), ext("CU-09", "CU-08")]),

    Diagrama("03", "03-duena-ordenes-y-prendas", "Órdenes y prendas", "duena-del-taller/03-ordenes-y-prendas.md", [
        actor("duena", DUENA, 0, 5),
        caso("CU-10", 1, 1.5),
        caso("CU-11", 2, 0), ref("CU-07", 2, 1), caso("CU-12", 2, 2), ref("CU-18", 2, 3),
        caso("CU-13", 1, 4), caso("CU-14", 1, 5), caso("CU-15", 1, 6), caso("CU-16", 1, 7), caso("CU-17", 1, 8),
    ], [asoc("duena", c) for c in ("CU-10", "CU-13", "CU-14", "CU-15", "CU-16", "CU-17")]
       + [ext("CU-11", "CU-10"), ext("CU-07", "CU-10"), ext("CU-12", "CU-10"), ext("CU-18", "CU-10")]),

    Diagrama("04", "04-duena-fotos", "Fotos", "duena-del-taller/04-fotos.md", [
        actor("duena", DUENA, 0, 1),
        caso("CU-18", 1, 0), caso("CU-19", 1, 1), caso("CU-20", 1, 2),
        ref("CU-10", 2, 0), ref("CU-16", 2, 1), ref("CU-14", 2, 2),
    ], [asoc("duena", "CU-18"), asoc("duena", "CU-19"), asoc("duena", "CU-20"),
        ext("CU-18", "CU-10"), ext("CU-19", "CU-16"), ext("CU-20", "CU-14")]),

    Diagrama("05", "05-duena-estados-y-entrega", "Estados y entrega", "duena-del-taller/05-estados-y-entrega.md", [
        actor("duena", DUENA, 0, 1.5),
        caso("CU-21", 1, 0), caso("CU-22", 1, 1), caso("CU-23", 1, 2), caso("CU-25", 1, 3), caso("CU-24", 2, 2),
    ], [asoc("duena", c) for c in ("CU-21", "CU-22", "CU-23", "CU-25")] + [ext("CU-24", "CU-23")]),

    Diagrama("06", "06-duena-pagos", "Pagos", "duena-del-taller/06-pagos.md", [
        actor("duena", DUENA, 0, 1.5),
        caso("CU-26", 1, 0), caso("CU-27", 1, 1), caso("CU-28", 1, 2), caso("CU-29", 1, 3), ref("CU-12", 2, 0),
    ], [asoc("duena", c) for c in ("CU-26", "CU-27", "CU-28", "CU-29")] + [gen("CU-12", "CU-26")]),

    Diagrama("07", "07-duena-avisos", "Avisos", "duena-del-taller/07-avisos.md", [
        actor("duena", DUENA, 0, 0.5),
        caso("CU-30", 1, 0), caso("CU-31", 1, 1), ref("CU-16", 2, 1),
    ], [asoc("duena", "CU-30"), asoc("duena", "CU-31"), ext("CU-31", "CU-16")]),

    Diagrama("08", "08-duena-seguimiento", "Seguimiento", "duena-del-taller/08-seguimiento.md", [
        actor("duena", DUENA, 0, 1),
        caso("CU-33", 1, 1), caso("CU-34", 2, 0), caso("CU-35", 2, 1), ref("CU-28", 2, 2),
    ], [asoc("duena", "CU-33"), ext("CU-34", "CU-33"), ext("CU-35", "CU-33"), ext("CU-28", "CU-33")]),

    Diagrama("09", "09-cliente-y-whatsapp", "Avisos al cliente", "cliente-y-whatsapp/09-avisos-al-cliente.md", [
        actor("cliente", CLIENTE, 0, 0.5),
        ref("CU-30", 1, 0), caso("CU-32", 1, 1),
        ref("CU-21", 2, 2), ref("CU-22", 2, 3),
        sistema("whatsapp", WHATSAPP, 3, 1),
    ], [asoc("cliente", "CU-30"), asoc("cliente", "CU-32"), asoc("whatsapp", "CU-32"),
        ext("CU-32", "CU-21"), ext("CU-32", "CU-22")]),
]


# --- Especificaciones y datos de referencia ---------------------------------------------------


def leer_especificaciones() -> dict[str, dict]:
    especificaciones: dict[str, dict] = {}
    for md in sorted(CARPETA.rglob("*.md")):
        if md == LEEME:
            continue
        for bloque in re.split(r"^### ", md.read_text(encoding="utf-8"), flags=re.M)[1:]:
            if not (m := re.match(r"(CU-\d+) · (.+)", bloque.splitlines()[0])):
                continue
            campos = {k.strip(): v.strip() for k, v in re.findall(r"^\| \*\*([^*]+)\*\* \| (.+?) \|\s*$", bloque, re.M)}
            relaciones = {(tipo, codigo) for tipo, codigos in re.findall(r"«(extend|generalización)» ((?:CU-\d+(?:, )?)+)",
                                                                          campos.get("Relaciones", ""))
                          for codigo in re.findall(r"CU-\d+", codigos)}
            actores = {a.strip() for campo in ("Actor principal", "Actores secundarios")
                       for a in campos.get(campo, "").split(",") if a.strip() and a.strip() != "—"}
            especificaciones[m.group(1)] = {"nombre": m.group(2).strip(), "archivo": md, "campos": campos,
                                            "texto": bloque, "relaciones": relaciones, "actores": actores}
    return especificaciones


def referencias() -> dict[str, set[str]]:
    estructura = re.search(r"<!-- estructura:inicio -->(.*?)<!-- estructura:fin -->",
                           ARQUITECTURA.read_text(encoding="utf-8"), re.S).group(1)
    historias = HISTORIAS.read_text(encoding="utf-8")
    return {
        "clases": set(re.findall(r"\b([A-Z]\w+)\.php", estructura)),
        "historias": set(re.findall(r"^### (HU-\d+)", historias, re.M)),
        "criterios": set(re.findall(r"\*\*(CA-\d+\.\d+)\*\*", historias)),
        "reglas": set(re.findall(r"^### (RN-\d+)", REGLAS.read_text(encoding="utf-8"), re.M)),
        "pantallas": {re.search(r'name="pantalla" content="([^"]+)"', p.read_text(encoding="utf-8")).group(1)
                      for p in MOCKUPS.glob("pt-*.html")},
    }


# --- Geometría y cruces ------------------------------------------------------------------------


def distribuir(d: Diagrama) -> tuple[dict[str, tuple[float, float]], tuple[float, float, float, float], float, float]:
    usa_col2 = any(n.col == 2 for n in d.nodos)
    media_ancho = MITAD["paquete"][0] if any(n.tipo == "paquete" for n in d.nodos) else RX
    x_limite = X_COLUMNA[2 if usa_col2 else 1] + media_ancho + MARGEN_LIMITE
    x_derecha = x_limite + 130
    centros = {}
    for n in d.nodos:
        x = X_ACTOR if n.col == 0 else x_derecha if n.col == 3 else X_COLUMNA[n.col]
        centros[n.id] = (x, ARRIBA + 20 + n.fila * FILA)
    ultima = max(n.fila for n in d.nodos)
    alto = ARRIBA + 20 + ultima * FILA + 100
    ancho = (x_derecha + 110) if any(n.col == 3 for n in d.nodos) else x_limite + 30
    limite = (X_ACTOR + 100, 36, x_limite, alto - 22)
    return centros, limite, ancho, alto


def borde(n: Nodo, centro: tuple[float, float], hacia: tuple[float, float]) -> tuple[float, float]:
    dx, dy = hacia[0] - centro[0], hacia[1] - centro[1]
    if n.tipo in ("caso", "referencia"):
        t = 1 / math.sqrt((dx / RX) ** 2 + (dy / RY) ** 2)
    else:
        mw, mh = MITAD[n.tipo]
        t = min(mw / abs(dx) if dx else math.inf, mh / abs(dy) if dy else math.inf)
    return centro[0] + dx * t, centro[1] + dy * t


def dentro(n: Nodo, centro: tuple[float, float], punto: tuple[float, float], margen: float = 6) -> bool:
    dx, dy = punto[0] - centro[0], punto[1] - centro[1]
    if n.tipo in ("caso", "referencia"):
        return (dx / (RX + margen)) ** 2 + (dy / (RY + margen)) ** 2 <= 1
    mw, mh = MITAD[n.tipo]
    return abs(dx) <= mw + margen and abs(dy) <= mh + margen


def se_cruzan(a1, a2, b1, b2) -> bool:
    def orientacion(p, q, r):
        return (q[0] - p[0]) * (r[1] - p[1]) - (q[1] - p[1]) * (r[0] - p[0])
    return (orientacion(b1, b2, a1) * orientacion(b1, b2, a2) < 0
            and orientacion(a1, a2, b1) * orientacion(a1, a2, b2) < 0)


def extremo_lateral(n: Nodo, centro: tuple[float, float], desde_x: float) -> tuple[float, float]:
    """Punto del lado del caso o paquete que mira al actor: las líneas llegan en abanico sin rozar a los vecinos."""
    media = RX if n.tipo in ("caso", "referencia") else MITAD[n.tipo][0]
    return (centro[0] - media, centro[1]) if desde_x < centro[0] else (centro[0] + media, centro[1])


def segmentos(d: Diagrama, centros) -> list[tuple[Relacion, tuple[float, float], tuple[float, float]]]:
    nodos = {n.id: n for n in d.nodos}
    salida = []
    for r in d.relaciones:
        o, t = nodos[r.origen], nodos[r.destino]
        if r.tipo == "asociacion":
            llegada = extremo_lateral(t, centros[t.id], centros[o.id][0])
            salida.append((r, borde(o, centros[o.id], llegada), llegada))
        else:
            salida.append((r, borde(o, centros[o.id], centros[t.id]), borde(t, centros[t.id], centros[o.id])))
    return salida


def comprobar_cruces(d: Diagrama, centros, lineas) -> list[str]:
    errores = []
    for i, (ri, a1, a2) in enumerate(lineas):
        for rj, b1, b2 in lineas[i + 1:]:
            if {ri.origen, ri.destino} & {rj.origen, rj.destino}:
                continue
            if se_cruzan(a1, a2, b1, b2):
                errores.append(f"Diagrama {d.numero}: se cruzan {ri.origen}–{ri.destino} y {rj.origen}–{rj.destino}")
        for n in d.nodos:
            if n.id in (ri.origen, ri.destino):
                continue
            puntos = ((a1[0] + (a2[0] - a1[0]) * k / 50, a1[1] + (a2[1] - a1[1]) * k / 50) for k in range(1, 50))
            if any(dentro(n, centros[n.id], p) for p in puntos):
                errores.append(f"Diagrama {d.numero}: la línea {ri.origen}–{ri.destino} atraviesa {n.id}")
    return errores


# --- SVG ------------------------------------------------------------------------------------------


def partir(texto: str, ancho: int) -> list[str]:
    lineas: list[str] = []
    for palabra in texto.split():
        if lineas and len(lineas[-1]) + 1 + len(palabra) <= ancho:
            lineas[-1] += " " + palabra
        else:
            lineas.append(palabra)
    return lineas


def texto(x, y, contenido, tamano=13, color=C["tinta"], peso=400, ancla="middle", estilo="") -> str:
    extra = ' font-style="italic"' if estilo == "italic" else ""
    return (f'<text x="{x:.1f}" y="{y:.1f}" font-size="{tamano}" font-weight="{peso}" fill="{color}" '
            f'text-anchor="{ancla}"{extra}>{escape(contenido)}</text>')


def bloque_de_texto(cx, cy, lineas: list[tuple[str, float, str, int, str]]) -> str:
    alto = 15
    y = cy - (len(lineas) - 1) * alto / 2 + 4
    return "".join(texto(cx, y + i * alto, t, tam, color, peso, estilo=est) for i, (t, tam, color, peso, est) in enumerate(lineas))


def dibujar(d: Diagrama, especificaciones: dict[str, dict], dueno: dict[str, str]) -> str:
    centros, (lx0, ly0, lx1, ly1), ancho, alto = distribuir(d)
    uid = d.archivo
    partes = [
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {ancho:.0f} {alto:.0f}" width="{ancho:.0f}" height="{alto:.0f}" '
        f'font-family="{FUENTE}" role="img" aria-label="Casos de uso · {escape(d.limite)}">',
        f"<title>Casos de uso · {escape(d.limite)}</title>",
        f'<defs><marker id="abierta-{uid}" viewBox="0 0 12 12" refX="11" refY="6" markerWidth="11" markerHeight="11" orient="auto">'
        f'<path d="M1 1 L11 6 L1 11" fill="none" stroke="{C["linea"]}" stroke-width="1.5"/></marker>'
        f'<marker id="triangulo-{uid}" viewBox="0 0 14 14" refX="13" refY="7" markerWidth="14" markerHeight="14" orient="auto">'
        f'<path d="M1 1 L13 7 L1 13 z" fill="{C["papel"]}" stroke="{C["linea"]}" stroke-width="1.5"/></marker></defs>',
        f'<rect width="{ancho:.0f}" height="{alto:.0f}" fill="{C["papel"]}"/>',
        f'<rect x="{lx0}" y="{ly0}" width="{lx1 - lx0:.0f}" height="{ly1 - ly0:.0f}" rx="8" fill="{C["limite"]}" '
        f'stroke="{C["tinta"]}" stroke-width="1.5"/>',
        texto(lx0 + 18, ly0 + 28, f"Puntada · {d.limite}", 15, C["tinta"], 700, "start"),
    ]
    nodos = {n.id: n for n in d.nodos}

    for r, p1, p2 in segmentos(d, centros):
        if r.tipo == "asociacion":
            partes.append(f'<line x1="{p1[0]:.1f}" y1="{p1[1]:.1f}" x2="{p2[0]:.1f}" y2="{p2[1]:.1f}" '
                          f'stroke="{C["linea"]}" stroke-width="1.4"/>')
        else:
            guiones = ' stroke-dasharray="7 5"' if r.tipo == "extend" else ""
            marcador = f"abierta-{uid}" if r.tipo == "extend" else f"triangulo-{uid}"
            partes.append(f'<line x1="{p1[0]:.1f}" y1="{p1[1]:.1f}" x2="{p2[0]:.1f}" y2="{p2[1]:.1f}" stroke="{C["linea"]}" '
                          f'stroke-width="1.4"{guiones} marker-end="url(#{marcador})"/>')
            if r.tipo == "extend":
                mx, my = (p1[0] + p2[0]) / 2, (p1[1] + p2[1]) / 2
                if math.dist(p1, p2) < 80:
                    # Flecha corta: la etiqueta va al lado para no tapar la línea ni la punta.
                    partes.append(texto(mx + 10, my + 4, "«extend»", 11.5, C["suave"], 600, "start"))
                else:
                    partes.append(f'<rect x="{mx - 30:.1f}" y="{my - 12:.1f}" width="60" height="17" rx="3" fill="{C["limite"]}"/>')
                    partes.append(texto(mx, my + 1, "«extend»", 11.5, C["suave"], 600))

    for n in d.nodos:
        cx, cy = centros[n.id]
        if n.tipo in ("caso", "referencia"):
            especificacion = especificaciones.get(n.id, {"nombre": "SIN ESPECIFICACIÓN"})
            es_ref = n.tipo == "referencia"
            relleno, borde_color = (C["referencia"], C["referencia_borde"]) if es_ref else ("#ffffff", C["tinta"])
            guiones = ' stroke-dasharray="6 4"' if es_ref else ""
            partes.append(f'<ellipse cx="{cx}" cy="{cy}" rx="{RX}" ry="{RY}" fill="{relleno}" stroke="{borde_color}" '
                          f'stroke-width="1.5"{guiones}/>')
            lineas = [(n.id, 11.5, C["codigo"], 700, "")]
            lineas += [(l, 13, C["tinta"] if not es_ref else C["suave"], 400, "") for l in partir(especificacion["nombre"], 26)]
            if es_ref:
                lineas.append((f"ver diagrama {dueno.get(n.id, '?')}", 10.5, C["suave"], 400, "italic"))
            partes.append(bloque_de_texto(cx, cy, lineas))
        elif n.tipo == "paquete":
            mw, mh = MITAD["paquete"]
            partes.append(f'<rect x="{cx - mw}" y="{cy - mh - 12}" width="96" height="14" fill="#ffffff" stroke="{C["tinta"]}" stroke-width="1.3"/>')
            partes.append(f'<rect x="{cx - mw}" y="{cy - mh + 2}" width="{2 * mw}" height="{2 * mh - 2}" fill="#ffffff" '
                          f'stroke="{C["tinta"]}" stroke-width="1.5"/>')
            partes.append(bloque_de_texto(cx, cy + 1, [(n.texto, 14, C["tinta"], 700, ""), (n.detalle, 11, C["suave"], 400, "")]))
        elif n.tipo == "sistema":
            mw, mh = MITAD["sistema"]
            partes.append(f'<rect x="{cx - mw}" y="{cy - mh}" width="{2 * mw}" height="{2 * mh}" rx="4" fill="#ffffff" '
                          f'stroke="{C["tinta"]}" stroke-width="1.5"/>')
            partes.append(bloque_de_texto(cx, cy, [("«sistema»", 11, C["suave"], 600, "")]
                                          + [(l, 13, C["tinta"], 700, "") for l in partir(n.texto, 18)]))
        else:
            trazo = f'stroke="{C["tinta"]}" stroke-width="1.8" fill="none" stroke-linecap="round"'
            partes.append(f'<circle cx="{cx}" cy="{cy - 36}" r="11" fill="#ffffff" stroke="{C["tinta"]}" stroke-width="1.8"/>'
                          f'<line x1="{cx}" y1="{cy - 25}" x2="{cx}" y2="{cy + 6}" {trazo}/>'
                          f'<line x1="{cx - 19}" y1="{cy - 13}" x2="{cx + 19}" y2="{cy - 13}" {trazo}/>'
                          f'<polyline points="{cx - 15},{cy + 30} {cx},{cy + 6} {cx + 15},{cy + 30}" {trazo}/>')
            for i, linea in enumerate(partir(n.texto, 14)):
                partes.append(texto(cx, cy + 48 + i * 15, linea, 13, C["tinta"], 700))

    partes.append("</svg>")
    return "\n".join(partes)


# --- Trazabilidad -------------------------------------------------------------------------------


def ancla(titulo: str) -> str:
    return re.sub(r"\s", "-", re.sub(r"[^\w\s-]", "", titulo.lower()))


def tabla_de_trazabilidad(especificaciones: dict[str, dict], dueno: dict[str, str]) -> str:
    filas = ["| Caso de uso | Actor principal | Historias | Pantallas | Implementa | Diagrama |",
             "| --- | --- | --- | --- | --- | --- |"]
    for codigo in sorted(especificaciones, key=lambda c: int(c[3:])):
        e = especificaciones[codigo]
        ruta = e["archivo"].relative_to(CARPETA).as_posix()
        campos = e["campos"]
        filas.append(f"| [**{codigo}** · {e['nombre']}]({ruta}#{ancla(codigo + ' · ' + e['nombre'])}) | {campos['Actor principal']} | "
                     f"{campos['Historias']} | {campos['Pantallas']} | {campos['Implementa']} | {dueno[codigo]} |")
    return "\n".join(filas)


# --- Principal -----------------------------------------------------------------------------------


def main() -> int:
    especificaciones = leer_especificaciones()
    datos = referencias()
    errores: list[str] = []

    codigos = sorted(especificaciones, key=lambda c: int(c[3:]))
    if codigos != [f"CU-{i:02d}" for i in range(1, len(codigos) + 1)]:
        errores.append("Los códigos de los casos de uso no son consecutivos desde CU-01")

    cubiertas: set[str] = set()
    for codigo, e in especificaciones.items():
        campos = e["campos"]
        errores += [f"{codigo}: falta el campo «{c}»" for c in CAMPOS_OBLIGATORIOS if c not in campos]
        historias = set(re.findall(r"HU-\d+", campos.get("Historias", "")))
        cubiertas |= historias
        errores += [f"{codigo}: {h} no existe" for h in historias - datos["historias"]]
        errores += [f"{codigo}: la pantalla {p} no existe" for p in set(re.findall(r"PT-\d+", campos.get("Pantallas", ""))) - datos["pantallas"]]
        errores += [f"{codigo}: la clase {c} no está en la arquitectura"
                    for c in re.findall(r"`([A-Z]\w+)`", campos.get("Implementa", "")) if c not in datos["clases"]]
        errores += [f"{codigo}: cita {r}, que no existe" for r in set(re.findall(r"\bRN-\d+\b", e["texto"])) - datos["reglas"]]
        errores += [f"{codigo}: cita {c}, que no existe" for c in set(re.findall(r"\bCA-\d+\.\d+\b", e["texto"])) - datos["criterios"]]
        if not re.search(r"\*\*Flujo principal\*\*", e["texto"]):
            errores.append(f"{codigo}: falta el flujo principal")
    if faltan := sorted(datos["historias"] - cubiertas, key=lambda h: int(h[3:])):
        errores.append("Historias sin caso de uso: " + ", ".join(faltan))

    dueno: dict[str, str] = {}
    for d in DIAGRAMAS:
        for n in d.nodos:
            if n.tipo in ("caso", "referencia") and n.id not in especificaciones:
                errores.append(f"Diagrama {d.numero}: {n.id} no tiene especificación")
            if n.tipo == "caso":
                if n.id in dueno:
                    errores.append(f"{n.id} es caso propio en los diagramas {dueno[n.id]} y {d.numero}")
                dueno[n.id] = d.numero
    errores += [f"{c} no aparece como caso propio en ningún diagrama" for c in codigos if c not in dueno]

    dibujadas: set[tuple[str, str, str]] = set()
    asociados: dict[str, set[str]] = {}
    for d in DIAGRAMAS:
        nodos = {n.id: n for n in d.nodos}
        for r in d.relaciones:
            if r.tipo == "asociacion":
                if nodos[r.destino].tipo == "paquete":
                    continue
                nombre = nodos[r.origen].texto
                asociados.setdefault(r.destino, set()).add(nombre)
                if nombre not in especificaciones.get(r.destino, {}).get("actores", set()):
                    errores.append(f"Diagrama {d.numero}: {nombre} no es actor de {r.destino} según su especificación")
            else:
                dibujadas.add((r.origen, "extend" if r.tipo == "extend" else "generalización", r.destino))
        centros, *_ = distribuir(d)
        errores += comprobar_cruces(d, centros, segmentos(d, centros))

    especificadas = {(c, tipo, destino) for c, e in especificaciones.items() for tipo, destino in e["relaciones"]}
    errores += [f"{o} «{t}» {d} está en la especificación pero no en ningún diagrama" for o, t, d in sorted(especificadas - dibujadas)]
    errores += [f"{o} «{t}» {d} está dibujada pero no en la especificación" for o, t, d in sorted(dibujadas - especificadas)]
    for codigo, e in especificaciones.items():
        principal = e["campos"].get("Actor principal", "")
        es_extension = any(t == "extend" for t, _ in e["relaciones"])
        if principal not in asociados.get(codigo, set()) and not es_extension:
            errores.append(f"{codigo}: su actor principal ({principal}) no está unido al caso en ningún diagrama")

    if errores:
        print("Errores:")
        for error in errores:
            print(f"- {error}")
        return 1

    SVGS.mkdir(parents=True, exist_ok=True)
    for d in DIAGRAMAS:
        (SVGS / f"{d.archivo}.svg").write_text('<?xml version="1.0" encoding="UTF-8"?>\n' + dibujar(d, especificaciones, dueno) + "\n",
                                               encoding="utf-8", newline="\n")
    leeme = LEEME.read_text(encoding="utf-8")
    patron = re.compile(r"<!-- trazabilidad:inicio -->.*?<!-- trazabilidad:fin -->", re.S)
    if not patron.search(leeme):
        print("README.md no tiene los marcadores de trazabilidad")
        return 1
    LEEME.write_text(patron.sub(lambda _: f"<!-- trazabilidad:inicio -->\n\n{tabla_de_trazabilidad(especificaciones, dueno)}\n\n<!-- trazabilidad:fin -->", leeme),
                     encoding="utf-8", newline="\n")
    print(f"{len(especificaciones)} casos de uso · {len(cubiertas)} historias cubiertas · {len(DIAGRAMAS)} diagramas sin cruces")
    return 0


if __name__ == "__main__":
    sys.exit(main())
