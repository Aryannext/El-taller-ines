"""Genera los diagramas BPMN del proceso actual y del proceso propuesto.

Por cada fase escribe en docs/01-problema/procesos/:
- un archivo .bpmn (BPMN 2.0 con su diagrama), que se abre y edita en bpmn.io, Camunda Modeler u otra
  herramienta compatible con BPMN 2.0;
- un .svg con el mismo dibujo, que GitHub muestra en proceso-actual-y-propuesto.md.
Además escribe la página docs/01-problema/proceso-actual-y-propuesto.html con todas las fases.

Antes de escribir, comprueba que cada código (C-, E-, HU-, RN-) exista en los documentos y que cada
diagrama esté bien formado.

Uso: python scripts/generar_procesos.py
"""

from __future__ import annotations

import re
import sys
from dataclasses import dataclass
from pathlib import Path
from xml.sax.saxutils import escape

RAIZ = Path(__file__).resolve().parent.parent
DOCS = RAIZ / "docs"
CARPETA = DOCS / "01-problema" / "procesos"
PAGINA = DOCS / "01-problema" / "proceso-actual-y-propuesto.html"

# --- Geometría (px) ----------------------------------------------------------------------------

MARGEN = 10
FRANJA = 30  # franja con el nombre del pool y de cada carril
COLUMNA = 160
FILA = 136
RELLENO_CARRIL = 24
TAREA = (130, 96)
EVENTO = 36
COMPUERTA = 50

FUENTE = "'Source Sans 3', 'Segoe UI', Arial, sans-serif"
COLORES = {
    "papel": "#fffdf8",
    "tinta": "#23211d",
    "suave": "#5d5850",
    "linea": "#6f685d",
    "franja": "#efe9dd",
    "neutro": "#f4f0e7",
    "neutro_borde": "#8f877a",
    "problema": "#9c3d2e",
    "problema_fondo": "#f8e9e4",
    "solucion": "#4b6130",
    "solucion_fondo": "#edf0e2",
}

ETIQUETAS_BPMN = {
    "inicio": "startEvent",
    "inicio_fecha": "startEvent",
    "fin": "endEvent",
    "mensaje": "intermediateCatchEvent",
    "manual": "manualTask",
    "usuario": "userTask",
    "servicio": "serviceTask",
    "envio": "sendTask",
    "decision": "exclusiveGateway",
}
DEFINICIONES_BPMN = {"inicio_fecha": "timerEventDefinition", "mensaje": "messageEventDefinition"}
TAREAS = {"manual", "usuario", "servicio", "envio"}
EVENTOS = {"inicio", "inicio_fecha", "fin", "mensaje"}


@dataclass
class Nodo:
    id: str
    tipo: str
    texto: str
    carril: str
    col: int
    fila: int = 0
    codigos: tuple[str, ...] = ()

    @property
    def tamano(self) -> tuple[int, int]:
        if self.tipo in TAREAS:
            return TAREA
        if self.tipo == "decision":
            return COMPUERTA, COMPUERTA
        return EVENTO, EVENTO


@dataclass
class Flujo:
    origen: str
    destino: str
    etiqueta: str = ""
    ruta: str = ""  # recta, vertical, rama, entra, codo, abajo, frontera, volver; vacío = automática


@dataclass
class Diagrama:
    archivo: str
    proceso: str  # "actual" o "propuesto"
    fase: str
    carriles: list[tuple[str, str]]
    nodos: list[Nodo]
    flujos: list[Flujo]

    @property
    def pool(self) -> str:
        return "Taller de costura · hoy" if self.proceso == "actual" else "Taller de costura · con El-taller-ines"


N, F = Nodo, Flujo
CLIENTE = ("cliente", "Cliente del taller")
DUENA = ("duena", "Dueña del taller")
SISTEMA = ("sistema", "El-taller-ines")

