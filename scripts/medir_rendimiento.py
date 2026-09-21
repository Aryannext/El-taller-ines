"""Mide el tiempo de respuesta de las pantallas de uso diario (RNF-01).

Es el script de medición que usa PM-06: por cada pantalla hace unas solicitudes de calentamiento
que no cuentan y luego las que se miden, y reporta el percentil 95 y el máximo. El percentil 95 se
usa en vez del promedio porque el promedio esconde las respuestas lentas.

Mide el tiempo del servidor, no el del navegador: el LCP en 4G simulada se toma aparte con
Lighthouse, como dice PM-06.

Uso:
    python scripts/medir_rendimiento.py https://proyectosena.online/taller --usuario taller --orden 42

La contraseña se pide sin mostrarla. Solo usa la biblioteca estándar: no hace falta instalar nada
en el servidor para correrlo.
"""

from __future__ import annotations

import argparse
import getpass
import http.cookiejar
import math
import re
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

TOPE_MS = 500
TOKEN = re.compile(r'name="_token"\s+value="([^"]+)"')


def opener() -> urllib.request.OpenerDirector:
    """Un navegador mínimo: guarda la cookie de sesión y no sigue los redirecciones a ciegas."""
    tarro = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(tarro))


def entrar(cliente: urllib.request.OpenerDirector, base: str, usuario: str, contrasena: str) -> None:
    with cliente.open(f"{base}/entrar") as respuesta:
        formulario = respuesta.read().decode("utf-8", "replace")

    encontrado = TOKEN.search(formulario)
    if not encontrado:
        raise SystemExit(f"No se encontró el campo _token en {base}/entrar: ¿la dirección es la correcta?")

    datos = urllib.parse.urlencode(
        {"_token": encontrado.group(1), "usuario": usuario, "contrasena": contrasena}
    ).encode()

    with cliente.open(f"{base}/entrar", data=datos) as respuesta:
        destino = respuesta.geturl()

    if destino.rstrip("/").endswith("/entrar"):
        raise SystemExit("El inicio de sesión no pasó: revisa el usuario y la contraseña.")


def medir(cliente: urllib.request.OpenerDirector, url: str, cuantas: int, calentamiento: int) -> list[float]:
    """Devuelve los milisegundos de cada solicitud, sin contar las de calentamiento."""
    for _ in range(calentamiento):
        with cliente.open(url) as respuesta:
            respuesta.read()

    tiempos = []
    for _ in range(cuantas):
        empezo = time.perf_counter()
        with cliente.open(url) as respuesta:
            respuesta.read()
        tiempos.append((time.perf_counter() - empezo) * 1000)

    return tiempos


def percentil(tiempos: list[float], porcentaje: int) -> float:
    """El valor por debajo del cual queda ese porcentaje de las solicitudes."""
    ordenados = sorted(tiempos)
    posicion = math.ceil(porcentaje / 100 * len(ordenados)) - 1

    return ordenados[max(0, posicion)]


def main() -> int:
    analizador = argparse.ArgumentParser(description="Mide el tiempo de respuesta del servidor (RNF-01, PM-06).")
    analizador.add_argument("base", help="Dirección del sistema, por ejemplo https://proyectosena.online/taller")
    analizador.add_argument("--usuario", required=True, help="Usuario con sesión en el sistema")
    analizador.add_argument("--orden", required=True, help="Identificador de una orden de 10 prendas (PT-09)")
    analizador.add_argument("--buscar", default="Ana", help="Término de búsqueda que devuelva varios clientes")
    analizador.add_argument("--solicitudes", type=int, default=100, help="Solicitudes medidas por pantalla")
    analizador.add_argument("--calentamiento", type=int, default=5, help="Solicitudes que no se cuentan")
    argumentos = analizador.parse_args()

    base = argumentos.base.rstrip("/")
    contrasena = getpass.getpass(f"Contraseña de {argumentos.usuario}: ")

    cliente = opener()
    entrar(cliente, base, argumentos.usuario, contrasena)

    pantallas = [
        ("Panel del día (PT-02)", f"{base}/"),
        ("Detalle de una orden de 10 prendas (PT-09)", f"{base}/ordenes/{argumentos.orden}"),
        ("Búsqueda de clientes (PT-03)", f"{base}/clientes?q={urllib.parse.quote(argumentos.buscar)}"),
        ("Órdenes atrasadas (PT-20)", f"{base}/seguimiento/atrasadas"),
        ("Órdenes sin reclamar (PT-21)", f"{base}/seguimiento/sin-reclamar"),
    ]

    print(f"{argumentos.solicitudes} solicitudes por pantalla, tras {argumentos.calentamiento} de calentamiento.\n")
    print(f"| {'Pantalla':<42} | {'Percentil 95':>12} | {'Máximo':>8} | {'¿Cumple?':<9} |")
    print(f"| {'-' * 42} | {'-' * 12} | {'-' * 8} | {'-' * 9} |")

    lentas = 0
    fallidas = 0

    for nombre, url in pantallas:
        try:
            tiempos = medir(cliente, url, argumentos.solicitudes, argumentos.calentamiento)
        except urllib.error.HTTPError as error:
            # No medida no es lo mismo que lenta: un 404 suele ser una dirección mal pasada
            print(f"| {nombre:<42} | {'error ' + str(error.code):>12} | {'—':>8} | sin medir |")
            fallidas += 1
            continue

        p95 = percentil(tiempos, 95)
        maximo = max(tiempos)
        cumple = p95 <= TOPE_MS
        lentas += 0 if cumple else 1
        print(f"| {nombre:<42} | {p95:>9.0f} ms | {maximo:>5.0f} ms | {'sí' if cumple else 'NO':<9} |")

    print()

    if fallidas:
        print(f"{fallidas} pantalla(s) no se pudieron medir: revisa la dirección y la orden que se pasó.")

    if lentas:
        print(f"{lentas} pantalla(s) pasan de {TOPE_MS} ms en el percentil 95: RNF-01 no se cumple.")

    if fallidas or lentas:
        return 1

    print(f"Las {len(pantallas)} pantallas responden en {TOPE_MS} ms o menos en el percentil 95 (RNF-01).")

    return 0


if __name__ == "__main__":
    sys.exit(main())
