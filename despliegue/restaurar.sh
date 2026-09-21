#!/bin/sh
# Restaura un respaldo en una base vacía y en un disco privado. Es lo que mide PM-02 con cronómetro (RNF-15).
# Corre donde se quiera recuperar la información, no en el VPS: el peor caso es que el VPS ya no exista.
#
# Uso: sh restaurar.sh CARPETA_DEL_RESPALDO BASE CARPETA_STORAGE
#   sh restaurar.sh ~/Descargas/2026-10-05 taller_restaurada ~/el-taller-ines/sistema/storage
#
# La contraseña no se pasa por argumento, para que no quede en el historial del shell:
#   read -r clave; MYSQL_PWD=$clave; export MYSQL_PWD
# MYSQL_USER, MYSQL_HOST y MYSQL_PORT cambian a dónde se restaura; por defecto root en 127.0.0.1:3306.
# El puerto hace falta más de lo que parece: la máquina de desarrollo puede tener su MySQL en otro,
# y PM-02 restaura justamente ahí.
set -eu

if [ $# -ne 3 ]; then
    sed -n '2,12p' "$0" >&2
    exit 1
fi

respaldo=$1
base=$2
storage=$3
usuario=${MYSQL_USER:-root}
servidor=${MYSQL_HOST:-127.0.0.1}
puerto=${MYSQL_PORT:-3306}

for archivo in base.sql.gz fotos.tar.gz sumas.txt; do
    if [ ! -f "$respaldo/$archivo" ]; then
        echo "Falta $archivo en $respaldo" >&2
        exit 1
    fi
done

# Que el respaldo no se haya dañado en el viaje a Google Drive y de vuelta
(cd "$respaldo" && sha256sum -c sumas.txt)

gzip -dc "$respaldo/base.sql.gz" | mysql --host="$servidor" --port="$puerto" --user="$usuario" "$base"

mkdir -p "$storage/app/privado"
tar -xzf "$respaldo/fotos.tar.gz" -C "$storage/app/privado"

fotos=$(find "$storage/app/privado/fotos" -type f | wc -l)
echo "Base $base restaurada y $fotos fotos en $storage/app/privado/fotos"
echo "Falta apuntar el sistema a esta base con DB_DATABASE=$base (PM-02, paso 9)."
