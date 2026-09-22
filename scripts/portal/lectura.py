"""
Lee el proyecto para el portal: los elementos con código de los documentos, las clases del sistema, las
menciones a códigos en el código fuente, las pruebas de cada criterio y los diagramas.

Un elemento se define donde aparece por primera vez como encabezado (`### HU-11 · …`) o como la primera
celda de una fila (`| **RF-11** | …`). Todo lo demás son menciones, y de ellas salen las relaciones.
"""

from __future__ import annotations

import re
import unicodedata
from dataclasses import dataclass, field
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent.parent
DOCS = RAIZ / "docs"
SISTEMA = RAIZ / "sistema"

# El orden de la alternancia importa: RNF antes que RN, FN antes que F, CU antes que C, EP antes que E.
CODIGO = re.compile(
    r"\b(?:ADR-\d{3}|RNF-\d+|RN-\d+|RF-\d+|HU-\d+|CA-\d+\.\d+|CU-\d+|PT-\d+|OE-\d+|FN-\d+|EP-\d+|HT-\d+"
    r"|PM-\d+|DOC-\d+|C-\d+(?:\.\d+)?|E-\d+(?:\.\d+)?|M-\d+(?:\.\d+)?|F-\d+)\b"
)
# Menciones a la especificación anterior, como «(v1 RN-06)»: no son el RN-06 de hoy
MENCION_V1 = re.compile(r"\([^)]*\bv1\b[^)]*\)|modifica v1 RN-\d+")


@dataclass
class Tipo:
    prefijo: str
    singular: str
    plural: str
    seccion: str
    explicacion: str = ""  # qué es, en palabras de alguien que no conoce el proyecto
    ejemplo: str = ""  # un código para ver cómo luce


TIPOS = [
    Tipo("C", "Causa", "Causas", "Problema",
         "Una razón por la que el taller tiene problemas hoy. Sale del árbol de problemas.", "C-01.1"),
    Tipo("E", "Efecto", "Efectos", "Problema",
         "Una consecuencia de esos problemas: lo que el taller pierde o sufre por ellos.", "E-03"),
    Tipo("M", "Medio", "Medios", "Problema",
         "Lo que hay que lograr para eliminar una causa. Es la causa escrita en positivo, en el árbol de objetivos.", "M-01.1"),
    Tipo("FN", "Fin", "Fines", "Problema",
         "El beneficio que se consigue al lograr los medios: el efecto escrito en positivo.", "FN-03"),
    Tipo("OE", "Objetivo específico", "Objetivos específicos", "Problema",
         "Una meta concreta del proyecto. Agrupa varios medios.", "OE-01"),
    Tipo("F", "Fuente de requisitos", "Fuentes de requisitos", "Requisitos",
         "De dónde salió la información: una entrevista, un documento, el prototipo anterior.", "F-01"),
    Tipo("RF", "Requisito funcional", "Requisitos funcionales", "Requisitos",
         "Algo que el sistema debe permitir hacer. Por ejemplo, agregar una prenda a una orden.", "RF-11"),
    Tipo("RNF", "Requisito no funcional", "Requisitos no funcionales", "Requisitos",
         "Una condición de calidad: qué tan rápido, seguro, fácil o confiable debe ser el sistema.", "RNF-03"),
    Tipo("RN", "Regla de negocio", "Reglas de negocio", "Requisitos",
         "Una regla del taller que el sistema hace cumplir. Por ejemplo, que una orden no puede quedar sin prendas.", "RN-22"),
    Tipo("EP", "Épica", "Épicas", "Historias",
         "Un grupo de historias de usuario sobre el mismo tema, como «Pagos» o «Avisos».", "EP-03"),
    Tipo("HU", "Historia de usuario", "Historias de usuario", "Historias",
         "Algo que la dueña del taller necesita hacer, contado desde su punto de vista: «Como dueña, quiero…, para…».", "HU-11"),
    Tipo("CA", "Criterio de aceptación", "Criterios de aceptación", "Historias",
         "Un ejemplo concreto que dice cuándo una historia está bien hecha: «Dado… cuando… entonces…». Cada uno tiene su prueba.", "CA-11.1"),
    Tipo("CU", "Caso de uso", "Casos de uso", "Diseño",
         "El paso a paso de una tarea: qué hace la persona, qué responde el sistema y qué pasa si algo sale distinto.", "CU-13"),
    Tipo("PT", "Pantalla", "Pantallas", "Diseño",
         "Una pantalla del sistema, con su diseño (mockup) y cómo quedó construida.", "PT-09"),
    Tipo("ADR", "Decisión de arquitectura", "Decisiones de arquitectura", "Diseño",
         "Una decisión técnica importante, con las opciones que se compararon y por qué se eligió una.", "ADR-001"),
    Tipo("PM", "Prueba manual", "Pruebas manuales", "Pruebas",
         "Una prueba que hace una persona, no una máquina, con su registro de resultados. Por ejemplo, probar la app en un celular real.", "PM-04"),
    Tipo("HT", "Habilitador técnico", "Habilitadores técnicos", "Entrega",
         "Trabajo técnico necesario que no es una historia: el servidor, los respaldos, la app para Android.", "HT-07"),
    Tipo("DOC", "Entregable", "Entregables", "Entrega",
         "Un documento que se entrega en el proyecto formativo, como un manual o el plan de pruebas.", "DOC-22"),
]

