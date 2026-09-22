# PM-07 · Seguridad y operación del despliegue

**Verifica:** RNF-16, RNF-18, RNF-23, RNF-26, RNF-34 · **Cuándo:** Sprint 4, con HT-04; la sección F después de cada despliegue · **Entorno:** VPS

## Objetivo

Comprobar sobre el sistema desplegado lo que las pruebas automáticas no alcanzan:

- que la comunicación va cifrada;
- que el servidor corre el mismo código del repositorio y está bien configurado;
- que resiste los ataques web más comunes;
- que trata los datos personales según la ley;
- que un monitor vigila que esté disponible.

## Preparación

1. HT-04 está terminado: el sistema corre en el dominio con HTTPS y la cola de avisos es un servicio.
2. Tener acceso SSH al VPS.

## A · Comunicación cifrada (RNF-18)

| Qué se hace | Qué debe pasar | Resultado |
| --- | --- | --- |
| Pedir la página de inicio por `http://` | Responde con una redirección permanente a `https://` | **Sí.** 301 a `https://proyectosena.online/taller/` |
| Revisar el certificado en el navegador | Es válido, del dominio y no está vencido | **Sí.** Let's Encrypt para `proyectosena.online`, válido del 19 de septiembre al 18 de diciembre de 2026 |

## B · Mismo código y configuración por entorno (RNF-34)

| Qué se hace | Qué debe pasar | Resultado |
| --- | --- | --- |
| Comparar el commit del VPS con el de `main` | Es el mismo | **Sí.** `38ed8fb` en los dos al momento de la revisión |
| Revisar si hay archivos modificados en el VPS | No hay ninguno | **Sí.** `git status` sin cambios |
| Revisar el `.env` del VPS | `APP_ENV=production` y `APP_DEBUG=false`; la cola no es `sync` | **Sí.** `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_SECURE_COOKIE=true` y `QUEUE_CONNECTION=database` |
| Simular una caída de la cola: `docker compose exec cola sh -c 'kill -TERM 1'` | Docker la vuelve a iniciar sola, y el contador de reinicios sube a 1 | **Sí.** Al recibir la señal el trabajador salió, y veinte segundos después el contenedor estaba arriba otra vez sin que nadie lo tocara: `reinicios=1`, arranque a las 21:18:02 UTC del 22 de septiembre |

> **Por qué así y no de otra forma.** El 22 de septiembre el paso se intentó de dos maneras que no prueban nada, y vale la pena dejarlas escritas para no repetirlas:
>
> 1. `docker compose kill cola` dejó el contenedor abajo. El demonio de Docker marca como «detenido a mano» lo que muera por `docker kill` o `docker stop`, y en ese caso **no aplica** la política de reinicio. Como efecto secundario, la cola estuvo ocho minutos abajo: al volver no había ningún aviso atascado ni fallido, que es justo lo que se espera de una cola guardada en base de datos.
> 2. `kill -9 1` dentro del contenedor no hizo nada. El núcleo de Linux no entrega SIGKILL al PID 1 desde su propio espacio de procesos; solo el demonio, que está afuera, puede hacerlo.
>
> `kill -TERM 1` sí funciona porque el PID 1 es el trabajador de la cola y Laravel le tiene puesto un manejador de SIGTERM (la extensión `pcntl` está instalada en la imagen). El trabajador termina lo que esté haciendo y sale por su cuenta, igual que cuando se le cumple la hora de `--max-time=3600`: esa es la caída que interesa probar. Desde el servidor sirve también `docker kill -s SIGSEGV taller-cola-1`, porque una señal que no es la de parada tampoco marca el contenedor como detenido a mano.

## C · Ataques web comunes (RNF-23)

Lista de chequeo del OWASP Top 10:2021. Si al ejecutarla ya está publicada la edición final de 2025, se usa esa y se anota.

