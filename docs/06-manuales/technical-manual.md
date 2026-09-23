# Technical and installation manual · Puntada

**Deliverable:** DOC-23 · **System version:** `main` as of September 22, 2026 · [Versión en español](manual-tecnico.md)

For whoever installs, maintains or operates the system: a developer installing it on their machine, or whoever runs the server. The shop owner has her own [user manual](user-manual.md).

This manual says **how** to do each task. The **why** behind each decision is in the [technical specification](../04-especificacion-tecnica/README.md) and the [architecture decisions](../03-diseno/adr/), both in Spanish. Everything can be browsed, with every code linked, in the **[project portal](https://aryannext.github.io/puntada/)**.

The code, the screens and the documentation are in Spanish. File, class and command names are given as they are.

## Contents

1. [How it is built](#1-how-it-is-built)
2. [Installing on a development machine](#2-installing-on-a-development-machine)
3. [Working on the code](#3-working-on-the-code)
4. [The production server](#4-the-production-server)
5. [Backups and restore](#5-backups-and-restore)
6. [The Android app](#6-the-android-app)
7. [Monitoring and logs](#7-monitoring-and-logs)
8. [Troubleshooting](#8-troubleshooting)

## 1. How it is built

| Part | What it is |
| --- | --- |
| **Application** | Laravel 13 on PHP 8.4. Blade views, one stylesheet and one hand-written script, with no build step and no Node ([ADR-001](../03-diseno/adr/ADR-001-laravel-mysql.md)) |
| **Database** | MySQL 8.4. **MariaDB does not work**: customer search uses the `utf8mb4_0900_ai_ci` collation and validation uses MySQL 8 regular expressions |
| **Layers** | `Dominio` (pure rules) → `Aplicacion` (one use case or query per action) → `Http` (controllers and validation) and `Infraestructura` (WhatsApp, photos, clock). PHPat enforces the layer rules ([ADR-005](../03-diseno/adr/ADR-005-arquitectura-en-capas.md)) |
| **Several businesses** | Every query is filtered by the signed-in business ([ADR-002](../03-diseno/adr/ADR-002-un-taller-preparado-para-varios.md)) |
| **Notices** | A database queue. They are sent through Evolution API or the official WhatsApp API; if neither is configured, they wait for assisted sending ([ADR-003](../03-diseno/adr/ADR-003-canal-de-avisos-whatsapp.md), [ADR-007](../03-diseno/adr/ADR-007-avisos-por-evolution-api.md)) |
| **Photos** | Shrunk on the server and stored on a private disk, `storage/app/privado`: they can only be seen while signed in |
| **Phone** | An installable web app, plus an Android APK that opens it full screen ([ADR-006](../03-diseno/adr/ADR-006-instalacion-en-el-celular.md)) |

**Repository folders:**

| Folder | Contents |
| --- | --- |
| `sistema/` | The Laravel application |
| `despliegue/` | Docker, Nginx, cron and the server scripts |
| `movil/` | The APK configuration (`twa-manifest.json`); Bubblewrap generates the rest, which is not versioned |
| `docs/` | All the documentation, from the problem to the tests |
| `scripts/` | Documentation checkers and generators, the local quality run and the portal |

## 2. Installing on a development machine

With the programs already installed, this takes under 30 minutes (RNF-33, checked with [PM-03](../05-pruebas/pruebas-manuales/PM-03-instalacion-desde-el-manual.md)).

### What the machine needs

| Program | Version | Note |
| --- | --- | --- |
| **PHP** | 8.4 | With the `pdo_mysql`, `mbstring`, `gd`, `exif`, `fileinfo`, `curl`, `openssl` and `zip` extensions. `gd` shrinks photos: without it, uploading a photo fails |
| **Composer** | 2 | |
| **MySQL** | 8.4 | Not MariaDB. On Windows, WAMP's or Laragon's works, as long as it is MySQL 8.4 |
| **Git** | any recent one | |

No Node, Docker or web server is needed: Laravel ships its own for development.

To check:

```sh
php -v
php -m
composer -V
mysql --version
```

`php -m` must list the extensions in the table.

### Steps

**1. Get the code.** On Windows, into a folder with a short path, such as `C:\proyectos`: some project files have long names and, inside a deep folder, Git fails with «Filename too long» because Windows limits paths to 260 characters.

```sh
git clone https://github.com/Aryannext/puntada.git puntada
cd puntada/sistema
```

**2. Install the PHP dependencies.** The first time it downloads everything and it is the longest step: about 12 minutes in this manual's trial run. After that, Composer uses its cache.

```sh
composer install
```

**3. Create the configuration file and its key.**

```sh
cp .env.example .env
php artisan key:generate
```

On Windows `cmd`, the first one is `copy .env.example .env`.

**4. Create the databases and their user.** Sign in to MySQL as `root` (`mysql -u root -p`) and run, changing the password:

```sql
CREATE DATABASE taller CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE DATABASE taller_pruebas CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
CREATE USER 'taller'@'localhost' IDENTIFIED BY 'a-local-password';
GRANT ALL PRIVILEGES ON taller.* TO 'taller'@'localhost';
GRANT ALL PRIVILEGES ON taller_pruebas.* TO 'taller'@'localhost';
```

`taller` is the system's database; `taller_pruebas` is used by the automated tests and wiped on every run, so it must never be the same one.

**5. Configure `.env`.** Open it and fill in:

| Variable | Value |
| --- | --- |
| `DB_PORT` | Your MySQL 8.4 port; `3306` if it is the only one |
| `DB_PASSWORD` | The password from step 4 |
| `USUARIA_INICIAL_USUARIO` | The username you will sign in with; `taller` if you change nothing |
| `USUARIA_INICIAL_CONTRASENA` | A password for that user |

Leave the rest as they are. With the WhatsApp variables empty, notices wait for assisted sending, which is what you want while developing.

**6. Create the tables.**

```sh
php artisan migrate
```

**7. Create the business and its user.** This creates the business, the user from step 5, and the initial garment types and payment methods.

```sh
php artisan db:seed --class=NegocioInicialSeeder
```

Afterwards, **delete the value of `USUARIA_INICIAL_CONTRASENA`** from `.env`: it is no longer needed.

**8. (Optional) Load the sample data.** It is the mockups' data: Marta Rincón, order #0042 and the rest. Useful to see the system with information in it.

```sh
php artisan db:seed --class=DatosDeLosMockupsSeeder
```

**9. Open the system.**

```sh
php artisan serve
```

Go to **http://localhost:8000** and sign in with the username and password from step 5. The **Hoy** (Today) screen must appear.

**10. Run the tests.**

```sh
php artisan test
```

All of them must pass. They use the `taller_pruebas` database.

### What does not run by itself in development

| What | How |
| --- | --- |
| **The notice queue** | Without a worker, an automatic notice stays «En cola» (Queued). To process them: `php artisan queue:work --queue=avisos` in another terminal |
| **Scheduled tasks** | `php artisan schedule:work`. Today they only prune failed jobs: not needed to develop |

## 3. Working on the code

- **Conventions:** Spanish names, one class per use case, rules cited by code in comments (`// RN-22: …`). See the [code conventions](../04-especificacion-tecnica/08-convenciones-de-codigo.md).
- **Before every commit:** `python scripts/calidad.py` runs on your machine the same steps as GitHub Actions: secrets, vulnerable dependencies, Pint style, Larastan static analysis, views, migrations and tests on a temporary MySQL, and the documentation checkers. It needs Python 3.12 or newer.
- **Style only:** `vendor/bin/pint` fixes it; `vendor/bin/pint --test` only checks.
- **Static analysis only:** `vendor/bin/phpstan analyse`.
- **A new story** needs its use case, a test named after each criterion (`test_ca_11_1_orden_lista`), its route in the [routes specification](../04-especificacion-tecnica/02-rutas.md) and, if the route takes a parameter, its case in `AislamientoEntreNegociosTest`: a test fails if it is missing.
- **Generated documentation** is not edited by hand: fix the source document and run its generator (`scripts/generar_*.py`). The checkers (`scripts/verificar_*.py`) make sure design, schema and code agree.
- **The portal:** `pip install markdown` and `python scripts/generar_portal.py` write it to `portal/`. CI publishes it on every change to `main`.

## 4. The production server

The system runs on an Ubuntu 24.04 VPS **shared with other projects**, at `https://proyectosena.online/taller`. Because the server has other PHP versions and a MariaDB database for those projects, the system runs in Docker containers and touches none of them. For the same reason, **`sudo` is only used in the steps that say so**, and other users' containers and folders are never touched.

| Container | What it does |
| --- | --- |
| `web` | The application, with PHP 8.4 and Apache, on `127.0.0.1:3012`. Applies migrations on start |
| `cola` | The notice worker. Restarts by itself |
| `programador` | The application's scheduled tasks |
| `db` | MySQL 8.4, with no ports exposed |
| `evolution` and `evolution-db` | Evolution API, which sends notices through WhatsApp, and its PostgreSQL database. Listens on `127.0.0.1:3013` |

The portfolio's Nginx terminates HTTPS and, through `despliegue/nginx/taller.conf`, passes `/taller/` to the `web` container, serves `/.well-known/assetlinks.json` at the domain root and serves the APK at `/taller/descargas/`.

Every command runs as the `cristian` user in `/home/cristian/proyectos/proyectosena.online/el-taller-ines`. Docker commands run inside `despliegue/`.

### First installation

**1. Get the code.**

```sh
cd /home/cristian/proyectos/proyectosena.online
git clone https://github.com/Aryannext/puntada.git puntada
cd puntada
```

**2. Bring the system up.** It creates `despliegue/.env` with the key and passwords generated on the server (nobody types or sees them), builds the image, starts the containers and waits until it answers.

```sh
sh despliegue/instalar.sh
```

**3. Create the owner's user.** It asks for the username, the Google address —optional— and the password without showing it; the password is not stored in any file. With the address registered, the owner signs in by tapping «Entrar con Google», typing nothing (HU-37).

```sh
sh despliegue/crear-usuaria.sh
```

**3.1. Signing in with Google, optional.** For the «Entrar con Google» button, create a project in [Google Cloud](https://console.cloud.google.com/):

1. **APIs & Services → OAuth consent screen.** Type **External**, with the system name, the support address and the link to the data policy: `https://proyectosena.online/taller/politica-de-datos`.
2. **Credentials → Create credentials → OAuth client ID**, type **Web application**. Under **Authorised redirect URIs**, exactly: `https://proyectosena.online/taller/entrar/google/respuesta`.
3. Copy the **client ID** and the **client secret** into the server's `.env`, as `GOOGLE_IDENTIFICADOR` and `GOOGLE_SECRETO`, and deploy again.

Only the address and the name are requested —no other data—, so with basic scopes Google usually does not require app verification. If it did, testing mode allows up to 100 authorised addresses, plenty for one shop.

**Without those two variables the button does not appear** and sign-in works with username and password: the system runs the same.

**4. Publish `/taller` in Nginx** (needs `sudo`). In the `server` 443 block of `proyectosena.online`, add:

```nginx
include /home/cristian/proyectos/proyectosena.online/el-taller-ines/despliegue/nginx/taller.conf;
```

then test and reload:

```sh
sudo nginx -t && sudo systemctl reload nginx
```

**5. Schedule the backups** (needs `sudo`, only once):

```sh
sudo install -d -o cristian -g cristian /var/respaldos/taller
sudo install -m 644 -o cristian -g cristian /dev/null /var/log/taller-respaldos.log
sudo cp despliegue/cron/taller /etc/cron.d/taller
```

**6. Authorize Google Drive** for the weekly copy: `rclone config`, create a remote named `drive` of type Google Drive with `drive.file` access, and authorize the account in the browser when rclone asks. The destination folder is `RESPALDO_REMOTO` in `despliegue/.env` (`drive:taller-respaldos`).

**7. Connect the shop's WhatsApp**, below.

**Check:** `https://proyectosena.online/taller/up` answers 200 and the sign-in page opens.

### Connecting WhatsApp

Automatic notices are sent from the WhatsApp linked to Evolution API, like WhatsApp Web.

1. From your computer, open a tunnel to the server:

   ```sh
   ssh -L 3013:127.0.0.1:3013 cristian@proyectosena.online
   ```

2. On the server, read the key: `grep EVOLUTION_API_KEY despliegue/.env`.
3. In your browser, open `http://localhost:3013/manager` and sign in with that key.
4. Create the `taller` instance and scan the QR code from the shop's phone: **WhatsApp → Linked devices → Link a device**.
5. On the server, set `EVOLUTION_INSTANCIA=taller` in `despliegue/.env` and apply it: `cd despliegue && docker compose up -d`.

If the session is closed on the phone, notices are not lost: they wait for assisted sending until steps 1 to 4 are repeated.

### Updating to a new version

Only deploy a commit that is green in GitHub Actions.

```sh
sh despliegue/desplegar.sh
```

It gets the code (`git pull --ff-only`), adds new variables to `.env`, rebuilds the image, recreates the containers (migrations apply themselves) and waits until `/up` answers. For a few seconds the system returns 502 while `web` is recreated.

**If `git pull` was already done by hand**, apply the rest with `sh despliegue/aplicar.sh`.

**Rolling back:**

```sh
git checkout <previous-commit>
sh despliegue/aplicar.sh
```

### Common tasks

| What | Command (inside `despliegue/`) |
| --- | --- |
| Container status | `docker compose ps` |
| A container's logs | `docker compose logs --tail 100 web` (or `cola`, `evolution`) |
| A Laravel command | `docker compose exec -u www-data web php artisan <command>` |
| Restart the notice worker | `docker compose restart cola` |

**Always `-u www-data`** for commands that write to `storage`: without it they run as `root`, and whatever they create, such as a photo folder, cannot be written by the application. The symptom is a 500 error when viewing a photo.

## 5. Backups and restore

| Backup | When | Where |
| --- | --- | --- |
| **Daily** | 2:00 a.m. | `/var/respaldos/taller/YYYY-MM-DD/`: `base.sql.gz`, `fotos.tar.gz` and `sumas.txt`. Kept for 14 days |
| **Weekly** | Sundays, 3:00 a.m. | That day's copy on Google Drive, in `taller-respaldos/`. 2 are kept |

**Checking they work:** `tail /var/log/taller-respaldos.log` must show last night's run with no errors, and `ls /var/respaldos/taller` one folder per day.

**Backing up by hand**, for example before a delicate change: `sh despliegue/respaldar.sh`.

### Restoring

Restore **on another machine**: the worst case is that the server no longer exists. Tested in [PM-02](../05-pruebas/pruebas-manuales/PM-02-restauracion-de-respaldos.md): 7 seconds, with identical record counts.

1. **Bring the backup** to the machine: the day's folder, from `/var/respaldos/taller` or from Google Drive (`rclone copy drive:taller-respaldos/YYYY-MM-DD ./YYYY-MM-DD`).
2. **Have the system installed** on that machine, [as in section 2](#2-installing-on-a-development-machine), with an empty database to restore into, for example `taller_restaurada`.
3. **Restore.** The MySQL password is given as a variable, not as an argument, so it does not stay in the shell history:

   ```sh
   read -r clave; MYSQL_PWD=$clave; export MYSQL_PWD
   MYSQL_USER=taller MYSQL_PORT=3306 sh despliegue/restaurar.sh ./YYYY-MM-DD taller_restaurada sistema/storage
   ```

   It first checks the backup's checksums; if a file is damaged, it stops without touching anything. Then it loads the database and the photos.
4. **Point the system** to that database (`DB_DATABASE` in `.env`), sign in and check an order with photos.

## 6. The Android app

The APK is built with **Bubblewrap** on the developer's machine, from `movil/twa-manifest.json`. The signing key is **not in the repository** and must be backed up: without it, no update of the app can be published.

**First time:**

1. `npm install -g @bubblewrap/cli` (Node is only needed here).
2. In `movil/`: `bubblewrap update --skipVersionUpgrade`. It downloads its JDK and the Android SDK and asks you to accept the license.
3. Create the key, if it does not exist, with the `keytool` of the JDK Bubblewrap downloaded, in `~/.bubblewrap/jdk/<version>/bin/`:

   ```sh
   keytool -genkeypair -v -keystore <path>/taller.keystore -alias taller -keyalg RSA -keysize 2048 -validity 10000
   ```

   `bubblewrap build` does not create the key. The store password and the key password are the same.
4. Copy the key somewhere safe, off the computer.

**Each version:**

1. Increase `appVersionCode` by 1 in `movil/twa-manifest.json` and update `appVersionName`.
2. In `movil/`: `bubblewrap build`. It asks for the password and produces `app-release-signed.apk`.
3. Publish it on the server:

   ```sh
   scp movil/app-release-signed.apk cristian@proyectosena.online:descargas-taller/taller-1.0.1.apk
   ssh cristian@proyectosena.online "cd descargas-taller && cp taller-1.0.1.apk taller.apk"
   ```

   It is served at `https://proyectosena.online/taller/descargas/taller.apk`.

**The link between the APK and the site:** `sistema/public/.well-known/assetlinks.json` holds the key's SHA-256 fingerprint. If the key changes, the fingerprint must change too; get it from the signed APK with `apksigner verify --print-certs`. To check it on a phone connected over USB: `adb shell pm get-app-links online.proyectosena.taller` must say `verified`.

**System changes do not need a new APK:** the APK only opens the system's address. A new one is only built when the name, icon, colors or address change.

## 7. Monitoring and logs

| What | Where |
| --- | --- |
| **Availability** | UptimeRobot checks `/taller/up` every 5 minutes and emails if it fails |
| **Application errors** | `docker compose exec -u www-data web tail -n 100 storage/logs/laravel-$(date +%F).log`: one file per day, 14 kept |
| **Notices that did not go out** | Logged by the application as warnings. Those that run out of retries wait for assisted sending and show up on the Today screen |
| **Backups** | `/var/log/taller-respaldos.log` |
| **Code quality** | GitHub Actions, on every push. If Actions fails within seconds without reaching the steps, first check the GitHub account's billing: Actions gets locked even for public repositories |

## 8. Troubleshooting

| Symptom | Likely cause | What to do |
| --- | --- | --- |
| `git clone` fails with «Filename too long» and «checkout failed» | Windows limits paths to 260 characters and the destination folder is too deep | Clone into a short path, such as `C:\proyectos`, or enable long paths in Git: `git config --global core.longpaths true` |
| `SQLSTATE[HY000] [1045] Access denied` | The `.env` user, password or port differ from MySQL's | Check `DB_USERNAME`, `DB_PASSWORD` and `DB_PORT` |
| Migrations fail with a collation or `CHECK` error | The database is MariaDB, not MySQL 8.4 | Install MySQL 8.4 |
| `No application encryption key has been specified` | `php artisan key:generate` was skipped | Run it |
| Uploading a photo gives a 500 error | The `gd` extension is missing, or in production the photo folder ended up owned by `root` | Enable `gd`; in production, fix the owner with `docker compose exec web chown -R www-data:www-data storage/app/privado` |
| The tests wipe data | The test database is the same as the system's | `phpunit.xml` uses `taller_pruebas`: it must exist and differ from `taller` |
| A notice stays «En cola» and never goes out | No queue worker is running | In development, `php artisan queue:work --queue=avisos`; in production, `docker compose ps` and `restart cola` |
| Every notice ends up «Por enviar» | The WhatsApp was unlinked from Evolution API | [Connect it again](#connecting-whatsapp) |
| `/taller` answers 502 | `web` is being recreated or stopped | Wait a few seconds; if it persists, `docker compose ps` and `docker compose logs web` |
| The APK opens with the browser bar | If it lasts 2 seconds, the default browser is not Chrome; if it stays, `assetlinks.json` does not match the key | See [the Android app](#6-the-android-app) |
| Gradle crashes without an error while building the APK | The JDK Bubblewrap downloaded is 32-bit and runs out of memory | Install 64-bit Temurin 17 and change `jdkPath` in `~/.bubblewrap/config.json` |
