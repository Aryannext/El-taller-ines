#!/bin/sh
# Despliega lo último de main en el VPS: sh despliegue/desplegar.sh
# Solo se despliega un commit que pasa scripts/calidad.py; GitHub Actions sigue bloqueado por la facturación de la cuenta (#26).
set -eu
cd "$(dirname "$0")/.."

git pull --ff-only origin main
# exec carga aplicar.sh ya actualizado por el pull
exec sh despliegue/aplicar.sh