# Palabras que aparecen en el portal sin ser un código
OTRAS_PALABRAS = [
    ("Must, Should, Could", "La prioridad de una historia. Must: se tiene que hacer. Should: debería hacerse si hay tiempo. Could: se puede hacer si sobra tiempo."),
    ("Sprint", "Un periodo corto de trabajo, de una o dos semanas, con un grupo de historias por terminar. Viene de Scrum, la forma de trabajo del proyecto."),
    ("Puntos", "Cuánto esfuerzo cuesta una historia, comparada con las demás. No son horas."),
    ("Mockup", "El dibujo de una pantalla antes de construirla, para acordar cómo se ve."),
    ("Prueba automática", "Un programa que revisa solo que el sistema haga lo que debe. Se corren todas con cada cambio."),
    ("Diagrama", "Un dibujo que explica cómo funciona una parte del sistema: sus pasos, sus estados o sus piezas."),
    ("Clase", "Una pieza del código con una tarea. En «Dónde está en el código» se enlaza al archivo exacto."),
    ("Orden", "Todo lo que un cliente deja en una visita al taller. Tiene un número, como #0042."),
]
TIPO = {t.prefijo: t for t in TIPOS}


def prefijo(codigo: str) -> str:
    return codigo.split("-")[0]


def orden(codigo: str) -> tuple:
    p, numero = codigo.split("-", 1)
    posicion = next((i for i, t in enumerate(TIPOS) if t.prefijo == p), 99)
    return (posicion, [int(x) for x in numero.split(".")])


def codigos_en(texto: str) -> list[str]:
    return list(dict.fromkeys(CODIGO.findall(MENCION_V1.sub("", texto))))


def relativa(ruta: Path) -> str:
    return ruta.relative_to(RAIZ).as_posix()


def ancla_github(titulo: str) -> str:
    """El ancla que GitHub le da a un encabezado: «HU-11 · Agregar» → «hu-11--agregar»."""
    texto = re.sub(r"[`*_]", "", titulo.strip().lower())
    return re.sub(r"[^\w\- ]", "", texto).replace(" ", "-")


def sin_tildes(texto: str) -> str:
    """Para direcciones: «Mapa de navegación» → «mapa-de-navegacion»."""
    plano = unicodedata.normalize("NFKD", texto).encode("ascii", "ignore").decode()
    return re.sub(r"[^a-z0-9]+", "-", plano.lower()).strip("-")


def celdas(linea: str) -> list[str]:
    return [c.strip() for c in linea.strip().strip("|").split("|")]


@dataclass
class Elemento:
    codigo: str
    titulo: str
    fuente: Path
    linea: int
    ancla: str = ""
    cuerpo: str = ""  # markdown, cuando se define con un encabezado
    campos: list[tuple[str, str]] = field(default_factory=list)  # cuando se define en una fila de tabla
    datos: dict = field(default_factory=dict)

    @property
    def tipo(self) -> Tipo:
        return TIPO[prefijo(self.codigo)]

    def texto(self) -> str:
        return self.cuerpo + "\n" + "\n".join(v for _, v in self.campos)