DIAGRAMAS = [
    Diagrama("actual-1-recepcion-y-arreglo", "actual", "Recepción y arreglo", [CLIENTE, DUENA], [
        N("llega", "inicio", "Llega con prendas para arreglar", "cliente", 0),
        N("explica", "manual", "Entregar las prendas y explicar cada arreglo", "cliente", 1),
        N("acuerda", "manual", "Acordar precio y fecha de entrega, sin anotarlos", "duena", 2, codigos=("C-01", "C-01.1")),
        N("abona", "decision", "¿Abona al dejar la ropa?", "duena", 3),
        N("abono", "manual", "Recibir el abono y recordarlo", "duena", 4, codigos=("C-04",)),
        N("une_abono", "decision", "", "duena", 5),
        N("guarda", "manual", "Guardar las prendas en el rincón, junto con las demás", "duena", 6, codigos=("C-06",)),
        N("arregla", "manual", "Arreglar las prendas sin registrar el avance", "duena", 7, codigos=("C-02",)),
        N("fin", "fin", "Sigue en aviso y seguimiento", "duena", 8),
    ], [
        F("llega", "explica"), F("explica", "acuerda"), F("acuerda", "abona"),
        F("abona", "abono", "Sí"), F("abona", "une_abono", "No", "abajo"), F("abono", "une_abono"),
        F("une_abono", "guarda"), F("guarda", "arregla"), F("arregla", "fin"),
    ]),
    Diagrama("actual-2-aviso-y-seguimiento", "actual", "Aviso y seguimiento", [CLIENTE, DUENA], [
        N("fecha", "inicio_fecha", "Llega la fecha de entrega acordada", "cliente", 0),
        N("viene", "decision", "¿Viene en la fecha?", "cliente", 1),
        N("escribe", "manual", "Escribirle por WhatsApp para acordar otro día", "duena", 1, codigos=("C-03", "C-05")),
        N("vuelve", "decision", "¿El cliente viene?", "duena", 2),
        N("dispone", "manual", "Quedarse las prendas: venderlas, usarlas o como tela", "duena", 3, codigos=("E-04",)),
        N("sin_reclamar", "fin", "Prendas sin reclamar, sin saber cuántas", "duena", 4),
        N("une", "decision", "", "cliente", 4),
        N("fin", "fin", "Sigue en entrega y cobro", "cliente", 5),
    ], [
        F("fecha", "viene"), F("viene", "une", "Sí"), F("viene", "escribe", "No"), F("escribe", "vuelve"),
        F("vuelve", "une", "Sí", "frontera"), F("vuelve", "dispone", "No"), F("dispone", "sin_reclamar"), F("une", "fin"),
    ]),
    Diagrama("actual-3-entrega-y-cobro", "actual", "Entrega y cobro", [CLIENTE, DUENA], [
        N("llega", "inicio", "Llega a recoger sus prendas", "cliente", 0),
        N("busca", "manual", "Buscar sus prendas en el rincón, sin saber con certeza cuáles son", "duena", 1,
          codigos=("C-06.1", "E-06")),
        N("terminadas", "decision", "¿Están terminadas?", "duena", 2),
        N("rapido", "decision", "¿El arreglo es rápido?", "duena", 2, fila=1),
        N("espera", "manual", "Terminarlo mientras el cliente espera", "duena", 3, fila=1, codigos=("E-01",)),
        N("otro_dia", "manual", "Acordar otro día o devolver la prenda sin arreglar", "duena", 3, fila=2,
          codigos=("E-01", "E-01.1")),
        N("sin_prenda", "fin", "El cliente se va sin su arreglo", "duena", 4, fila=2),
        N("une", "decision", "", "duena", 4),
        N("mide", "manual", "Medirse las prendas", "cliente", 5),
        N("falta", "decision", "¿Le falta algo al arreglo?", "cliente", 6),
        N("retoque", "manual", "Terminar el retoque", "duena", 6),
        N("cobra", "manual", "Cobrar lo que recuerda que se debe", "duena", 8, codigos=("C-04", "E-02")),
        N("paga", "decision", "¿Paga todo?", "duena", 9),
        N("pagada", "fin", "Entregada y pagada", "duena", 11),
        N("deuda", "manual", "Entregar y recordar la deuda de memoria", "duena", 10, fila=1, codigos=("C-04", "E-03")),
        N("con_deuda", "fin", "Entregada con una deuda sin registrar", "duena", 11, fila=1),
    ], [
        F("llega", "busca"), F("busca", "terminadas"), F("terminadas", "une", "Sí"), F("terminadas", "rapido", "No"),
        F("rapido", "espera", "Sí"), F("rapido", "otro_dia", "No"), F("espera", "une"), F("otro_dia", "sin_prenda"),
        F("une", "mide"), F("mide", "falta"), F("falta", "retoque", "Sí"), F("retoque", "mide", ruta="volver"),
        F("falta", "cobra", "No", "codo"), F("cobra", "paga"), F("paga", "pagada", "Sí"), F("paga", "deuda", "No"),
        F("deuda", "con_deuda"),
    ]),
    Diagrama("propuesto-1-recepcion-y-arreglo", "propuesto", "Recepción y arreglo", [CLIENTE, DUENA, SISTEMA], [
        N("llega", "inicio", "Llega con prendas para arreglar", "cliente", 0),
        N("explica", "manual", "Entregar las prendas y explicar cada arreglo", "cliente", 1),
        N("cliente", "usuario", "Buscar al cliente o registrarlo", "duena", 2, codigos=("HU-04", "HU-03", "HU-10")),
        N("orden", "usuario", "Registrar la orden: tipo, arreglo, precio, fecha y fotos", "duena", 3,
          codigos=("HU-07", "HU-09", "HU-17")),
        N("abona", "decision", "¿Abona al dejar la ropa?", "duena", 4),
        N("abono", "usuario", "Registrar el abono", "duena", 5, codigos=("HU-24",)),
        N("une_abono", "decision", "", "duena", 6),
        N("guarda", "servicio", "Guardar la orden, darle número y calcular valor y saldo", "sistema", 7,
          codigos=("RN-08", "RN-26", "RN-27")),
        N("bolsa", "manual", "Escribir el número en la bolsa y guardar las prendas", "duena", 8, codigos=("HU-08",)),
        N("arregla", "usuario", "Arreglar una prenda y marcar su avance", "duena", 9, codigos=("HU-20",)),
        N("estado", "servicio", "Recalcular el estado de la orden", "sistema", 10, codigos=("RN-18", "RN-22")),
        N("terminadas", "decision", "¿Todas las prendas terminadas?", "sistema", 11),
        N("lista", "fin", "Orden lista: sigue en aviso y seguimiento", "sistema", 12),
    ], [
        F("llega", "explica"), F("explica", "cliente"), F("cliente", "orden"), F("orden", "abona"),
        F("abona", "abono", "Sí"), F("abona", "une_abono", "No", "abajo"), F("abono", "une_abono"),
        F("une_abono", "guarda", ruta="codo"), F("guarda", "bolsa"), F("bolsa", "arregla"), F("arregla", "estado"),
        F("estado", "terminadas"), F("terminadas", "lista", "Sí"), F("terminadas", "arregla", "No", "abajo"),
    ]),
    Diagrama("propuesto-2-aviso-y-seguimiento", "propuesto", "Aviso y seguimiento", [CLIENTE, DUENA, SISTEMA], [
        N("lista", "inicio", "La orden queda lista para entregar", "sistema", 0),
        N("aviso", "envio", "Enviar el aviso automático por WhatsApp", "sistema", 1, codigos=("HU-28", "RN-37", "RN-40")),
        N("acepto", "decision", "¿WhatsApp aceptó el mensaje?", "sistema", 2),
        N("asistido", "usuario", "Enviar el aviso asistido con un toque", "duena", 3, codigos=("HU-29",)),
        N("une_aviso", "decision", "", "sistema", 4),
        N("recibe", "mensaje", "Recibe el aviso", "cliente", 5),
        N("viene", "decision", "¿Viene en la fecha acordada?", "cliente", 6),
        N("panel", "servicio", "Mostrar la orden en el panel con sus días de espera", "sistema", 7,
          codigos=("HU-32", "HU-34", "RN-35", "RN-36")),
        N("escribe", "manual", "Escribirle por WhatsApp para acordar otro día", "duena", 8),
        N("vuelve", "decision", "¿El cliente viene?", "duena", 9),
        N("decide", "manual", "Decidir qué hacer con las prendas, fuera del sistema", "duena", 10),
        N("sin_reclamar", "fin", "Orden sin reclamar, contada en el panel", "duena", 11),
        N("une", "decision", "", "cliente", 10),
        N("fin", "fin", "Sigue en entrega y cobro", "cliente", 11),
    ], [
        F("lista", "aviso"), F("aviso", "acepto"), F("acepto", "une_aviso", "Sí"), F("acepto", "asistido", "No"),
        F("asistido", "une_aviso"), F("une_aviso", "recibe", ruta="codo"), F("recibe", "viene"),
        F("viene", "une", "Sí"), F("viene", "panel", "No"), F("panel", "escribe"), F("escribe", "vuelve"),
        F("vuelve", "une", "Sí", "frontera"), F("vuelve", "decide", "No"), F("decide", "sin_reclamar"), F("une", "fin"),
    ]),
    Diagrama("propuesto-3-entrega-y-cobro", "propuesto", "Entrega y cobro", [CLIENTE, DUENA, SISTEMA], [
        N("llega", "inicio", "Llega a recoger sus prendas", "cliente", 0),
        N("busca", "usuario", "Buscar la orden y reconocer las prendas por sus fotos", "duena", 1, codigos=("HU-15", "HU-18")),
        N("terminadas", "decision", "¿Están terminadas?", "duena", 2),
        N("rapido", "decision", "¿El arreglo es rápido?", "duena", 2, fila=1),
        N("espera", "usuario", "Terminarlo mientras el cliente espera y marcarlo Terminada", "duena", 3, fila=1,
          codigos=("HU-20",)),
        N("otro_dia", "usuario", "Acordar otro día o devolver la prenda sin arreglar", "duena", 3, fila=2,
          codigos=("HU-36", "RN-44")),
        N("sin_prenda", "fin", "Vuelve otro día o se lleva la prenda sin arreglar", "duena", 4, fila=2),
        N("une", "decision", "", "duena", 4),
        N("mide", "manual", "Medirse las prendas", "cliente", 5),
        N("falta", "decision", "¿Le falta algo al arreglo?", "cliente", 6),
        N("retoque", "usuario", "Devolver la prenda a En proceso y terminar el retoque", "duena", 6, codigos=("HU-20", "RN-14")),
        N("paga", "decision", "¿Paga algo ahora?", "duena", 8),
        N("pago", "usuario", "Registrar el pago", "duena", 9, codigos=("HU-23", "RN-28")),
        N("une_pago", "decision", "", "duena", 10),
        N("entrega", "usuario", "Entregar la orden y confirmar si queda saldo", "duena", 11, codigos=("HU-21", "RN-20", "RN-21")),
        N("registra", "servicio", "Registrar la entrega y dejar el saldo por cobrar", "sistema", 12,
          codigos=("RN-23", "RN-27", "RN-32")),
        N("fin", "fin", "Orden entregada", "sistema", 13),
    ], [
        F("llega", "busca"), F("busca", "terminadas"), F("terminadas", "une", "Sí"), F("terminadas", "rapido", "No"),
        F("rapido", "espera", "Sí"), F("rapido", "otro_dia", "No"), F("espera", "une"), F("otro_dia", "sin_prenda"),
        F("une", "mide"), F("mide", "falta"), F("falta", "retoque", "Sí"), F("retoque", "mide", ruta="volver"),
        F("falta", "paga", "No", "codo"), F("paga", "pago", "Sí"), F("paga", "une_pago", "No", "abajo"),
        F("pago", "une_pago"), F("une_pago", "entrega"), F("entrega", "registra"), F("registra", "fin"),
    ]),
]


