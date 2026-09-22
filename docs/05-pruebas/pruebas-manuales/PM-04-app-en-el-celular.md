# PM-04 · App en el celular

**Verifica:** RNF-35, CA-17.1, CA-17.2, CA-29.2 · **Cuándo:** Sprint 4, con HT-07 · **Entorno:** un celular Android real y otro celular cualquiera

## Objetivo

Comprobar que el sistema se instala y funciona como una app en el celular, que es donde trabajan las usuarias ([ADR-006](../../03-diseno/adr/ADR-006-instalacion-en-el-celular.md)):

- el APK se instala en Android y abre a pantalla completa;
- la cámara, la galería y WhatsApp funcionan desde el APK;
- sin conexión aparece una página que lo explica;
- en otro celular se instala desde el navegador.

## Preparación

1. HT-07 está terminado: el APK está firmado y `assetlinks.json` está publicado en el dominio con HTTPS.
2. El negocio de prueba **no** tiene configurada la API de WhatsApp, para que los avisos queden para envío asistido.
3. Existe un cliente de prueba cuyo celular es un número del aprendiz. Nunca se usa el número de un tercero.
4. Ese cliente tiene una orden con una sola prenda En proceso.

## Pasos

| Paso | Qué se hace | Qué debe pasar |
| --- | --- | --- |
| 1 | Instalar el APK en el Android real | Se instala. Anotar si el celular pidió permitir la instalación de fuentes desconocidas |
| 2 | Abrir la app | Abre a pantalla completa, sin la barra de dirección del navegador. Si la barra aparece, `assetlinks.json` no se está validando |
| 3 | Iniciar sesión | Entra al panel del día |
| 4 | **CA-17.1:** registrar una prenda y tomarle una foto con la cámara | La cámara abre y la foto queda asociada a la prenda |
| 5 | **CA-17.2:** agregar otra foto eligiéndola de la galería | La foto queda asociada a la prenda |
| 6 | Marcar Terminada la prenda de la orden del cliente de prueba | La orden queda Lista para entregar y aparece un aviso pendiente |
| 7 | **CA-29.2:** abrir los avisos pendientes y tocar enviar | Se abre WhatsApp con el chat del celular del cliente y el mensaje escrito. No hace falta enviarlo |
| 8 | Volver a la app sin confirmar | El aviso sigue pendiente (CA-29.4) |
| 9 | Activar el modo avión y abrir la app | Aparece la página que explica que se necesita internet, no el error del navegador |
| 10 | En otro celular, abrir el sistema en el navegador e instalarlo | El navegador ofrece instalarlo, queda un ícono en la pantalla de inicio y abre sin la barra de dirección |

## Criterio de aprobación

Los 10 pasos pasan. Si el otro celular es un iPhone y el navegador no ofrece instalarlo, se anota cómo quedó y se declara: RNF-05 no incluye Safari.

## Registro

**Fecha:** 22 de septiembre de 2026 · **Commit:** `947ab45` · **Versión del APK:** 1.0.0 (`appVersionCode` 1), firmada con la llave `taller` (huella SHA-256 `59:1E:30:…:62:FA`)

**Android real (modelo y versión):** Android 10 con Chrome 153, según el registro del servidor; el modelo no se anotó · **Otro celular (modelo, sistema y navegador):** un Tecno con Android; el navegador no se anotó

La primera corrida, del 21 de septiembre, probó los pasos 4 y 5 desde Chrome para Android porque aún no había APK: fotos #4 y #5 de la #0003, a 1200 × 1600 px y 242 KB, y a 1600 × 1200 px y 320 KB. Esta corrida repite todo dentro del APK.

| Paso | Resultado | Evidencia | Observaciones |
| --- | --- | --- | --- |
| 1 | **Sí.** El APK se instaló en el Android | | Se pasó al celular a mano, porque `/descargas/` todavía no existe |
| 2 | **Sí, con una observación.** Abre a pantalla completa, sin barra | El servidor recibió `GET /.well-known/assetlinks.json` desde el celular. La API de Digital Asset Links de Google devuelve el paquete `online.proyectosena.taller` con la misma huella del APK | **Cada vez que se abre**, se ve el navegador unos 2 segundos antes de quedar a pantalla completa. Como la barra se va sola, Android sí valida el enlace; si no lo validara, se quedaría todo el tiempo. Queda para revisar con `adb logcat` si molesta a las usuarias |
| 3 | **Sí.** Entra al panel del día | `POST /entrar` 302 desde el APK | |
| 4 · CA-17.1 | **Sí.** La cámara abrió dentro del APK y la foto quedó en la prenda «E» de la #0004 | Foto #7, 9:27:56 | Guardada a 1200 × 1600 px y 212 KB (RNF-03). Se agregó a una prenda existente: la app no permite agregar prendas a una orden que ya existe, porque HU-11 no se construyó |
| 5 · CA-17.2 | **Sí.** La foto de la galería quedó como la tercera de la misma prenda | Foto #8, 9:28:10 | Guardada a 720 × 1600 px y 117 KB |
| 6 | **Sí.** La #0004 de Cristian Cantillo pasó a Lista para entregar al marcar Terminada su única prenda | Aviso #3, generado a las 9:10:41 | Como el negocio sí tiene Evolution API (PM-08), el aviso se envió solo y llegó al celular del aprendiz. Para probar los pasos 7 y 8, ese aviso se pasó a mano a `pendiente_asistido`, el estado en que queda cuando la API falla tres veces (RN-40) |
| 7 · CA-29.2 | **Sí.** Desde «Avisos por enviar», «Abrir WhatsApp y enviar» abrió WhatsApp en el chat del cliente con el mensaje escrito | | No se envió |
| 8 · CA-29.4 | **Sí.** Al volver sin confirmar, el aviso sigue por enviar | El aviso #3 seguía `pendiente_asistido`, sin canal ni fecha de resuelto | |
| 9 | **Sí.** En modo avión, la app muestra la página «Sin internet…» y no el error del navegador | El servidor entregó `sin-conexion.html` al celular | |
| 10 | **Sí.** En el Tecno, el navegador ofreció instalarlo, quedó el ícono y abre sin barra | | A diferencia del APK, instalada así no muestra el navegador al abrir |

**Resultado:** **Aprobada con observaciones.** Los 10 pasos pasan: la app se instala, abre a pantalla completa y la cámara, la galería, WhatsApp y la página sin conexión funcionan desde el APK (RNF-35).

Quedan tres observaciones:

- **El APK muestra el navegador unos 2 segundos cada vez que se abre.** No impide trabajar. Está para revisar en la versión 1.0.1 del APK.
- **La preparación no se cumplió al pie de la letra:** el negocio sí tiene API de WhatsApp. El paso 7 se probó con un aviso pasado a pendiente a mano, que es el mismo estado que deja la cola cuando la API falla.
- **No se puede agregar una prenda a una orden que ya existe (HU-11).** No es parte de PM-04, pero apareció al buscar cómo registrar una prenda nueva.