# ─── Elementos de los documentos ──────────────────────────────────────────────────────────────────

ENCABEZADO = re.compile(r"^(#{1,4}) (" + CODIGO.pattern + r") · (.+)$")
FILA = re.compile(r"^\|\s*(?:\*\*)?(" + CODIGO.pattern + r")(?:\*\*)?\s*([^|]*)\|")


def leer_documento(ruta: Path, elementos: dict[str, Elemento]) -> None:
    lineas = ruta.read_text(encoding="utf-8").splitlines()
    cabecera: list[str] = []
    titulo_seccion = ""
    for i, linea in enumerate(lineas):
        if linea.startswith("#"):
            titulo_seccion = linea.lstrip("#").strip()
        m = ENCABEZADO.match(linea)
        if m:
            nivel, codigo, titulo = len(m.group(1)), m.group(2), m.group(3).strip()
            if codigo in elementos:
                continue
            fin = next(
                (j for j in range(i + 1, len(lineas)) if re.match(rf"^#{{1,{nivel}}} ", lineas[j])),
                len(lineas),
            )
            elementos[codigo] = Elemento(
                codigo, titulo, ruta, i + 1, ancla_github(linea.lstrip("#")), cuerpo="\n".join(lineas[i + 1:fin]).strip()
            )
            continue

        if linea.startswith("|") and re.match(r"^\|[\s:|-]+\|$", lineas[i + 1] if i + 1 < len(lineas) else ""):
            cabecera = celdas(linea)
            continue
        m = FILA.match(linea)
        if m and cabecera:
            codigo = m.group(1)
            if codigo in elementos:
                continue
            valores = celdas(linea)
            resto = m.group(2).strip()
            # La fila se define con el código solo en la primera celda: «| **RF-11** | …» o «| **CA-11.1** Orden lista | …»
            if resto and not codigo.startswith("CA-"):
                continue
            campos = [(c, v) for c, v in zip(cabecera[1:], valores[1:]) if v and v not in ("—", "-")]
            titulo = resto or limpiar_titulo(valores[1] if len(valores) > 1 else codigo)
            elementos[codigo] = Elemento(codigo, titulo, ruta, i + 1, ancla_github(titulo_seccion), campos=campos)


def limpiar_titulo(texto: str) -> str:
    texto = re.sub(r"\[([^\]]+)\]\([^)]*\)", r"\1", texto)  # [Nombre](enlace) → Nombre
    texto = re.sub(r"[*`]", "", texto).strip()
    return texto if len(texto) <= 110 else texto[:107].rstrip() + "…"


def leer_archivo_completo(ruta: Path, elementos: dict[str, Elemento]) -> None:
    """Los ADR y las pruebas manuales: un archivo por elemento, con el código en el título o en el nombre."""
    texto = ruta.read_text(encoding="utf-8")
    primera = texto.splitlines()[0].lstrip("# ").strip()
    codigo = re.match(r"^(ADR-\d{3}|PM-\d+)", ruta.name).group(1)
    titulo = re.sub(r"^(ADR-\d{3}|PM-\d+)\s*[·:.-]?\s*", "", primera)
    cuerpo = "\n".join(texto.splitlines()[1:]).strip()
    elementos[codigo] = Elemento(codigo, titulo, ruta, 1, "", cuerpo=cuerpo)


