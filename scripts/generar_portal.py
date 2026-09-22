#!/usr/bin/env python3
"""
Genera el portal navegable del proyecto: una página por cada elemento con código (causas, requisitos, reglas,
historias, criterios, casos de uso, pantallas, decisiones, pruebas…), con sus relaciones, dónde está en el código,
cómo se probó y en qué diagramas aparece. También los diagramas y la especificación técnica.

    python scripts/generar_portal.py            # escribe portal/
    python scripts/generar_portal.py --salida otra/carpeta

Los documentos de docs/ son la fuente: el portal no se edita a mano, se regenera. CI lo publica en GitHub Pages
y la carpeta que genera se abre sin internet, directamente desde el navegador.

Necesita el paquete markdown (pip install markdown). Mermaid se descarga una vez y queda en .cache/.
"""

from __future__ import annotations

import argparse
import json
import os
import re
import shutil
import subprocess
import sys
import urllib.request
from collections import defaultdict
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent / "portal"))

from html import escape, unescape  # noqa: E402

import html_portal as H  # noqa: E402
import lectura as L  # noqa: E402
from lectura import DOCS, RAIZ, TIPO, TIPOS, Lugar, codigos_en, orden, prefijo, relativa  # noqa: E402


def e(texto: str) -> str:
    return escape(texto, quote=True)


MERMAID = "https://cdn.jsdelivr.net/npm/mermaid@11.4.1/dist/mermaid.min.js"
RECURSOS = Path(__file__).resolve().parent / "portal"

# Documentos que el portal muestra completos, con los códigos enlazados. El resto se enlaza en GitHub.
DOCUMENTOS = {
    "docs/04-especificacion-tecnica/README.md": "Especificación técnica",
    "docs/04-especificacion-tecnica/01-plataforma-y-dependencias.md": "Plataforma y dependencias",
    "docs/04-especificacion-tecnica/02-rutas.md": "Rutas",
    "docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md": "Validaciones y mensajes",
    "docs/04-especificacion-tecnica/04-datos-y-modelos.md": "Datos y modelos",
    "docs/04-especificacion-tecnica/05-avisos-fotos-y-reloj.md": "Avisos, fotos y reloj",
    "docs/04-especificacion-tecnica/06-seguridad.md": "Seguridad",
    "docs/04-especificacion-tecnica/07-despliegue-y-operacion.md": "Despliegue y operación",
    "docs/04-especificacion-tecnica/08-convenciones-de-codigo.md": "Convenciones de código",
    "docs/03-diseno/arquitectura/README.md": "Arquitectura",
    "docs/03-diseno/modelo-de-datos/README.md": "Modelo de datos",
    "docs/05-pruebas/plan-de-pruebas.md": "Plan de pruebas",
    "docs/06-manuales/manual-de-usuario.md": "Manual de usuario",
    "docs/06-manuales/user-manual.md": "User manual",
    "docs/06-manuales/manual-tecnico.md": "Manual técnico",
    "docs/06-manuales/technical-manual.md": "Technical manual",
}
GRUPOS_DE_DOCUMENTOS = [
    ("Especificación técnica", [d for d in DOCUMENTOS if "04-especificacion" in d]),
    ("Documentos de diseño", ["docs/03-diseno/arquitectura/README.md", "docs/03-diseno/modelo-de-datos/README.md"]),
    ("Pruebas y manuales", ["docs/05-pruebas/plan-de-pruebas.md", "docs/06-manuales/manual-de-usuario.md", "docs/06-manuales/user-manual.md",
                            "docs/06-manuales/manual-tecnico.md", "docs/06-manuales/technical-manual.md"]),
]

# El recorrido para la sustentación: un hilo del problema a la prueba. Los que no existan se omiten.
RECORRIDO = [
    ("C-01.1", "El problema: nada queda anotado y todo depende de la memoria."),
    ("M-01.1", "El medio que lo resuelve: registrar cada prenda con sus datos."),
    ("OE-01", "El objetivo específico que agrupa ese medio."),
    ("RF-11", "Un requisito funcional que nace de ese objetivo."),
    ("HU-11", "La historia que lo cumple, con sus criterios de aceptación."),
    ("RN-22", "Una regla de negocio que la historia respeta, y dónde se hace cumplir en el código."),
    ("CU-13", "El caso de uso: el flujo paso a paso y la clase que lo implementa."),
    ("PT-09", "La pantalla, en el mockup y en el sistema real."),
    ("CA-11.1", "Un criterio de aceptación y la prueba automática que lo comprueba."),
    ("PM-04", "La prueba manual en el celular real, con su registro."),
]


# ─── Utilidades ──────────────────────────────────────────────────────────────────────────────────

def git(*args: str) -> str:
    try:
        return subprocess.run(["git", *args], cwd=RAIZ, capture_output=True, text=True, check=True).stdout.strip()
    except (OSError, subprocess.CalledProcessError):
        return ""


def repositorio() -> str:
    remoto = git("remote", "get-url", "origin") or "https://github.com/Aryannext/El-taller-ines.git"
    m = re.search(r"github\.com[:/](.+?)(?:\.git)?$", remoto)
    return f"https://github.com/{m.group(1)}" if m else remoto


def escribir(ruta: Path, contenido: str) -> None:
    ruta.parent.mkdir(parents=True, exist_ok=True)
    # Siempre LF: en Windows, Python escribiría CRLF y cada regeneración cambiaría todo el archivo
    ruta.write_text(contenido, encoding="utf-8", newline="\n")


