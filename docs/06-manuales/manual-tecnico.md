# Manual técnico y de instalación · El-taller-ines

**Entregable:** DOC-23 · **Versión del sistema:** la de `main` el 22 de septiembre de 2026 · [English version](technical-manual.md)

Para quien instala, mantiene u opera el sistema: un desarrollador que lo instala en su máquina, o quien administra el servidor. La dueña del taller tiene su propio [manual de usuario](manual-de-usuario.md).

Este manual dice **cómo hacer** cada tarea. El **por qué** de cada decisión está en la [especificación técnica](../04-especificacion-tecnica/README.md) y en las [decisiones de arquitectura](../03-diseno/adr/). Todo se puede recorrer, con los códigos enlazados, en el **[portal del proyecto](https://aryannext.github.io/El-taller-ines/)**.

## Contenido

1. [Cómo está hecho](#1-cómo-está-hecho)
2. [Instalar en una máquina de desarrollo](#2-instalar-en-una-máquina-de-desarrollo)
3. [Trabajar en el código](#3-trabajar-en-el-código)
4. [El servidor de producción](#4-el-servidor-de-producción)
5. [Respaldos y restauración](#5-respaldos-y-restauración)
6. [La app para Android](#6-la-app-para-android)
7. [Monitoreo y registros](#7-monitoreo-y-registros)
8. [Solución de problemas](#8-solución-de-problemas)

## 1. Cómo está hecho

| Pieza | Qué es |
| --- | --- |
| **Aplicación** | Laravel 13 con PHP 8.4. Vistas Blade, un solo CSS y un solo JavaScript propio, sin paso de compilación ni Node ([ADR-001](../03-diseno/adr/ADR-001-laravel-mysql.md)) |
| **Base de datos** | MySQL 8.4. **No sirve MariaDB**: el buscador de clientes usa la intercalación `utf8mb4_0900_ai_ci` y las validaciones usan expresiones regulares de MySQL 8 |
| **Capas** | `Dominio` (reglas puras) → `Aplicacion` (un caso de uso o consulta por acción) → `Http` (controladores y validación) e `Infraestructura` (WhatsApp, fotos, reloj). Las reglas de capas las comprueba PHPat ([ADR-005](../03-diseno/adr/ADR-005-arquitectura-en-capas.md)) |
| **Varios negocios** | Toda consulta se filtra por el negocio de la sesión ([ADR-002](../03-diseno/adr/ADR-002-un-taller-preparado-para-varios.md)) |
| **Avisos** | Una cola en la base de datos. Se envían por Evolution API, o por la API oficial de WhatsApp, y si ninguna está configurada quedan para envío asistido ([ADR-003](../03-diseno/adr/ADR-003-canal-de-avisos-whatsapp.md), [ADR-007](../03-diseno/adr/ADR-007-avisos-por-evolution-api.md)) |
| **Fotos** | Se reducen en el servidor y se guardan en un disco privado, `storage/app/privado`: solo se ven con la sesión iniciada |
| **Celular** | Aplicación web instalable, más un APK para Android que la abre a pantalla completa ([ADR-006](../03-diseno/adr/ADR-006-instalacion-en-el-celular.md)) |

**Carpetas del repositorio:**

| Carpeta | Contenido |
| --- | --- |
| `sistema/` | La aplicación Laravel |
| `despliegue/` | Docker, Nginx, cron y los scripts del servidor |
| `movil/` | La configuración del APK (`twa-manifest.json`); lo demás lo genera Bubblewrap y no se versiona |
| `docs/` | Toda la documentación, del problema a las pruebas |
| `scripts/` | Verificadores y generadores de la documentación, la calidad local y el portal |

## 2. Instalar en una máquina de desarrollo

Con los programas ya instalados, esto toma menos de 30 minutos (RNF-33, se comprueba con [PM-03](../05-pruebas/pruebas-manuales/PM-03-instalacion-desde-el-manual.md)).

### Lo que necesita la máquina

| Programa | Versión | Nota |
| --- | --- | --- |
| **PHP** | 8.4 | Con las extensiones `pdo_mysql`, `mbstring`, `gd`, `exif`, `fileinfo`, `curl`, `openssl` y `zip`. `gd` reduce las fotos: sin ella, subir una foto falla |
| **Composer** | 2 | |
| **MySQL** | 8.4 | No MariaDB. En Windows sirve el de WAMP o Laragon, siempre que sea MySQL 8.4 |
| **Git** | cualquiera reciente | |

No hace falta Node, ni Docker, ni un servidor web: Laravel trae el suyo para desarrollo.

Para comprobarlo:

```sh
php -v
php -m
composer -V
mysql --version
```

`php -m` debe listar las extensiones de la tabla.

### Pasos

**1. Traer el código.** En Windows, en una carpeta de ruta corta, como `C:\proyectos`: algunos archivos del proyecto tienen nombres largos y, dentro de una carpeta profunda, Git falla con «Filename too long» porque Windows limita las rutas a 260 caracteres.

```sh
git clone https://github.com/Aryannext/El-taller-ines.git el-taller-ines
cd el-taller-ines/sistema
```

**2. Instalar las dependencias de PHP.** La primera vez descarga todo y es el paso más largo: unos 12 minutos en el ensayo de este manual. Las siguientes, Composer usa su caché.

```sh
composer install
```

**3. Crear el archivo de configuración y su llave.**

```sh
cp .env.example .env
php artisan key:generate
```

En Windows, con `cmd`, el primero es `copy .env.example .env`.

**4. Crear las bases de datos y su usuario.** Entrar a MySQL como `root` (`mysql -u root -p`) y ejecutar, cambiando la contraseña:

```sql
CREATE DATABASE taller CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE DATABASE taller_pruebas CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE USER 'taller'@'localhost' IDENTIFIED BY 'una-contraseña-local';
GRANT ALL PRIVILEGES ON taller.* TO 'taller'@'localhost';
GRANT ALL PRIVILEGES ON taller_pruebas.* TO 'taller'@'localhost';
```

`taller` es la del sistema; `taller_pruebas` la usan las pruebas automáticas y se borra en cada corrida, así que nunca debe ser la misma.

**5. Configurar `.env`.** Abrirlo y completar:

| Variable | Qué poner |
| --- | --- |
| `DB_PORT` | El puerto de tu MySQL 8.4; `3306` si es el único |
| `DB_PASSWORD` | La contraseña del paso 4 |
| `USUARIA_INICIAL_USUARIO` | El usuario con el que vas a entrar; `taller` si no cambias nada |
| `USUARIA_INICIAL_CONTRASENA` | Una contraseña para ese usuario |

Las demás se quedan como vienen. Con las variables de WhatsApp vacías, los avisos quedan para envío asistido, que es lo que se quiere al desarrollar.

**6. Crear las tablas.**

```sh
php artisan migrate
```

**7. Crear el negocio y la usuaria.** Crea el negocio, la usuaria del paso 5, los tipos de prenda y los métodos de pago iniciales.

```sh
php artisan db:seed --class=NegocioInicialSeeder
```

Después, **borrar el valor de `USUARIA_INICIAL_CONTRASENA`** de `.env`: ya no se necesita.

**8. (Opcional) Cargar los datos de ejemplo.** Son los de los mockups: Marta Rincón, la orden #0042 y los demás. Sirven para ver el sistema con información.

```sh
php artisan db:seed --class=DatosDeLosMockupsSeeder
```

**9. Abrir el sistema.**

```sh
php artisan serve
```

Entrar a **http://localhost:8000** con el usuario y la contraseña del paso 5. Debe aparecer la pantalla **Hoy**.

**10. Correr las pruebas.**

```sh
php artisan test
```

Todas deben pasar. Usan la base `taller_pruebas`.

### Lo que no corre solo en desarrollo

| Qué | Cómo |
| --- | --- |
| **La cola de avisos** | Sin trabajador, un aviso automático queda «En cola». Para procesarlos: `php artisan queue:work --queue=avisos` en otra terminal |
| **Las tareas programadas** | `php artisan schedule:work`. Hoy solo limpian trabajos fallidos: no hacen falta para desarrollar |

## 3. Trabajar en el código

- **Convenciones:** nombres en español, una clase por caso de uso, reglas citadas por su código en los comentarios (`// RN-22: …`). Están en [convenciones de código](../04-especificacion-tecnica/08-convenciones-de-codigo.md).
- **Antes de cada commit:** `python scripts/calidad.py` corre en la máquina los mismos pasos que GitHub Actions: secretos, dependencias vulnerables, estilo con Pint, análisis estático con Larastan, vistas, migraciones y pruebas en un MySQL temporal, y los verificadores de la documentación. Necesita Python 3.12 o más.
- **Solo el estilo:** `vendor/bin/pint` lo corrige; `vendor/bin/pint --test` solo revisa.
- **Solo el análisis estático:** `vendor/bin/phpstan analyse`.
- **Una historia nueva** necesita su caso de uso, su prueba con el nombre del criterio (`test_ca_11_1_orden_lista`), su ruta en la [especificación de rutas](../04-especificacion-tecnica/02-rutas.md) y, si la ruta recibe un parámetro, su caso en `AislamientoEntreNegociosTest`: una prueba falla si falta.
- **La documentación generada** no se edita a mano: se corrige el documento de origen y se corre su generador (`scripts/generar_*.py`). Los verificadores (`scripts/verificar_*.py`) comprueban que el diseño, el esquema y el código coincidan.
- **El portal:** `pip install markdown` y `python scripts/generar_portal.py` lo escriben en `portal/`. CI lo publica solo con cada cambio en `main`.

## 4. El servidor de producción

El sistema corre en un VPS con Ubuntu 24.04 **compartido con otros proyectos**, en `https://proyectosena.online/taller`. Como el servidor tiene otras versiones de PHP y una base MariaDB para esos proyectos, el sistema corre en contenedores Docker y no toca nada de ellos. Por la misma razón, **no se usa `sudo` más que en los pasos que lo dicen**, y nunca se tocan los contenedores ni las carpetas de otros usuarios.

| Contenedor | Qué hace |
| --- | --- |
| `web` | La aplicación, con PHP 8.4 y Apache, en `127.0.0.1:3012`. Aplica las migraciones al arrancar |
| `cola` | El trabajador de los avisos. Se reinicia solo |
| `programador` | Las tareas programadas de la aplicación |
| `db` | MySQL 8.4, sin puertos hacia afuera |
| `evolution` y `evolution-db` | Evolution API, que envía los avisos por WhatsApp, y su base PostgreSQL. Escucha en `127.0.0.1:3013` |

El Nginx del portafolio recibe el HTTPS y, con `despliegue/nginx/taller.conf`, pasa `/taller/` al contenedor `web`, publica `/.well-known/assetlinks.json` en la raíz del dominio y sirve el APK en `/taller/descargas/`.

Todos los comandos se ejecutan como el usuario `cristian`, en `/home/cristian/proyectos/proyectosena.online/el-taller-ines`. Los de Docker se ejecutan dentro de `despliegue/`.

### Primera instalación

**1. Traer el código.**

```sh
cd /home/cristian/proyectos/proyectosena.online
git clone https://github.com/Aryannext/El-taller-ines.git el-taller-ines
cd el-taller-ines
```

**2. Levantar el sistema.** Crea `despliegue/.env` con la llave y las contraseñas generadas en el servidor (nadie las escribe ni las ve), construye la imagen, levanta los contenedores y espera a que responda.

```sh
sh despliegue/instalar.sh
```

**3. Crear la usuaria de la dueña.** Pide el usuario y la contraseña sin mostrarla; la contraseña no queda en ningún archivo.

```sh
sh despliegue/crear-usuaria.sh
```

**4. Publicar `/taller` en Nginx** (necesita `sudo`). En el bloque `server` 443 de `proyectosena.online`, agregar:

```nginx
include /home/cristian/proyectos/proyectosena.online/el-taller-ines/despliegue/nginx/taller.conf;
```

y comprobar y recargar:

```sh
sudo nginx -t && sudo systemctl reload nginx
```

**5. Programar los respaldos** (necesita `sudo`, una sola vez):

```sh
sudo install -d -o cristian -g cristian /var/respaldos/taller
sudo install -m 644 -o cristian -g cristian /dev/null /var/log/taller-respaldos.log
sudo cp despliegue/cron/taller /etc/cron.d/taller
```

**6. Autorizar Google Drive** para la copia semanal: `rclone config`, crear un remoto llamado `drive` de tipo Google Drive con acceso `drive.file`, y autorizar la cuenta desde el navegador cuando rclone lo pida. La carpeta de destino es la de `RESPALDO_REMOTO` en `despliegue/.env` (`drive:taller-respaldos`).

**7. Conectar el WhatsApp del taller**, abajo.

**Comprobar:** `https://proyectosena.online/taller/up` responde 200 y el inicio de sesión abre.

### Conectar el WhatsApp

Los avisos automáticos salen por el WhatsApp que se vincule a Evolution API, como WhatsApp Web.

1. Desde tu equipo, abrir un túnel al servidor:

   ```sh
   ssh -L 3013:127.0.0.1:3013 cristian@proyectosena.online
   ```

2. En el servidor, leer la clave: `grep EVOLUTION_API_KEY despliegue/.env`.
3. En tu navegador, abrir `http://localhost:3013/manager` y entrar con esa clave.
4. Crear la instancia `taller` y escanear el QR desde el celular del taller: **WhatsApp → Dispositivos vinculados → Vincular un dispositivo**.
5. En el servidor, poner `EVOLUTION_INSTANCIA=taller` en `despliegue/.env` y aplicar: `cd despliegue && docker compose up -d`.

Si se cierra la sesión en el celular, los avisos no se pierden: quedan para envío asistido hasta que se repitan los pasos 1 a 4.

### Actualizar a una versión nueva

Solo se despliega un commit que esté en verde en GitHub Actions.

```sh
sh despliegue/desplegar.sh
```

Trae el código (`git pull --ff-only`), agrega al `.env` las variables nuevas, reconstruye la imagen, recrea los contenedores (las migraciones se aplican solas) y espera a que `/up` responda. Durante unos segundos el sistema devuelve 502 mientras se recrea `web`.

**Si el `git pull` ya se hizo a mano**, el resto se aplica con `sh despliegue/aplicar.sh`.

**Volver a la versión anterior:**

```sh
git checkout <commit-anterior>
sh despliegue/aplicar.sh
```

### Tareas frecuentes

| Qué | Comando (dentro de `despliegue/`) |
| --- | --- |
| Ver el estado de los contenedores | `docker compose ps` |
| Ver los registros de un contenedor | `docker compose logs --tail 100 web` (o `cola`, `evolution`) |
| Un comando de Laravel | `docker compose exec -u www-data web php artisan <comando>` |
| Reiniciar el trabajador de avisos | `docker compose restart cola` |

**Siempre `-u www-data`** en los comandos que escriben en `storage`: sin él corren como `root`, y los archivos que crean, por ejemplo una carpeta de fotos, quedan sin permiso para la aplicación. El síntoma es un error 500 al ver una foto.

## 5. Respaldos y restauración

| Respaldo | Cuándo | Dónde |
| --- | --- | --- |
| **Diario** | 2:00 a. m. | `/var/respaldos/taller/AAAA-MM-DD/`: `base.sql.gz`, `fotos.tar.gz` y `sumas.txt`. Se conservan 14 días |
| **Semanal** | Domingos, 3:00 a. m. | La copia del día en Google Drive, en `taller-respaldos/`. Se conservan 2 |

**Comprobar que funcionan:** `tail /var/log/taller-respaldos.log` debe mostrar la corrida de la última noche sin errores, y `ls /var/respaldos/taller` una carpeta por día.

**Respaldar a mano**, por ejemplo antes de un cambio delicado: `sh despliegue/respaldar.sh`.

### Restaurar

Se restaura **en otra máquina**: el peor caso es que el servidor ya no exista. Probado en [PM-02](../05-pruebas/pruebas-manuales/PM-02-restauracion-de-respaldos.md): 7 segundos, con los conteos idénticos.

1. **Traer el respaldo** a la máquina: la carpeta del día, de `/var/respaldos/taller` o de Google Drive (`rclone copy drive:taller-respaldos/AAAA-MM-DD ./AAAA-MM-DD`).
2. **Tener el sistema instalado** en esa máquina, [como en la sección 2](#2-instalar-en-una-máquina-de-desarrollo), con una base vacía para restaurar, por ejemplo `taller_restaurada`.
3. **Restaurar.** La contraseña de MySQL se da por variable, no como argumento, para que no quede en el historial:

   ```sh
   read -r clave; MYSQL_PWD=$clave; export MYSQL_PWD
   MYSQL_USER=taller MYSQL_PORT=3306 sh despliegue/restaurar.sh ./AAAA-MM-DD taller_restaurada sistema/storage
   ```

   Comprueba primero las sumas del respaldo; si algún archivo se dañó, se detiene sin tocar nada. Luego carga la base y las fotos.
4. **Apuntar el sistema** a esa base (`DB_DATABASE` en `.env`), entrar y revisar una orden con fotos.

## 6. La app para Android

El APK se genera con **Bubblewrap** en la máquina del desarrollador, desde `movil/twa-manifest.json`. La llave de firma **no está en el repositorio** y hay que respaldarla: sin ella no se puede publicar una actualización de la app.

**Primera vez:**

1. `npm install -g @bubblewrap/cli` (Node solo hace falta aquí).
2. En `movil/`: `bubblewrap update --skipVersionUpgrade`. Descarga su JDK y el SDK de Android y pide aceptar la licencia.
3. Crear la llave, si no existe, con el `keytool` del JDK que descargó Bubblewrap, en `~/.bubblewrap/jdk/<versión>/bin/`:

   ```sh
   keytool -genkeypair -v -keystore <ruta>/taller.keystore -alias taller -keyalg RSA -keysize 2048 -validity 10000
   ```

   `bubblewrap build` no crea la llave. La contraseña del almacén y la de la llave son la misma.
4. Copiar la llave a un lugar seguro, fuera del equipo.

**Cada versión:**

1. Subir `appVersionCode` en 1 en `movil/twa-manifest.json` y ajustar `appVersionName`.
2. En `movil/`: `bubblewrap build`. Pide la contraseña y deja `app-release-signed.apk`.
3. Publicarlo en el servidor:

   ```sh
   scp movil/app-release-signed.apk cristian@proyectosena.online:descargas-taller/taller-1.0.1.apk
   ssh cristian@proyectosena.online "cd descargas-taller && cp taller-1.0.1.apk taller.apk"
   ```

   Queda en `https://proyectosena.online/taller/descargas/taller.apk`.

**El enlace entre el APK y el sitio:** `sistema/public/.well-known/assetlinks.json` lleva la huella SHA-256 de la llave. Si la llave cambia, hay que cambiar la huella; se saca del APK firmado con `apksigner verify --print-certs`. Para comprobarlo en un celular conectado por USB: `adb shell pm get-app-links online.proyectosena.taller` debe decir `verified`.

**Los cambios del sistema no necesitan un APK nuevo:** el APK solo abre la dirección del sistema. Se genera otro cuando cambia el nombre, el ícono, los colores o la dirección.

## 7. Monitoreo y registros

| Qué | Dónde |
| --- | --- |
| **Disponibilidad** | UptimeRobot consulta `/taller/up` cada 5 minutos y avisa por correo si falla |
| **Errores de la aplicación** | `docker compose exec -u www-data web tail -n 100 storage/logs/laravel-$(date +%F).log`: un archivo por día, se conservan 14 |
| **Avisos que no salieron** | Los registra la aplicación como advertencia. Los que agotan los reintentos quedan para envío asistido y aparecen en la pantalla Hoy |
| **Respaldos** | `/var/log/taller-respaldos.log` |
| **Calidad del código** | GitHub Actions, en cada envío. Si Actions falla en segundos sin llegar a los pasos, revisar primero la facturación de la cuenta de GitHub: Actions se bloquea aunque el repositorio sea público |

## 8. Solución de problemas

| Síntoma | Causa probable | Qué hacer |
| --- | --- | --- |
| `git clone` falla con «Filename too long» y «checkout failed» | Windows limita las rutas a 260 caracteres y la carpeta de destino es muy profunda | Clonar en una ruta corta, como `C:\proyectos`, o habilitar las rutas largas en Git: `git config --global core.longpaths true` |
| `SQLSTATE[HY000] [1045] Access denied` | Usuario, contraseña o puerto de `.env` distintos a los de MySQL | Revisar `DB_USERNAME`, `DB_PASSWORD` y `DB_PORT` |
| Las migraciones fallan con un error de intercalación o de `CHECK` | La base es MariaDB, no MySQL 8.4 | Instalar MySQL 8.4 |
| `No application encryption key has been specified` | Faltó `php artisan key:generate` | Correrlo |
| Subir una foto da error 500 | Falta la extensión `gd`, o en producción la carpeta de fotos quedó con dueño `root` | Activar `gd`; en producción, corregir el dueño con `docker compose exec web chown -R www-data:www-data storage/app/privado` |
| Las pruebas borran datos | La base de pruebas es la misma del sistema | `phpunit.xml` usa `taller_pruebas`: debe existir y ser distinta de `taller` |
| El aviso queda «En cola» y no sale | No hay trabajador de la cola | En desarrollo, `php artisan queue:work --queue=avisos`; en producción, `docker compose ps` y `restart cola` |
| Todos los avisos quedan «Por enviar» | El WhatsApp se desvinculó de Evolution API | [Volver a conectarlo](#conectar-el-whatsapp) |
| `/taller` responde 502 | `web` se está recreando o se detuvo | Esperar unos segundos; si sigue, `docker compose ps` y `docker compose logs web` |
| El APK abre con la barra del navegador | Si dura 2 segundos, el navegador predeterminado no es Chrome; si no se va, `assetlinks.json` no coincide con la llave | Ver [la app para Android](#6-la-app-para-android) |
| Gradle se cae sin error al construir el APK | El JDK que bajó Bubblewrap es de 32 bits y no le alcanza la memoria | Instalar Temurin 17 de 64 bits y cambiar `jdkPath` en `~/.bubblewrap/config.json` |
