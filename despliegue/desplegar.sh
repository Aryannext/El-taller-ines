#!/bin/sh
# Despliega lo último de main en el VPS: sh despliegue/desplegar.sh
# Solo se despliega un commit que pasa scripts/calidad.py; GitHub Actions sigue bloqueado por la facturación de la cuenta (#26).
set -eu
cd "$(dirname "$0")/.."

git pull --ff-only origin main
cd despliegue
docker compose build web
# Recrea web y cola con la imagen nueva. El contenedor web aplica las migraciones al arrancar (RNF-31)
docker compose up -d
sh ./esperar.sh
