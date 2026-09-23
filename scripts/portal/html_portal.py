"""
Convierte el markdown de los documentos a HTML para el portal: cada código que existe se vuelve un enlace a su
página, los enlaces relativos de los documentos apuntan a GitHub y los bloques de Mermaid se dibujan en la página.
"""

from __future__ import annotations

import html
import posixpath
import re
from pathlib import Path

import markdown

from lectura import CODIGO, RAIZ, ancla_github, relativa

ETIQUETA = re.compile(r"(<[^>]+>)")


class Enlazador:
    """Sabe la dirección de cada página del portal y la del repositorio en GitHub."""

    def __init__(self, conocidos: set[str], documentos: dict[str, str], repositorio: str, ref: str, titulos: dict[str, str] | None = None):
        self.conocidos = conocidos
        # «Regla de negocio · Fecha en que la orden quedó lista»: se ve al pasar el cursor sobre cualquier código
        self.titulos = titulos or {}
        self.documentos = documentos  # ruta del .md en el repositorio → página del portal
        self.repositorio = repositorio
        self.ref = ref

    def elemento(self, codigo: str) -> str:
        return f"e/{codigo}.html"

    def github(self, ruta: str, linea: int | None = None, ancla: str = "") -> str:
        url = f"{self.repositorio}/blob/{self.ref}/{ruta}"
        if linea and not ruta.endswith(".md"):
            return f"{url}#L{linea}"
        if linea and ruta.endswith(".md") and not ancla:
            return f"{url}?plain=1#L{linea}"
        return f"{url}#{ancla}" if ancla else url

    def enlazar_codigos(self, fragmento_html: str, raiz: str, propio: str = "") -> str:
        """Vuelve enlace cada código conocido del texto, sin tocar las etiquetas, los enlaces ni los diagramas."""
        partes = ETIQUETA.split(fragmento_html)
        dentro_de = 0
        for i, parte in enumerate(partes):
            if parte.startswith("<"):
                nombre = re.match(r"</?(\w+)", parte)
                if nombre and nombre.group(1).lower() in ("a", "pre"):
                    dentro_de += -1 if parte.startswith("</") else (0 if parte.endswith("/>") else 1)
                continue
            if dentro_de > 0:
                continue
            partes[i] = CODIGO.sub(
                lambda m: m.group(0)
                if m.group(0) not in self.conocidos or m.group(0) == propio
                else f'<a class="codigo-enlace" href="{raiz}{self.elemento(m.group(0))}" title="{e(self.titulos.get(m.group(0), ""))}">{m.group(0)}</a>',
                parte,
            )
        return "".join(partes)

    def reescribir_enlaces(self, fragmento_html: str, fuente: Path, raiz: str) -> str:
        """Los enlaces relativos del documento: a otra página del portal si existe; si no, al archivo en GitHub."""
        carpeta = posixpath.dirname(relativa(fuente))

        def cambiar(m: re.Match) -> str:
            destino = html.unescape(m.group(1))
            if re.match(r"^(https?:|mailto:|#|data:)", destino) and not destino.startswith("#"):
                return m.group(0)
            if destino.startswith("#"):
                pagina = self.documentos.get(relativa(fuente))
                nuevo = f"{raiz}{pagina}{destino}" if pagina else self.github(relativa(fuente), ancla=destino[1:])
                return f'href="{html.escape(nuevo)}"'
            ruta, _, ancla = destino.partition("#")
            completa = posixpath.normpath(posixpath.join(carpeta, ruta))
            if completa in self.documentos:
                nuevo = f"{raiz}{self.documentos[completa]}" + (f"#{ancla}" if ancla else "")
            elif completa.startswith("docs/03-diseno/mockups/") and completa.endswith(".html"):
                nuevo = f"{raiz}mockups/{posixpath.basename(completa)}"
            else:
                nuevo = self.github(completa, ancla=ancla)
            return f'href="{html.escape(nuevo)}"'

        return re.sub(r'href="([^"]+)"', cambiar, fragmento_html)


def a_html(texto: str, fuente: Path, raiz: str, enlazador: Enlazador, propio: str = "") -> str:
    convertido = markdown.markdown(
        texto,
        extensions=["tables", "fenced_code", "sane_lists", "toc"],
        extension_configs={"toc": {"slugify": lambda valor, _separador: ancla_github(valor)}},
    )
    # Los bloques de Mermaid los dibuja el navegador
    convertido = re.sub(
        r'<pre><code class="language-mermaid">(.*?)</code></pre>',
        lambda m: f'<div class="diagrama"><pre class="mermaid">{m.group(1)}</pre></div>',
        convertido,
        flags=re.S,
    )
    convertido = enlazador.reescribir_enlaces(convertido, fuente, raiz)
    return enlazador.enlazar_codigos(convertido, raiz, propio)


def en_linea(texto: str, fuente: Path, raiz: str, enlazador: Enlazador, propio: str = "") -> str:
    """Una celda de tabla: markdown de una línea, sin el párrafo que lo envuelve."""
    convertido = a_html(texto.replace("<br>", "\n\n"), fuente, raiz, enlazador, propio)
    return re.sub(r"^<p>(.*)</p>$", r"\1", convertido.strip(), flags=re.S)


def e(texto: str) -> str:
    return html.escape(texto, quote=True)


def pagina(titulo: str, raiz: str, menu: str, contenido: str, pie: str) -> str:
    return f"""<!doctype html>
<html lang="es" data-raiz="{raiz}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{e(titulo)} · Portal de Puntada</title>
<link rel="stylesheet" href="{raiz}recursos/estilos.css">
</head>
<body>
<header class="cabecera">
  <a class="marca" href="{raiz}index.html">Puntada <span>· portal del proyecto</span></a>
  <div class="buscador">
    <input type="search" placeholder="Buscar un código o una palabra: RN-22, abono, foto…  (tecla /)" aria-label="Buscar" data-buscar autocomplete="off">
    <ul class="resultados" data-resultados hidden></ul>
  </div>
</header>
<div class="cuerpo">
  <nav class="menu" aria-label="Secciones">{menu}</nav>
  <main class="contenido">{contenido}</main>
</div>
<footer class="pie">{pie}</footer>
<script src="{raiz}recursos/indice.js"></script>
<script src="{raiz}recursos/mermaid.min.js"></script>
<script src="{raiz}recursos/portal.js"></script>
</body>
</html>
"""