def mermaid_local() -> Path | None:
    cache = RAIZ / ".cache" / "mermaid-11.4.1.min.js"
    if not cache.exists():
        try:
            cache.parent.mkdir(exist_ok=True)
            with urllib.request.urlopen(MERMAID, timeout=60) as respuesta:
                cache.write_bytes(respuesta.read())
        except OSError as error:
            print(f"Aviso: no se pudo descargar Mermaid ({error}); los diagramas se verán como texto.")
            return None
    return cache


def secciones_de(ruta: Path) -> list[tuple[str, int, str]]:
    """Las secciones de un markdown: título, línea y texto, para saber dónde se menciona cada código."""
    lineas = ruta.read_text(encoding="utf-8").splitlines()
    marcas = [(i, l.lstrip("#").strip()) for i, l in enumerate(lineas) if re.match(r"^#{1,4} ", l)]
    if not marcas or marcas[0][0] > 0:
        marcas.insert(0, (0, ruta.stem))
    resultado = []
    for n, (i, titulo) in enumerate(marcas):
        fin = marcas[n + 1][0] if n + 1 < len(marcas) else len(lineas)
        resultado.append((titulo, i + 1, "\n".join(lineas[i:fin])))
    return resultado


# ─── El portal ───────────────────────────────────────────────────────────────────────────────────