# --- Validación ----------------------------------------------------------------------------------


def codigos_existentes() -> set[str]:
    problemas = (DOCS / "01-problema" / "arbol-de-problemas.md").read_text(encoding="utf-8")
    historias = (DOCS / "02-requisitos" / "historias-de-usuario.md").read_text(encoding="utf-8")
    reglas = (DOCS / "02-requisitos" / "reglas-de-negocio.md").read_text(encoding="utf-8")
    return (
        set(re.findall(r"^\| \**([CE]-\d+(?:\.\d+)?)\** \|", problemas, re.M))
        | set(re.findall(r"^### (HU-\d+)", historias, re.M))
        | set(re.findall(r"^### (RN-\d+)", reglas, re.M))
    )


def validar(diagramas: list[Diagrama]) -> list[str]:
    errores: list[str] = []
    existentes = codigos_existentes()
    for d in diagramas:
        ids = {n.id: n for n in d.nodos}
        carriles = {c for c, _ in d.carriles}
        posiciones: set[tuple[str, int, int]] = set()
        for n in d.nodos:
            if n.carril not in carriles:
                errores.append(f"{d.archivo}: {n.id} está en un carril que no existe ({n.carril})")
            if (n.carril, n.col, n.fila) in posiciones:
                errores.append(f"{d.archivo}: {n.id} ocupa la misma celda que otro nodo")
            posiciones.add((n.carril, n.col, n.fila))
            errores += [f"{d.archivo}: {n.id} cita {c}, que no existe" for c in n.codigos if c not in existentes]
        for f in d.flujos:
            for extremo in (f.origen, f.destino):
                if extremo not in ids:
                    errores.append(f"{d.archivo}: un flujo usa {extremo}, que no existe")
        salen = {f.origen for f in d.flujos}
        llegan = {f.destino for f in d.flujos}
        for n in d.nodos:
            if n.tipo not in ("inicio", "inicio_fecha") and n.id not in llegan:
                errores.append(f"{d.archivo}: a {n.id} no llega ningún flujo")
            if n.tipo != "fin" and n.id not in salen:
                errores.append(f"{d.archivo}: de {n.id} no sale ningún flujo")
    return errores


