"""Genera los casos de prueba y comprueba que el plan de pruebas no deje nada sin probar.

Lee las historias de usuario, las reglas de negocio, los requisitos no funcionales, la arquitectura, los mockups
y el propio plan, y:

- genera un archivo de casos de prueba por épica, donde cada criterio de aceptación es un caso con su clase y su
  método de prueba, y otro con las reglas de negocio, probadas en el nivel de la capa donde viven;
- escribe el resumen del plan y las tablas de recorrido de PM-05;
- comprueba que cada criterio con parte manual tenga un protocolo que lo verifique, y al revés;
- comprueba que cada requisito no funcional tenga su fila en el plan, y que las filas manuales citen su protocolo;
- comprueba que cada regla tenga una clase donde probarse y que las clases citadas existan en la arquitectura;
- comprueba que todo código citado en el plan y en los protocolos exista;
- cuenta cuántas pruebas planeadas ya están escritas en sistema/tests.

Uso: python scripts/generar_plan_de_pruebas.py
"""

from __future__ import annotations

import re
import sys
import unicodedata
from dataclasses import dataclass, field
from pathlib import Path

RAIZ = Path(__file__).resolve().parent.parent
DOCS = RAIZ / "docs"
PRUEBAS = DOCS / "05-pruebas"
PLAN = PRUEBAS / "plan-de-pruebas.md"
CASOS = PRUEBAS / "casos-de-prueba"
MANUALES = PRUEBAS / "pruebas-manuales"
HISTORIAS = DOCS / "02-requisitos" / "historias-de-usuario.md"
REGLAS = DOCS / "02-requisitos" / "reglas-de-negocio.md"
NO_FUNCIONALES = DOCS / "02-requisitos" / "requisitos-no-funcionales.md"
BACKLOG = DOCS / "00-scrum" / "product-backlog.md"
ARQUITECTURA = DOCS / "03-diseno" / "arquitectura" / "README.md"
MOCKUPS = DOCS / "03-diseno" / "mockups"
CODIGO_DE_PRUEBAS = RAIZ / "sistema" / "tests"

NIVELES = {"Unitaria", "Integración", "Funcionalidad", "Aislamiento", "Sistema"}
FORMAS_CRITERIO = {"Automática", "Manual", "Automática y manual"}
FORMAS_NO_FUNCIONAL = FORMAS_CRITERIO | {"Revisión"}
NIVEL_POR_CAPA = {
    "Dominio": "Unitaria", "Aplicación": "Integración", "Http": "Funcionalidad",
    "Modelos": "Aislamiento", "Base de datos": "Integración", "Infraestructura": "Integración",
}
CARPETA_POR_CAPA = {"Dominio": "Dominio", "Aplicación": "Aplicacion", "Http": "Http", "Infraestructura": "Infraestructura"}
PRUEBA_DE_AISLAMIENTO = "Feature/Aislamiento/AislamientoEntreNegociosTest.php"
PRUEBA_DEL_ESQUEMA = "Feature/BaseDeDatos/EsquemaTest.php"

CLASE = re.compile(r"`([A-Z][A-Za-z]+)`")
CODIGO = re.compile(r"\b(?:CA-\d+\.\d+|RNF-\d+|RN-\d+|HU-\d+|HT-\d+|PM-\d+|PT-\d+)\b")


@dataclass
class Criterio:
    codigo: str
    nombre: str
    dado: str
    cuando: str
    entonces: str
    historia: str


@dataclass
class Historia:
    codigo: str
    titulo: str
    epica: str
    prioridad: str = ""
    reglas: list[str] = field(default_factory=list)
    calidad: list[str] = field(default_factory=list)
    criterios: list[Criterio] = field(default_factory=list)


@dataclass
class Regla:
    codigo: str
    titulo: str
    seccion: str
    ejemplo: str


@dataclass
class Excepcion:
    nivel: str
    forma: str
    clase: str | None
    protocolo: str | None


@dataclass
class Protocolo:
    codigo: str
    nombre: str
    archivo: Path
    verifica: list[str]


# --- Lectura -----------------------------------------------------------------------------------

def celdas(linea: str) -> list[str]:
    return [c.strip() for c in linea.strip().strip("|").split("|")]