class Portal:
    def __init__(self, salida: Path):
        self.salida = salida
        self.elementos = L.leer_elementos()
        self.conocidos = set(self.elementos)
        self.backlog = L.leer_backlog()
        self.arq_historias, self.arq_reglas = L.leer_arquitectura()
        self.clases = L.leer_clases()
        self.en_codigo = L.leer_menciones(L.CARPETAS_DE_CODIGO)
        self.en_pruebas = L.leer_menciones(["sistema/tests"])
        self.metodos = L.leer_metodos_de_prueba()
        self.pruebas, self.pruebas_faltantes = L.leer_pruebas(self.metodos)
        self.vistas = L.leer_vistas()
        self.diagramas = L.leer_diagramas()
        self.capturas = L.capturas_reales()
        self.mockups = L.capturas_de_mockup()
        self.avisos: list[str] = []

        paginas_doc = {ruta: f"doc/{Path(ruta).parent.name}-{Path(ruta).stem}.html".lower() for ruta in DOCUMENTOS}
        self.paginas_doc = paginas_doc
        ref = os.environ.get("GITHUB_SHA") or git("rev-parse", "HEAD") or "main"
        titulos = {c: f"{el.tipo.singular} · {L.limpiar_titulo(el.titulo)}" for c, el in self.elementos.items()}
        self.enlazador = H.Enlazador(self.conocidos, paginas_doc, repositorio(), ref, titulos)
        self.archivos_copiados: set[str] = set()
        self.relacionar()

    # --- Relaciones -------------------------------------------------------------------------------

    def relacionar(self) -> None:
        self.salientes: dict[str, list[str]] = {}
        self.entrantes: dict[str, list[str]] = defaultdict(list)
        desconocidos: dict[str, set[str]] = defaultdict(set)
        for codigo, elemento in self.elementos.items():
            citados = []
            for otro in codigos_en(elemento.texto()):
                if otro == codigo:
                    continue
                if otro in self.conocidos:
                    citados.append(otro)
                else:
                    desconocidos[otro].add(codigo)
            self.salientes[codigo] = citados
            for otro in citados:
                self.entrantes[otro].append(codigo)
        # Un criterio es parte de su historia aunque no la nombre
        for codigo, elemento in self.elementos.items():
            historia = elemento.datos.get("historia")
            if historia in self.elementos and codigo not in self.salientes[historia]:
                self.salientes[historia].append(codigo)
                self.entrantes[codigo].append(historia)
        for codigo, donde in sorted(desconocidos.items(), key=lambda x: orden(x[0]) if prefijo(x[0]) in TIPO else (99, [0])):
            self.avisos.append(f"{codigo} se cita en {', '.join(sorted(donde)[:4])} pero no está definido en ningún documento")

        # Dónde se menciona cada código en la documentación, fuera de su propia definición
        self.en_documentos: dict[str, list[tuple[str, str, int, str]]] = defaultdict(list)
        for ruta in sorted(DOCS.rglob("*.md")):
            for titulo, linea, texto in secciones_de(ruta):
                for codigo in codigos_en(texto):
                    if codigo in self.conocidos:
                        self.en_documentos[codigo].append((relativa(ruta), titulo, linea, L.ancla_github(titulo)))

        # Los diagramas en que aparece cada código, por su código o por una clase que lo implementa
        self.en_diagramas: dict[str, list[L.Diagrama]] = defaultdict(list)
        self.clases_de_diagrama: dict[str, list[str]] = {}
        for d in self.diagramas:
            texto = d.seccion + "\n" + d.mermaid
            clases = [c for c in self.clases if re.search(rf"\b{c}\b", d.mermaid or texto)]
            self.clases_de_diagrama[d.id] = clases
            for codigo in codigos_en(texto):
                if codigo in self.conocidos and d not in self.en_diagramas[codigo]:
                    self.en_diagramas[codigo].append(d)
            for codigo in self.conocidos:
                propias = self.arq_historias[codigo]["propias"] if codigo in self.arq_historias else self.clases_que_implementan(codigo)
                propias = [c for c in propias if not c.endswith("Controller")]
                if set(propias) & set(clases) and d not in self.en_diagramas[codigo]:
                    self.en_diagramas[codigo].append(d)

    def clases_que_implementan(self, codigo: str) -> list[str]:
        p = prefijo(codigo)
        if p == "HU":
            return self.arq_historias.get(codigo, {}).get("clases", [])
        if p == "RN":
            return self.arq_reglas.get(codigo, {}).get("clases", [])
        if p == "CU":
            fila = next((v for c, v in self.elementos[codigo].campos if c == "Implementa"), "")
            return L.clases_en(fila) or L.clases_en(self.elementos[codigo].cuerpo.split("\n\n")[0])
        return []

    def estado_de_historia(self, codigo: str) -> tuple[str, str, str]:
        """(etiqueta, clase, explicación). Se considera construida si tiene criterios con prueba automática."""
        criterios = [c for c in self.salientes.get(codigo, []) if c.startswith("CA-") and self.elementos[c].datos.get("historia") == codigo]
        probados = [c for c in criterios if self.pruebas.get(c)]
        if probados:
            return "Construida", "exito", f"{len(probados)} de {len(criterios)} criterios con prueba automática"
        return "Sin construir", "aviso", "Ningún criterio tiene prueba automática todavía"

    # --- Piezas de las páginas --------------------------------------------------------------------

    def titulo_corto(self, codigo: str) -> str:
        return L.limpiar_titulo(self.elementos[codigo].titulo)

    def lista_de(self, codigos: list[str], raiz: str) -> str:
        items = "".join(
            f'<li><a href="{raiz}{self.enlazador.elemento(c)}"><span class="codigo">{c}</span></a>'
            f'<span class="sub">{e(self.titulo_corto(c))}</span></li>'
            for c in sorted(dict.fromkeys(codigos), key=orden)
        )
        return f'<ul class="lista-enlaces">{items}</ul>'

    def agrupados(self, codigos: list[str], raiz: str) -> str:
        grupos: dict[str, list[str]] = defaultdict(list)
        for c in codigos:
            grupos[prefijo(c)].append(c)
        bloques = []
        for tipo in TIPOS:
            if tipo.prefijo in grupos:
                nombre = tipo.plural if len(grupos[tipo.prefijo]) > 1 else tipo.singular
                bloques.append(f'<div><h3><span class="sigla">{tipo.prefijo}</span>{e(nombre)}</h3>{self.lista_de(grupos[tipo.prefijo], raiz)}</div>')
        return f'<div class="relaciones">{"".join(bloques)}</div>'

    def lugares(self, lugares: list[Lugar], limite: int = 40) -> str:
        por_archivo: dict[str, list[Lugar]] = defaultdict(list)
        for lugar in lugares:
            por_archivo[lugar.ruta].append(lugar)
        items = []
        for ruta, del_archivo in list(por_archivo.items())[:limite]:
            lineas = "".join(
                f'<li><a href="{e(self.enlazador.github(ruta, l.linea))}">línea {l.linea}</a><span>{e(l.texto)}</span></li>'
                for l in del_archivo[:6]
            )
            mas = f"<li><span>… y {len(del_archivo) - 6} más</span></li>" if len(del_archivo) > 6 else ""
            items.append(f'<li><a class="archivo" href="{e(self.enlazador.github(ruta))}">{e(ruta)}</a><ul class="lineas">{lineas}{mas}</ul></li>')
        return f'<ul class="codigo-fuente">{"".join(items)}</ul>'

    def clase_enlazada(self, nombre: str) -> str:
        lugar = self.clases.get(nombre)
        if not lugar:
            return f"<code>{e(nombre)}</code> <span class=\"etiqueta aviso\">no existe en el código</span>"
        return f'<a href="{e(self.enlazador.github(lugar.ruta, lugar.linea))}"><code>{e(nombre)}</code></a> <span class="sub">{e(lugar.ruta)}</span>'

    def copiar(self, ruta: Path) -> str:
        """Copia un archivo del repositorio al portal y devuelve su dirección dentro de él."""
        destino = "archivos/" + relativa(ruta)
        if destino not in self.archivos_copiados:
            (self.salida / destino).parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(ruta, self.salida / destino)
            self.archivos_copiados.add(destino)
        return destino

    def html_de(self, texto: str, fuente: Path, raiz: str, propio: str = "") -> str:
        convertido = H.a_html(texto, fuente, raiz, self.enlazador, propio)
        return self.imagenes(convertido, fuente, raiz)

    def imagenes(self, fragmento: str, fuente: Path, raiz: str) -> str:
        def cambiar(m: re.Match) -> str:
            src = unescape(m.group(1))
            if re.match(r"^(https?:|data:)", src):
                return m.group(0)
            ruta = (fuente.parent / src).resolve()
            if ruta.is_file():
                return f'src="{raiz}{self.copiar(ruta)}"'
            return m.group(0)

        return re.sub(r'src="([^"]+)"', cambiar, fragmento)

    def menu(self, raiz: str, actual: str) -> str:
        cuenta = defaultdict(int)
        for c in self.elementos:
            cuenta[prefijo(c)] += 1

        def enlace(url: str, texto: str, numero: int | None = None, sigla: str = "") -> str:
            marca = ' aria-current="page"' if url == actual else ""
            n = f'<span class="cuenta">{numero}</span>' if numero is not None else ""
            s = f'<span class="sigla">{sigla}</span>' if sigla else ""
            return f'<li><a href="{raiz}{url}"{marca}><span>{s}{e(texto)}</span>{n}</a></li>'

        bloques = [f'<ul>{enlace("index.html", "Inicio")}{enlace("glosario.html", "Glosario: qué significa cada sigla")}</ul>']
        for seccion in dict.fromkeys(t.seccion for t in TIPOS):
            items = "".join(enlace(f"t/{t.prefijo}.html", t.plural, cuenta[t.prefijo], t.prefijo) for t in TIPOS if t.seccion == seccion and cuenta[t.prefijo])
            if seccion == "Diseño":
                items += enlace("t/diagramas.html", "Diagramas", len(self.diagramas))
            bloques.append(f"<h2>{e(seccion)}</h2><ul>{items}</ul>")
        for grupo, rutas in GRUPOS_DE_DOCUMENTOS:
            items = "".join(enlace(self.paginas_doc[r], DOCUMENTOS[r]) for r in rutas if (RAIZ / r).exists())
            bloques.append(f"<h2>{e(grupo)}</h2><ul>{items}</ul>")
        return "".join(bloques)

    def pie(self) -> str:
        return (f'Generado con <code>python scripts/generar_portal.py</code> desde los documentos del repositorio, '
                f'versión <a href="{self.enlazador.repositorio}/commit/{self.enlazador.ref}">{self.enlazador.ref[:7]}</a>. '
                f"No se edita a mano: se corrige el documento de origen y se vuelve a generar.")

    def escribir_pagina(self, url: str, titulo: str, contenido: str, en_menu: str | None = None) -> None:
        raiz = "../" * url.count("/")
        escribir(self.salida / url, H.pagina(titulo, raiz, self.menu(raiz, en_menu or url), contenido, self.pie()))

    # --- Página de un elemento --------------------------------------------------------------------

    def pagina_elemento(self, codigo: str) -> None:
        el = self.elementos[codigo]
        url = self.enlazador.elemento(codigo)
        raiz = "../"
        tipo = el.tipo
        partes = [
            f'<div class="migas"><a href="{raiz}index.html">Inicio</a> › <a href="{raiz}t/{tipo.prefijo}.html">{e(tipo.plural)}</a></div>',
            f'<h1 class="titulo"><span class="codigo-grande">{codigo}</span><span>{e(el.titulo)}</span></h1>',
            f'<div class="etiquetas">{self.etiquetas(codigo)}</div>',
            # Quien llega sin conocer el proyecto sabe primero qué clase de cosa está viendo
            f'<p class="tipo-explicado"><span class="sigla">{tipo.prefijo}</span><span><strong>{e(tipo.singular)}:</strong> '
            f'{e(tipo.explicacion)} <a href="{raiz}glosario.html">Ver todas las siglas</a></span></p>',
        ]

        # Qué dice
        if el.cuerpo:
            cuerpo = self.html_de(el.cuerpo, el.fuente, raiz, codigo)
        else:
            filas = "".join(
                f"<dt>{e(c)}</dt><dd>{self.imagenes(H.en_linea(v, el.fuente, raiz, self.enlazador, codigo), el.fuente, raiz)}</dd>"
                for c, v in el.campos
            )
            cuerpo = f'<dl class="campos">{filas}</dl>'
        fuente = self.enlazador.github(relativa(el.fuente), el.linea, el.ancla)
        partes.append(
            f'<section class="tarjeta"><h2>Qué dice <span class="nota">· tomado de <a href="{e(fuente)}">{e(relativa(el.fuente))}</a></span></h2>'
            f'<div class="texto">{cuerpo}</div></section>'
        )

        # Cómo se ve
        capturas = self.capturas_de(codigo, raiz)
        if capturas:
            partes.append(f'<section class="tarjeta"><h2>Cómo se ve <span class="nota">· la pantalla real y su diseño</span></h2><div class="capturas">{capturas}</div></section>')

        # Relaciones
        salientes = self.salientes.get(codigo, [])
        entrantes = self.entrantes.get(codigo, [])
        if salientes:
            partes.append(f'<section class="tarjeta"><h2>Qué menciona <span class="nota">· lo que nombra su texto, agrupado por tipo</span></h2>{self.agrupados(salientes, raiz)}</section>')
        if entrantes:
            partes.append(f'<section class="tarjeta"><h2>Quién lo menciona <span class="nota">· los elementos que nombran a {codigo}</span></h2>{self.agrupados(entrantes, raiz)}</section>')

        # Dónde está en el código
        codigo_html = self.en_el_codigo(codigo, raiz)
        if codigo_html:
            partes.append(f'<section class="tarjeta"><h2>Dónde está en el código <span class="nota">· cada enlace abre el archivo en GitHub</span></h2>{codigo_html}</section>')

        # Cómo se prueba
        pruebas_html = self.como_se_prueba(codigo)
        if pruebas_html:
            partes.append(f'<section class="tarjeta"><h2>Cómo se comprueba que funciona <span class="nota">· las pruebas automáticas</span></h2>{pruebas_html}</section>')

        # Diagramas
        diagramas = self.en_diagramas.get(codigo, [])
        if diagramas:
            items = "".join(f'<li><a href="{raiz}d/{d.id}.html">{e(d.titulo)}</a> <span class="sub">{e(relativa(d.fuente))}</span></li>' for d in diagramas)
            partes.append(f'<section class="tarjeta"><h2>En qué diagramas aparece</h2><ul class="lista-enlaces">{items}</ul></section>')

        # Dónde se menciona en la documentación
        menciones = [m for m in self.en_documentos.get(codigo, []) if not (m[0] == relativa(el.fuente) and m[2] <= el.linea <= m[2] + 400 and m[1].startswith(codigo))]
        if menciones:
            items = []
            vistos = set()
            for ruta, titulo, linea, ancla in menciones:
                if (ruta, titulo) in vistos:
                    continue
                vistos.add((ruta, titulo))
                destino = f"{raiz}{self.paginas_doc[ruta]}#{ancla}" if ruta in self.paginas_doc else self.enlazador.github(ruta, ancla=ancla)
                items.append(f'<li><a href="{e(destino)}">{e(L.limpiar_titulo(titulo))}</a> <span class="sub">{e(ruta)}</span></li>')
            partes.append(
                f'<section class="tarjeta"><h2>Otros documentos que lo mencionan <span class="nota">· {len(items)} secciones</span></h2>'
                f'<ul class="lista-enlaces">{"".join(items[:60])}</ul></section>'
            )

        self.escribir_pagina(url, f"{codigo} · {self.titulo_corto(codigo)}", "".join(partes), f"t/{tipo.prefijo}.html")

    def etiquetas(self, codigo: str) -> str:
        el = self.elementos[codigo]
        marcas = [f'<span class="etiqueta">{e(el.tipo.singular)}</span>']
        datos = self.backlog.get(codigo, {})
        for clave in ("prioridad", "sprint", "fase", "estado"):
            if datos.get(clave) and datos[clave] not in ("—", "-"):
                clase = "exito" if datos[clave] == "Terminado" else ("aviso" if datos[clave] == "Pendiente" else "")
                marcas.append(f'<span class="etiqueta {clase}">{e(datos[clave])}</span>')
        if datos.get("puntos"):
            marcas.append(f'<span class="etiqueta">{e(datos["puntos"])} puntos</span>')
        if codigo.startswith("HU-"):
            estado, clase, explicacion = self.estado_de_historia(codigo)
            marcas.append(f'<span class="etiqueta {clase}" title="{e(explicacion)}">{estado} · {e(explicacion)}</span>')
        if codigo.startswith("CA-") and el.datos.get("historia") in self.elementos:
            marcas.append(f'<span class="etiqueta">De <a href="{self.enlazador.elemento(el.datos["historia"])}">{el.datos["historia"]}</a></span>'.replace('href="e/', 'href="../e/'))
        if codigo.startswith("RN-") and codigo in self.arq_reglas:
            marcas.append(f'<span class="etiqueta">Capa {e(self.arq_reglas[codigo]["capa"])}</span>')
        return "".join(marcas)

    def capturas_de(self, codigo: str, raiz: str) -> str:
        pantallas = [codigo] if codigo.startswith("PT-") else []
        if codigo.startswith("HU-"):
            pantallas = self.arq_historias.get(codigo, {}).get("pantallas", [])
        figuras = []
        for pt in pantallas:
            for ruta in self.capturas.get(pt, []):
                figuras.append(f'<figure><img src="{raiz}{self.copiar(ruta)}" alt="Captura real de {pt}" loading="lazy"><figcaption>{pt} · el sistema real</figcaption></figure>')
            if pt in self.mockups:
                mockup = next((DOCS / "03-diseno" / "mockups").glob(f"pt-{pt[3:]}-*.html"), None)
                enlace = f' · <a href="{raiz}mockups/{mockup.name}">abrir el mockup</a>' if mockup else ""
                figuras.append(f'<figure><img src="{raiz}{self.copiar(self.mockups[pt])}" alt="Mockup de {pt}" loading="lazy"><figcaption>{pt} · mockup{enlace}</figcaption></figure>')
        return "".join(figuras)

    def en_el_codigo(self, codigo: str, raiz: str) -> str:
        bloques = []
        p = prefijo(codigo)
        if p == "HU" and codigo in self.arq_historias:
            fila = self.arq_historias[codigo]
            clases = "".join(f"<li>{self.clase_enlazada(c)}</li>" for c in fila["clases"])
            bloques.append(f'<p>Según la <a href="{raiz}{self.paginas_doc["docs/03-diseno/arquitectura/README.md"]}#historias-y-casos-de-uso">arquitectura</a>: '
                           f'{e(re.sub(r"`", "", fila["controlador"]))} → {e(re.sub(r"`", "", fila["caso"]))}.</p><ul class="lista-enlaces">{clases}</ul>')
        if p == "RN" and codigo in self.arq_reglas:
            fila = self.arq_reglas[codigo]
            clases = "".join(f"<li>{self.clase_enlazada(c)}</li>" for c in fila["clases"])
            bloques.append(f'<p>{H.en_linea(fila["como"], DOCS / "03-diseno" / "arquitectura" / "README.md", raiz, self.enlazador, codigo)}</p><ul class="lista-enlaces">{clases}</ul>')
        if p == "CU":
            clases = self.clases_que_implementan(codigo)
            if clases:
                bloques.append(f'<ul class="lista-enlaces">{"".join(f"<li>{self.clase_enlazada(c)}</li>" for c in clases)}</ul>')
        if p == "PT" and codigo in self.vistas:
            vista = self.vistas[codigo]
            bloques.append(f'<p>La vista: <a href="{e(self.enlazador.github(vista.ruta))}"><code>{e(vista.ruta)}</code></a></p>')
        menciones = self.en_codigo.get(codigo, [])
        if menciones:
            bloques.append(f"<p>Líneas del código que la citan ({len(menciones)}):</p>{self.lugares(menciones)}")
        return "".join(bloques)

    def como_se_prueba(self, codigo: str) -> str:
        bloques = []
        propias = self.pruebas.get(codigo, [])
        if codigo.startswith("HU-"):
            criterios = [c for c in self.salientes.get(codigo, []) if c.startswith("CA-")]
            filas = []
            for c in sorted(criterios, key=orden):
                metodos = self.pruebas.get(c, [])
                celda = "<br>".join(f'<a href="{e(self.enlazador.github(m.ruta, m.linea))}"><code>{e(m.texto)}</code></a>' for m in metodos) or '<span class="etiqueta aviso">sin prueba automática</span>'
                filas.append(f'<tr><td><a href="../e/{c}.html">{c}</a></td><td>{e(self.titulo_corto(c))}</td><td>{celda}</td></tr>')
            if filas:
                bloques.append(f'<table class="tabla"><thead><tr><th>Criterio</th><th></th><th>Prueba automática</th></tr></thead><tbody>{"".join(filas)}</tbody></table>')
        elif propias:
            bloques.append("<ul class=\"lista-enlaces\">" + "".join(
                f'<li><a href="{e(self.enlazador.github(m.ruta, m.linea))}"><code>{e(m.texto)}</code></a> <span class="sub">{e(m.ruta)}</span></li>' for m in propias
            ) + "</ul>")
        menciones = [m for m in self.en_pruebas.get(codigo, []) if not any(m.ruta == p.ruta and m.linea == p.linea for p in propias)]
        if menciones and not codigo.startswith("HU-"):
            bloques.append(f"<p>Otras líneas de las pruebas que la citan:</p>{self.lugares(menciones, 15)}")
        return "".join(bloques)

    # --- Listas, diagramas, documentos e inicio ---------------------------------------------------

    def pagina_tipo(self, tipo: L.Tipo) -> None:
        codigos = sorted((c for c in self.elementos if prefijo(c) == tipo.prefijo), key=orden)
        filas = []
        for c in codigos:
            extra = ""
            if tipo.prefijo == "HU":
                estado, clase, _ = self.estado_de_historia(c)
                datos = self.backlog.get(c, {})
                extra = f'<td>{e(datos.get("prioridad", ""))}</td><td>{e(datos.get("sprint", ""))}</td><td><span class="etiqueta {clase}">{estado}</span></td>'
            filas.append(f'<tr><td><a href="../e/{c}.html"><strong>{c}</strong></a></td><td>{e(self.titulo_corto(c))}</td>{extra}</tr>')
        cabecera = "<th>Prioridad</th><th>Sprint</th><th>Estado</th>" if tipo.prefijo == "HU" else ""
        contenido = (f'<div class="migas"><a href="../index.html">Inicio</a> › {e(tipo.seccion)}</div>'
                     f'<h1 class="titulo"><span class="sigla sigla-grande">{tipo.prefijo}</span>{e(tipo.plural)} <span class="etiqueta">{len(codigos)}</span></h1>'
                     f'<p class="tipo-explicado"><span><strong>{e(tipo.singular)}:</strong> {e(tipo.explicacion)} '
                     f'<a href="../glosario.html">Ver todas las siglas</a></span></p>'
                     f'<table class="tabla"><thead><tr><th>Código</th><th>Título</th>{cabecera}</tr></thead><tbody>{"".join(filas)}</tbody></table>')
        self.escribir_pagina(f"t/{tipo.prefijo}.html", tipo.plural, contenido)

    def pagina_diagrama(self, d: L.Diagrama) -> None:
        raiz = "../"
        if d.svg:
            dibujo = f'<div class="diagrama"><img src="{raiz}{self.copiar(d.svg)}" alt="{e(d.titulo)}"></div>'
            texto = ""
        else:
            dibujo = f'<div class="diagrama"><pre class="mermaid">{e(d.mermaid)}</pre></div>'
            sin_diagrama = re.sub(r"```mermaid\n.*?```", "", d.seccion, flags=re.S)
            texto = self.html_de(sin_diagrama, d.fuente, raiz)
        codigos = [c for c in codigos_en(d.seccion + d.mermaid) if c in self.conocidos]
        clases = self.clases_de_diagrama.get(d.id, [])
        partes = [
            f'<div class="migas"><a href="{raiz}index.html">Inicio</a> › <a href="{raiz}t/diagramas.html">Diagramas</a></div>',
            f'<h1 class="titulo">{e(d.titulo)}</h1>',
            f'<section class="tarjeta">{dibujo}<p class="sub">Fuente: <a href="{e(self.enlazador.github(relativa(d.fuente), d.linea, d.ancla))}">{e(relativa(d.fuente))}</a></p></section>',
        ]
        if clases:
            items = "".join(f"<li>{self.clase_enlazada(c)}</li>" for c in clases)
            partes.append(f'<section class="tarjeta"><h2>Dónde se ejecuta <span class="nota">· las clases del diagrama en el código</span></h2><ul class="lista-enlaces">{items}</ul></section>')
        if codigos:
            partes.append(f'<section class="tarjeta"><h2>Elementos que aparecen</h2>{self.agrupados(codigos, raiz)}</section>')
        if texto.strip():
            partes.append(f'<section class="tarjeta"><h2>Qué explica</h2><div class="texto">{texto}</div></section>')
        self.escribir_pagina(f"d/{d.id}.html", d.titulo, "".join(partes), "t/diagramas.html")

    def pagina_diagramas(self) -> None:
        por_fuente: dict[str, list[L.Diagrama]] = defaultdict(list)
        for d in self.diagramas:
            por_fuente[relativa(d.fuente.parent if d.svg else d.fuente)].append(d)
        bloques = []
        for fuente, lista in por_fuente.items():
            items = "".join(f'<li><a href="../d/{d.id}.html">{e(d.titulo)}</a></li>' for d in lista)
            bloques.append(f'<section class="tarjeta"><h2>{e(fuente)}</h2><ul class="lista-enlaces">{items}</ul></section>')
        self.escribir_pagina("t/diagramas.html", "Diagramas", f'<h1 class="titulo">Diagramas <span class="etiqueta">{len(self.diagramas)}</span></h1>{"".join(bloques)}')

    def pagina_documento(self, ruta: str) -> None:
        fuente = RAIZ / ruta
        if not fuente.exists():
            return
        url = self.paginas_doc[ruta]
        raiz = "../"
        texto = fuente.read_text(encoding="utf-8")
        cuerpo = self.html_de(texto, fuente, raiz)
        contenido = (f'<div class="migas"><a href="{raiz}index.html">Inicio</a> › <a href="{e(self.enlazador.github(ruta))}">{e(ruta)}</a></div>'
                     f'<article class="tarjeta texto">{cuerpo}</article>')
        self.escribir_pagina(url, DOCUMENTOS[ruta], contenido)

    # Las seis etapas del proyecto, en el orden en que se hicieron. El inicio y el glosario se organizan con ellas.
    ETAPAS = [
        ("1", "El problema", "Por qué el taller necesita un sistema: qué le pasa hoy y qué pierde por eso.", ["C", "E"]),
        ("2", "Lo que se quiere lograr", "Cada problema escrito en positivo, agrupado en metas concretas.", ["M", "FN", "OE"]),
        ("3", "Lo que el sistema debe cumplir", "Qué debe permitir hacer, con qué calidad y qué reglas del taller respeta.", ["F", "RF", "RNF", "RN"]),
        ("4", "Lo que necesita la dueña", "Cada necesidad contada como una historia, con ejemplos que dicen cuándo está bien hecha.", ["EP", "HU", "CA"]),
        ("5", "Cómo se diseñó", "El paso a paso de cada tarea, las pantallas, las decisiones técnicas y los diagramas.", ["CU", "PT", "ADR"]),
        ("6", "Cómo se comprobó y se entregó", "Las pruebas hechas por personas, el trabajo técnico y los documentos entregados.", ["PM", "HT", "DOC"]),
    ]

    def ficha_de_tipo(self, p: str, cuenta: dict, raiz: str = "") -> str:
        t = TIPO[p]
        return (f'<a class="ficha-tipo" href="{raiz}t/{p}.html"><span class="sigla">{p}</span>'
                f'<span><strong>{e(t.plural)}</strong> <span class="cuenta">{cuenta[p]}</span><br>'
                f'<span class="sub">{e(t.explicacion)}</span></span></a>')

    def inicio(self) -> None:
        cuenta = defaultdict(int)
        for c in self.elementos:
            cuenta[prefijo(c)] += 1
        construidas = sum(1 for c in self.elementos if c.startswith("HU-") and self.estado_de_historia(c)[0] == "Construida")

        etapas = []
        for numero, titulo, texto, prefijos in self.ETAPAS:
            fichas = "".join(self.ficha_de_tipo(p, cuenta) for p in prefijos if cuenta[p])
            if numero == "5":
                fichas += (f'<a class="ficha-tipo" href="t/diagramas.html"><span class="sigla">◇</span>'
                           f'<span><strong>Diagramas</strong> <span class="cuenta">{len(self.diagramas)}</span><br>'
                           f'<span class="sub">Dibujos de cómo funciona cada parte, y en qué partes del código se ejecuta.</span></span></a>')
            etapas.append(f'<li class="etapa"><div class="etapa-numero">{numero}</div><div><h3>{e(titulo)}</h3><p class="sub">{e(texto)}</p>'
                          f'<div class="fichas">{fichas}</div></div></li>')

        pasos = "".join(
            f'<li><div><a href="e/{c}.html"><span class="sigla">{prefijo(c)}</span><strong>{c}</strong> · {e(self.titulo_corto(c))}</a>'
            f'<p><em>{e(TIPO[prefijo(c)].singular)}.</em> {e(texto)}</p></div></li>'
            for c, texto in RECORRIDO if c in self.elementos
        )
        estados = next((d for d in self.diagramas if "estado" in d.titulo.lower() and "orden" in d.titulo.lower()), None)
        if estados:
            pasos += (f'<li><div><a href="d/{estados.id}.html"><span class="sigla">◇</span><strong>Diagrama</strong> · {e(estados.titulo)}</a>'
                      f'<p>Cómo cambia de estado una orden, y las partes del código que lo hacen.</p></div></li>')

        contenido = f"""
<section class="portada">
  <h1>El-taller-ines</h1>
  <p class="bajada">Todo el proyecto en un solo lugar: del problema del taller hasta el código y las pruebas.</p>
  <p>El sistema lleva las órdenes, las entregas y los cobros de un taller de costura. Aquí está todo lo que se hizo para
  construirlo. Cada cosa tiene un <strong>código corto</strong>, como <a href="e/HU-11.html">HU-11</a>: las letras dicen qué es
  y el número cuál es. Tocando un código llegas a su página; pasando el cursor encima, ves qué es sin abrirlo.</p>
  <div class="acciones-portada">
    <a class="boton" href="#recorrido">Empezar el recorrido guiado</a>
    <a class="boton secundario" href="glosario.html">Qué significa cada sigla</a>
  </div>
</section>

<section class="tarjeta">
  <h2>Cómo se conecta todo</h2>
  <p class="sub">El proyecto avanzó en seis etapas y cada una parte de la anterior: una historia (HU) existe porque resuelve un
  problema (C), y una prueba existe porque comprueba una historia. Por eso todo está enlazado.</p>
  <ol class="etapas">{"".join(etapas)}</ol>
</section>

<section class="tarjeta" id="recorrido">
  <h2>Recorrido guiado <span class="nota">· para la primera vez, o para la sustentación</span></h2>
  <p class="sub">Sigue un solo hilo, en orden: un problema real del taller, cómo se convirtió en una necesidad, cómo se
  construyó y cómo se comprobó que funciona.</p>
  <ol class="recorrido">{pasos}</ol>
</section>

<section class="tarjeta">
  <h2>En números</h2>
  <div class="mosaico">
    <a href="t/HU.html"><strong>{construidas} de {cuenta["HU"]}</strong>historias construidas</a>
    <a href="t/CA.html"><strong>{len(self.metodos)}</strong>pruebas automáticas</a>
    <a href="t/diagramas.html"><strong>{len(self.diagramas)}</strong>diagramas</a>
    <a href="t/PM.html"><strong>{cuenta["PM"]}</strong>pruebas manuales</a>
  </div>
</section>
"""
        self.escribir_pagina("index.html", "Inicio", contenido)

    def glosario(self) -> None:
        cuenta = defaultdict(int)
        for c in self.elementos:
            cuenta[prefijo(c)] += 1
        bloques = []
        for numero, titulo, _, prefijos in self.ETAPAS:
            filas = []
            for p in prefijos:
                if not cuenta[p]:
                    continue
                t = TIPO[p]
                ejemplo = (f'<a href="e/{t.ejemplo}.html" title="{e(self.titulo_corto(t.ejemplo))}">{t.ejemplo}</a>'
                           if t.ejemplo in self.elementos else "")
                filas.append(f'<tr><td><span class="sigla sigla-grande">{p}</span></td>'
                             f'<td><a href="t/{p}.html"><strong>{e(t.singular)}</strong></a><br><span class="sub">{e(t.explicacion)}</span></td>'
                             f'<td>{ejemplo}</td><td>{cuenta[p]}</td></tr>')
            bloques.append(f'<h2>{numero}. {e(titulo)}</h2><table class="tabla glosario"><thead><tr><th>Sigla</th>'
                           f'<th>Qué es</th><th>Ejemplo</th><th>Cuántos</th></tr></thead><tbody>{"".join(filas)}</tbody></table>')
        otras = "".join(f"<dt>{e(palabra)}</dt><dd>{e(texto)}</dd>" for palabra, texto in L.OTRAS_PALABRAS)
        contenido = f"""
<div class="migas"><a href="index.html">Inicio</a></div>
<h1 class="titulo">Glosario</h1>
<section class="tarjeta texto">
  <h2>Cómo se lee un código</h2>
  <p>Cada elemento del proyecto tiene un <strong>código</strong>: unas letras que dicen <strong>qué es</strong> y un número que
  dice <strong>cuál es</strong>. Así se nombra sin repetir su título, y se puede enlazar desde cualquier documento.</p>
  <ul>
    <li><strong>HU-11</strong> es la historia de usuario número 11.</li>
    <li><strong>CA-11.1</strong> es el primer criterio de aceptación de la historia 11: el número antes del punto dice de qué historia es.</li>
    <li><strong>C-01.1</strong> es una causa más concreta dentro de la causa C-01. Igual con los efectos (E) y los medios (M).</li>
  </ul>
</section>
<section class="tarjeta">{"".join(bloques)}</section>
<section class="tarjeta"><h2>Otras palabras</h2><dl class="campos">{otras}</dl></section>
"""
        self.escribir_pagina("glosario.html", "Glosario", contenido)

    def indice(self) -> None:
        entradas = [[c, self.titulo_corto(c), el.tipo.singular, self.enlazador.elemento(c)] for c, el in sorted(self.elementos.items(), key=lambda x: orden(x[0]))]
        entradas += [["Diagrama", d.titulo, "Diagrama", f"d/{d.id}.html"] for d in self.diagramas]
        entradas += [["Documento", titulo, "Documento", self.paginas_doc[r]] for r, titulo in DOCUMENTOS.items() if (RAIZ / r).exists()]
        escribir(self.salida / "recursos" / "indice.js", "window.INDICE = " + json.dumps(entradas, ensure_ascii=False) + ";\n")

    # --- Todo ------------------------------------------------------------------------------------

    def generar(self) -> None:
        # Se vacía la carpeta sin borrarla: en Windows no se puede borrar si un servidor la tiene abierta
        self.salida.mkdir(parents=True, exist_ok=True)
        for hijo in self.salida.iterdir():
            shutil.rmtree(hijo) if hijo.is_dir() else hijo.unlink()
        (self.salida / "recursos").mkdir(parents=True)
        shutil.copyfile(RECURSOS / "estilos.css", self.salida / "recursos" / "estilos.css")
        shutil.copyfile(RECURSOS / "portal.js", self.salida / "recursos" / "portal.js")
        mermaid = mermaid_local()
        if mermaid:
            shutil.copyfile(mermaid, self.salida / "recursos" / "mermaid.min.js")
        else:
            escribir(self.salida / "recursos" / "mermaid.min.js", "")
        # Los mockups se abren tal cual, con sus estilos
        shutil.copytree(DOCS / "03-diseno" / "mockups", self.salida / "mockups", ignore=shutil.ignore_patterns("*.md"))

        for codigo in self.elementos:
            self.pagina_elemento(codigo)
        for tipo in TIPOS:
            if any(prefijo(c) == tipo.prefijo for c in self.elementos):
                self.pagina_tipo(tipo)
        for d in self.diagramas:
            self.pagina_diagrama(d)
        self.pagina_diagramas()
        for ruta in DOCUMENTOS:
            self.pagina_documento(ruta)
        self.inicio()
        self.glosario()
        self.indice()
        escribir(self.salida / ".nojekyll", "")


def main() -> int:
    argumentos = argparse.ArgumentParser(description=__doc__.split("\n\n")[0])
    argumentos.add_argument("--salida", default=str(RAIZ / "portal"))
    opciones = argumentos.parse_args()

    portal = Portal(Path(opciones.salida).resolve())
    portal.generar()

    cuenta = defaultdict(int)
    for c in portal.elementos:
        cuenta[prefijo(c)] += 1
    resumen = ", ".join(f"{n} {TIPO[p].plural.lower()}" for p, n in sorted(cuenta.items(), key=lambda x: orden(x[0] + "-0")))
    print(f"Portal generado en {relativa(portal.salida) if portal.salida.is_relative_to(RAIZ) else portal.salida}: {resumen}, {len(portal.diagramas)} diagramas.")
    for aviso in portal.avisos:
        print(f"Aviso: {aviso}")
    for faltante in portal.pruebas_faltantes:
        print(f"Aviso: el caso de prueba nombra un método que no existe: {faltante}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