# --- Distribución --------------------------------------------------------------------------------


@dataclass
class Plano:
    centros: dict[str, tuple[float, float]]
    carriles: dict[str, tuple[float, float]]  # carril -> (y superior, alto)
    ancho: float
    alto: float
    rutas: dict[int, list[tuple[float, float]]]


def distribuir(d: Diagrama) -> Plano:
    filas = {c: 1 + max((n.fila for n in d.nodos if n.carril == c), default=0) for c, _ in d.carriles}
    carriles, y = {}, float(MARGEN)
    for c, _ in d.carriles:
        alto = 2 * RELLENO_CARRIL + filas[c] * FILA
        carriles[c] = (y, alto)
        y += alto
    x0 = MARGEN + 2 * FRANJA
    centros = {
        n.id: (x0 + n.col * COLUMNA + COLUMNA / 2, carriles[n.carril][0] + RELLENO_CARRIL + FILA / 2 + n.fila * FILA)
        for n in d.nodos
    }
    columnas = 1 + max(n.col for n in d.nodos)
    plano = Plano(centros, carriles, x0 + columnas * COLUMNA + MARGEN, y - MARGEN, {})
    nodos = {n.id: n for n in d.nodos}
    for i, f in enumerate(d.flujos):
        plano.rutas[i] = trazar(f, nodos[f.origen], nodos[f.destino], plano)
    return plano


def trazar(f: Flujo, o: Nodo, t: Nodo, plano: Plano) -> list[tuple[float, float]]:
    (ox, oy), (tx, ty) = plano.centros[o.id], plano.centros[t.id]
    (ow, oh), (tw, th) = o.tamano, t.tamano
    ruta = f.ruta
    if not ruta:
        if abs(oy - ty) < 1:
            ruta = "recta" if tx > ox else "abajo"
        elif abs(ox - tx) < 1:
            ruta = "vertical"
        elif tx < ox:
            ruta = "volver" if ty < oy else "abajo"
        elif o.tipo == "decision":
            ruta = "rama"
        elif t.tipo == "decision":
            ruta = "entra"
        else:
            ruta = "codo"

    derecha_o, izquierda_o = (ox + ow / 2, oy), (ox - ow / 2, oy)
    arriba_o, abajo_o = (ox, oy - oh / 2), (ox, oy + oh / 2)
    izquierda_t, arriba_t, abajo_t = (tx - tw / 2, ty), (tx, ty - th / 2), (tx, ty + th / 2)

    if ruta == "recta":
        return [derecha_o, izquierda_t]
    if ruta == "vertical":
        return [abajo_o, arriba_t] if ty > oy else [arriba_o, abajo_t]
    if ruta == "rama":
        return [abajo_o if ty > oy else arriba_o, (ox, ty), izquierda_t]
    if ruta == "entra":
        return [derecha_o, (tx, oy), arriba_t if ty > oy else abajo_t]
    if ruta == "codo":
        medio = (ox + ow / 2 + tx - tw / 2) / 2
        return [derecha_o, (medio, oy), (medio, ty), izquierda_t]
    if ruta == "abajo":
        y = max(oy + oh / 2, ty + th / 2) + 30
        return [abajo_o, (ox, y), (tx, y), abajo_t]
    if ruta == "frontera":
        y = plano.carriles[o.carril][0] + 14  # justo debajo del borde del carril, para que no se confunda con él
        return [arriba_o, (ox, y), (tx, y), abajo_t]
    if ruta == "volver":
        return [izquierda_o, (tx, oy), abajo_t]
    raise ValueError(f"Ruta desconocida: {ruta}")


def lados_usados(d: Diagrama, plano: Plano) -> dict[str, set[str]]:
    """Qué lados (arriba o abajo) de cada nodo usan los flujos, para no poner la etiqueta encima."""
    usados: dict[str, set[str]] = {n.id: set() for n in d.nodos}
    for i, f in enumerate(d.flujos):
        puntos = plano.rutas[i]
        for nodo_id, (px, py) in ((f.origen, puntos[0]), (f.destino, puntos[-1])):
            cx, cy = plano.centros[nodo_id]
            if abs(px - cx) < 1:
                usados[nodo_id].add("arriba" if py < cy else "abajo")
    return usados


