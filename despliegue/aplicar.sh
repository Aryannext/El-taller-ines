#!/bin/sh
# La segunda parte del despliegue. desplegar.sh la ejecuta después de git pull, para que corra siempre la versión nueva:
# un script que se actualiza a sí mismo mientras corre sigue leyendo la versión anterior.
set -eu
cd "$(dirname "$0")"

# Las variables nuevas de .env.example llegan al .env del servidor (ADR-007)
sh ./completar-env.sh
docker compose build web
# Recrea los contenedores que cambiaron. El contenedor web aplica las migraciones al arrancar (RNF-31)
docker compose up -d
sh ./esperar.sh
