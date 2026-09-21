# Despliegue y operación

Detalla el [diagrama de despliegue](../03-diseno/diagramas/README.md#13-despliegue). Los archivos de configuración y los scripts viven en `despliegue/`, en la raíz del repositorio.

## Servidor

HT-04 confirmó el servidor: un VPS de Hostinger con Ubuntu 24.04, **compartido** con el portafolio del aprendiz y otros proyectos. El servidor tiene PHP 8.3 y MariaDB 10.11 para esos proyectos, pero el sistema necesita PHP 8.4.1 (lo exige Symfony 8, que usa Laravel 13) y MySQL 8.4 (la intercalación `utf8mb4_0900_ai_ci` de `BuscarClientes` y la validación del celular con expresiones regulares de MySQL 8). Cambiar esas versiones afectaría a los demás proyectos. Por eso el sistema corre en contenedores Docker, igual que el SGPD que ya está en el servidor.

| Pieza | Configuración |
| --- | --- |
| **Dirección** | `https://proyectosena.online/taller`, dentro del sitio del portafolio, como sus otros proyectos |
| **Carpeta** | `/home/cristian/proyectos/proyectosena.online/el-taller-ines`, un clon del repositorio que maneja el usuario `cristian` |
| **Contenedores** | `despliegue/docker-compose.yml`: `web` (PHP 8.4 con Apache, sirve `sistema/public`), `cola` (el trabajador de los avisos), `programador` (las tareas de la aplicación), `db` (MySQL 8.4), y `evolution` con `evolution-db` (Evolution API y su PostgreSQL, ADR-007) |
| **Puertos** | `web` escucha solo en `127.0.0.1:3012`. `db` no publica ningún puerto: solo la alcanzan los otros dos contenedores |
| **Datos** | Los volúmenes `taller_db` (la base) y `taller_storage` (fotos, sesiones y registros) |
| **Escritura** | Dentro del contenedor, `www-data` solo escribe en `sistema/storage` y `sistema/bootstrap/cache` |
| **Certificado** | El de Let's Encrypt que ya tiene `proyectosena.online`, que certbot renueva solo |
| **Firewall** | No se cambia, porque el servidor atiende otros servicios. El sistema no abre puertos hacia afuera |

## Variables de entorno

`sistema/.env.example` lleva las variables de desarrollo y `despliegue/.env.example` las de producción, sin valores secretos. En el VPS, `instalar.sh` copia la segunda a `despliegue/.env`, que no se versiona, y genera ahí mismo la llave y las contraseñas. Laravel solo las lee dentro de `config/`; el código usa `config()`, nunca `env()`.

| Variable | Desarrollo | Producción | Para qué |
| --- | --- | --- | --- |
| `APP_NAME` | `El-taller-ines` | `El-taller-ines` | Nombre en los títulos |
| `APP_ENV` | `local` | `production` | Entorno |
| `APP_KEY` | Generada con `php artisan key:generate` | Propia del servidor | Cifra la sesión |
| `APP_DEBUG` | `true` | `false` | Detalles de los errores; nunca en producción (RNF-23) |
| `APP_URL` | `http://localhost:8000` | `https://proyectosena.online/taller` | Direcciones completas fuera de una solicitud, como en la consola |
| `APP_LOCALE` | `es` | `es` | Idioma de Laravel |
| `APP_FALLBACK_LOCALE` | `es` | `es` | Idioma de respaldo |
| `LOG_CHANNEL` | `stack` | `stack` | Canal de registro |
| `LOG_STACK` | `single` | `daily` | Un archivo de registro por día en producción |
| `LOG_DAILY_DAYS` | — | `14` | Días que se conservan los registros |
| `LOG_LEVEL` | `debug` | `warning` | Qué se registra |
| `DB_CONNECTION` | `mysql` | `mysql` | Motor |
| `DB_HOST` | `127.0.0.1` | `db` | Servidor de MySQL; en producción, el contenedor `db` |
| `DB_PORT` | El puerto de MySQL 8.4 en WAMP | `3306` | Puerto |
| `DB_DATABASE` | `taller` | `taller` | Base de datos; las pruebas usan `taller_pruebas`, fijada en `phpunit.xml` |
| `DB_USERNAME` | `taller` | `taller` | Usuario de MySQL |
| `DB_PASSWORD` | Local | Secreta, generada por `instalar.sh` | Contraseña de MySQL |
| `MYSQL_ROOT_PASSWORD` | — | Secreta, generada por `instalar.sh` | Contraseña de `root` dentro del contenedor `db`; el sistema nunca la usa |
| `SESSION_DRIVER` | `file` | `file` | [Seguridad](06-seguridad.md#sesión) |
| `SESSION_LIFETIME` | `480` | `480` | 8 horas (RNF-21) |
| `SESSION_EXPIRE_ON_CLOSE` | `false` | `false` | [Seguridad](06-seguridad.md#sesión) |
| `SESSION_SECURE_COOKIE` | `false` | `true` | Cookie solo por HTTPS |
| `SESSION_PATH` | `/` | `/taller` | La cookie de sesión no se comparte con el portafolio ni con los otros proyectos del dominio |
| `CACHE_STORE` | `file` | `file` | Caché y límite de intentos |
| `QUEUE_CONNECTION` | `database` | `database` | Cola de avisos; nunca `sync` |
| `TALLER_ROL` | — | `web` o `cola`, fijado en `docker-compose.yml` | Solo el contenedor `web` aplica las migraciones al arrancar |
| `WHATSAPP_TOKEN` | Vacía para probar el envío asistido, o el token de prueba | Secreta | Token de la API de Meta |
| `WHATSAPP_ID_NUMERO` | El del número de prueba | El del número del negocio | Identificador del número que envía |
| `WHATSAPP_VERSION_API` | La vigente al hacer HT-01 | La misma | Versión de la API de Meta en la dirección |
| `WHATSAPP_PLANTILLA` | `orden_lista` | `orden_lista` | Nombre de la plantilla |
| `WHATSAPP_IDIOMA` | `es` | `es` | Idioma de la plantilla |
| `EVOLUTION_URL` | Vacía | `http://evolution:8080` | Dirección de Evolution API dentro de Docker (ADR-007) |
| `EVOLUTION_API_KEY` | Vacía | Secreta, generada por `completar-env.sh` | Clave de Evolution API |
| `EVOLUTION_INSTANCIA` | Vacía | `taller`, cuando el WhatsApp ya está conectado | Nombre de la conexión de WhatsApp; vacía, los avisos van al envío asistido |
| `EVOLUTION_DB_PASSWORD` | — | Secreta, generada por `completar-env.sh` | Contraseña de la base de Evolution API |
| `USUARIA_INICIAL_USUARIO` | `taller` | El que elija la dueña | Solo para `NegocioInicialSeeder` |
| `USUARIA_INICIAL_CONTRASENA` | Local | No se guarda: `crear-usuaria.sh` la pide sin mostrarla y la pasa a un contenedor temporal | Solo para `NegocioInicialSeeder` |
| `RESPALDO_CARPETA` | — | `/var/respaldos/taller` | Dónde quedan los respaldos diarios |
| `RESPALDO_DIAS` | — | `14` | Días que se conservan (RNF-15) |
| `RESPALDO_REMOTO` | — | `drive:taller-respaldos` | Carpeta de Google Drive configurada en rclone |
| `RESPALDO_COPIAS_REMOTAS` | — | `2` | Copias semanales que se conservan en Google Drive |
| `MYSQL_PWD` | — | No se guarda: `respaldar.sh` y `restaurar.sh` se la pasan a `mysqldump` y a `mysql` solo mientras corren | Contraseña de MySQL fuera de la lista de procesos (RNF-24) |

## Nginx

El Nginx del servidor ya atiende `proyectosena.online` con HTTPS y redirige HTTP a HTTPS. El sistema no tiene un sitio propio: el bloque `server` 443 del portafolio incluye `despliegue/nginx/taller.conf`, versionado en el repositorio:

```nginx
include /home/cristian/proyectos/proyectosena.online/el-taller-ines/despliegue/nginx/taller.conf;
```

- **`location ^~ /taller/`** pasa las solicitudes a `127.0.0.1:3012` sin el prefijo. `^~` impide que las `location` con expresiones regulares del portafolio atiendan rutas del sistema.
- **`X-Forwarded-Prefix: /taller`**, junto con el protocolo, el host y el puerto, le dice a Laravel dónde vive. `bootstrap/app.php` confía en esas cabeceras y arma todas las direcciones con el prefijo; una prueba automática lo comprueba.
- **`X-Forwarded-For`** se reemplaza con la IP real en vez de agregarse, para que nadie la falsee ante el límite de intentos del inicio de sesión (RNF-20).
- **`client_max_body_size 64M`** coincide con `post_max_size` del contenedor.
- **`.env` y `.git`** no se pueden descargar: Apache solo sirve `sistema/public`.
- **`location = /.well-known/assetlinks.json`** atiende la raíz del dominio, no `/taller`: Android solo busca ahí el [enlace entre el APK y el sitio](#enlace-entre-el-apk-y-el-sitio). Como `taller.conf` se incluye en el `server` del portafolio, esa `location` vive en `taller.conf`, versionada, y el sitio del portafolio no se toca.
- **Pendiente:** las cabeceras de [seguridad](06-seguridad.md#cabeceras) se envían desde el contenedor, no desde el Nginx compartido, para no cambiar las de los otros proyectos.

## Servicio de la cola

El contenedor `cola` de `docker-compose.yml` usa la misma imagen que `web` y ejecuta:

```sh
php artisan queue:work --queue=avisos --sleep=3 --max-time=3600
```

- **`restart: always`:** si el trabajador se detiene por cualquier motivo, Docker lo vuelve a iniciar, también cuando se reinicia el servidor (HT-04).
- **`--max-time=3600`:** el trabajador termina cada hora y Docker lo reinicia limpio, lo que evita que acumule memoria.
- **`user: www-data`** y espera a que `web` esté sano, para no competir con él al aplicar las migraciones.

## Evolution API

Implementa ADR-007. Los contenedores `evolution` y `evolution-db` van en el mismo `docker-compose.yml`.

- **Imagen fija:** `evoapicloud/evolution-api:v2.3.7`, la última estable al tomar la decisión.
- **Sin salida pública:** escucha solo en `127.0.0.1:3013` y exige `EVOLUTION_API_KEY`. El sistema la alcanza dentro de Docker en `EVOLUTION_URL`.
- **No guarda conversaciones:** está configurada para guardar solo la sesión del número conectado, sin mensajes, chats, contactos ni historial.
- **Claves:** `completar-env.sh` agrega al `.env` del servidor las variables que falten y genera ahí la clave y la contraseña. `instalar.sh` y `desplegar.sh` lo llaman antes de levantar los contenedores.

### Conectar el WhatsApp

1. El aprendiz abre un túnel desde su equipo: `ssh -L 3013:127.0.0.1:3013 cristian@31.97.129.144`.
2. Abre `http://localhost:3013/manager` e inicia sesión con la clave, que lee en el servidor con `grep EVOLUTION_API_KEY despliegue/.env`.
3. Crea la instancia `taller` y escanea el QR desde WhatsApp → Dispositivos vinculados.
4. Escribe `EVOLUTION_INSTANCIA=taller` en `despliegue/.env` y corre `docker compose up -d` para que `web` y `cola` tomen el valor.

Si la sesión se cierra en el celular, los envíos fallan, los avisos quedan para envío asistido y hay que repetir los pasos 1 a 3.

## Tareas programadas

Las tareas se reparten en dos lugares, según lo que necesiten alcanzar.

**Las de la aplicación** las corre el contenedor `programador`, que usa la misma imagen y ejecuta `php artisan schedule:work`. Están en `routes/console.php` y usan la hora de Colombia porque la aplicación está en esa zona.

| Tarea | Cuándo | Qué hace | Requisito |
| --- | --- | --- | --- |
| Limpiar trabajos fallidos | Domingos, 4:00 a. m. | `php artisan queue:prune-failed --hours=336` | — |

Si una tarea falla, el programador lo anota en el registro de Laravel.

**Los respaldos** los corre el cron del host, como el usuario `cristian`, con el archivo `despliegue/cron/taller` instalado en `/etc/cron.d/taller`.

| Tarea | Cuándo | Qué hace | Requisito |
| --- | --- | --- | --- |
| Respaldo diario | Todos los días, 2:00 a. m. | `despliegue/respaldar.sh` | RNF-15 |
| Copia semanal a Google Drive | Domingos, 3:00 a. m. | `despliegue/copiar-a-drive.sh` | RNF-15 |

**Por qué no van con las otras:** `mysqldump` corre dentro del contenedor `db`, y alcanzarlo desde el `programador` exigiría montarle el socket de Docker, que es darle control del host entero a un contenedor que atiende peticiones. En un VPS compartido eso contradice lo que revisa PM-07. El cliente de MySQL tampoco sirve como atajo: el de Debian es de MariaDB y no se autentica contra MySQL 8.4, que usa `caching_sha2_password`.

La salida de las dos queda en `/var/log/taller-respaldos.log`.

## Respaldos

### Respaldo diario

`despliegue/respaldar.sh`:

1. Crea la carpeta `RESPALDO_CARPETA/AAAA-MM-DD`.
2. Exporta la base con `mysqldump --single-transaction --no-tablespaces` dentro del contenedor `db` y la comprime en `base.sql.gz`. `--single-transaction` copia un estado coherente sin detener el sistema. El dump se escribe y se comprime en dos pasos, y no encadenado con una tubería: en `sh` un fallo de `mysqldump` se perdería y el respaldo quedaría a medias sin que nadie se entere.
3. Empaqueta las fotos del volumen `taller_storage` en `fotos.tar.gz`, con `tar -czf - -C /var/www/taller/sistema/storage/app/privado fotos` dentro del contenedor `web`.
4. Guarda la huella de cada archivo en `sumas.txt` con `sha256sum`, para comprobar en la restauración que no se dañaron.
5. Borra las carpetas de más de `RESPALDO_DIAS` días.
6. Si un paso falla, se detiene con error.

La contraseña llega al contenedor en `MYSQL_PWD` y no como argumento, para que no aparezca en la lista de procesos del servidor (RNF-24).

### Copia semanal

`despliegue/copiar-a-drive.sh`:

1. Copia la carpeta del respaldo más reciente a `RESPALDO_REMOTO/AAAA-MM-DD` con `rclone copy`.
2. Borra en Google Drive las copias que pasen de `RESPALDO_COPIAS_REMOTAS`.

- **Por qué 2 copias:** el requisito no fija cuántas guardar. RNF-15 calcula unos 2,6 GB de fotos después de 3 años; con 2 copias son 5,2 GB, que caben con margen en los 15 GB gratuitos.
- **Configuración de rclone:** el aprendiz la crea en el servidor con `rclone config`. Autorizar la cuenta de Google es un paso que hace él.

### Restauración

`despliegue/restaurar.sh CARPETA BASE CARPETA_STORAGE` deshace el respaldo donde haga falta:

1. Comprueba `sumas.txt` con `sha256sum -c`, por si el viaje a Google Drive y de vuelta dañó algo.
2. Carga `base.sql.gz` en la base que se le indique.
3. Descomprime `fotos.tar.gz` en el disco privado.

No corre en el VPS ni lo toca: el peor caso que mide PM-02 es que el VPS ya no exista. La contraseña se pasa en `MYSQL_PWD`, no por argumento, para que no quede en el historial del shell.

- **Restauración:** se prueba con PM-02 y se explica en el manual técnico (DOC-23).

## Despliegue

Solo se despliega un commit que esté en verde en GitHub Actions. Mientras Actions siga bloqueado por la facturación de la cuenta (HT-02), vale un commit con `scripts/calidad.py` en 7 de 7.

### Primera instalación

El usuario `cristian`, desde la carpeta del clon:

| Paso | Comando | Qué hace |
| --- | --- | --- |
| 1 | `git clone https://github.com/Aryannext/El-taller-ines.git el-taller-ines` | Trae el código |
| 2 | `sh despliegue/instalar.sh` | Crea `despliegue/.env` con la llave y las contraseñas generadas ahí mismo, construye la imagen, levanta los contenedores y espera a que `/up` responda |
| 3 | `sh despliegue/crear-usuaria.sh` | Crea el negocio inicial y la usuaria; la contraseña la escribe la dueña o el aprendiz, sin que se muestre |
| 4 | Agregar la línea `include` al sitio del portafolio, `sudo nginx -t` y `sudo systemctl reload nginx` | Publica `/taller` |
| 5 | Preparar los respaldos, abajo | Programa el respaldo diario y la copia semanal (RNF-15) |
| 6 | `rclone config` | Autoriza la cuenta de Google Drive donde va la copia semanal; solo se hace una vez |

Los pasos 4 y 5 son los únicos que necesitan `sudo`. El paso 5 son tres comandos: el cron corre como `cristian`, así que la carpeta de los respaldos y el registro tienen que existir y ser suyos antes de la primera corrida.

```sh
sudo install -d -o cristian -g cristian /var/respaldos/taller
sudo install -m 644 -o cristian -g cristian /dev/null /var/log/taller-respaldos.log
sudo cp despliegue/cron/taller /etc/cron.d/taller
```

### Actualizar

`despliegue/desplegar.sh`:

| Paso | Comando | Por qué |
| --- | --- | --- |
| 1 | `git pull --ff-only origin main` | Trae el código aprobado; falla si el servidor tiene cambios propios (RNF-34) |
| 2 | `docker compose build web` | Construye la imagen con las dependencias de producción de `composer.lock` |
| 3 | `docker compose up -d` | Recrea `web` y `cola`. Al arrancar, `web` aplica las migraciones (RNF-31) y guarda en caché la configuración, las rutas, las vistas y los eventos |
| 4 | `esperar.sh` | Espera a que `/up` responda |
| 5 | Prueba de humo | La sección F de PM-07 |

- **Dos scripts:** `desplegar.sh` solo hace el paso 1 y luego ejecuta `aplicar.sh` con `exec`, que ya viene actualizado por el pull. Un script que se actualiza a sí mismo mientras corre sigue leyendo la versión anterior: así se desplegaron una vez los contenedores de Evolution API sin sus claves.
- **`aplicar.sh`** corre primero `completar-env.sh`, para que las variables nuevas lleguen al `.env` del servidor antes de levantar los contenedores.
- **Mientras se recrea `web`** el sistema no responde unos segundos; Nginx devuelve 502 en ese momento.
- **Para volver a la versión anterior:** `git checkout <commit anterior>` y los pasos 2 a 4.
- **Migraciones compatibles:** una migración nueva no rompe la versión anterior del código. Por ejemplo, una columna se agrega en un despliegue y se deja de usar antes de borrarla en otro.

El manual técnico (DOC-23) explica estos pasos con más detalle, junto con rclone.

## App en el celular

Implementa ADR-006 y RNF-35.

### Manifiesto

`public/manifest.webmanifest`:

| Campo | Valor |
| --- | --- |
| `name` | El-taller-ines |
| `short_name` | Taller |
| `lang` | `es-CO` |
| `start_url` | `./` |
| `scope` | `./` |
| `display` | `standalone` |
| `orientation` | `portrait` |
| `background_color` | `#f3f4f8`, el `--fondo` de los mockups |
| `theme_color` | `#2a44a8`, el `--primario` de los mockups |
| `icons` | `iconos/icono-192.png`, `iconos/icono-512.png` y `iconos/icono-maskable-512.png`, que Android puede recortar en círculo |

Los íconos llevan las tijeras de la marca, las mismas de PT-01, en blanco sobre el color primario. `start_url` y `scope` son relativos al manifiesto, así el sistema se instala igual en `/taller` que en la raíz de un dominio propio.

### Service worker

`public/sw.js`:

- Al instalarse, guarda en caché solo `sin-conexion.html`, `css/estilos.css`, `js/app.js`, las fuentes y los íconos.
- `app.js` lo registra tomando su dirección del manifiesto, así el alcance queda en `/taller` sin escribirlo en el código.
- Para abrir una página, pide primero a la red. Si no hay red, muestra `sin-conexion.html`.
- Los estilos, el guion, las fuentes y los íconos también se piden primero a la red, y la respuesta actualiza el caché. Si se sirvieran desde el caché, un despliegue nuevo no se vería hasta cambiarle el nombre al caché a mano.
- **Nunca guarda páginas ni fotos con datos del taller.** Así, un celular perdido sin conexión no muestra información de clientes (RNF-25).
- Nginx lo sirve sin caché, para que una versión nueva llegue enseguida. Cuando cambian los archivos que guarda, cambia el nombre de su caché: `taller-v1`, `taller-v2`.

### Página sin conexión

`public/sin-conexion.html` es una página estática con los estilos del sistema y un botón para reintentar. Su mensaje: «Sin internet. El sistema necesita conexión para guardar y consultar tus órdenes. Revisa el wifi o los datos y vuelve a intentarlo.»

### Enlace entre el APK y el sitio

Android comprueba que el APK y el sitio son del mismo dueño leyendo `https://proyectosena.online/.well-known/assetlinks.json`, **en la raíz del dominio**. No lo busca bajo `/taller`, y exige que responda 200 con `application/json` y sin redirecciones.

- **Dónde está el archivo:** `sistema/public/.well-known/assetlinks.json`, en el repositorio. El contenedor lo sirve como cualquier archivo de `public/`.
- **Cómo llega a la raíz:** `taller.conf` tiene una `location = /.well-known/assetlinks.json` que lo pide al contenedor. Si el archivo estuviera solo en `public/`, sin esa `location`, quedaría publicado en `/taller/.well-known/assetlinks.json`, donde Android nunca mira, y el APK abriría con la barra del navegador.
- **Si otra app del dominio lo necesitara:** hay un solo archivo por dominio. Sus declaraciones irían en el mismo arreglo, no en otra `location`.

El contenido:

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
| **Disponibilidad** | UptimeRobot consulta `https://proyectosena.online/taller/up` cada 5 minutos y avisa por correo al aprendiz si falla | RNF-16 |
| **Errores** | Registro diario de Laravel en `storage/logs`, dentro del volumen `taller_storage`, conservado 14 días; `docker compose logs web` muestra los de Apache y PHP | — |
| **Contenedores** | `docker compose ps` muestra si `web` está sano y si `cola`, `programador` y `db` corren | HT-04 |
| **Cola** | `docker compose exec web php artisan queue:failed` lista los envíos fallidos | RNF-17 |
| **Respaldos** | El cron del host escribe cada corrida en `/var/log/taller-respaldos.log`; PM-02 revisa que existan los 14 días | RNF-15 |
