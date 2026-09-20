#!/bin/sh
# Copia semanal del respaldo más reciente a Google Drive (RNF-15). La llama el cron del host los domingos.
# La cuenta de Google se autoriza una sola vez en el servidor con «rclone config».
set -eu
cd "$(dirname "$0")"
. ./entorno.sh

carpeta=$(leer RESPALDO_CARPETA)
remoto=$(leer RESPALDO_REMOTO)
copias=$(leer RESPALDO_COPIAS_REMOTAS)

# El más reciente por nombre: las carpetas se llaman AAAA-MM-DD, así que el orden alfabético es el cronológico
ultimo=$(find "$carpeta" -mindepth 1 -maxdepth 1 -type d | sort | tail -1)

if [ -z "$ultimo" ]; then
    echo "No hay ningún respaldo en $carpeta: ¿corrió respaldar.sh?" >&2
    exit 1
fi

fecha=$(basename "$ultimo")
rclone copy "$ultimo" "$remoto/$fecha"

# Las copias que pasan de RESPALDO_COPIAS_REMOTAS, de la más vieja a la más nueva.
# Son unos 2,6 GB cada una después de 3 años y Google Drive regala 15 GB (RNF-15).
sobran=$(rclone lsf --dirs-only "$remoto" | sed 's|/$||' | sort | head -n "-$copias")

for vieja in $sobran; do
    rclone purge "$remoto/$vieja"
    echo "Se borró de Google Drive la copia $vieja"
done

echo "Copia de $fecha en $remoto"
