# Seguridad

## Autenticación

| Qué | Cómo | Requisito |
| --- | --- | --- |
| **Iniciar sesión** | `Auth::attempt(['usuario' => ..., 'password' => ...])`. La clave del arreglo es `password` aunque la columna se llame `contrasena`: Laravel la traduce con `$authPasswordName` | RF-01 |
| **Hash** | bcrypt, el algoritmo por defecto de Laravel, con costo 12 | RNF-19 |
| **Al entrar** | Se regenera el identificador de la sesión, para que nadie reutilice uno anterior | RNF-23 |
| **Error** | Un solo mensaje para usuario o contraseña equivocados | CA-01.2 |
| **La contraseña no vuelve al formulario** | Al volver con un error, Laravel guarda en la sesión lo escrito, salvo los campos llamados `password`. `bootstrap/app.php` agrega a esa lista los campos de contraseña en español | RNF-19 |
| **Cerrar sesión** | POST a `sesion.salir`: cierra la sesión, la invalida y regenera el token CSRF | RF-02, CA-01.4 |
| **Cambiar la contraseña** | Además de cambiarla, cierra la sesión en los demás dispositivos con `Auth::logoutOtherDevices`, por si alguien más la conocía. Por eso las rutas usan el middleware `auth.session` | HU-02 |
| **Sin «recordarme»** | La sesión dura mientras se use; RNF-21 pide cerrarla después de 8 horas sin uso | RNF-21 |
| **Sin registro ni recuperación por correo** | La usuaria se crea al instalar; los requisitos no piden ninguna de las dos | — |

## Límite de intentos

`AppServiceProvider` define el limitador `inicio-de-sesion`:

- 5 intentos por minuto por la combinación de usuario en minúsculas y dirección IP (RNF-20).
- Cuenta todos los intentos, también el correcto: el sexto intento espera aunque la contraseña esté bien (CA-01.3).
- Al superarlo, vuelve a PT-01 con el mensaje de espera y los segundos que faltan.

## Sesión

| Variable | Valor | Por qué |
| --- | --- | --- |
| `SESSION_DRIVER` | `file` | Un solo servidor; no agrega tablas al modelo |
| `SESSION_LIFETIME` | `480` | 8 horas sin uso (RNF-21) |
| `SESSION_EXPIRE_ON_CLOSE` | `false` | Cerrar la app no obliga a entrar de nuevo si no han pasado las 8 horas |
| `SESSION_SECURE_COOKIE` | `true` en producción | La cookie solo viaja por HTTPS (RNF-18) |

La cookie es `HttpOnly`, así que JavaScript no la puede leer, y `SameSite=Lax`. Los dos son valores por defecto de Laravel.

## Cabeceras

| Cabecera | Valor | Dónde | Por qué |
| --- | --- | --- | --- |
| `Strict-Transport-Security` | `max-age=31536000` | Nginx | El navegador usa HTTPS aunque se escriba `http://` (RNF-18) |
| `Cache-Control` | `no-store, private` | Laravel, en las rutas con sesión | Después de cerrar sesión, el botón Atrás no muestra datos guardados (CA-01.4) |
| `X-Content-Type-Options` | `nosniff` | Nginx | El navegador no interpreta un archivo como otro tipo |
| `X-Frame-Options` | `DENY` | Nginx | Ningún otro sitio puede incrustar el sistema para engañar a la usuaria |
| `Referrer-Policy` | `same-origin` | Nginx | Al abrir WhatsApp, la dirección con el número de orden no se envía a otro sitio |
| `Content-Security-Policy` | `default-src 'self'; img-src 'self' blob:; script-src 'self'; style-src 'self'; font-src 'self'; frame-ancestors 'none'; form-action 'self'` | Nginx | Solo se ejecutan scripts y estilos del propio sistema, lo que frena un ataque XSS aunque algo se escape (RNF-23) |

`img-src` permite `blob:` para la vista previa de la foto antes de enviarla. La política no permite estilos en línea, por eso los de los mockups se convierten en clases (01).

## Aislamiento entre negocios

RN-01 y RNF-22 se cumplen en varias capas, porque un solo punto de control se puede olvidar:

| Capa | Mecanismo | Detalle |
| --- | --- | --- |
| Modelos | Filtro global de las tablas raíz | [Datos y modelos](04-datos-y-modelos.md#filtro-por-negocio) |
| Rutas | Parámetros que se buscan a través de la orden | [Rutas](02-rutas.md#parámetros) |
| Validación | `exists` limitado al negocio para el cliente, el tipo de prenda y el método de pago | [Validaciones](03-validaciones-y-mensajes.md) |
| Respuesta | 404, como si el registro no existiera | — |
| Pruebas | `AislamientoEntreNegociosTest` recorre todas las rutas con dos negocios | [Plan de pruebas](../05-pruebas/plan-de-pruebas.md) |

## Ataques web comunes

| Ataque | Protección | Requisito |
| --- | --- | --- |
| **Falsificación de solicitudes (CSRF)** | `@csrf` en todo formulario; Laravel rechaza el envío sin el token | RNF-23 |
| **Código inyectado en la página (XSS)** | Blade escapa todo lo que se imprime con `{{ }}`. `{!! !!}` está prohibido, y GitHub Actions falla si aparece en las vistas | RNF-23 |
| **Inyección SQL** | Eloquent y el constructor de consultas pasan los datos como parámetros. `whereRaw` y `DB::raw` solo se usan con parámetros, y se revisan en el código | RNF-23 |
| **Archivos disfrazados** | Se revisa el tipo real del archivo, se vuelve a codificar la imagen y se guarda fuera de la carpeta pública | RNF-25 |
| **Dependencias vulnerables** | `composer audit` en GitHub Actions | RNF-23 |

## Secretos

| Secreto | Dónde vive | Requisito |
| --- | --- | --- |
| `.env` | Solo en cada máquina. `.gitignore` lo excluye; `.env.example` lleva los nombres sin valores reales | RNF-24 |
| `APP_KEY` | En `.env`; es distinta en desarrollo y en producción | RNF-24 |
| Contraseña de MySQL y token de WhatsApp | En `.env` del VPS, con permisos `640`: dueño `taller` y grupo `www-data` | RNF-24 |
| Credenciales de `mysqldump` | `/etc/taller/respaldo.cnf`, con permisos `600` | RNF-24 |
| Configuración de rclone | En la carpeta del usuario `taller`, con permisos `600` | RNF-24 |
| Llave de firma del APK y sus contraseñas | Fuera del repositorio, en el gestor de contraseñas del aprendiz y en una copia sin conexión | ADR-006 |

- **Revisión automática:** gitleaks revisa el repositorio en cada envío (RNF-24).
- **Si un secreto se expone:** se cambia de inmediato. El token de WhatsApp se revoca en Meta y la contraseña de MySQL se cambia en el servidor.

## Datos personales

RNF-26 aplica la Ley 1581 de 2012:

- **Datos del cliente:** solo nombre y celular.
- **Política de tratamiento:** se publica en `/politica-de-datos`, sin iniciar sesión. Dice qué datos se guardan, para qué (registrar sus órdenes y avisarle cuando estén listas), quién es el responsable (el taller) y cómo pedir que se corrijan. Su texto se escribe en el Sprint 4 y lo revisa el instructor.
- **Fotos:** se guardan sin la ubicación GPS del celular (05).
- **Registros del servidor:** no guardan nombres ni celulares de clientes.
