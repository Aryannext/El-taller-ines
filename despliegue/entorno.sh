# Lee una variable de despliegue/.env. Lo cargan los scripts de respaldo con «. ./entorno.sh».
# No exporta nada al entorno: las contraseñas solo viajan a donde hacen falta (RNF-24).

leer() {
    if [ ! -f .env ]; then
        echo "Falta despliegue/.env: primero corre instalar.sh" >&2
        exit 1
    fi

    valor=$(grep -E "^$1=" .env | head -1 | cut -d= -f2-)

    if [ -z "$valor" ]; then
        echo "Falta $1 en despliegue/.env: corre completar-env.sh" >&2
        exit 1
    fi

    printf '%s' "$valor"
}
