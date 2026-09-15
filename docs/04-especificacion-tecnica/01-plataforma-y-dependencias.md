# Plataforma y dependencias

## Versiones

| Pieza | Versión | Dónde se usa | Nota |
| --- | --- | --- | --- |
| **PHP** | 8.4 | WAMP, GitHub Actions y VPS | La misma en los tres entornos (ADR-001) |
| **Laravel** | 13.31.0, instalada el 14 de septiembre de 2026 | Todo el sistema | La versión exacta de cada paquete queda en `composer.lock` |
| **MySQL** | 8.4 | WAMP, GitHub Actions y VPS | La intercalación `utf8mb4_0900_ai_ci` hace las búsquedas sin tildes (RN-43) |
| **Composer** | 2 | Desarrollo y VPS | — |
| **Nginx** | La del sistema operativo del VPS | VPS | Se anota en HT-04 |
| **Sistema operativo del VPS** | Linux | VPS | La distribución y su versión se anotan en HT-04 |
| **Node.js** | 24 | Solo en la máquina de desarrollo | Para los scripts de documentación y para generar el APK. No se usa para construir el sistema |
| **Chrome** | La actual | Celulares Android | El APK abre el sistema en Chrome (ADR-006) |

## Extensiones de PHP

| Extensión | Para qué |
| --- | --- |
| **pdo_mysql** | Conexión con MySQL |
| **mbstring** | Textos con tildes y eñes |
| **openssl** | HTTPS y cifrado de la sesión |
| **curl** | Llamadas a la API de WhatsApp |
| **gd**, con soporte de JPEG y WebP | Reducir las fotos (RNF-03) |
| **exif** | Leer cómo estaba girado el celular al tomar la foto |
| **fileinfo** | Reconocer el tipo real de un archivo subido, no el que dice su nombre |
| **ctype**, **dom**, **tokenizer** y **xml** | Las pide Laravel |
| **zip** | La usa Composer al instalar paquetes |

## Paquetes de Composer

| Paquete | Para qué | Entorno |
| --- | --- | --- |
| **laravel/framework** | El marco de la aplicación | Producción |
| **intervention/image**, versión 3 | Enderezar, reducir y volver a codificar las fotos | Producción |
| **laravel/pint** | Estilo de código (RNF-29) | Desarrollo |
| **larastan/larastan** | Análisis estático en nivel 5 (RNF-29) | Desarrollo |
| **phpat/phpat** | Reglas de dependencias entre capas (RNF-27) | Desarrollo |
| **phpunit/phpunit** | Pruebas | Desarrollo |
| **fakerphp/faker** | Datos de las fábricas de prueba | Desarrollo |

En HT-02 se revisan los paquetes que el esqueleto de Laravel trae por defecto y se quitan los que no se usen, como Sail, porque el proyecto no usa Docker.

**Regla:** toda dependencia nueva se agrega primero a esta tabla, con su uso. Menos dependencias significan menos cosas que actualizar y menos vulnerabilidades posibles (RNF-23).

## Interfaz sin compilación

### Estilos y fuentes

- `public/css/estilos.css` es la hoja de estilos de los mockups. Se copia en HT-02; desde entonces manda la del sistema, y la de los mockups queda como registro del diseño aprobado.
- Las fuentes Atkinson Hyperlegible Next y Atkinson Hyperlegible Mono se sirven desde `public/fuentes/`. Su licencia (SIL Open Font License) lo permite, y así cada carga no depende de Google ni le informa las visitas.
- Los estilos en línea de los mockups, como `style="gap:16px"`, se convierten en clases de `estilos.css`. La política de seguridad de contenido no los permite (06).

### JavaScript

Un solo archivo, `public/js/app.js`, sin librerías.

| Comportamiento | Historia o requisito | Qué garantiza el servidor aunque falle el JavaScript |
| --- | --- | --- |
| Mostrar el campo «¿Qué prenda es?» al elegir «Otro» | HU-09 | La validación exige el tipo escrito cuando se eligió «Otro» |
| Agregar y quitar prendas en la orden | HU-07 | La validación exige al menos una prenda |
| Confirmar con un cuadro de diálogo antes de eliminar una prenda o una foto | HU-13, HU-19 · RNF-10 | Sin el campo de confirmación, nada se elimina |
| Reducir la foto en el navegador antes de enviarla, para gastar menos datos | HU-17 | El servidor reduce toda foto (RNF-03) |
| Deshabilitar el botón de guardar después del primer toque | RNF-14 | El token del formulario impide el registro repetido |
| Mostrar el dinero con punto de miles mientras se escribe | RNF-08 | El servidor acepta el valor con o sin puntos |
| Registrar el service worker | RNF-35 | — |

### Vistas

| Qué | Dónde |
| --- | --- |
| Plantilla común, con la barra de navegación | `resources/views/plantilla.blade.php` |
| Una vista por pantalla, con el nombre de su mockup | `resources/views/pantallas/pt-09-detalle-orden.blade.php` |
| Lo que se repite en los mockups: campo con su error, chip de estado, fila de orden | `resources/views/components/` |
| Páginas de error | `resources/views/errors/` |

## Configuración de PHP

| Directiva | Valor | Por qué |
| --- | --- | --- |
| `date.timezone` | `America/Bogota` | RN-09 |
| `upload_max_filesize` | `10M` | Una foto de celular sin reducir |
| `post_max_size` | `64M` | Una orden con varias prendas y sus fotos |
| `max_file_uploads` | `30` | Hasta 3 fotos por prenda en una orden de 10 prendas |
| `memory_limit` | `256M` | Abrir en memoria una foto de 12 megapíxeles para reducirla |
| `expose_php` | `Off` | No anunciar la versión de PHP (RNF-23) |
| `opcache.enable` | `1` | Rapidez de respuesta (RNF-01) |

Nginx acepta solicitudes del mismo tamaño que `post_max_size` (07).
