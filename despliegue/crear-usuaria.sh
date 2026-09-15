#!/bin/sh
# Crea el negocio inicial con la usuaria de la dueña, una sola vez: sh despliegue/crear-usuaria.sh
# La contraseña se escribe aquí sin mostrarse. No queda en ningún archivo ni en la lista de procesos: pasa a un contenedor
# temporal que se borra al terminar.
set -eu
cd "$(dirname "$0")"

printf 'Usuario de la dueña [taller]: '
read -r usuario
USUARIA_INICIAL_USUARIO=${usuario:-taller}

stty -echo
printf 'Contraseña (no se muestra): '
read -r USUARIA_INICIAL_CONTRASENA
stty echo
echo

if [ -z "$USUARIA_INICIAL_CONTRASENA" ]; then
    echo "La contraseña no puede quedar vacía."
    exit 1
fi

export USUARIA_INICIAL_USUARIO USUARIA_INICIAL_CONTRASENA
docker compose run --rm --no-deps -e USUARIA_INICIAL_USUARIO -e USUARIA_INICIAL_CONTRASENA web \
    php artisan db:seed --class=NegocioInicialSeeder --force
echo "Listo: ya puedes entrar en https://proyectosena.online/taller con el usuario $USUARIA_INICIAL_USUARIO."
