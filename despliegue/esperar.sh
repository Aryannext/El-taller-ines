#!/bin/sh
# Espera a que el sistema responda en /up después de instalar o desplegar, hasta 5 minutos.
set -eu
cd "$(dirname "$0")"

intentos=0
until curl -fsS -o /dev/null http://127.0.0.1:3012/up; do
    intentos=$((intentos + 1))
    if [ "$intentos" -gt 60 ]; then
        echo "El sistema no respondió en 5 minutos. Revisa: docker compose logs web"
        docker compose ps
        exit 1
    fi
    sleep 5
done

docker compose ps
echo "El sistema responde en http://127.0.0.1:3012/up"
