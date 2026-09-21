# PM-05 · Pantallas y navegadores

**Verifica:** RNF-05, RNF-07, RNF-09, RNF-11, CA-01.4, CA-10.1, CA-18.2 · **Cuándo:** Sprint 3 sobre las pantallas que existan; completo en el Sprint 4 · **Entorno:** sistema en el VPS con los datos de los mockups

## Objetivo

Comprobar lo que solo se ve en un navegador:

- que las historias Must funcionan en los navegadores del taller;
- que cada pantalla sirve en un celular de 360 px y es accesible;
- que los mensajes de error se entienden;
- tres criterios que dependen del comportamiento de la pantalla.

## Preparación

1. Cargar los datos de los mockups con las fechas corridas a hoy.
2. Tener a mano las [capturas de los mockups](../../03-diseno/mockups/capturas/).
3. Anotar la versión de cada navegador: Chrome para Android en un celular real, y Chrome y Edge de escritorio.

## A · Historias Must en cada navegador (RNF-05)

Recorrer cada historia con los datos de sus criterios. Marcar **Sí** si todos sus criterios se cumplen en ese navegador, o anotar el número del issue del defecto.

RNF-05 pide las dos últimas versiones principales de cada navegador. Se prueba la versión actual; para la anterior, se revisa que el CSS y el JavaScript usados figuren como disponibles de forma amplia en Baseline, la referencia de compatibilidad de los navegadores.

<!-- recorrido:inicio -->

| Historia | Chrome para Android | Chrome | Edge |
| --- | --- | --- | --- |
| **HU-01** Iniciar y cerrar sesión | | | |
| **HU-02** Cambiar mi contraseña | | | |
| **HU-03** Registrar un cliente | | | |
| **HU-04** Buscar un cliente | | | |
| **HU-05** Consultar la ficha de un cliente | | | |
| **HU-06** Corregir los datos de un cliente | | | |
| **HU-07** Registrar una orden con sus prendas | | | |
| **HU-08** Obtener el número de la orden para marcar la bolsa | | | |
| **HU-09** Escribir un tipo de prenda que no está en la lista | | | |
| **HU-12** Corregir la descripción o el precio de una prenda | | | |
| **HU-14** Consultar el detalle de una orden | | | |
| **HU-15** Listar y buscar órdenes | | | |
| **HU-17** Tomar fotos de las prendas | | | |
| **HU-18** Ver las fotos de una orden para reconocer las prendas | | | |
| **HU-20** Actualizar el estado de una prenda | | | |
| **HU-21** Entregar la orden al cliente | | | |
| **HU-22** Cancelar una orden | | | |
| **HU-23** Registrar un pago o abono | | | |
| **HU-25** Anular un pago mal registrado | | | |
| **HU-28** Recibir un aviso cuando mi ropa está lista | | | |
| **HU-29** Enviar con un toque los avisos pendientes | | | |
| **HU-30** No avisar una orden que ya no está lista | | | |
| **HU-31** Consultar los avisos de una orden | | | |
| **HU-32** Ver el panel del día | | | |
| **HU-33** Ver las órdenes atrasadas | | | |
| **HU-34** Ver las órdenes sin reclamar | | | |

<!-- recorrido:fin -->

## B · Cada pantalla a 360 px y su accesibilidad (RNF-07, RNF-11)

Para cada pantalla:

1. **Sin desplazamiento horizontal:** a 360 px de ancho, la página no se mueve hacia los lados.
2. **Controles de 44 × 44 px o más:** botones, enlaces y campos. Se miden con el script de capturas adaptado, que recorre cada pantalla y reporta los elementos más pequeños.
3. **Accesibilidad de Lighthouse de 90 o más**, con el perfil móvil.
4. **Contraste de 4,5:1 o más** en los textos, según el reporte de Lighthouse.
5. **Igual al mockup:** se compara con su captura. Una diferencia se acepta solo si hay un motivo anotado.

<!-- pantallas:inicio -->

| Pantalla | Sin desplazamiento horizontal | Controles de 44 px | Lighthouse | Contraste | Igual al mockup |
| --- | --- | --- | --- | --- | --- |
| **PT-01** Iniciar sesión | | | | | |
| **PT-02** Panel del día | | | | | |
| **PT-03** Clientes | | | | | |
| **PT-04** Registrar o corregir un cliente | | | | | |
| **PT-05** Ficha del cliente | | | | | |
| **PT-06** Nueva orden | | | | | |
| **PT-07** Número para la bolsa | | | | | |
| **PT-08** Órdenes | | | | | |
| **PT-09** Detalle de la orden | | | | | |
| **PT-10** Fotos de la orden | | | | | |
| **PT-11** Acciones de una prenda | | | | | |
| **PT-12** Devolver una prenda sin arreglar | | | | | |
| **PT-13** Corregir una prenda | | | | | |
| **PT-14** Registrar un pago | | | | | |
| **PT-15** Anular un pago | | | | | |
| **PT-16** Entregar la orden | | | | | |
| **PT-17** Cancelar la orden | | | | | |
| **PT-18** Avisos por enviar | | | | | |
| **PT-19** Mensaje en el celular del cliente | | | | | |
| **PT-20** Órdenes atrasadas | | | | | |
| **PT-21** Órdenes sin reclamar | | | | | |
| **PT-22** Dinero | | | | | |
| **PT-23** Ajustes | | | | | |