def partir(texto: str, ancho: int) -> list[str]:
    lineas: list[str] = []
    for palabra in texto.split():
        if lineas and len(lineas[-1]) + 1 + len(palabra) <= ancho:
            lineas[-1] += " " + palabra
        else:
            lineas.append(palabra)
    return lineas


def partir_codigos(codigos: tuple[str, ...], ancho: int) -> list[str]:
    """Parte la lista de códigos sin dejar un separador suelto al inicio de una línea."""
    lineas: list[str] = []
    for codigo in codigos:
        if lineas and len(lineas[-1]) + 3 + len(codigo) <= ancho:
            lineas[-1] += " · " + codigo
        else:
            lineas.append(codigo)
    return lineas


# --- BPMN 2.0 ------------------------------------------------------------------------------------


def atributo(texto: str) -> str:
    return escape(texto, {'"': "&quot;", "\n": "&#10;"})


def nombre_bpmn(n: Nodo) -> str:
    return n.texto + ("\n" + " · ".join(n.codigos) if n.codigos else "")


def generar_bpmn(d: Diagrama, plano: Plano) -> str:
    salen: dict[str, list[str]] = {n.id: [] for n in d.nodos}
    llegan: dict[str, list[str]] = {n.id: [] for n in d.nodos}
    for i, f in enumerate(d.flujos):
        salen[f.origen].append(f"Flujo_{i}")
        llegan[f.destino].append(f"Flujo_{i}")

    proceso = [f'    <bpmn:laneSet id="Carriles">']
    for c, nombre in d.carriles:
        proceso.append(f'      <bpmn:lane id="Carril_{c}" name="{atributo(nombre)}">')
        proceso += [f"        <bpmn:flowNodeRef>{n.id}</bpmn:flowNodeRef>" for n in d.nodos if n.carril == c]
        proceso.append("      </bpmn:lane>")
    proceso.append("    </bpmn:laneSet>")
    for n in d.nodos:
        etiqueta = ETIQUETAS_BPMN[n.tipo]
        nombre = f' name="{atributo(nombre_bpmn(n))}"' if n.texto else ""
        proceso.append(f'    <bpmn:{etiqueta} id="{n.id}"{nombre}>')
        if n.codigos:
            proceso.append(f"      <bpmn:documentation>{escape(' · '.join(n.codigos))}</bpmn:documentation>")
        proceso += [f"      <bpmn:incoming>{f}</bpmn:incoming>" for f in llegan[n.id]]
        proceso += [f"      <bpmn:outgoing>{f}</bpmn:outgoing>" for f in salen[n.id]]
        if n.tipo in DEFINICIONES_BPMN:
            proceso.append(f'      <bpmn:{DEFINICIONES_BPMN[n.tipo]} id="Definicion_{n.id}" />')
        proceso.append(f"    </bpmn:{etiqueta}>")
    for i, f in enumerate(d.flujos):
        nombre = f' name="{atributo(f.etiqueta)}"' if f.etiqueta else ""
        proceso.append(f'    <bpmn:sequenceFlow id="Flujo_{i}"{nombre} sourceRef="{f.origen}" targetRef="{f.destino}" />')

    alto_total = plano.alto
    figuras = [
        f'      <bpmndi:BPMNShape id="Participante_di" bpmnElement="Participante" isHorizontal="true">',
        f'        <dc:Bounds x="{MARGEN}" y="{MARGEN}" width="{plano.ancho - 2 * MARGEN:.0f}" height="{alto_total:.0f}" />',
        "      </bpmndi:BPMNShape>",
    ]
    for c, _ in d.carriles:
        y, alto = plano.carriles[c]
        figuras += [
            f'      <bpmndi:BPMNShape id="Carril_{c}_di" bpmnElement="Carril_{c}" isHorizontal="true">',
            f'        <dc:Bounds x="{MARGEN + FRANJA}" y="{y:.0f}" width="{plano.ancho - 2 * MARGEN - FRANJA:.0f}" height="{alto:.0f}" />',
            "      </bpmndi:BPMNShape>",
        ]
    for n in d.nodos:
        (cx, cy), (w, h) = plano.centros[n.id], n.tamano
        marcador = ' isMarkerVisible="true"' if n.tipo == "decision" else ""
        figuras += [
            f'      <bpmndi:BPMNShape id="{n.id}_di" bpmnElement="{n.id}"{marcador}>',
            f'        <dc:Bounds x="{cx - w / 2:.0f}" y="{cy - h / 2:.0f}" width="{w}" height="{h}" />',
            "      </bpmndi:BPMNShape>",
        ]
    for i, f in enumerate(d.flujos):
        figuras.append(f'      <bpmndi:BPMNEdge id="Flujo_{i}_di" bpmnElement="Flujo_{i}">')
        figuras += [f'        <di:waypoint x="{x:.0f}" y="{y:.0f}" />' for x, y in plano.rutas[i]]
        if f.etiqueta:
            lx, ly = posicion_etiqueta_flujo(plano.rutas[i])
            figuras += ["        <bpmndi:BPMNLabel>", f'          <dc:Bounds x="{lx:.0f}" y="{ly - 12:.0f}" width="22" height="14" />',
                        "        </bpmndi:BPMNLabel>"]
        figuras.append("      </bpmndi:BPMNEdge>")

    return "\n".join([
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"'
        ' xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI"'
        ' xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI"'
        f' id="Definiciones_{d.archivo.replace("-", "_")}" targetNamespace="https://github.com/Aryannext/El-taller-ines"'
        ' exporter="scripts/generar_procesos.py" exporterVersion="1.0">',
        '  <bpmn:collaboration id="Colaboracion">',
        f'    <bpmn:participant id="Participante" name="{atributo(d.pool + " · " + d.fase)}" processRef="Proceso" />',
        "  </bpmn:collaboration>",
        f'  <bpmn:process id="Proceso" name="{atributo(d.fase)}" isExecutable="false">',
        *proceso,
        "  </bpmn:process>",
        '  <bpmndi:BPMNDiagram id="Diagrama">',
        '    <bpmndi:BPMNPlane id="Plano" bpmnElement="Colaboracion">',
        *figuras,
        "    </bpmndi:BPMNPlane>",
        "  </bpmndi:BPMNDiagram>",
        "</bpmn:definitions>",
        "",
    ])


