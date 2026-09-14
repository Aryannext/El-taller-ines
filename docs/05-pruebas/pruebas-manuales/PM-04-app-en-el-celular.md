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

**Fecha:** · **Commit:** · **Versión del APK:**

**Android real (modelo y versión):** · **Otro celular (modelo, sistema y navegador):**

| Paso | Resultado | Captura | Observaciones |
| --- | --- | --- | --- |
| 1 | | | |
| 2 | | | |
| 3 | | | |
| 4 · CA-17.1 | | | |
| 5 · CA-17.2 | | | |
| 6 | | | |
| 7 · CA-29.2 | | | |
| 8 | | | |
| 9 | | | |
| 10 | | | |

**Resultado:**