# Dónde se define cada tipo. El orden decide quién gana si un código aparece en dos lados: la definición va primero.
FUENTES = [
    "01-problema/arbol-de-problemas.md",
    "01-problema/arbol-de-objetivos.md",
    "01-problema/objetivos-especificos.md",
    "02-requisitos/fuentes-de-requisitos.md",
    "02-requisitos/requisitos-funcionales.md",
    "02-requisitos/requisitos-no-funcionales.md",
    "02-requisitos/reglas-de-negocio.md",
    "02-requisitos/historias-de-usuario.md",
    "03-diseno/casos-de-uso/duena-del-taller/*.md",
    "03-diseno/casos-de-uso/cliente-y-whatsapp/*.md",
    "03-diseno/mockups/README.md",
    "00-scrum/product-backlog.md",
]
# Un código se define solo en el documento de su tipo: así «| **HU-11** |» del backlog no gana a la historia
DEFINE = {
    "01-problema/arbol-de-problemas.md": {"C", "E"},
    "01-problema/arbol-de-objetivos.md": {"M", "FN"},
    "01-problema/objetivos-especificos.md": {"OE"},
    "02-requisitos/fuentes-de-requisitos.md": {"F"},
    "02-requisitos/requisitos-funcionales.md": {"RF"},
    "02-requisitos/requisitos-no-funcionales.md": {"RNF"},
    "02-requisitos/reglas-de-negocio.md": {"RN"},
    "02-requisitos/historias-de-usuario.md": {"EP", "HU", "CA"},
    "03-diseno/casos-de-uso/duena-del-taller/*.md": {"CU"},
    "03-diseno/casos-de-uso/cliente-y-whatsapp/*.md": {"CU"},
    "03-diseno/mockups/README.md": {"PT"},
    "00-scrum/product-backlog.md": {"HT", "DOC"},
}


def leer_elementos() -> dict[str, Elemento]:
    elementos: dict[str, Elemento] = {}
    for patron in FUENTES:
        for ruta in sorted(DOCS.glob(patron)):
            encontrados: dict[str, Elemento] = {}
            leer_documento(ruta, encontrados)
            for codigo, elemento in encontrados.items():
                if prefijo(codigo) in DEFINE[patron] and codigo not in elementos:
                    elementos[codigo] = elemento
    for ruta in sorted((DOCS / "03-diseno" / "adr").glob("ADR-*.md")):
        leer_archivo_completo(ruta, elementos)
    for ruta in sorted((DOCS / "05-pruebas" / "pruebas-manuales").glob("PM-*.md")):
        leer_archivo_completo(ruta, elementos)
    # Un criterio es parte de su historia: se sabe a cuál por el número
    for codigo, e in elementos.items():
        if codigo.startswith("CA-"):
            e.datos["historia"] = "HU-" + codigo[3:].split(".")[0].zfill(2)
    return elementos


# ─── Datos del backlog y de la arquitectura ───────────────────────────────────────────────────────

def leer_backlog() -> dict[str, dict]:
    """Prioridad, puntos y sprint de cada historia y habilitador, y el estado de cada entregable."""
    texto = (DOCS / "00-scrum" / "product-backlog.md").read_text(encoding="utf-8")
    datos: dict[str, dict] = {}
    for linea in texto.splitlines():
        c = celdas(linea) if linea.startswith("|") else []
        if len(c) == 7 and re.fullmatch(r"\*\*(HU|HT)-\d+\*\*", c[1]):
            datos[c[1].strip("*")] = {"prioridad": c[3], "puntos": c[4], "sprint": c[5]}
        elif len(c) == 5 and re.fullmatch(r"\*\*DOC-\d+\*\*", c[0]):
            datos[c[0].strip("*")] = {"fase": c[2], "estado": c[3], "commit": c[4]}
    return datos


def tabla_despues_de(texto: str, titulo: str) -> list[list[str]]:
    inicio = texto.index(titulo)
    filas = []
    for linea in texto[inicio:].splitlines()[1:]:
        if linea.startswith("## "):
            break
        if linea.startswith("| ") and not re.match(r"^\|[\s:|-]+\|$", linea):
            filas.append(celdas(linea))
    return filas[1:]


def clases_en(texto: str) -> list[str]:
    return list(dict.fromkeys(re.findall(r"`([A-Z][A-Za-z]+)(?:@\w+)?`", texto)))