def seccion(texto: str, titulo: str) -> str:
    m = re.search(rf"^(#+) {re.escape(titulo)}\s*$", texto, re.M)
    if not m:
        raise SystemExit(f"Falta la sección «{titulo}» en el plan")
    resto = texto[m.end():]
    fin = re.search(rf"^#{{1,{len(m.group(1))}}} ", resto, re.M)
    return resto[: fin.start()] if fin else resto


def slug(texto: str) -> str:
    sin_tildes = "".join(c for c in unicodedata.normalize("NFD", texto) if unicodedata.category(c) != "Mn")
    return re.sub(r"[^a-z0-9]+", "_", sin_tildes.lower()).strip("_")


def numero(codigo: str) -> tuple[int, ...]:
    return tuple(int(p) for p in re.findall(r"\d+", codigo))


def leer_historias() -> tuple[list[tuple[str, str]], list[Historia]]:
    epicas: list[tuple[str, str]] = []
    historias: list[Historia] = []
    for linea in HISTORIAS.read_text(encoding="utf-8").splitlines():
        if m := re.match(r"^## (EP-\d+) · (.+)$", linea):
            epicas.append((m.group(1), m.group(2).strip()))
        elif m := re.match(r"^### (HU-\d+) · (.+)$", linea):
            historias.append(Historia(m.group(1), m.group(2).strip(), epicas[-1][0]))
        elif not historias:
            continue
        elif linea.startswith("**Requisitos:**"):
            historias[-1].reglas = re.findall(r"\bRN-\d+\b", linea)
            historias[-1].calidad = re.findall(r"\bRNF-\d+\b", linea)
        elif m := re.match(r"^\*\*Prioridad:\*\* (\w+)", linea):
            historias[-1].prioridad = m.group(1)
        elif m := re.match(r"^\| \*\*(CA-\d+\.\d+)\*\* ([^|]+)\|", linea):
            _, dado, cuando, entonces = celdas(linea)
            historias[-1].criterios.append(
                Criterio(m.group(1), m.group(2).strip(), dado, cuando, entonces, historias[-1].codigo))
    return epicas, historias


def leer_reglas() -> list[Regla]:
    reglas: list[Regla] = []
    titulo_seccion = ""
    texto = REGLAS.read_text(encoding="utf-8")
    for bloque in re.split(r"^(?=#{2,3} )", texto, flags=re.M):
        if m := re.match(r"^## (.+)$", bloque, re.M):
            titulo_seccion = m.group(1).strip()
        elif m := re.match(r"^### (RN-\d+) · (.+)$", bloque, re.M):
            ejemplo = re.search(r"^\*\*Ejemplo:\*\*\s*(.+)$", bloque, re.M)
            reglas.append(Regla(m.group(1), m.group(2).strip(), titulo_seccion, ejemplo.group(1).strip() if ejemplo else ""))
    return reglas


def leer_estructura(arquitectura: str) -> dict[str, tuple[str, ...]]:
    """Ruta de cada clase dentro del árbol de carpetas de la arquitectura."""
    bloque = re.search(r"<!-- estructura:inicio -->(.*?)<!-- estructura:fin -->", arquitectura, re.S).group(1)
    pila: list[str] = []
    rutas: dict[str, tuple[str, ...]] = {}
    for linea in bloque.splitlines():
        m = re.match(r"^([│├└─ ]*)(\S+)", linea)
        if not m or linea.startswith("```"):
            continue
        nivel = len(m.group(1)) // 4
        pila[nivel:] = [m.group(2).rstrip("/")]
        if re.fullmatch(r"[A-Z]\w+\.php", m.group(2)):
            rutas[m.group(2)[:-4]] = tuple(pila)
    return rutas


def filas(texto: str, prefijo: str) -> dict[str, list[str]]:
    patron = re.compile(rf"^\| \*\*({prefijo}-\d+(?:\.\d+)?)\*\* \|")
    return {m.group(1): celdas(l)[1:] for l in texto.splitlines() if (m := patron.match(l))}


