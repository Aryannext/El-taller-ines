#!/bin/sh
# Arranque de los contenedores web y cola de Puntada. La configuración llega solo por variables de entorno (RNF-34).
set -eu

cd /var/www/taller/sistema

if [ "$(id -u)" = "0" ]; then
    # storage es un volumen: la primera vez llega vacío
    mkdir -p storage/app/privado storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
    chown -R www-data:www-data storage bootstrap/cache
fi

# Solo el contenedor web aplica las migraciones, para que la cola no compita con él (RNF-31)
if [ "${TALLER_ROL:-web}" = "web" ]; then
    php artisan migrate --force
fi

# Configuración, rutas, vistas y eventos en caché
php artisan optimize

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

exec "$@"
