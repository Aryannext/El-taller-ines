#!/bin/sh
# Primera instalación en el VPS. Se ejecuta como cristian desde la raíz del clon: sh despliegue/instalar.sh
# Después faltan dos pasos: crear la usuaria (crear-usuaria.sh) e incluir despliegue/nginx/taller.conf en Nginx.
set -eu
cd "$(dirname "$0")"

if [ ! -f .env ]; then
    cp .env.example .env
    chmod 600 .env
    # Los secretos se generan aquí mismo: nadie los escribe ni los ve (RNF-24)
    sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -hex 24)|" .env
    sed -i "s|^MYSQL_ROOT_PASSWORD=.*|MYSQL_ROOT_PASSWORD=$(openssl rand -hex 24)|" .env
    echo "Se creó despliegue/.env con secretos nuevos."
fi

sh ./completar-env.sh
docker compose up -d --build
sh ./esperar.sh