def leer_protocolos() -> list[Protocolo]:
    protocolos = []
    for archivo in sorted(MANUALES.glob("PM-*.md")):
        texto = archivo.read_text(encoding="utf-8")
        titulo = re.search(r"^# (PM-\d+) · (.+)$", texto, re.M)
        verifica = re.search(r"^\*\*Verifica:\*\* ([^·\n]+)", texto, re.M)
        if not titulo or not verifica:
            raise SystemExit(f"{archivo.name}: falta el título «# PM-xx · Nombre» o la línea «**Verifica:**»")
        protocolos.append(Protocolo(titulo.group(1), titulo.group(2).strip(), archivo, CODIGO.findall(verifica.group(1))))
    return protocolos


# --- Clases de prueba --------------------------------------------------------------------------

def archivo_de_prueba(clase: str, rutas: dict[str, tuple[str, ...]]) -> str:
    partes = rutas[clase]
    capa, carpetas = partes[partes.index("app") + 1], partes[partes.index("app") + 2:-1]
    if capa == "Dominio":
        return "/".join(["Unit", "Dominio", *carpetas, f"{clase}Test.php"])
    if capa == "Aplicacion":
        return "/".join(["Feature", *carpetas, f"{clase}Test.php"])
    if capa in ("Http", "Infraestructura"):
        return f"Feature/{capa}/{clase}Test.php"
    return PRUEBA_DE_AISLAMIENTO


def metodo(codigo: str, nombre: str) -> str:
    return "test_" + slug(codigo) + "_" + slug(nombre)


def enlace_protocolo(protocolo: Protocolo, desde: Path) -> str:
    relativa = Path("..") / protocolo.archivo.relative_to(PRUEBAS) if desde.parent != PRUEBAS else protocolo.archivo.relative_to(PRUEBAS)
    return f"[{protocolo.codigo}]({relativa.as_posix()})"


def celda(texto: str) -> str:
    return texto.replace("|", "\\|")


# --- Generación --------------------------------------------------------------------------------

AVISO = ("> **Archivo generado** con `python scripts/generar_plan_de_pruebas.py` desde {fuentes} y el "
         "[plan de pruebas](../plan-de-pruebas.md). No se edita a mano: se corrige el documento de origen y se vuelve a generar.")


def escribir(ruta: Path, contenido: str) -> None:
    ruta.parent.mkdir(parents=True, exist_ok=True)
    ruta.write_text(contenido, encoding="utf-8", newline="\n")


def reemplazar_bloque(texto: str, nombre: str, contenido: str) -> str:
    patron = re.compile(rf"(<!-- {nombre}:inicio -->)(.*?)(<!-- {nombre}:fin -->)", re.S)
    if not patron.search(texto):
        raise SystemExit(f"Faltan los marcadores <!-- {nombre}:inicio/fin -->")
    return patron.sub(lambda m: f"{m.group(1)}\n\n{contenido}\n\n{m.group(3)}", texto)


