#!/bin/sh
# Respaldo diario de la base y las fotos (RNF-15). Lo llama el cron del host, no un contenedor:
# mysqldump corre dentro de db y para eso hace falta docker, que un contenedor no alcanza sin su socket.
# Qué guarda y cuánto se conserva: docs/04-especificacion-tecnica/07-despliegue-y-operacion.md
set -eu
cd "$(dirname "$0")"
. ./entorno.sh

carpeta=$(leer RESPALDO_CARPETA)
dias=$(leer RESPALDO_DIAS)
base=$(leer DB_DATABASE)
clave=$(leer MYSQL_ROOT_PASSWORD)

destino="$carpeta/$(date +%F)"
mkdir -p "$destino"

# La base. --single-transaction copia un estado coherente sin detener el sistema.
# El dump no se encadena a gzip con una tubería: en sh el fallo de mysqldump se perdería y
# el respaldo quedaría a medias sin que nadie se entere.
# La entrada cerrada: «docker compose exec -T» se lleva el stdin que tenga el script, y si alguien lo
# corre a mano desde la terminal empieza a tragarse lo que teclee
docker compose exec -T -e MYSQL_PWD="$clave" db \
    mysqldump --user=root --single-transaction --no-tablespaces "$base" > "$destino/base.sql" < /dev/null
gzip -f "$destino/base.sql"

# Las fotos, del volumen taller_storage que monta web. Se crea la carpeta por si todavía no hay ninguna.
docker compose exec -T web sh -c \
    'mkdir -p /var/www/taller/sistema/storage/app/privado/fotos \
     && tar -czf - -C /var/www/taller/sistema/storage/app/privado fotos' > "$destino/fotos.tar.gz" < /dev/null

# La huella de cada archivo, para comprobar en la restauración que no se dañaron
(cd "$destino" && sha256sum base.sql.gz fotos.tar.gz > sumas.txt)

# Los respaldos que pasan de RESPALDO_DIAS días
find "$carpeta" -mindepth 1 -maxdepth 1 -type d -mtime "+$dias" -exec rm -rf {} +

echo "Respaldo listo en $destino ($(du -sh "$destino" | cut -f1))"