<!-- pantallas:fin -->

## C · Mensajes de validación (RNF-09)

Provocar cada error de validación de los formularios: campos vacíos, celular inválido, precio cero, fecha pasada, pago mayor que el saldo y plazo fuera de rango. Cada mensaje debe cumplir las cuatro condiciones:

- aparece junto al campo que lo causó;
- está en español;
- dice cómo corregirlo;
- no tiene términos técnicos, como `validation.required`, `null`, `SQLSTATE` o un código de error.

| Formulario | Campo | Mensaje que apareció | Junto al campo | Dice cómo corregir | Sin términos técnicos |
| --- | --- | --- | --- | --- | --- |
| | | | | | |

## D · Criterios que dependen de la pantalla

Probar en los tres navegadores:

| Criterio | Qué se hace | Qué debe pasar | Chrome Android | Chrome | Edge |
| --- | --- | --- | --- | --- | --- |
| **CA-01.4** | Cerrar sesión y tocar Atrás en el navegador | Aparece el inicio de sesión y ningún dato del taller | Sí | Sí | Sí |
| **CA-10.1** | Llenar una orden con dos prendas, registrar un cliente nuevo desde ella y volver | La orden tiene al cliente seleccionado y las dos prendas siguen escritas | No aplica | No aplica | No aplica |
| **CA-18.2** | En las fotos de una orden, tocar una foto | La foto se ve ampliada | Sí | Sí | Sí |

## Criterio de aprobación

- **A:** todas las historias Must pasan en los tres navegadores.
- **B:** todas las pantallas cumplen los cinco puntos.
- **C:** todos los mensajes cumplen las cuatro condiciones.
- **D:** los tres criterios pasan en los tres navegadores.

## Registro

**Fecha:** 21 de septiembre de 2026 · **Commit:** `fc48f20` · **Chrome para Android (versión y celular):** no anotada · **Chrome (versión):** no anotada · **Edge (versión):** no anotada

Además se probó en **Brave 1.95.104**, que usa el mismo motor que Chrome. Queda como prueba adicional y no reemplaza a ninguno de los tres navegadores.

Las tablas de las secciones A a D son la hoja de registro.

**Resultado:** **Parcial.** Se ejecutó solo la sección D, que es la que cierra HU-01 y HU-18.

- **D · aprobada.** CA-01.4 y CA-18.2 pasan en los tres navegadores y también en Brave. CA-10.1 no aplica porque HU-10 es Should y no se construyó.
- **A, B y C · pendientes.** Faltan el recorrido de las historias Must, las 23 pantallas a 360 px con accesibilidad y los mensajes de validación. Las pantallas PT-12, PT-22 y PT-23 son de historias Should y Could sin construir, así que cuando se corran esas filas tampoco aplican.

Observaciones de esta corrida:

- **Al cerrar sesión y tocar Adelante, el navegador volvió a entrar.** Los registros del servidor muestran que, después del cierre, cada página con datos respondió 302 al inicio de sesión. Se volvió a entrar solo con un `POST /entrar` nuevo, con la contraseña correcta y el token CSRF recién emitido. Un formulario viejo sacado del historial habría respondido 419. Lo más probable es que el gestor de contraseñas del navegador llenara los campos. No es un defecto del sistema, pero en un celular compartido cualquiera podría entrar sin saber la contraseña. El manual de usuario (DOC-22) debe recomendar no guardarla en el navegador.
- **Las fotos de la orden de prueba devolvían 500.** Se crearon con `docker compose exec`, que corre como root, y la carpeta quedó con permisos 0700 a nombre de root, donde Apache no puede leer. Se corrigió el dueño de la carpeta. No es un defecto del sistema: las fotos que sube la aplicación quedan a nombre de `www-data`.

**Defectos abiertos:** ninguno. Se encontró y se corrigió uno: en el detalle de la orden, las miniaturas no llevaban a las fotos, y el único camino era un enlace pequeño en el título que pasó desapercibido. Corregido en `fc48f20`, también en el mockup.