| Riesgo | Qué se revisa | Resultado |
| --- | --- | --- |
| **A01** Control de acceso roto | Cambiar en la dirección el número de una orden por una de otro negocio responde como si no existiera; la prueba automática de aislamiento pasa | **Parcial.** `AislamientoEntreNegociosTest` pasa y cubre las rutas con parámetros; falta repetirlo a mano en el sistema desplegado |
| **A02** Fallas criptográficas | HTTPS en todo; las contraseñas se guardan con hash | **Sí.** HTTPS forzado (sección A); `CambiarContrasenaTest` comprueba el hash; las cookies salen con `secure`, `httponly` y `samesite=lax` |
| **A03** Inyección | Un cliente llamado `<script>alert(1)</script>` se muestra como texto; una búsqueda con `' OR 1=1 --` no devuelve clientes de más | **Sí, por prueba automática.** `SeguridadWebTest` guarda ese nombre y comprueba que sale escapado; `BuscarClientesTest` prueba la búsqueda con inyección. Falta repetirlo a mano en el sistema desplegado |
| **A04** Diseño inseguro | Las reglas se aplican en el servidor, no solo en la pantalla, como la confirmación de entregar con saldo (RN-21) | **Sí.** `EntregarOrdenTest` entrega sin la confirmación y el servidor la rechaza; igual con cancelar una orden y anular un pago |
| **A05** Configuración insegura | `https://<dominio>/.env` no se descarga; los errores no muestran detalles técnicos; no se listan carpetas | **Sí.** `/taller/.env`, `/taller/composer.json`, `/taller/vendor/autoload.php` y los registros responden 404; `/taller/iconos/` responde 403 sin listar; una orden inexistente no muestra detalles. **Aquí se encontró** que no se enviaba ninguna cabecera de seguridad, corregido en `38ed8fb` |
| **A06** Componentes vulnerables | `composer audit` no reporta vulnerabilidades altas ni críticas | **Sí.** «No security vulnerability advisories found», y el flujo lo repite en cada envío |
| **A07** Fallas de autenticación | Las pruebas de RNF-19, RNF-20 y RNF-21 pasan; la sesión se cierra de verdad | **Sí.** Hash de la contraseña, límite de intentos y expiración a las 8 horas, cada uno con su prueba; al cerrar sesión, el enlace de una foto deja de entregarla |
| **A08** Fallas de integridad | Las dependencias se instalan desde `composer.lock`; el APK está firmado | **Sí.** La imagen instala con `composer.lock`; el APK está firmado y `apksigner` verifica los esquemas v1, v2 y v3 |
| **A09** Fallas de registro y monitoreo | Los errores quedan en el registro de Laravel y el monitor externo está activo | **Parcial.** Los errores se registran, con un archivo por día: los 9 del 21 de septiembre son los del problema de permisos de las fotos, corregido ese mismo día, y desde entonces no hay ninguno. Falta anotar la disponibilidad (sección E) |
| **A10** Falsificación de solicitudes del lado del servidor | El sistema solo llama a la dirección fija de la API de Meta, sin direcciones que escriba la usuaria | **Sí.** Los dos únicos llamados salen de los adaptadores de WhatsApp, con direcciones de la configuración: `https://graph.facebook.com/…` y la de Evolution API dentro de Docker. Ningún dato de la usuaria arma una dirección |

## D · Datos personales (RNF-26)

| Qué se revisa | Qué debe pasar | Resultado |
| --- | --- | --- |
| Formularios de cliente | Solo piden nombre y celular | **Sí.** `ClienteRequest` solo acepta `nombre` y `celular` |
| Política de tratamiento de datos | Está publicada en el sistema y se puede abrir sin iniciar sesión | **Sí, desde esta prueba.** No existía: su ruta estaba especificada desde el Sprint 2 y nunca se construyó. Se escribió y se publicó en `/taller/politica-de-datos`, enlazada desde el inicio de sesión (`f086f13`) |

## E · Disponibilidad (RNF-16)

1. Crear en UptimeRobot un monitor que revise el sistema cada 5 minutos desde el despliegue. La cuenta la crea el aprendiz.
2. Al cerrar el Sprint 4 y antes de la entrega, anotar la disponibilidad entre las 7:00 a. m. y las 8:00 p. m.
3. El informe declara el período medido. RNF-16 pide un mes y el sistema se entrega pocos días después de desplegarse.

**Monitor creado el:** 21 de septiembre de 2026, en UptimeRobot, consultando `/taller/up` cada 5 minutos · **Período medido:** pendiente · **Disponibilidad:** pendiente · **Caídas y su causa:** pendiente

## F · Prueba de humo después de cada despliegue

Una prueba de humo es un recorrido corto que confirma que lo principal funciona antes de seguir probando.

| Qué se hace | Resultado |
| --- | --- |
| Iniciar sesión | |
| Abrir el panel del día | |
| Registrar una orden de prueba | |
| Cancelarla | |

## Criterio de aprobación

- Las secciones A a D pasan completas.
- La sección E tiene el monitor activo y su medición anotada.
- La prueba de humo pasa en el último despliegue.

## Registro

**Fecha:** 22 de septiembre de 2026 · **Commit desplegado:** `f086f13` · **Dominio:** proyectosena.online/taller

Las tablas de las secciones A a F son la hoja de registro.

**Resultado:** **Parcial.** Las secciones A, B y D pasan completas: la B cerró con el reinicio de la cola, que se levantó sola tras recibir la señal (HT-04, RNF-17). La C pasa en ocho de sus diez riesgos y deja A01 y A03 a la espera de repetirse a mano sobre el sistema desplegado, además de la disponibilidad que falta en A09. La E tiene el monitor activo desde el 21 de septiembre, sin su medición. La F está pendiente.

**Dos hallazgos, los dos corregidos durante la prueba:**

1. **No se enviaba ninguna cabecera de seguridad** (RNF-23): ni la política de contenido, ni `X-Frame-Options`, ni `Referrer-Policy`, ni `nosniff`. El módulo de Apache estaba activado, pero nadie las declaraba. Ahora salen del contenedor (`38ed8fb`), sin tocarles las cabeceras a los otros proyectos del dominio.
2. **La política de tratamiento de datos no existía** (RNF-26), aunque su ruta estaba especificada desde el Sprint 2. Se escribió y se publicó (`f086f13`).

**Pendiente de decisión:** `Strict-Transport-Security` vale para todo el dominio y no solo para `/taller`, así que la decide el dueño del portafolio.

**Defectos abiertos:** ninguno.