def posicion_etiqueta_flujo(puntos: list[tuple[float, float]]) -> tuple[float, float]:
    (x0, y0), (x1, y1) = puntos[0], puntos[1]
    if abs(y0 - y1) < 1:
        return x0 + 8, y0 - 6
    return x0 + 7, (y0 + 18 if y1 > y0 else y0 - 8)


# --- SVG ------------------------------------------------------------------------------------------


def texto_svg(x: float, y: float, contenido: str, tamano: float = 12, color: str = COLORES["tinta"],
              peso: int = 400, ancla: str = "middle", extra: str = "") -> str:
    return (f'<text x="{x:.1f}" y="{y:.1f}" font-size="{tamano}" font-weight="{peso}" fill="{color}"'
            f' text-anchor="{ancla}"{extra}>{escape(contenido)}</text>')


def icono(tipo: str, x: float, y: float, color: str) -> str:
    """Marcador BPMN del tipo de tarea, con su esquina superior izquierda en (x, y)."""
    trazo = f'stroke="{color}" stroke-width="1.3" fill="none"'
    if tipo == "usuario":
        return (f'<circle cx="{x + 7}" cy="{y + 4.5}" r="3.2" {trazo} />'
                f'<path d="M{x + 1} {y + 14} q0 -6 6 -6 q6 0 6 6 z" {trazo} />')
    if tipo == "servicio":
        radios = "".join(
            f'<line x1="{x + 7 + 4.2 * c:.1f}" y1="{y + 7 + 4.2 * s:.1f}" x2="{x + 7 + 6.6 * c:.1f}" y2="{y + 7 + 6.6 * s:.1f}" {trazo} />'
            for c, s in ((1, 0), (0, 1), (-1, 0), (0, -1), (0.707, 0.707), (-0.707, 0.707), (0.707, -0.707), (-0.707, -0.707)))
        return radios + f'<circle cx="{x + 7}" cy="{y + 7}" r="4.2" {trazo} /><circle cx="{x + 7}" cy="{y + 7}" r="1.6" {trazo} />'
    if tipo == "envio":
        return (f'<rect x="{x}" y="{y + 2}" width="15" height="10" fill="{color}" />'
                f'<path d="M{x} {y + 2} l7.5 5.5 l7.5 -5.5" stroke="{COLORES["papel"]}" stroke-width="1.2" fill="none" />')
    # manual: una mano estilizada
    return (f'<path d="M{x + 1} {y + 8} h8 M{x + 3} {y + 5} h8 M{x + 4} {y + 11} h7 M{x + 1} {y + 8} v5 h9 q3 0 3 -3 v-5 q0 -3 -3 -3 h-6"'
            f' {trazo} stroke-linecap="round" stroke-linejoin="round" />')