def leer_arquitectura() -> tuple[dict[str, dict], dict[str, dict]]:
    """Historia → pantallas, controlador y caso de uso; regla → capa y clases que la aplican."""
    texto = (DOCS / "03-diseno" / "arquitectura" / "README.md").read_text(encoding="utf-8")
    historias = {}
    for c in tabla_despues_de(texto, "## Historias y casos de uso"):
        codigo = c[0].strip("*")
        historias[codigo] = {
            "pantallas": codigos_en(c[1]),
            "controlador": c[2],
            "caso": c[3],
            "clases": clases_en(c[2] + " " + c[3]),
            # Un controlador atiende varias historias: para saber en qué diagramas aparece, cuenta solo su caso de uso
            "propias": clases_en(c[3]) or clases_en(c[2]),
        }
    reglas = {}
    for c in tabla_despues_de(texto, "## Dónde vive cada regla en el código"):
        codigo = c[0].strip("*")
        reglas[codigo] = {"capa": c[1], "donde": c[2], "como": c[3], "clases": clases_en(c[2] + " " + c[3])}
    return historias, reglas


# ─── Código del sistema ───────────────────────────────────────────────────────────────────────────

@dataclass
class Lugar:
    ruta: str  # relativa a la raíz del repositorio
    linea: int
    texto: str = ""


def leer_clases() -> dict[str, Lugar]:
    clases = {}
    for ruta in sorted((SISTEMA / "app").rglob("*.php")) + sorted((SISTEMA / "tests").rglob("*.php")):
        for i, linea in enumerate(ruta.read_text(encoding="utf-8").splitlines()):
            m = re.match(r"^(?:final |abstract |readonly )*(?:class|interface|trait|enum) ([A-Z]\w+)", linea)
            if m:
                clases.setdefault(m.group(1), Lugar(relativa(ruta), i + 1))
                break
    return clases


CARPETAS_DE_CODIGO = [
    "sistema/app", "sistema/routes", "sistema/resources/views", "sistema/database", "sistema/config",
    "sistema/public/js", "sistema/public/css", "sistema/public/sw.js", "despliegue", ".github/workflows",
]
EXTENSIONES = {".php", ".js", ".css", ".sh", ".yml", ".yaml", ".conf", ""}


def fragmento(linea: str) -> str:
    texto = linea.strip()
    texto = re.sub(r"^(//+|#+|\*+|/\*+|\{\{--|<!--)\s*", "", texto)
    texto = re.sub(r"\s*(\*/|--\}\}|-->)$", "", texto)
    return texto if len(texto) <= 150 else texto[:147] + "…"


def leer_menciones(carpetas: list[str]) -> dict[str, list[Lugar]]:
    """Cada línea de código que cita un código del proyecto, casi siempre en un comentario."""
    menciones: dict[str, list[Lugar]] = {}
    archivos: list[Path] = []
    for carpeta in carpetas:
        ruta = RAIZ / carpeta
        if ruta.is_file():
            archivos.append(ruta)
        elif ruta.is_dir():
            archivos += [p for p in sorted(ruta.rglob("*")) if p.is_file() and (p.suffix in EXTENSIONES or p.name.endswith(".blade.php"))]
    for ruta in archivos:
        try:
            lineas = ruta.read_text(encoding="utf-8").splitlines()
        except UnicodeDecodeError:
            continue
        for i, linea in enumerate(lineas):
            for codigo in codigos_en(linea):
                menciones.setdefault(codigo, []).append(Lugar(relativa(ruta), i + 1, fragmento(linea)))
    return menciones


def leer_metodos_de_prueba() -> dict[str, Lugar]:
    metodos = {}
    for ruta in sorted((SISTEMA / "tests").rglob("*.php")):
        for i, linea in enumerate(ruta.read_text(encoding="utf-8").splitlines()):
            m = re.search(r"function (test_\w+)\s*\(", linea)
            if m:
                metodos.setdefault(m.group(1), Lugar(relativa(ruta), i + 1, m.group(1)))
    return metodos