def main() -> int:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")

    epicas, historias = leer_historias()
    reglas = leer_reglas()
    no_funcionales = re.findall(r"^\| \*\*(RNF-\d+)\*\* \|", NO_FUNCIONALES.read_text(encoding="utf-8"), re.M)
    arquitectura = ARQUITECTURA.read_text(encoding="utf-8")
    rutas = leer_estructura(arquitectura)
    filas_historias = filas(arquitectura, "HU")
    filas_reglas = filas(arquitectura, "RN")
    plan = PLAN.read_text(encoding="utf-8")
    excepciones_texto = filas(seccion(plan, "Criterios con otra forma de prueba"), "CA")
    no_funcionales_plan = filas(seccion(plan, "Cómo se prueba cada requisito no funcional"), "RNF")
    protocolos = {p.codigo: p for p in leer_protocolos()}
    pantallas = sorted(
        (re.search(r"<title>(PT-\d+) · (.+?)</title>", p.read_text(encoding="utf-8")).groups()
         for p in MOCKUPS.glob("pt-*.html")), key=lambda t: numero(t[0]))
    habilitadores = set(re.findall(r"^### (HT-\d+)", BACKLOG.read_text(encoding="utf-8"), re.M))

    criterios = {c.codigo: c for h in historias for c in h.criterios}
    historias_por_codigo = {h.codigo: h for h in historias}
    errores: list[str] = []

    # Excepciones de los criterios
    excepciones: dict[str, Excepcion] = {}
    for codigo, columnas in excepciones_texto.items():
        if len(columnas) != 5:
            errores.append(f"Plan, {codigo}: la fila de excepciones debe tener 6 columnas")
            continue
        nivel, forma, clase, protocolo, _ = columnas
        clase_ = CLASE.fullmatch(clase)
        excepciones[codigo] = Excepcion(nivel, forma, clase_.group(1) if clase_ else None,
                                        protocolo if protocolo != "—" else None)
        if codigo not in criterios:
            errores.append(f"Plan: la excepción {codigo} no es un criterio de las historias")
        if nivel not in NIVELES:
            errores.append(f"Plan, {codigo}: nivel «{nivel}» desconocido")
        if forma not in FORMAS_CRITERIO:
            errores.append(f"Plan, {codigo}: forma «{forma}» desconocida")
        if clase != "—" and (not clase_ or clase_.group(1) not in rutas):
            errores.append(f"Plan, {codigo}: la clase {clase} no está en la estructura de la arquitectura")
        tiene_manual = "anual" in forma
        if tiene_manual and protocolo not in protocolos:
            errores.append(f"Plan, {codigo}: su parte manual necesita un protocolo existente, no «{protocolo}»")
        if not tiene_manual and protocolo != "—":
            errores.append(f"Plan, {codigo}: es solo automática y no debe citar protocolo")
        if tiene_manual and protocolo in protocolos and codigo not in protocolos[protocolo].verifica:
            errores.append(f"{protocolo}: no incluye {codigo} en «Verifica»")

    # Caso de prueba de cada criterio
    planeados: set[str] = set()
    casos: dict[str, dict] = {}
    for h in historias:
        fila = filas_historias.get(h.codigo)
        if not fila:
            errores.append(f"Arquitectura: {h.codigo} no tiene fila en «Historias y casos de uso»")
            continue
        clases = CLASE.findall(fila[2]) or CLASE.findall(fila[1])
        if not clases or clases[0] not in rutas:
            errores.append(f"{h.codigo}: no se encontró la clase de su caso de uso en la arquitectura")
            continue
        for c in h.criterios:
            e = excepciones.get(c.codigo, Excepcion("Funcionalidad", "Automática", None, None))
            archivo = archivo_de_prueba(e.clase or clases[0], rutas)
            automatica = e.forma != "Manual"
            casos[c.codigo] = {
                "nivel": e.nivel, "forma": e.forma, "archivo": archivo, "por_defecto": e.clase is None,
                "metodo": metodo(c.codigo, c.nombre) if automatica else None,
                "protocolo": protocolos.get(e.protocolo) if e.protocolo else None,
            }
            if automatica:
                planeados.add(f"{archivo}::{casos[c.codigo]['metodo']}")
        casos[h.codigo] = {"archivo": archivo_de_prueba(clases[0], rutas)}

    # Caso de prueba de cada regla
    pruebas_de_reglas: dict[str, dict] = {}
    for r in reglas:
        fila = filas_reglas.get(r.codigo)
        if not fila:
            errores.append(f"Arquitectura: {r.codigo} no tiene fila en «Dónde vive cada regla en el código»")
            continue
        capa = fila[0]
        if capa not in NIVEL_POR_CAPA:
            errores.append(f"Arquitectura, {r.codigo}: capa «{capa}» desconocida")
            continue
        if capa == "Modelos":
            archivo = PRUEBA_DE_AISLAMIENTO
        elif capa == "Base de datos":
            archivo = PRUEBA_DEL_ESQUEMA
        else:
            de_la_capa = [c for c in CLASE.findall(fila[1])
                          if c in rutas and rutas[c][rutas[c].index("app") + 1] == CARPETA_POR_CAPA[capa]]
            if not de_la_capa:
                errores.append(f"Arquitectura, {r.codigo}: ninguna clase de la capa {capa} para probarla")
                continue
            archivo = archivo_de_prueba(de_la_capa[0], rutas)
        if not r.ejemplo:
            errores.append(f"Reglas, {r.codigo}: no tiene ejemplo para probarla")
        pruebas_de_reglas[r.codigo] = {"capa": capa, "nivel": NIVEL_POR_CAPA[capa], "archivo": archivo,
                                       "metodo": metodo(r.codigo, r.titulo)}
        planeados.add(f"{archivo}::{pruebas_de_reglas[r.codigo]['metodo']}")

    # Requisitos no funcionales
    for codigo in no_funcionales:
        if codigo not in no_funcionales_plan:
            errores.append(f"Plan: {codigo} no tiene fila en «Cómo se prueba cada requisito no funcional»")
    for codigo, columnas in no_funcionales_plan.items():
        if codigo not in no_funcionales:
            errores.append(f"Plan: {codigo} no existe en los requisitos no funcionales")
            continue
        if len(columnas) != 4:
            errores.append(f"Plan, {codigo}: la fila debe tener 5 columnas")
            continue
        forma, _, _, evidencia = columnas
        citados = re.findall(r"PM-\d+", evidencia)
        if forma not in FORMAS_NO_FUNCIONAL:
            errores.append(f"Plan, {codigo}: forma «{forma}» desconocida")
        if "anual" in forma and not citados:
            errores.append(f"Plan, {codigo}: tiene parte manual y su evidencia no cita un protocolo")
        for p in citados:
            if p not in protocolos:
                errores.append(f"Plan, {codigo}: el protocolo {p} no existe")
            elif codigo not in protocolos[p].verifica:
                errores.append(f"{p}: no incluye {codigo} en «Verifica»")

    # Protocolos: lo que dicen verificar debe apuntar de vuelta a ellos
    for p in protocolos.values():
        for codigo in p.verifica:
            if codigo.startswith("CA-"):
                if excepciones.get(codigo, Excepcion("", "", None, None)).protocolo != p.codigo:
                    errores.append(f"{p.codigo}: verifica {codigo}, pero el plan no le asigna ese protocolo")
            elif codigo.startswith("RNF-"):
                if p.codigo not in no_funcionales_plan.get(codigo, ["", "", "", ""])[-1]:
                    errores.append(f"{p.codigo}: verifica {codigo}, pero la fila del plan no lo cita como evidencia")
            else:
                errores.append(f"{p.codigo}: «Verifica» solo admite criterios y requisitos no funcionales, no {codigo}")
        if not p.verifica:
            errores.append(f"{p.codigo}: no verifica nada")

    # Métodos repetidos dentro de una misma clase
    todos = [f"{v['archivo']}::{v['metodo']}" for k, v in casos.items() if k.startswith("CA-") and v["metodo"]]
    todos += [f"{v['archivo']}::{v['metodo']}" for v in pruebas_de_reglas.values()]
    for repetido in sorted({m for m in todos if todos.count(m) > 1}):
        errores.append(f"Método de prueba repetido: {repetido}")

    # Códigos citados en el plan y los protocolos
    existentes = (set(criterios) | {r.codigo for r in reglas} | set(no_funcionales) | set(historias_por_codigo)
                  | habilitadores | set(protocolos) | {p for p, _ in pantallas})
    for ruta in [PLAN, *(p.archivo for p in protocolos.values())]:
        for codigo in sorted(set(CODIGO.findall(ruta.read_text(encoding="utf-8"))) - existentes, key=numero):
            errores.append(f"{ruta.name}: cita {codigo}, que no existe")

    if errores:
        print("Errores:")
        for error in errores:
            print(f"- {error}")
        return 1

    # Pruebas ya escritas
    escritas: set[str] = set()
    if CODIGO_DE_PRUEBAS.exists():
        for php in CODIGO_DE_PRUEBAS.rglob("*Test.php"):
            relativa = php.relative_to(CODIGO_DE_PRUEBAS).as_posix()
            escritas |= {f"{relativa}::{m}" for m in re.findall(r"function\s+(test_\w+)", php.read_text(encoding="utf-8"))}

    # --- Casos de prueba por épica
    archivos_epica: list[tuple[str, str, Path, int, int]] = []
    for codigo_epica, nombre_epica in epicas:
        de_la_epica = [h for h in historias if h.epica == codigo_epica]
        destino = CASOS / f"{codigo_epica[3:]}-{slug(nombre_epica).replace('_', '-')}.md"
        conteo = {"Automática": 0, "Automática y manual": 0, "Manual": 0}
        partes = []
        for h in de_la_epica:
            partes += [
                f"## {h.codigo} · {h.titulo}", "",
                f"**Prioridad:** {h.prioridad} · **Reglas:** {', '.join(h.reglas) or '—'} · "
                f"**Calidad:** {', '.join(h.calidad) or '—'} · **Clase de prueba:** `{casos[h.codigo]['archivo']}`", "",
                "| Caso | Situación | Resultado esperado | Nivel | Forma | Prueba |",
                "| --- | --- | --- | --- | --- | --- |",
            ]
            for c in h.criterios:
                caso = casos[c.codigo]
                conteo[caso["forma"]] += 1
                prueba = []
                if caso["metodo"]:
                    prueba.append(("" if caso["por_defecto"] else f"`{caso['archivo']}`<br>") + f"`{caso['metodo']}`")
                if caso["protocolo"]:
                    prueba.append(enlace_protocolo(caso["protocolo"], destino))
                partes.append(
                    f"| **{c.codigo}** {celda(c.nombre)} | Dado {celda(c.dado)}, cuando {celda(c.cuando)} | "
                    f"{celda(c.entonces)} | {caso['nivel']} | {caso['forma']} | {'<br>y '.join(prueba)} |")
            partes.append("")
        total = sum(conteo.values())
        encabezado = [
            f"# Casos de prueba · {codigo_epica} · {nombre_epica}", "",
            AVISO.format(fuentes="las [historias de usuario](../../02-requisitos/historias-de-usuario.md), la "
                                 "[arquitectura](../../03-diseno/arquitectura/README.md)"), "",
            "Cada criterio de aceptación es un caso de prueba con su mismo código. La clase de prueba de cada historia "
            "es la del caso de uso que la implementa; cuando un criterio usa otra, aparece junto al método. "
            "Las rutas son relativas a `sistema/tests/`.", "",
            f"**Resumen:** {len(de_la_epica)} historias · {total} casos · {conteo['Automática']} automáticos · "
            f"{conteo['Automática y manual']} automáticos y manuales · {conteo['Manual']} manuales.", "",
        ]
        escribir(destino, "\n".join(encabezado + partes).rstrip() + "\n")
        archivos_epica.append((codigo_epica, nombre_epica, destino, len(de_la_epica), total))

    # --- Casos de prueba de las reglas
    destino_reglas = CASOS / "09-reglas-de-negocio.md"
    historias_de_regla = {r.codigo: [h.codigo for h in historias if r.codigo in h.reglas] for r in reglas}
    por_nivel: dict[str, int] = {}
    partes = []
    seccion_actual = None
    for r in reglas:
        prueba = pruebas_de_reglas[r.codigo]
        por_nivel[prueba["nivel"]] = por_nivel.get(prueba["nivel"], 0) + 1
        if r.seccion != seccion_actual:
            seccion_actual = r.seccion
            partes += ["", f"## {r.seccion}", "",
                       "| Regla | Ejemplo que se prueba | Capa | Nivel | Prueba | Historias |",
                       "| --- | --- | --- | --- | --- | --- |"]
        partes.append(
            f"| **{r.codigo}** {celda(r.titulo)} | {celda(r.ejemplo)} | {prueba['capa']} | {prueba['nivel']} | "
            f"`{prueba['archivo']}`<br>`{prueba['metodo']}` | {', '.join(historias_de_regla[r.codigo]) or '—'} |")
    orden_niveles = ["Unitaria", "Integración", "Funcionalidad", "Aislamiento"]
    encabezado = [
        "# Casos de prueba · Reglas de negocio", "",
        AVISO.format(fuentes="las [reglas de negocio](../../02-requisitos/reglas-de-negocio.md), la "
                             "[arquitectura](../../03-diseno/arquitectura/README.md#dónde-vive-cada-regla-en-el-código)"), "",
        "Cada regla tiene al menos una prueba automática que usa su ejemplo (RNF-28). El nivel sale de la capa donde "
        "la arquitectura ubica la regla, y la columna de historias dice qué historias dejarían de necesitarla si se "
        "recortaran. Las rutas son relativas a `sistema/tests/`.", "",
        f"**Resumen:** {len(reglas)} reglas · " + " · ".join(
            f"{por_nivel[n]} de {n.lower()}" if n != "Unitaria" else f"{por_nivel[n]} unitarias"
            for n in orden_niveles if n in por_nivel) + ".",
    ]
    escribir(destino_reglas, "\n".join(encabezado + partes).rstrip() + "\n")

    # --- Resumen del plan
    formas_no_funcionales: dict[str, int] = {}
    for columnas in no_funcionales_plan.values():
        formas_no_funcionales[columnas[0]] = formas_no_funcionales.get(columnas[0], 0) + 1
    solo_ca = [v for k, v in casos.items() if k.startswith("CA-")]
    contar = lambda forma: sum(1 for v in solo_ca if v["forma"] == forma)
    escritas_planeadas = len(planeados & escritas)
    estado_codigo = (f"{escritas_planeadas} de {len(planeados)}" if CODIGO_DE_PRUEBAS.exists()
                     else f"0 de {len(planeados)}; el código empieza en el Sprint 3")
    clases_planeadas = {p.split("::")[0] for p in planeados}
    resumen = [
        "## Cobertura del plan", "",
        "> **Sección generada** por `generar_plan_de_pruebas.py`.", "",
        "| Qué se prueba | Casos | Automáticos | Automáticos y manuales | Manuales o por revisión |",
        "| --- | --- | --- | --- | --- |",
        f"| [Criterios de aceptación](casos-de-prueba/) | {len(solo_ca)} | {contar('Automática')} | "
        f"{contar('Automática y manual')} | {contar('Manual')} |",
        f"| [Reglas de negocio](casos-de-prueba/09-reglas-de-negocio.md) | {len(reglas)} | {len(reglas)} | 0 | 0 |",
        f"| [Requisitos no funcionales](#cómo-se-prueba-cada-requisito-no-funcional) | {len(no_funcionales_plan)} | "
        f"{formas_no_funcionales.get('Automática', 0)} | {formas_no_funcionales.get('Automática y manual', 0)} | "
        f"{formas_no_funcionales.get('Manual', 0) + formas_no_funcionales.get('Revisión', 0)} |", "",
        f"**Pruebas automáticas planeadas:** {len(planeados)} métodos en {len(clases_planeadas)} clases · "
        f"**Escritas en `sistema/tests/`:** {estado_codigo}.", "",
        "| Épica | Historias | Casos |", "| --- | --- | --- |",
        *[f"| [{c} · {n}]({d.relative_to(PRUEBAS).as_posix()}) | {h} | {t} |" for c, n, d, h, t in archivos_epica],
        f"| [Reglas de negocio]({destino_reglas.relative_to(PRUEBAS).as_posix()}) | — | {len(reglas)} |", "",
        "| Prueba manual | Verifica |", "| --- | --- |",
        *[f"| [{p.codigo} · {p.nombre}]({p.archivo.relative_to(PRUEBAS).as_posix()}) | {', '.join(p.verifica)} |"
          for p in protocolos.values()],
    ]
    escribir(PLAN, reemplazar_bloque(plan, "resumen", "\n".join(resumen)))

    # --- Tablas de recorrido de PM-05
    pm05 = protocolos["PM-05"].archivo
    texto = pm05.read_text(encoding="utf-8")
    must = [h for h in historias if h.prioridad == "Must"]
    recorrido = ["| Historia | Chrome para Android | Chrome | Edge |", "| --- | --- | --- | --- |",
                 *[f"| **{h.codigo}** {h.titulo} | | | |" for h in sorted(must, key=lambda h: numero(h.codigo))]]
    texto = reemplazar_bloque(texto, "recorrido", "\n".join(recorrido))
    tabla_pantallas = ["| Pantalla | Sin desplazamiento horizontal | Controles de 44 px | Lighthouse | Contraste | Igual al mockup |",
                       "| --- | --- | --- | --- | --- | --- |",
                       *[f"| **{p}** {n} | | | | | |" for p, n in pantallas]]
    texto = reemplazar_bloque(texto, "pantallas", "\n".join(tabla_pantallas))
    escribir(pm05, texto)

    print(f"{len(solo_ca)} criterios · {len(reglas)} reglas · {len(no_funcionales_plan)} requisitos no funcionales · "
          f"{len(protocolos)} pruebas manuales")
    print(f"{len(planeados)} pruebas automáticas planeadas en {len(clases_planeadas)} clases · escritas: {estado_codigo}")
    print("El plan cubre todos los criterios, reglas y requisitos no funcionales.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
