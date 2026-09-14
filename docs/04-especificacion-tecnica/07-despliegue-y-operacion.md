# Despliegue y operación

Detalla el [diagrama de despliegue](../03-diseno/diagramas/README.md#13-despliegue). Los archivos de configuración y los scripts viven en `despliegue/`, en la raíz del repositorio. Las rutas y versiones exactas del VPS se confirman en HT-04.

## Servidor

| Pieza | Configuración |
| --- | --- |
| **Usuario de despliegue** | `taller`, dueño del código y miembro del grupo `www-data` |
| **Carpeta del sistema** | `/var/www/taller`, un clon del repositorio; Nginx sirve `sistema/public` |
| **Escritura** | `www-data` solo escribe en `sistema/storage` y `sistema/bootstrap/cache` |
| **MySQL** | Escucha solo en `127.0.0.1`. El usuario `taller` tiene permisos solo sobre su base, nunca `root` |
| **Firewall** | Solo los puertos 22, 80 y 443 abiertos |
| **Certificado** | Let's Encrypt con certbot, que lo renueva solo |

## Variables de entorno

`.env.example` lleva todas estas variables, sin valores secretos. Laravel solo las lee dentro de `config/`; el código usa `config()`, nunca `env()`.

| Variable | Desarrollo | Producción | Para qué |
| --- | --- | --- | --- |
| `APP_NAME` | `El-taller-ines` | `El-taller-ines` | Nombre en los títulos |
| `APP_ENV` | `local` | `production` | Entorno |
| `APP_KEY` | Generada con `php artisan key:generate` | Propia del servidor | Cifra la sesión |
| `APP_DEBUG` | `true` | `false` | Detalles de los errores; nunca en producción (RNF-23) |
| `APP_URL` | `http://localhost:8000` | `https://<dominio>` | Direcciones completas |
| `APP_LOCALE` | `es` | `es` | Idioma de Laravel |
| `APP_FALLBACK_LOCALE` | `es` | `es` | Idioma de respaldo |
| `LOG_CHANNEL` | `stack` | `stack` | Canal de registro |
| `LOG_STACK` | `single` | `daily` | Un archivo de registro por día en producción |
| `LOG_DAILY_DAYS` | — | `14` | Días que se conservan los registros |
| `LOG_LEVEL` | `debug` | `warning` | Qué se registra |
| `DB_CONNECTION` | `mysql` | `mysql` | Motor |
| `DB_HOST` | `127.0.0.1` | `127.0.0.1` | Servidor de MySQL |
| `DB_PORT` | El puerto de MySQL 8.4 en WAMP | `3306` | Puerto |
| `DB_DATABASE` | `taller` | `taller` | Base de datos; las pruebas usan `taller_pruebas` en `.env.testing` |
| `DB_USERNAME` | `taller` | `taller` | Usuario de MySQL |
| `DB_PASSWORD` | Local | Secreta | Contraseña de MySQL |
| `SESSION_DRIVER` | `file` | `file` | [Seguridad](06-seguridad.md#sesión) |
| `SESSION_LIFETIME` | `480` | `480` | 8 horas (RNF-21) |
| `SESSION_EXPIRE_ON_CLOSE` | `false` | `false` | [Seguridad](06-seguridad.md#sesión) |
| `SESSION_SECURE_COOKIE` | `false` | `true` | Cookie solo por HTTPS |
| `CACHE_STORE` | `file` | `file` | Caché y límite de intentos |
| `QUEUE_CONNECTION` | `database` | `database` | Cola de avisos; nunca `sync` |
| `WHATSAPP_TOKEN` | Vacía para probar el envío asistido, o el token de prueba | Secreta | Token de la API de Meta |
| `WHATSAPP_ID_NUMERO` | El del número de prueba | El del número del negocio | Identificador del número que envía |
| `WHATSAPP_VERSION_API` | La vigente al hacer HT-01 | La misma | Versión de la API de Meta en la dirección |
| `WHATSAPP_PLANTILLA` | `orden_lista` | `orden_lista` | Nombre de la plantilla |
| `WHATSAPP_IDIOMA` | `es` | `es` | Idioma de la plantilla |
| `USUARIA_INICIAL_USUARIO` | `taller` | El que elija la dueña | Solo para `NegocioInicialSeeder` |
| `USUARIA_INICIAL_CONTRASENA` | Local | Secreta; se borra después de instalar | Solo para `NegocioInicialSeeder` |
| `RESPALDO_CARPETA` | — | `/var/respaldos/taller` | Dónde quedan los respaldos diarios |
| `RESPALDO_DIAS` | — | `14` | Días que se conservan (RNF-15) |
| `RESPALDO_REMOTO` | — | `drive:taller-respaldos` | Carpeta de Google Drive configurada en rclone |
| `RESPALDO_COPIAS_REMOTAS` | — | `2` | Copias semanales que se conservan en Google Drive |

## Nginx

`despliegue/nginx/taller.conf`:

```nginx
server {
    listen 80;
    server_name <dominio>;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name <dominio>;
    root /var/www/taller/sistema/public;
    index index.php;
    client_max_body_size 64m;
    # certbot agrega aquí las líneas del certificado

    include /var/www/taller/despliegue/nginx/seguridad.conf;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location = /sw.js {
        include /var/www/taller/despliegue/nginx/seguridad.conf;
        add_header Cache-Control "no-cache" always;
    }

    location = /.well-known/assetlinks.json {
        include /var/www/taller/despliegue/nginx/seguridad.conf;
        default_type application/json;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

- **`seguridad.conf`** tiene las cabeceras de [seguridad](06-seguridad.md#cabeceras). Se incluye también dentro de cada `location` que agrega cabeceras, porque en Nginx un `add_header` dentro de un `location` anula todos los del servidor.
- **`client_max_body_size`** coincide con `post_max_size` de PHP.
- **La última regla** impide descargar `.env` o `.git`.

## Servicio de la cola

`despliegue/systemd/taller-cola.service`:

```ini
[Unit]
Description=Cola de avisos de El-taller-ines
After=network.target mysql.service

[Service]
User=www-data
WorkingDirectory=/var/www/taller/sistema
ExecStart=/usr/bin/php artisan queue:work --queue=avisos --sleep=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

- **`Restart=always`:** si el trabajador se detiene por cualquier motivo, systemd lo vuelve a iniciar a los 5 segundos (HT-04).
- **`--max-time=3600`:** el trabajador termina cada hora y systemd lo reinicia limpio, lo que evita que acumule memoria.

## Tareas programadas

El crontab del usuario `taller` ejecuta el programador de Laravel cada minuto:

```cron
* * * * * cd /var/www/taller/sistema && php artisan schedule:run >> /dev/null 2>&1
```

Las tareas están en `routes/console.php`. Usan la hora de Colombia porque la aplicación está en esa zona.

| Tarea | Cuándo | Qué hace | Requisito |
| --- | --- | --- | --- |
| Respaldo diario | Todos los días, 2:00 a. m. | `despliegue/respaldar.sh` | RNF-15 |
| Copia semanal a Google Drive | Domingos, 3:00 a. m. | `despliegue/copiar-a-drive.sh` | RNF-15 |
| Limpiar trabajos fallidos | Domingos, 4:00 a. m. | `php artisan queue:prune-failed --hours=336` | — |

Si una tarea falla, el programador lo anota en el registro de Laravel.

## Respaldos

### Respaldo diario

`despliegue/respaldar.sh`:

1. Crea la carpeta `RESPALDO_CARPETA/AAAA-MM-DD`.
2. Exporta la base: `mysqldump --defaults-extra-file=/etc/taller/respaldo.cnf --single-transaction --no-tablespaces taller | gzip > base.sql.gz`. `--single-transaction` copia un estado coherente sin detener el sistema.
3. Empaqueta las fotos: `tar -czf fotos.tar.gz -C /var/www/taller/sistema/storage/app/privado fotos`.
4. Guarda la huella de cada archivo en `sumas.txt` con `sha256sum`, para comprobar en la restauración que no se dañaron.
5. Borra las carpetas de más de `RESPALDO_DIAS` días.
6. Si un paso falla, se detiene con error.

### Copia semanal

`despliegue/copiar-a-drive.sh`:

1. Copia la carpeta del respaldo más reciente a `RESPALDO_REMOTO/AAAA-MM-DD` con `rclone copy`.
2. Borra en Google Drive las copias que pasen de `RESPALDO_COPIAS_REMOTAS`.

- **Por qué 2 copias:** el requisito no fija cuántas guardar. RNF-15 calcula unos 2,6 GB de fotos después de 3 años; con 2 copias son 5,2 GB, que caben con margen en los 15 GB gratuitos.
- **Configuración de rclone:** el aprendiz la crea en el servidor con `rclone config`. Autorizar la cuenta de Google es un paso que hace él.
- **Restauración:** se prueba con PM-02 y se explica en el manual técnico (DOC-23).

## Despliegue

`despliegue/desplegar.sh` lo ejecuta el usuario `taller` en el VPS. Solo se despliega un commit que esté en verde en GitHub Actions.

| Paso | Comando | Por qué |
| --- | --- | --- |
| 1 | `php artisan down --retry=60` | Muestra la página de mantenimiento (503) |
| 2 | `git pull --ff-only origin main` | Trae el código aprobado; falla si el servidor tiene cambios propios (RNF-34) |
| 3 | `composer install --no-dev --optimize-autoloader --no-interaction` | Instala las dependencias de producción de `composer.lock` |
| 4 | `php artisan migrate --force` | Aplica las migraciones nuevas (RNF-31) |
| 5 | `php artisan optimize` | Guarda en caché la configuración, las rutas, las vistas y los eventos |
| 6 | `php artisan queue:restart` | El trabajador de la cola toma el código nuevo |
| 7 | `php artisan up` | Vuelve a abrir el sistema |
| 8 | Prueba de humo | La sección F de PM-07 |

- **Para volver a la versión anterior:** `git checkout <commit anterior>` y los pasos 3, 5, 6 y 7.
- **Migraciones compatibles:** una migración nueva no rompe la versión anterior del código. Por ejemplo, una columna se agrega en un despliegue y se deja de usar antes de borrarla en otro.

La primera instalación se explica paso a paso en el manual técnico (DOC-23): clonar el repositorio, `.env`, `key:generate`, migraciones, `NegocioInicialSeeder`, permisos, Nginx, certbot, el servicio de la cola, el crontab y rclone.

## App en el celular

Implementa ADR-006 y RNF-35.

### Manifiesto

`public/manifest.webmanifest`:

| Campo | Valor |
| --- | --- |
| `name` | El-taller-ines |
| `short_name` | Taller |
| `lang` | `es-CO` |
| `start_url` | `/` |
| `scope` | `/` |
| `display` | `standalone` |
| `orientation` | `portrait` |
| `background_color` | `#f3f4f8`, el `--fondo` de los mockups |
| `theme_color` | `#2a44a8`, el `--primario` de los mockups |
| `icons` | 192 y 512 px, y una versión `maskable` de 512 px que Android puede recortar en círculo |

Los íconos se diseñan en HT-07 con la letra del avatar de PT-19 sobre el color primario.

### Service worker

`public/sw.js`:

- Al instalarse, guarda en caché solo `sin-conexion.html`, `css/estilos.css`, las fuentes y los íconos.
- Para abrir una página, pide primero a la red. Si no hay red, muestra `sin-conexion.html`.
- **Nunca guarda páginas ni fotos con datos del taller.** Así, un celular perdido sin conexión no muestra información de clientes (RNF-25).
- Nginx lo sirve sin caché, para que una versión nueva llegue enseguida. Cuando cambian los archivos que guarda, cambia el nombre de su caché: `taller-v1`, `taller-v2`.

### Página sin conexión

`public/sin-conexion.html` es una página estática con los estilos del sistema y un botón para reintentar. Su mensaje: «Sin internet. El sistema necesita conexión para guardar y consultar tus órdenes. Revisa el wifi o los datos y vuelve a intentarlo.»

### Enlace entre el APK y el sitio

`public/.well-known/assetlinks.json`:

```json
[{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "<paquete>",
    "sha256_cert_fingerprints": ["<huella SHA-256 de la llave de firma>"]
  }
}]
```

- **El nombre del paquete** se define en HT-07 a partir del dominio y no cambia nunca: Android trata un paquete distinto como otra app.
- **Si el archivo no coincide con la llave,** el APK abre con la barra del navegador. PM-04 lo revisa en su paso 2.

### APK

| Qué | Cómo |
| --- | --- |
| **Herramienta** | Bubblewrap. La primera vez descarga su propio JDK y Android SDK; esa descarga la hace el aprendiz |
| **Configuración versionada** | `movil/twa-manifest.json` |
| **Fuera del repositorio** | `*.keystore`, `*.jks`, `*.apk` y `*.aab`, excluidos en `.gitignore` |
| **Si Chrome no puede abrir la app** | `fallbackType: customtabs`, que abre el sistema en una pestaña de Chrome |
| **Versiones** | `appVersionCode` sube en 1 con cada APK nuevo. Los cambios del sistema no necesitan un APK nuevo (ADR-006) |
| **Distribución** | El APK firmado se publica en el servidor, en `/descargas/`, desde una carpeta fuera del repositorio. El manual de usuario (DOC-22) explica cómo instalarlo |

## Monitoreo

| Qué | Cómo | Requisito |
| --- | --- | --- |
| **Disponibilidad** | UptimeRobot consulta `https://<dominio>/up` cada 5 minutos y avisa por correo al aprendiz si falla | RNF-16 |
| **Errores** | Registro diario de Laravel en `storage/logs`, conservado 14 días | — |
| **Cola** | `php artisan queue:failed` lista los envíos fallidos | RNF-17 |
| **Respaldos** | El registro de Laravel anota si una tarea programada falló; PM-02 revisa que existan los 14 días | RNF-15 |