def generar_svg(d: Diagrama, plano: Plano) -> str:
    uid = d.archivo
    partes = [
        f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {plano.ancho:.0f} {plano.alto + 2 * MARGEN:.0f}"'
        f' width="{plano.ancho:.0f}" height="{plano.alto + 2 * MARGEN:.0f}" font-family="{FUENTE}" role="img"'
        f' aria-label="{atributo(d.pool + " · " + d.fase)}">',
        f"<title>{escape(d.pool + ' · ' + d.fase)}</title>",
        f'<defs><marker id="flecha-{uid}" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8"'
        f' orient="auto-start-reverse"><path d="M0 0 L10 5 L0 10 z" fill="{COLORES["linea"]}" /></marker></defs>',
        f'<rect x="0" y="0" width="{plano.ancho:.0f}" height="{plano.alto + 2 * MARGEN:.0f}" fill="{COLORES["papel"]}" />',
    ]

    alto_pool = plano.alto
    ancho_pool = plano.ancho - 2 * MARGEN
    partes += [
        f'<rect x="{MARGEN}" y="{MARGEN}" width="{ancho_pool:.0f}" height="{alto_pool:.0f}" fill="{COLORES["papel"]}"'
        f' stroke="{COLORES["tinta"]}" stroke-width="1.5" />',
        f'<rect x="{MARGEN}" y="{MARGEN}" width="{FRANJA}" height="{alto_pool:.0f}" fill="{COLORES["tinta"]}" />',
        texto_svg(MARGEN + FRANJA / 2 + 4, MARGEN + alto_pool / 2, d.pool, 12, COLORES["papel"], 600,
                  extra=f' transform="rotate(-90 {MARGEN + FRANJA / 2 + 4} {MARGEN + alto_pool / 2:.1f})"'),
    ]
    for c, nombre in d.carriles:
        y, alto = plano.carriles[c]
        partes += [
            f'<rect x="{MARGEN + FRANJA}" y="{y:.0f}" width="{ancho_pool - FRANJA:.0f}" height="{alto:.0f}" fill="none"'
            f' stroke="{COLORES["tinta"]}" stroke-width="1" />',
            f'<rect x="{MARGEN + FRANJA}" y="{y:.0f}" width="{FRANJA}" height="{alto:.0f}" fill="{COLORES["franja"]}"'
            f' stroke="{COLORES["tinta"]}" stroke-width="1" />',
            texto_svg(MARGEN + FRANJA * 1.5 + 4, y + alto / 2, nombre, 11.5, COLORES["tinta"], 600,
                      extra=f' transform="rotate(-90 {MARGEN + FRANJA * 1.5 + 4} {y + alto / 2:.1f})"'),
        ]

    for i, f in enumerate(d.flujos):
        puntos = " ".join(f"{x:.1f},{y:.1f}" for x, y in plano.rutas[i])
        partes.append(f'<polyline points="{puntos}" fill="none" stroke="{COLORES["linea"]}" stroke-width="1.4"'
                      f' marker-end="url(#flecha-{uid})" />')
        if f.etiqueta:
            lx, ly = posicion_etiqueta_flujo(plano.rutas[i])
            partes.append(texto_svg(lx, ly, f.etiqueta, 11, COLORES["suave"], 600, "start"))

    usados = lados_usados(d, plano)
    for n in d.nodos:
        (cx, cy), (w, h) = plano.centros[n.id], n.tamano
        acento, fondo = COLORES["neutro_borde"], COLORES["neutro"]
        if n.codigos:
            clave = "problema" if d.proceso == "actual" else "solucion"
            acento, fondo = COLORES[clave], COLORES[f"{clave}_fondo"]

        if n.tipo in TAREAS:
            x, y = cx - w / 2, cy - h / 2
            partes.append(f'<rect x="{x:.1f}" y="{y:.1f}" width="{w}" height="{h}" rx="10" fill="{fondo}"'
                          f' stroke="{acento}" stroke-width="{1.8 if n.codigos else 1.3}" />')
            partes.append(icono(n.tipo, x + 7, y + 6, COLORES["tinta"]))
            lineas = partir(n.texto, 19)
            codigos = partir_codigos(n.codigos, 22)
            total = len(lineas) + len(codigos)
            base = max(y + 35, cy - (total - 1) * 13 / 2 + 4)  # la primera línea queda debajo del marcador
            for k, linea in enumerate(lineas):
                partes.append(texto_svg(cx, base + k * 13, linea, 12))
            for k, linea in enumerate(codigos):
                partes.append(texto_svg(cx, base + (len(lineas) + k) * 13, linea, 10.5, acento, 700))
            continue

        if n.tipo == "decision":
            r = w / 2
            partes.append(f'<path d="M{cx} {cy - r} L{cx + r} {cy} L{cx} {cy + r} L{cx - r} {cy} z" fill="{COLORES["papel"]}"'
                          f' stroke="{COLORES["tinta"]}" stroke-width="1.5" />')
            partes.append(f'<path d="M{cx - 8} {cy - 8} L{cx + 8} {cy + 8} M{cx + 8} {cy - 8} L{cx - 8} {cy + 8}"'
                          f' stroke="{COLORES["tinta"]}" stroke-width="3" />')
        else:
            r = w / 2
            grosor = 3.5 if n.tipo == "fin" else 1.5
            partes.append(f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="{COLORES["papel"]}" stroke="{COLORES["tinta"]}"'
                          f' stroke-width="{grosor}" />')
            if n.tipo in ("inicio_fecha", "mensaje"):
                partes.append(f'<circle cx="{cx}" cy="{cy}" r="{r - 3.5}" fill="none" stroke="{COLORES["tinta"]}" stroke-width="1.2" />')
            if n.tipo == "inicio_fecha":
                partes.append(f'<circle cx="{cx}" cy="{cy}" r="{r - 7}" fill="none" stroke="{COLORES["tinta"]}" stroke-width="1.2" />'
                              f'<path d="M{cx} {cy - 7} V{cy} L{cx + 5} {cy + 3}" stroke="{COLORES["tinta"]}" stroke-width="1.4" fill="none" />')
            if n.tipo == "mensaje":
                partes.append(f'<rect x="{cx - 7}" y="{cy - 5}" width="14" height="10" fill="none" stroke="{COLORES["tinta"]}" stroke-width="1.2" />'
                              f'<path d="M{cx - 7} {cy - 5} l7 5 l7 -5" stroke="{COLORES["tinta"]}" stroke-width="1.2" fill="none" />')

        if n.texto:
            lineas = partir(n.texto, 22)
            if n.tipo == "decision" and {"arriba", "abajo"} <= usados[n.id]:
                # Arriba y abajo tienen flujos: la pregunta va a la izquierda del rombo.
                lineas = partir(n.texto, 14)
                inicio = cy - (len(lineas) - 1) * 13 / 2 + 4
                partes += [texto_svg(cx - w / 2 - 8, inicio + k * 13, linea, 11.5, COLORES["tinta"], 600, "end")
                           for k, linea in enumerate(lineas)]
                continue
            if n.tipo == "decision" and "arriba" in usados[n.id] and "abajo" not in usados[n.id]:
                inicio = cy + h / 2 + 15
            elif n.tipo == "decision":
                inicio = cy - h / 2 - 8 - (len(lineas) - 1) * 13
            else:
                inicio = cy + h / 2 + 15
            partes += [texto_svg(cx, inicio + k * 13, linea, 11.5, COLORES["tinta"], 600 if n.tipo == "decision" else 400)
                       for k, linea in enumerate(lineas)]

    partes.append("</svg>")
    return "\n".join(partes)


