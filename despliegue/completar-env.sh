#!/bin/sh
# Agrega a despliegue/.env las variables de .env.example que falten, sin tocar las que ya tienen valor.
# Las claves se generan aquí mismo con openssl: nadie las escribe ni las ve (RNF-24).
set -eu
cd "$(dirname "$0")"
[ -f .env ] || exit 0

se_genera() {
    case "$1" in
        EVOLUTION_API_KEY|EVOLUTION_DB_PASSWORD) return 0 ;;
        *) return 1 ;;
    esac
}

grep -E '^[A-Z][A-Z0-9_]*=' .env.example | while IFS= read -r linea; do
    nombre=${linea%%=*}
    if ! grep -q "^${nombre}=" .env; then
        valor=${linea#*=}
        if se_genera "$nombre"; then
            valor=$(openssl rand -hex 24)
        fi
        printf '%s=%s\n' "$nombre" "$valor" >> .env
        echo "Se agregó ${nombre} a despliegue/.env"
    elif se_genera "$nombre" && grep -q "^${nombre}=$" .env; then
        sed -i "s|^${nombre}=$|${nombre}=$(openssl rand -hex 24)|" .env
        echo "Se generó ${nombre} en despliegue/.env"
    fi
done