def leer_pruebas(metodos: dict[str, Lugar]) -> tuple[dict[str, list[Lugar]], list[str]]:
    """Criterio o regla → sus métodos de prueba, según los casos de prueba. Devuelve también las que no se encontraron."""
    pruebas: dict[str, list[Lugar]] = {}
    faltantes = []
    for ruta in sorted((DOCS / "05-pruebas" / "casos-de-prueba").glob("*.md")):
        for linea in ruta.read_text(encoding="utf-8").splitlines():
            m = re.match(r"^\| \*\*((?:CA|RN|RNF)-[\d.]+)\*\*", linea)
            if not m:
                continue
            for nombre in re.findall(r"`(test_\w+)`", linea):
                if nombre in metodos:
                    pruebas.setdefault(m.group(1), []).append(metodos[nombre])
                else:
                    faltantes.append(f"{m.group(1)}: {nombre}")
    return pruebas, faltantes


def leer_vistas() -> dict[str, Lugar]:
    vistas = {}
    for ruta in sorted((SISTEMA / "resources" / "views" / "pantallas").glob("pt-*.blade.php")):
        numero = re.match(r"pt-(\d+)", ruta.name).group(1)
        vistas.setdefault(f"PT-{numero}", Lugar(relativa(ruta), 1))
    return vistas


# ─── Diagramas ────────────────────────────────────────────────────────────────────────────────────

@dataclass
class Diagrama:
    id: str
    titulo: str
    fuente: Path
    linea: int
    ancla: str
    seccion: str  # el markdown de la sección que lo contiene
    mermaid: str = ""
    svg: Path | None = None


def leer_diagramas() -> list[Diagrama]:
    diagramas: list[Diagrama] = []
    for ruta in sorted(DOCS.rglob("*.md")):
        texto = ruta.read_text(encoding="utf-8")
        if "```mermaid" not in texto:
            continue
        lineas = texto.splitlines()
        encabezados = [(i, l) for i, l in enumerate(lineas) if re.match(r"^#{1,4} ", l)]
        for m in re.finditer(r"^```mermaid\n(.*?)^```", texto, flags=re.M | re.S):
            linea = texto[: m.start()].count("\n")
            previos = [(i, l) for i, l in encabezados if i < linea]
            i_enc, encabezado = previos[-1] if previos else (0, ruta.stem)
            titulo = encabezado.lstrip("#").strip()
            siguientes = [i for i, _ in encabezados if i > i_enc and len(_) - len(_.lstrip("#")) <= len(encabezado) - len(encabezado.lstrip("#"))]
            fin = siguientes[0] if siguientes else len(lineas)
            id_ = sin_tildes(f"{ruta.parent.name}-{ruta.stem}-{ancla_github(titulo)}")
            base, n = id_, 2
            while any(d.id == id_ for d in diagramas):
                id_, n = f"{base}-{n}", n + 1
            diagramas.append(Diagrama(id_, titulo, ruta, linea + 1, ancla_github(titulo), "\n".join(lineas[i_enc + 1:fin]), mermaid=m.group(1)))
    # Los diagramas de casos de uso son SVG generados: se muestran como imagen
    for ruta in sorted((DOCS / "03-diseno" / "casos-de-uso" / "diagramas").glob("*.svg")):
        texto = ruta.read_text(encoding="utf-8")
        titulo = "Casos de uso · " + ruta.stem.split("-", 1)[1].replace("-", " ").capitalize()
        diagramas.append(Diagrama("casos-de-uso-" + ruta.stem, titulo, ruta, 1, "", texto, svg=ruta))
    return diagramas


def capturas_reales() -> dict[str, list[Path]]:
    """Pantalla → sus capturas del sistema real, las del manual de usuario. Las que no son una PT se asignan a mano."""
    otras = {"12-agregar-prenda": "PT-06", "19-sin-conexion": None}
    capturas: dict[str, list[Path]] = {}
    for ruta in sorted((DOCS / "06-manuales" / "capturas").glob("*.png")):
        pantalla = otras.get(ruta.stem, f"PT-{ruta.stem[:2]}")
        if pantalla:
            capturas.setdefault(pantalla, []).append(ruta)
    return capturas


def capturas_de_mockup() -> dict[str, Path]:
    capturas = {}
    for ruta in sorted((DOCS / "03-diseno" / "mockups" / "capturas").glob("pt-*.png")):
        capturas["PT-" + ruta.stem[3:5]] = ruta
    return capturas