# --- Página HTML ---------------------------------------------------------------------------------

ESTILO = """
  :root { --fondo: #f6f4ef; --papel: #fffdf8; --tinta: #23211d; --suave: #5d5850; --borde: #e3ddd1;
          --problema: #9c3d2e; --problema-fondo: #f8e9e4; --solucion: #4b6130; --solucion-fondo: #edf0e2; }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--fondo); color: var(--tinta); font: 15px/1.5 "Source Sans 3", system-ui, "Segoe UI", sans-serif; }
  .pagina { max-width: 1280px; margin: 0 auto; padding: 32px 24px 56px; display: flex; flex-direction: column; gap: 28px; }
  .eyebrow { font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: var(--suave); }
  h1 { margin: 2px 0 8px; font: 700 34px/1.1 "Source Serif 4", Georgia, serif; }
  h2 { margin: 0; font: 700 24px/1.2 "Source Serif 4", Georgia, serif; }
  h3 { margin: 0; font-size: 13px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
  .intro { max-width: 70ch; margin: 0; color: var(--suave); }
  .leyenda { display: flex; flex-wrap: wrap; gap: 8px 20px; font-size: 13px; color: var(--suave); }
  .leyenda span { display: inline-flex; align-items: center; gap: 6px; }
  .muestra { width: 16px; height: 12px; border-radius: 3px; border: 2px solid; }
  .fase { display: flex; flex-direction: column; gap: 14px; }
  figure { margin: 0; display: flex; flex-direction: column; gap: 8px; }
  figcaption { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 4px 16px; align-items: baseline; }
  figcaption a { font-size: 13px; color: var(--suave); }
  .hoy h3 { color: var(--problema); }
  .propuesto h3 { color: var(--solucion); }
  .lienzo { overflow-x: auto; background: var(--papel); border: 1px solid var(--borde); border-radius: 10px; }
  .lienzo svg { display: block; }
  a { color: inherit; }
  a:focus-visible { outline: 2px solid var(--tinta); outline-offset: 2px; }
  @media (max-width: 700px) { .pagina { padding: 20px 16px 40px; } h1 { font-size: 28px; } }
"""


def generar_pagina(diagramas: list[Diagrama], svgs: dict[str, str]) -> str:
    fases: dict[str, list[Diagrama]] = {}
    for d in diagramas:
        fases.setdefault(d.fase, []).append(d)

    secciones = []
    for numero, (fase, lista) in enumerate(fases.items(), start=1):
        figuras = []
        for d in lista:
            titulo = "Hoy" if d.proceso == "actual" else "Con El-taller-ines"
            figuras.append(
                f'<figure class="{"hoy" if d.proceso == "actual" else "propuesto"}">'
                f"<figcaption><h3>{titulo}</h3>"
                f'<span><a href="procesos/{d.archivo}.bpmn">Archivo BPMN</a> · <a href="procesos/{d.archivo}.svg">Imagen</a></span>'
                f'</figcaption><div class="lienzo">{svgs[d.archivo]}</div></figure>')
        secciones.append(f'<section class="fase"><h2>Fase {numero} · {escape(fase)}</h2>{"".join(figuras)}</section>')

    return f"""<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Proceso actual y propuesto · El-taller-ines</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@400;600;700&family=Source+Serif+4:opsz,wght@8..60,700&display=swap">
<style>{ESTILO}</style>
</head>
<body>
<div class="pagina">
<header>
  <div class="eyebrow">El-taller-ines · Sprint 1 · DOC-11</div>
  <h1>Proceso actual y proceso propuesto</h1>
  <p class="intro">Cada fase del trabajo del taller, en BPMN 2.0: primero cómo se hace hoy y después cómo se hará con el sistema.
  El detalle de cada paso, las fuentes y lo que no cambia están en <a href="proceso-actual-y-propuesto.md">proceso-actual-y-propuesto.md</a>.</p>
</header>
<div class="leyenda">
  <span><i class="muestra" style="border-color:var(--problema);background:var(--problema-fondo)"></i>Hoy: paso donde nace una causa o un efecto del árbol de problemas</span>
  <span><i class="muestra" style="border-color:var(--solucion);background:var(--solucion-fondo)"></i>Propuesto: paso sostenido por historias de usuario y reglas de negocio</span>
  <span>Marcas en las tareas: mano = manual, persona = la dueña en el sistema, engranaje = el sistema solo, sobre = envío</span>
</div>
{"".join(secciones)}
</div>
</body>
</html>
"""


def main() -> int:
    errores = validar(DIAGRAMAS)
    if errores:
        print("Errores:")
        for error in errores:
            print(f"- {error}")
        return 1

    CARPETA.mkdir(parents=True, exist_ok=True)
    svgs: dict[str, str] = {}
    for d in DIAGRAMAS:
        plano = distribuir(d)
        (CARPETA / f"{d.archivo}.bpmn").write_text(generar_bpmn(d, plano), encoding="utf-8", newline="\n")
        svgs[d.archivo] = generar_svg(d, plano)
        (CARPETA / f"{d.archivo}.svg").write_text('<?xml version="1.0" encoding="UTF-8"?>\n' + svgs[d.archivo] + "\n",
                                                   encoding="utf-8", newline="\n")
        print(f"{d.archivo}: {len(d.nodos)} nodos · {len(d.flujos)} flujos · {plano.ancho:.0f} × {plano.alto + 2 * MARGEN:.0f} px")
    PAGINA.write_text(generar_pagina(DIAGRAMAS, svgs), encoding="utf-8", newline="\n")
    print(f"Página: {PAGINA.relative_to(RAIZ)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
