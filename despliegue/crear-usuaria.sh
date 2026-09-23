#!/bin/sh
# Crea el negocio inicial con la usuaria de la dueña, una sola vez: sh despliegue/crear-usuaria.sh
# La contraseña se escribe aquí sin mostrarse. No queda en ningún archivo ni en la lista de procesos: pasa a un contenedor
# temporal que se borra al terminar.
set -eu
cd "$(dirname "$0")"

printf 'Usuario de la dueña [taller]: '
read -r usuario
USUARIA_INICIAL_USUARIO=${usuario:-taller}

# HU-37: con este correo la dueña entra tocando «Entrar con Google», sin escribir contraseña (RN-45)
printf 'Correo de Google de la dueña, para entrar sin contraseña (opcional): '
read -r correo
USUARIA_INICIAL_CORREO=${correo:-}

stty -echo
printf 'Contraseña (no se muestra): '
read -r USUARIA_INICIAL_CONTRASENA
stty echo
echo

if [ -z "$USUARIA_INICIAL_CONTRASENA" ]; then
    echo "La contraseña no puede quedar vacía."
    exit 1
fi

export USUARIA_INICIAL_USUARIO USUARIA_INICIAL_CONTRASENA USUARIA_INICIAL_CORREO
docker compose run --rm --no-deps -e USUARIA_INICIAL_USUARIO -e USUARIA_INICIAL_CONTRASENA -e USUARIA_INICIAL_CORREO web \
    php artisan db:seed --class=NegocioInicialSeeder --force
echo "Listo: ya puedes entrar en https://proyectosena.online/taller con el usuario $USUARIA_INICIAL_USUARIO."
if [ -n "$USUARIA_INICIAL_CORREO" ]; then
    echo "Y también tocando «Entrar con Google» con el correo $USUARIA_INICIAL_CORREO."
fi
