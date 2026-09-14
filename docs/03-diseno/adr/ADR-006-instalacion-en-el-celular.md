# ADR-006 · El sistema se instala en el celular: PWA y APK para Android

**Estado:** aceptada · **Fecha:** 14 de septiembre de 2026 · **Complementa:** [ADR-001](ADR-001-laravel-mysql.md) y [ADR-005](ADR-005-arquitectura-en-capas.md)

## Contexto

- **Quién lo usa y desde dónde:** según el aprendiz, la mayoría de mujeres con talleres pequeños de arreglos trabajan desde el celular, no desde un computador de escritorio (F-05, 14 de septiembre de 2026). Es una percepción de quien conoce el oficio, no un dato medido.
- **Preferencia:** el aprendiz prefiere entregar el sistema como APK, para que se instale y se abra como cualquier app.
- **Lo ya decidido:** es un sistema web en Laravel (ADR-001), sin API aparte (ADR-005). Los mockups ya están diseñados para un celular de 390 px (RNF-07).
- **Lo que la app necesita del celular:** la cámara para las fotos de las prendas (HU-17) y abrir WhatsApp para el envío asistido (HU-29). El navegador ya permite ambas cosas.
- **Conexión:** el taller tiene internet estable, y el trabajo sin conexión está fuera del alcance.
- **Idea de negocio:** el sistema será gratuito para varias emprendedoras, así que instalarlo no debe costarles ni exigirles conocimientos técnicos.

## Decisión

1. **El sistema funciona como aplicación web instalable (PWA).** Tiene un manifiesto con nombre, íconos y los colores de los mockups, y un service worker mínimo. Sin conexión muestra una página que explica que se necesita internet; no guarda datos para trabajar sin ella.
2. **Para Android se genera un APK con Trusted Web Activity (TWA).** El APK abre el mismo sistema del VPS en Chrome, a pantalla completa y sin barra del navegador. Se genera con Bubblewrap, y el archivo `assetlinks.json` publicado en el dominio prueba que el APK y el sitio son del mismo dueño.
3. **La llave de firma del APK no va al repositorio** (RNF-24). Se guarda y se respalda aparte, porque sin ella no se pueden publicar versiones nuevas del APK.
4. **La dueña instala el APK directamente.** Publicarlo en Google Play queda fuera de esta entrega.
5. **En celulares que no son Android**, el sistema se instala desde el navegador como PWA.
6. **Un solo código.** Como el APK abre el sitio, cada mejora del sistema llega al celular sin reinstalar el APK.

## Alternativas consideradas

| Alternativa | Por qué no |
| --- | --- |
| **Solo el navegador**, como estaba | Sin ícono en el celular, con la barra del navegador y mezclado entre pestañas. No responde a cómo trabajan las usuarias |
| **Solo PWA**, sin APK | Es la base de la decisión, pero instalar desde el menú del navegador es menos conocido que instalar un APK, que es lo que prefiere el aprendiz |
| **APK con Capacitor que carga el sitio en un WebView** | Fue la tecnología de la versión 1. Con un WebView, la cámara, los enlaces a WhatsApp y la sesión necesitan configuración nativa adicional, y hay que mantener un proyecto Android más complejo. TWA usa Chrome, que ya resuelve todo eso |
| **App nativa (Kotlin o Flutter) con una API** | Son dos aplicaciones, una API y un segundo despliegue. Contradice ADR-001 y ADR-005 y no cabe en los 30 días |

## Consecuencias

- **La usuaria tiene su app** con ícono y a pantalla completa, sin una segunda aplicación que construir.
- **La arquitectura no cambia:** se agregan el manifiesto, el service worker, `assetlinks.json` y un proyecto pequeño generado para el APK.
- **Necesita un dominio propio con HTTPS** apuntando al VPS; solo con la IP, Android no puede verificar el sitio. Queda como supuesto del alcance.
- **Necesita Chrome en el celular Android**, que es el navegador que abre el sistema dentro del APK.
- **Sin internet no funciona**, igual que antes; la página sin conexión solo lo explica.
- **Más trabajo en el Sprint 4:** el habilitador HT-07, de 3 puntos, sube el compromiso de ese sprint de 40 a 43 puntos.
